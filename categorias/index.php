<?php
require_once __DIR__ . '/../config/conexion.php';

function e($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$mensaje = trim($_GET['mensaje'] ?? '');
$error = trim($_GET['error'] ?? '');
$buscar = trim($_GET['buscar'] ?? '');
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

$categoriaEditar = [
    'ID_CATEGORIA' => '',
    'NOMBRE' => '',
    'DESCRIPCION' => ''
];

if ($editarId !== false && $editarId !== null && $editarId > 0) {
    $sql = "SELECT id_categoria, nombre, descripcion
            FROM categorias
            WHERE id_categoria = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $editarId);
    oci_execute($stmt);

    $fila = oci_fetch_assoc($stmt);

    if ($fila) {
        $categoriaEditar = $fila;
    } else {
        $error = 'La categoría que buscas no existe.';
        $editarId = false;
    }

    oci_free_statement($stmt);
}

$sql = "SELECT id_categoria, nombre, descripcion
        FROM categorias";

if ($buscar !== '') {
    $sql .= " WHERE
              TO_CHAR(id_categoria) LIKE :buscar_id
              OR UPPER(nombre) LIKE UPPER(:buscar_nombre)";
}

$sql .= " ORDER BY id_categoria";

$stmtCategorias = oci_parse($conn, $sql);

if ($buscar !== '') {
    $buscarId = '%' . $buscar . '%';
    $buscarNombre = '%' . $buscar . '%';

    oci_bind_by_name($stmtCategorias, ':buscar_id', $buscarId);
    oci_bind_by_name($stmtCategorias, ':buscar_nombre', $buscarNombre);
}

oci_execute($stmtCategorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de categorías</title>

    <style>
        :root {
            --fondo: #f7f5fc;
            --blanco: #fff;
            --morado: #7963b8;
            --morado-oscuro: #59458f;
            --lavanda: #eee8fa;
            --texto: #302b40;
            --suave: #777187;
            --borde: #e9e3f2;
            --verde: #4c9276;
            --rojo: #c66b7a;
            --sombra: 0 10px 30px rgba(67, 48, 104, .07);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: var(--texto);
            background: radial-gradient(circle at top right, #eee8fa 0, transparent 34%), var(--fondo);
        }

        .contenedor { max-width: 1200px; margin: 0 auto; }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 26px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--morado);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(27px, 4vw, 36px);
            letter-spacing: -1px;
            color: #302644;
        }

        .subtitulo { color: var(--suave); margin: 9px 0 0; line-height: 1.6; }

        .etiqueta-modulo {
            flex-shrink: 0;
            background: var(--lavanda);
            color: var(--morado-oscuro);
            border: 1px solid #ded3f3;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .estadistica {
            background: var(--blanco);
            border: 1px solid var(--borde);
            box-shadow: var(--sombra);
            padding: 20px 22px;
            border-radius: 20px;
            min-height: 112px;
        }

        .estadistica .titulo {
            color: var(--suave);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .estadistica .numero {
            color: var(--morado-oscuro);
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .panel {
            background: var(--blanco);
            border: 1px solid var(--borde);
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 22px;
            box-shadow: var(--sombra);
        }

        .panel-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 21px;
        }

        h2 { margin: 0; font-size: 19px; color: #39304e; }

        .panel-descripcion {
            margin: 7px 0 0;
            color: var(--suave);
            font-size: 13px;
            line-height: 1.5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 8px;
            color: #4b435d;
        }

        input, textarea {
            width: 100%;
            min-height: 44px;
            padding: 11px 13px;
            border: 1px solid #e2dbea;
            border-radius: 12px;
            background: #fdfcff;
            color: var(--texto);
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        textarea { min-height: 105px; resize: vertical; }
        input:focus, textarea:focus {
            border-color: #a594d5;
            box-shadow: 0 0 0 3px rgba(121, 99, 184, .12);
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;
        }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 12px;
            background: var(--morado);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: transform .15s, background .15s;
        }

        .boton:hover { background: var(--morado-oscuro); transform: translateY(-1px); }
        .boton-primario { background: var(--morado); }
        .boton-verde { background: var(--verde); }
        .boton-verde:hover { background: #39795f; }
        .boton-editar { background: #e9e3f8; color: #59458f; }
        .boton-editar:hover { background: #dcd2f3; color: #463473; }
        .boton-rojo { background: #fff0f1; color: #ad4355; border: 1px solid #f4d8dc; }
        .boton-rojo:hover { background: #fbe0e4; color: #943344; }
        .boton-gris { background: #f0edf5; color: #625a70; }
        .boton-gris:hover { background: #e5dfef; color: #514761; }

        .aviso {
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 13px;
            font-size: 14px;
            background: #e8f6ee;
            color: #2d7451;
            border: 1px solid #cdebd9;
        }

        .aviso-error {
            background: #fff0f1;
            color: #a43f50;
            border-color: #f3d4d9;
        }

        .busqueda {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 19px;
        }

        .busqueda input { flex: 1; min-width: 180px; background: #fff; }

        .tabla-contenedor {
            overflow-x: auto;
            border: 1px solid var(--borde);
            border-radius: 15px;
        }

        table { width: 100%; border-collapse: collapse; min-width: 700px; }

        th, td {
            padding: 15px 14px;
            text-align: left;
            border-bottom: 1px solid #f0ecf5;
            font-size: 13px;
            vertical-align: top;
        }

        th {
            background: #f4f0fb;
            color: #5d4a8a;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
            font-weight: 800;
            white-space: nowrap;
        }

        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #fcfaff; }
        .codigo { color: var(--morado-oscuro); font-weight: 800; }
        .nombre-categoria { color: #3f374e; font-weight: 700; }
        .descripcion { color: var(--suave); line-height: 1.5; max-width: 350px; }
        .vacio { text-align: center; color: var(--suave); padding: 28px; }
        .acciones-tabla form { margin: 0; }

        .volver { margin: 4px 0 12px; }
        .volver a { color: var(--morado-oscuro); text-decoration: none; font-size: 13px; font-weight: 700; }
        .volver a:hover { text-decoration: underline; }

        @media (max-width: 760px) {
            body { padding: 18px 12px; }
            .encabezado { flex-direction: column; }
            .estadisticas { grid-template-columns: 1fr; gap: 10px; }
            .estadistica { min-height: auto; padding: 17px 19px; }
            .panel { padding: 18px 14px; border-radius: 17px; }
            .panel-cabecera { align-items: flex-start; flex-direction: column; }
            .form-grid { grid-template-columns: 1fr; }
            .busqueda { align-items: stretch; flex-direction: column; }
            .busqueda input { min-width: 0; }
            .busqueda .boton { width: 100%; }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div>
            <p class="eyebrow">Panel de administración</p>
            <h1>Gestión de categorías</h1>
            <p class="subtitulo">Organiza los productos de tu punto de venta y mantén las categorías ordenadas.</p>
        </div>
        <div class="etiqueta-modulo">Categorías</div>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="aviso"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="aviso aviso-error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="estadisticas">
        <div class="estadistica">
            <div class="titulo">Categorías registradas</div>
            <div class="numero" id="total-categorias">—</div>
        </div>
        <div class="estadistica">
            <div class="titulo">Estado del listado</div>
            <div class="numero" style="font-size:18px;padding-top:5px">
                <?= $buscar !== '' ? 'Filtrado' : 'Listado completo' ?>
            </div>
        </div>
        <div class="estadistica">
            <div class="titulo">Acción disponible</div>
            <div class="numero" style="font-size:18px;padding-top:5px">
                <?= $editarId ? 'Edición' : 'Nuevo registro' ?>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h2><?= $editarId ? 'Modificar categoría' : 'Registrar categoría' ?></h2>
                <p class="panel-descripcion">Ingresa el código, el nombre y una descripción para identificar la categoría.</p>
            </div>
        </div>

        <form method="POST" action="guardar.php">
            <input type="hidden" name="accion"
                   value="<?= $editarId ? 'modificar' : 'registrar' ?>">

            <div class="form-grid">
                <div>
                    <label for="id_categoria">Código de categoría</label>
                    <input type="number" id="id_categoria" name="id_categoria"
                           min="1" max="999999" required
                           value="<?= e($categoriaEditar['ID_CATEGORIA']) ?>"
                           <?= $editarId ? 'readonly' : '' ?>>
                </div>

                <div>
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre"
                           maxlength="50" required
                           placeholder="Ej. Bebidas"
                           value="<?= e($categoriaEditar['NOMBRE']) ?>">
                </div>

                <div style="grid-column: 1 / -1">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" maxlength="150"
                              placeholder="Describe brevemente esta categoría..."><?= e($categoriaEditar['DESCRIPCION']) ?></textarea>
                </div>
            </div>

            <div class="acciones" style="margin-top:22px">
                <button type="submit" class="boton boton-verde">
                    <?= $editarId ? 'Guardar cambios' : 'Registrar categoría' ?>
                </button>

                <?php if ($editarId): ?>
                    <a class="boton boton-gris" href="index.php">Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h2>Categorías registradas</h2>
                <p class="panel-descripcion">Busca, edita o elimina las categorías de tu catálogo.</p>
            </div>
        </div>

        <form method="GET" action="index.php" class="busqueda">
            <input type="text" name="buscar"
                   placeholder="Buscar por código o nombre..."
                   value="<?= e($buscar) ?>">
            <button type="submit" class="boton boton-primario">Buscar</button>
            <a class="boton boton-gris" href="index.php">Mostrar todas</a>
        </form>

        <div class="tabla-contenedor">
            <table>
                <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
                </thead>

                <tbody>
                <?php
                $hayCategorias = false;
                $totalCategorias = 0;

                while ($cat = oci_fetch_assoc($stmtCategorias)):
                    $hayCategorias = true;
                    $totalCategorias++;
                ?>
                    <tr>
                        <td class="codigo"><?= e($cat['ID_CATEGORIA']) ?></td>
                        <td class="nombre-categoria"><?= e($cat['NOMBRE']) ?></td>
                        <td class="descripcion"><?= e($cat['DESCRIPCION']) ?></td>
                        <td>
                            <div class="acciones acciones-tabla">
                                <a class="boton boton-editar"
                                   href="index.php?editar=<?= (int)$cat['ID_CATEGORIA'] ?>">
                                    Editar
                                </a>

                                <form method="POST" action="eliminar.php"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                    <input type="hidden" name="id_categoria"
                                           value="<?= (int)$cat['ID_CATEGORIA'] ?>">
                                    <button class="boton boton-rojo" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if (!$hayCategorias): ?>
                    <tr>
                        <td colspan="4" class="vacio">No se encontraron categorías.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="volver"><a href="../productos/index.php">← Volver a productos</a></p>
</div>

<script>
    document.getElementById('total-categorias').textContent =
        <?= json_encode($totalCategorias) ?>;
</script>
</body>
</html>

<?php
oci_free_statement($stmtCategorias);
oci_close($conn);
?>

<?php
require_once __DIR__ . '/../config/conexion.php';

function e($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$mensaje = trim($_GET['mensaje'] ?? '');
$error = trim($_GET['error'] ?? '');
$buscar = trim($_GET['buscar'] ?? '');
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

$marcaEditar = [
    'ID_MARCA' => '',
    'NOMBRE' => '',
    'DESCRIPCION' => ''
];

if ($editarId !== false && $editarId !== null && $editarId > 0) {
    $sqlEditar = "SELECT id_marca, nombre, descripcion FROM marcas WHERE id_marca = :id";
    $stmtEditar = oci_parse($conn, $sqlEditar);
    oci_bind_by_name($stmtEditar, ':id', $editarId);
    oci_execute($stmtEditar);
    $fila = oci_fetch_assoc($stmtEditar);

    if ($fila) {
        $marcaEditar = $fila;
    } else {
        $error = 'La marca que buscas no existe.';
        $editarId = false;
    }
    oci_free_statement($stmtEditar);
}

$sqlMarcas = "SELECT id_marca, nombre, descripcion FROM marcas";
if ($buscar !== '') {
    $sqlMarcas .= " WHERE TO_CHAR(id_marca) LIKE :buscar_id
                    OR UPPER(nombre) LIKE UPPER(:buscar_nombre)";
}
$sqlMarcas .= " ORDER BY id_marca";

$stmtMarcas = oci_parse($conn, $sqlMarcas);
if ($buscar !== '') {
    $buscarId = '%' . $buscar . '%';
    $buscarNombre = '%' . $buscar . '%';
    oci_bind_by_name($stmtMarcas, ':buscar_id', $buscarId);
    oci_bind_by_name($stmtMarcas, ':buscar_nombre', $buscarNombre);
}
oci_execute($stmtMarcas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas | Punto de Venta</title>
    <style>
        :root {
            --fondo: #f7f1f5; --panel: #fffdfb; --panel-suave: #f5edf6;
            --borde: #e8dce9; --texto: #34234b; --secundario: #7c7188;
            --morado: #7950a1; --morado-hover: #623b88; --verde: #32866f;
            --verde-fondo: #e4f3ed; --rojo: #b94f70; --rojo-fondo: #fae9ef;
            --sombra: 0 8px 24px rgba(79, 47, 91, .06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 30px;
            background: linear-gradient(135deg, #f3eaf3 0%, #faf6f1 55%, #f4eaf0 100%);
            color: var(--texto); font-family: "Segoe UI", Arial, sans-serif; min-height: 100vh;
        }
        .contenedor { max-width: 1200px; margin: 0 auto; }
        .encabezado {
            display: flex; justify-content: space-between; align-items: center;
            gap: 18px; margin-bottom: 25px; flex-wrap: wrap;
        }
        .titulo-con-icono { display: flex; align-items: center; gap: 15px; }
        .icono-principal {
            width: 54px; height: 54px; display: grid; place-items: center;
            background: #e9d9f1; border-radius: 17px; font-size: 27px;
        }
        h1 { margin: 0 0 7px; font-size: clamp(23px, 3vw, 30px); letter-spacing: -.5px; }
        h2 { margin: 0; font-size: 18px; }
        .subtitulo, .ayuda { color: var(--secundario); font-size: 13px; line-height: 1.6; margin: 0; }
        .volver {
            display: inline-flex; align-items: center; gap: 8px; color: var(--morado);
            text-decoration: none; font-weight: 600; background: var(--panel);
            border: 1px solid var(--borde); border-radius: 12px; padding: 12px 16px; box-shadow: var(--sombra);
        }
        .volver:hover { background: var(--panel-suave); }
        .indicadores {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px; margin-bottom: 22px;
        }
        .indicador {
            background: var(--panel); border: 1px solid var(--borde);
            border-radius: 16px; padding: 19px 21px; box-shadow: var(--sombra);
        }
        .etiqueta { color: var(--secundario); font-size: 13px; margin-bottom: 8px; }
        .numero { font-size: 27px; font-weight: 750; color: var(--morado); }
        .detalle { color: var(--secundario); font-size: 12px; margin-top: 5px; }
        .panel {
            background: rgba(255, 253, 251, .96); border: 1px solid var(--borde);
            border-radius: 17px; padding: 25px; margin-bottom: 22px; box-shadow: var(--sombra);
        }
        .titulo-panel { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .mini-icono {
            width: 35px; height: 35px; display: grid; place-items: center;
            border-radius: 11px; background: #eee1f4; font-size: 18px;
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px; }
        .campo { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .campo.completo { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 600; color: #554663; }
        input, textarea {
            width: 100%; min-width: 0; padding: 12px 13px; border-radius: 10px;
            border: 1px solid #e2d6e6; background: #fff; color: var(--texto);
            font: inherit; font-size: 14px; outline: none; transition: .2s;
        }
        input:focus, textarea:focus { border-color: #a783c2; box-shadow: 0 0 0 3px rgba(121, 80, 161, .12); }
        input::placeholder, textarea::placeholder { color: #b0a5b7; }
        input[readonly] { background: #f3eef4; color: var(--secundario); }
        textarea { min-height: 90px; resize: vertical; }
        .boton {
            display: inline-flex; justify-content: center; align-items: center; gap: 7px;
            border: 1px solid transparent; border-radius: 10px; padding: 11px 16px;
            font-size: 13px; font-weight: 650; text-align: center; text-decoration: none;
            cursor: pointer; transition: .2s; white-space: nowrap; font-family: inherit;
        }
        .primario { color: white; background: var(--morado); }
        .primario:hover { background: var(--morado-hover); transform: translateY(-1px); }
        .secundario { color: #5f4c70; background: #f4edf6; border-color: #e9dced; }
        .secundario:hover { background: #ebdff0; }
        .editar { color: #286c68; background: #e4f3ef; border-color: #cce8e0; }
        .editar:hover { background: #d2ece4; }
        .peligro { color: var(--rojo); background: var(--rojo-fondo); border-color: #f1d4df; }
        .peligro:hover { background: #f5d9e3; }
        .acciones-formulario { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
        .mensaje { padding: 13px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .exito { color: #286c59; background: var(--verde-fondo); border: 1px solid #c8e7db; }
        .error { color: #a43e60; background: var(--rojo-fondo); border: 1px solid #f0ccd9; }
        .cabecera-lista { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .busqueda { display: flex; gap: 10px; margin-bottom: 17px; }
        .busqueda input { flex: 1; }
        .contador { color: var(--secundario); font-size: 12px; margin-bottom: 13px; }
        .tabla-contenedor { overflow-x: auto; border: 1px solid #eee4ef; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 650px; }
        th {
            background: #f2eaf4; color: #695278; font-size: 11px;
            text-transform: uppercase; letter-spacing: .5px; text-align: left; white-space: nowrap;
        }
        th, td { padding: 14px 13px; border-bottom: 1px solid #eee6ef; }
        tbody tr:last-child td { border-bottom: 0; }
        td { color: #51445d; vertical-align: middle; }
        tbody tr:hover { background: #fcf8fd; }
        .codigo { display: inline-block; color: var(--morado); font-weight: 750; background: #f7f0fa; border-radius: 6px; padding: 5px 8px; }
        .acciones { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
        .acciones .boton { padding: 8px 10px; font-size: 12px; }
        .form-eliminar { margin: 0; }
        .vacio { text-align: center; color: var(--secundario); padding: 30px; }
        footer { text-align: center; color: #91849a; font-size: 12px; padding: 10px 0 4px; line-height: 1.7; }
        @media (max-width: 800px) {
            body { padding: 17px; }
            .indicadores { grid-template-columns: 1fr; gap: 11px; }
            .indicador { padding: 16px 18px; }
            .panel { padding: 18px; }
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .campo.completo { grid-column: auto; }
            .busqueda { flex-wrap: wrap; }
            .busqueda input { flex-basis: 100%; }
            .busqueda .boton { flex: 1; }
            .acciones-formulario .boton { flex: 1; }
            .titulo-con-icono { align-items: flex-start; }
        }
    </style>
</head>
<body>
<main class="contenedor">
    <header class="encabezado">
        <div class="titulo-con-icono">
            <div class="icono-principal">🏷️</div>
            <div>
                <h1>Gestión de marcas</h1>
                <p class="subtitulo">Administra las marcas de los productos de tu punto de venta.</p>
            </div>
        </div>
        <a class="volver" href="../index.php">← Volver al menú principal</a>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje exito">✓ <?= e($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="mensaje error">⚠ <?= e($error) ?></div>
    <?php endif; ?>

    <section class="indicadores">
        <div class="indicador">
            <div class="etiqueta">Marcas encontradas</div>
            <div class="numero" id="totalMarcas">—</div>
            <div class="detalle">Según la búsqueda actual</div>
        </div>
        <div class="indicador">
            <div class="etiqueta">Estado del módulo</div>
            <div class="numero" style="font-size:22px;color:#32866f">Disponible</div>
            <div class="detalle">Administración de marcas</div>
        </div>
        <div class="indicador">
            <div class="etiqueta">Acción actual</div>
            <div class="numero" style="font-size:22px"><?= $editarId ? 'Edición' : 'Registro' ?></div>
            <div class="detalle"><?= $editarId ? 'Modifica la marca seleccionada' : 'Agrega una nueva marca' ?></div>
        </div>
    </section>

    <section class="panel">
        <h2 class="titulo-panel">
            <span class="mini-icono"><?= $editarId ? '✏️' : '＋' ?></span>
            <?= $editarId ? 'Modificar marca' : 'Registrar marca' ?>
        </h2>

        <form method="POST" action="guardar.php">
            <input type="hidden" name="accion" value="<?= $editarId ? 'modificar' : 'registrar' ?>">
            <div class="form-grid">
                <div class="campo">
                    <label for="id_marca">Código de marca *</label>
                    <input type="number" id="id_marca" name="id_marca" min="1" max="999999"
                        required value="<?= e($marcaEditar['ID_MARCA']) ?>"
                        <?= $editarId ? 'readonly' : '' ?> placeholder="Ej. 101">
                    <?php if ($editarId): ?><span class="ayuda">El código no se puede modificar.</span><?php endif; ?>
                </div>
                <div class="campo">
                    <label for="nombre">Nombre de la marca *</label>
                    <input type="text" id="nombre" name="nombre" maxlength="50" required
                        value="<?= e($marcaEditar['NOMBRE']) ?>" placeholder="Nombre de la marca">
                </div>
                <div class="campo completo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" maxlength="150"
                        placeholder="Describe brevemente la marca..."><?= e($marcaEditar['DESCRIPCION']) ?></textarea>
                </div>
            </div>
            <div class="acciones-formulario">
                <button class="boton primario" type="submit">
                    <?= $editarId ? '✓ Guardar cambios' : '＋ Registrar marca' ?>
                </button>
                <?php if ($editarId): ?>
                    <a class="boton secundario" href="index.php">Cancelar edición</a>
                <?php else: ?>
                    <button class="boton secundario" type="reset">Limpiar formulario</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="cabecera-lista">
            <h2 class="titulo-panel"><span class="mini-icono">▤</span> Marcas registradas</h2>
        </div>

        <form method="GET" action="index.php" class="busqueda">
            <input type="search" name="buscar" maxlength="100"
                placeholder="⌕  Buscar por código o nombre..." value="<?= e($buscar) ?>">
            <button class="boton primario" type="submit">Buscar</button>
            <a class="boton secundario" href="index.php">Mostrar todas</a>
        </form>

        <div class="contador">Resultados de la consulta actual</div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php
                $hayMarcas = false;
                $totalMarcas = 0;
                while ($marca = oci_fetch_assoc($stmtMarcas)):
                    $hayMarcas = true;
                    $totalMarcas++;
                ?>
                    <tr>
                        <td><span class="codigo"><?= e($marca['ID_MARCA']) ?></span></td>
                        <td><?= e($marca['NOMBRE']) ?></td>
                        <td><?= e($marca['DESCRIPCION'] ?: '—') ?></td>
                        <td>
                            <div class="acciones">
                                <a class="boton editar" href="index.php?editar=<?= (int)$marca['ID_MARCA'] ?>">Editar</a>
                                <form method="POST" action="eliminar.php" class="form-eliminar"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta marca?');">
                                    <input type="hidden" name="id_marca" value="<?= (int)$marca['ID_MARCA'] ?>">
                                    <button class="boton peligro" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (!$hayMarcas): ?>
                    <tr><td colspan="4" class="vacio">No se encontraron marcas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
            document.getElementById('totalMarcas').textContent = <?= (int)$totalMarcas ?>;
        </script>
    </section>

    <footer>Sistema de Punto de Venta · PHP y Oracle Database 19c<br>Gestión de marcas</footer>
</main>
</body>
</html>
<?php
oci_free_statement($stmtMarcas);
oci_close($conn);
?>
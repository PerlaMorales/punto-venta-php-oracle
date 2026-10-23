<?php
require_once __DIR__ . '/../config/conexion.php';

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$buscar = trim($_GET['buscar'] ?? '');
$editar = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

$sqlProductos = "
    SELECT id_producto, nombre
    FROM productos
    ORDER BY nombre
";
$stidProductos = oci_parse($conn, $sqlProductos);
oci_execute($stidProductos);

$productos = [];
while ($fila = oci_fetch_assoc($stidProductos)) {
    $productos[] = $fila;
}
oci_free_statement($stidProductos);

$registroEditar = null;

if ($editar > 0) {
    $sqlEditar = "
        SELECT id_inventario, id_producto, cantidad,
               stock_minimo, stock_maximo, ubicacion
        FROM inventario
        WHERE id_inventario = :id
    ";

    $stidEditar = oci_parse($conn, $sqlEditar);
    oci_bind_by_name($stidEditar, ':id', $editar);
    oci_execute($stidEditar);
    $registroEditar = oci_fetch_assoc($stidEditar);
    oci_free_statement($stidEditar);
}

$sql = "
    SELECT i.id_inventario, i.id_producto, p.nombre AS producto,
           i.cantidad, i.stock_minimo, i.stock_maximo, i.ubicacion
    FROM inventario i
    INNER JOIN productos p
        ON p.id_producto = i.id_producto
    WHERE (:buscar1 IS NULL
        OR TO_CHAR(i.id_inventario) LIKE '%' || :buscar2 || '%'
        OR TO_CHAR(i.id_producto) LIKE '%' || :buscar3 || '%'
        OR UPPER(p.nombre) LIKE '%' || UPPER(:buscar4) || '%')
    ORDER BY i.id_inventario
";

$stid = oci_parse($conn, $sql);

$termino = ($buscar === '') ? null : $buscar;
oci_bind_by_name($stid, ':buscar1', $termino);
oci_bind_by_name($stid, ':buscar2', $buscar);
oci_bind_by_name($stid, ':buscar3', $buscar);
oci_bind_by_name($stid, ':buscar4', $buscar);

oci_execute($stid);

$totalInventario = 0;
$totalStockBajo = 0;
$totalStockMaximo = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario</title>

    <style>
        :root {
            --fondo: #f7f5fc;
            --blanco: #ffffff;
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
            background:
                radial-gradient(circle at top right, #eee8fa 0, transparent 34%),
                var(--fondo);
        }

        .contenedor { max-width: 1250px; margin: 0 auto; }

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
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 17px;
        }

        label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 8px;
            color: #4b435d;
        }

        input, select {
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

        input:focus, select:focus {
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
            gap: 7px;
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
        .boton-rojo { background: #fff0f1; color: #ad4355; border: 1px solid #f4d8dc; }
        .boton-rojo:hover { background: #fbe0e4; color: #943344; }
        .boton-gris { background: #f0edf5; color: #625a70; }
        .boton-gris:hover { background: #e5dfef; color: #514761; }

        .mensaje, .error {
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 13px;
            font-size: 14px;
        }

        .mensaje { background: #e8f6ee; color: #2d7451; border: 1px solid #cdebd9; }
        .error { background: #fff0f1; color: #a43f50; border: 1px solid #f3d4d9; }

        .busqueda {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 19px;
        }

        .busqueda input { max-width: 410px; background: #fff; }

        .tabla-contenedor {
            overflow-x: auto;
            border: 1px solid var(--borde);
            border-radius: 15px;
        }

        table { width: 100%; border-collapse: collapse; min-width: 900px; }

        th, td {
            padding: 15px 14px;
            text-align: left;
            border-bottom: 1px solid #f0ecf5;
            font-size: 13px;
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
        .producto { font-weight: 700; color: #3f374e; }

        .estado {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .estado-bajo { background: #fff0f1; color: #ad4355; }
        .estado-maximo { background: #fff4e5; color: #98652a; }
        .estado-normal { background: #e8f6ee; color: #2d7451; }

        .sin-registros { text-align: center; color: var(--suave); padding: 28px; }
        .acciones-tabla form { margin: 0; }

        @media (max-width: 760px) {
            body { padding: 18px 12px; }
            .encabezado { flex-direction: column; }
            .estadisticas { grid-template-columns: 1fr; gap: 10px; }
            .estadistica { min-height: auto; padding: 17px 19px; }
            .panel { padding: 18px 14px; border-radius: 17px; }
            .panel-cabecera { align-items: flex-start; flex-direction: column; }
            .busqueda { align-items: stretch; flex-direction: column; }
            .busqueda input { max-width: none; }
            .busqueda .boton { width: 100%; }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div>
            <p class="eyebrow">Panel de administración</p>
            <h1>Gestión de Inventario</h1>
            <p class="subtitulo">Controla las existencias, los niveles de stock y la ubicación de tus productos.</p>
        </div>
        <div class="etiqueta-modulo">Inventario</div>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="estadisticas">
        <div class="estadistica">
            <div class="titulo">Registros de inventario</div>
            <div class="numero" id="total-inventario">—</div>
        </div>
        <div class="estadistica">
            <div class="titulo">Productos disponibles para registrar</div>
            <div class="numero"><?= count($productos) ?></div>
        </div>
        <div class="estadistica">
            <div class="titulo">Búsqueda actual</div>
            <div class="numero" style="font-size:18px;padding-top:5px">
                <?= $buscar !== '' ? 'Filtrada' : 'Todos los registros' ?>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h2><?= $registroEditar ? 'Editar inventario' : 'Registrar inventario' ?></h2>
                <p class="panel-descripcion">Completa los datos del producto y define sus límites de existencias.</p>
            </div>
        </div>

        <form action="guardar.php" method="POST">
            <input type="hidden" name="accion"
                   value="<?= $registroEditar ? 'modificar' : 'registrar' ?>">

            <?php if ($registroEditar): ?>
                <input type="hidden" name="id_inventario"
                       value="<?= e($registroEditar['ID_INVENTARIO']) ?>">
            <?php endif; ?>

            <div class="formulario">
                <div>
                    <label for="id_producto">Producto</label>
                    <select name="id_producto" id="id_producto" required>
                        <option value="">Seleccione un producto</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= e($producto['ID_PRODUCTO']) ?>"
                                <?= $registroEditar &&
                                    (int)$registroEditar['ID_PRODUCTO'] ===
                                    (int)$producto['ID_PRODUCTO']
                                    ? 'selected' : '' ?>>
                                <?= e($producto['ID_PRODUCTO']) ?> -
                                <?= e($producto['NOMBRE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="cantidad">Cantidad actual</label>
                    <input type="number" name="cantidad" id="cantidad"
                           min="0" step="1" required
                           value="<?= e($registroEditar['CANTIDAD'] ?? '') ?>">
                </div>

                <div>
                    <label for="stock_minimo">Stock mínimo</label>
                    <input type="number" name="stock_minimo" id="stock_minimo"
                           min="0" step="1" required
                           value="<?= e($registroEditar['STOCK_MINIMO'] ?? '') ?>">
                </div>

                <div>
                    <label for="stock_maximo">Stock máximo</label>
                    <input type="number" name="stock_maximo" id="stock_maximo"
                           min="0" step="1" required
                           value="<?= e($registroEditar['STOCK_MAXIMO'] ?? '') ?>">
                </div>

                <div>
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" name="ubicacion" id="ubicacion"
                           maxlength="100"
                           value="<?= e($registroEditar['UBICACION'] ?? '') ?>"
                           placeholder="Ej. Bodega Central - Estantería A1">
                </div>
            </div>

            <div class="acciones" style="margin-top:22px">
                <button type="submit" class="boton boton-verde">
                    <?= $registroEditar ? 'Guardar cambios' : 'Registrar inventario' ?>
                </button>

                <?php if ($registroEditar): ?>
                    <a href="index.php" class="boton boton-gris">Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h2>Inventario registrado</h2>
                <p class="panel-descripcion">Consulta el estado de las existencias y administra cada registro.</p>
            </div>
        </div>

        <form method="GET" action="index.php" class="busqueda">
            <input type="text" name="buscar"
                   placeholder="Buscar por código o nombre del producto"
                   value="<?= e($buscar) ?>">
            <button type="submit" class="boton boton-primario">Buscar</button>
            <a href="index.php" class="boton boton-gris">Mostrar todos</a>
        </form>

        <div class="tabla-contenedor">
            <table>
                <thead>
                <tr>
                    <th>Código inventario</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Stock mínimo</th>
                    <th>Stock máximo</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>

                <tbody>
                <?php
                $hayRegistros = false;

                while ($fila = oci_fetch_assoc($stid)):
                    $hayRegistros = true;
                    $totalInventario++;

                    if ((int)$fila['CANTIDAD'] <= (int)$fila['STOCK_MINIMO']) {
                        $estado = 'Stock bajo';
                        $clase = 'estado-bajo';
                        $totalStockBajo++;
                    } elseif ((int)$fila['CANTIDAD'] >= (int)$fila['STOCK_MAXIMO']) {
                        $estado = 'Stock máximo';
                        $clase = 'estado-maximo';
                        $totalStockMaximo++;
                    } else {
                        $estado = 'Normal';
                        $clase = 'estado-normal';
                    }
                ?>
                    <tr>
                        <td class="codigo"><?= e($fila['ID_INVENTARIO']) ?></td>
                        <td class="producto"><?= e($fila['PRODUCTO']) ?></td>
                        <td><?= e($fila['CANTIDAD']) ?></td>
                        <td><?= e($fila['STOCK_MINIMO']) ?></td>
                        <td><?= e($fila['STOCK_MAXIMO']) ?></td>
                        <td><?= e($fila['UBICACION'] ?? '') ?></td>
                        <td><span class="estado <?= e($clase) ?>"><?= e($estado) ?></span></td>
                        <td>
                            <div class="acciones acciones-tabla">
                                <a class="boton boton-primario"
                                   href="index.php?editar=<?= e($fila['ID_INVENTARIO']) ?>">
                                    Editar
                                </a>

                                <form action="eliminar.php" method="POST"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar este registro de inventario?');">
                                    <input type="hidden" name="id_inventario"
                                           value="<?= e($fila['ID_INVENTARIO']) ?>">
                                    <button type="submit" class="boton boton-rojo">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if (!$hayRegistros): ?>
                    <tr>
                        <td colspan="8" class="sin-registros">No se encontraron registros de inventario.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<script>
    document.getElementById('total-inventario').textContent =
        <?= json_encode($totalInventario) ?>;
</script>
</body>
</html>

<?php
oci_free_statement($stid);
oci_close($conn);
?>

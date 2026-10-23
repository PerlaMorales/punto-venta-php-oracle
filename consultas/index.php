<?php
require_once __DIR__ . '/../config/conexion.php';

function e($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$consultas = [
    'mas_vendido' => [
        'titulo' => 'Producto más vendido',
        'descripcion' => 'Muestra el producto con mayor cantidad acumulada de unidades vendidas.',
        'sql' => "SELECT p.id_producto, p.nombre,
                         SUM(dv.cantidad) AS total_vendido
                  FROM productos p
                  JOIN detalle_venta dv
                    ON p.id_producto = dv.id_producto
                  GROUP BY p.id_producto, p.nombre
                  HAVING SUM(dv.cantidad) = (
                      SELECT MAX(total_producto)
                      FROM (
                          SELECT SUM(cantidad) AS total_producto
                          FROM detalle_venta
                          GROUP BY id_producto
                      )
                  )
                  ORDER BY p.nombre",
        'columnas' => [
            'ID_PRODUCTO' => 'Código',
            'NOMBRE' => 'Producto',
            'TOTAL_VENDIDO' => 'Unidades vendidas'
        ],
        'moneda' => []
    ],

    'ventas_dia' => [
        'titulo' => 'Ventas del día',
        'descripcion' => 'Muestra el número de ventas y el importe calculado de sus detalles durante el día actual.',
        'sql' => "SELECT COUNT(DISTINCT v.id_venta) AS numero_ventas,
                         NVL(SUM(dv.cantidad * dv.precio_unitario), 0) AS total_vendido
                  FROM ventas v
                  LEFT JOIN detalle_venta dv
                    ON v.id_venta = dv.id_venta
                  WHERE v.fecha_venta >= TRUNC(SYSDATE)
                    AND v.fecha_venta < TRUNC(SYSDATE) + 1",
        'columnas' => [
            'NUMERO_VENTAS' => 'Número de ventas',
            'TOTAL_VENDIDO' => 'Importe total'
        ],
        'moneda' => ['TOTAL_VENDIDO']
    ],

    'caro' => [
        'titulo' => 'Producto más caro',
        'descripcion' => 'Muestra el producto o los productos con el precio de venta más alto.',
        'sql' => "SELECT id_producto, nombre, precio_venta
                  FROM productos
                  WHERE precio_venta = (
                      SELECT MAX(precio_venta) FROM productos
                  )
                  ORDER BY nombre",
        'columnas' => [
            'ID_PRODUCTO' => 'Código',
            'NOMBRE' => 'Producto',
            'PRECIO_VENTA' => 'Precio de venta'
        ],
        'moneda' => ['PRECIO_VENTA']
    ],

    'barato' => [
        'titulo' => 'Producto más barato',
        'descripcion' => 'Muestra el producto o los productos con el precio de venta más bajo.',
        'sql' => "SELECT id_producto, nombre, precio_venta
                  FROM productos
                  WHERE precio_venta = (
                      SELECT MIN(precio_venta) FROM productos
                  )
                  ORDER BY nombre",
        'columnas' => [
            'ID_PRODUCTO' => 'Código',
            'NOMBRE' => 'Producto',
            'PRECIO_VENTA' => 'Precio de venta'
        ],
        'moneda' => ['PRECIO_VENTA']
    ],

    'precios' => [
        'titulo' => 'Productos ordenados por precio',
        'descripcion' => 'Lista todos los productos desde el más barato hasta el más caro.',
        'sql' => "SELECT id_producto, nombre, precio_venta
                  FROM productos
                  ORDER BY precio_venta ASC, nombre ASC",
        'columnas' => [
            'ID_PRODUCTO' => 'Código',
            'NOMBRE' => 'Producto',
            'PRECIO_VENTA' => 'Precio de venta'
        ],
        'moneda' => ['PRECIO_VENTA']
    ],

    'stock' => [
        'titulo' => 'Productos con poco inventario',
        'descripcion' => 'Muestra los productos cuya existencia es igual o inferior al stock mínimo.',
        'sql' => "SELECT p.id_producto,
                         p.nombre,
                         i.cantidad,
                         i.stock_minimo
                  FROM productos p
                  INNER JOIN inventario i
                      ON p.id_producto = i.id_producto
                  WHERE i.cantidad <= i.stock_minimo
                  ORDER BY i.cantidad ASC, p.nombre ASC",
        'columnas' => [
            'ID_PRODUCTO' => 'Código',
            'NOMBRE' => 'Producto',
            'CANTIDAD' => 'Existencia actual',
            'STOCK_MINIMO' => 'Stock mínimo'
        ],
        'moneda' => []
    ]
];

$seleccion = $_GET['consulta'] ?? 'caro';
$resultados = [];
$error = '';

if (!isset($consultas[$seleccion])) {
    $seleccion = 'caro';
}

$consultaActual = $consultas[$seleccion];
$stmt = oci_parse($conn, $consultaActual['sql']);

if ($stmt && oci_execute($stmt)) {
    while ($fila = oci_fetch_assoc($stmt)) {
        $resultados[] = $fila;
    }

    oci_free_statement($stmt);
} else {
    $error = 'No fue posible ejecutar la consulta. Verifica la estructura de las tablas.';
    if ($stmt) {
        oci_free_statement($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas y estadísticas | Punto de Venta</title>

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

        h2 { margin: 0; font-size: 19px; color: #39304e; }

        .subtitulo, .descripcion {
            color: var(--suave);
            font-size: 13px;
            line-height: 1.7;
        }

        .subtitulo { margin: 9px 0 0; }

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

        .volver {
            color: var(--morado-oscuro);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .volver:hover { text-decoration: underline; }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .tarjeta {
            background: var(--blanco);
            border: 1px solid var(--borde);
            box-shadow: var(--sombra);
            padding: 20px 22px;
            border-radius: 20px;
            min-height: 112px;
        }

        .tarjeta small {
            color: var(--suave);
            font-size: 12px;
            font-weight: 700;
        }

        .tarjeta h3 {
            margin: 12px 0 7px;
            color: var(--morado-oscuro);
            font-size: 23px;
        }

        .tarjeta p {
            margin: 0;
            color: var(--suave);
            font-size: 12px;
            line-height: 1.6;
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
            margin-bottom: 20px;
        }

        .descripcion { margin: 8px 0 0; }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
            color: #4b435d;
            font-weight: 700;
        }

        .selector {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        select {
            flex: 1;
            min-width: 0;
            min-height: 44px;
            padding: 11px 13px;
            color: var(--texto);
            background: #fdfcff;
            border: 1px solid #e2dbea;
            border-radius: 12px;
            font: inherit;
            font-size: 14px;
            outline: none;
        }

        select:focus {
            border-color: #a594d5;
            box-shadow: 0 0 0 3px rgba(121, 99, 184, .12);
        }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: transform .15s, background .15s;
        }

        .primario { background: var(--morado); color: #fff; }
        .primario:hover { background: var(--morado-oscuro); transform: translateY(-1px); }
        .secundario { background: #f0edf5; color: #625a70; }
        .secundario:hover { background: #e5dfef; }

        .titulo-resultados {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .contador {
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--lavanda);
            color: var(--morado-oscuro);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .tabla-contenedor {
            overflow-x: auto;
            border: 1px solid var(--borde);
            border-radius: 15px;
        }

        table { width: 100%; border-collapse: collapse; min-width: 550px; }

        th {
            padding: 15px 14px;
            background: #f4f0fb;
            color: #5d4a8a;
            text-align: left;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        td {
            padding: 15px 14px;
            color: #514960;
            border-bottom: 1px solid #f0ecf5;
            font-size: 13px;
        }

        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #fcfaff; }
        .codigo { color: var(--morado-oscuro); font-weight: 800; }
        .precio { color: var(--verde); font-weight: 800; white-space: nowrap; }
        .vacio { text-align: center; padding: 30px; color: var(--suave); }

        .error {
            color: #a43f50;
            background: #fff0f1;
            border: 1px solid #f3d4d9;
            border-radius: 13px;
            padding: 14px 16px;
            font-size: 14px;
        }

        .nota {
            margin: 18px 0 0;
            color: var(--suave);
            font-size: 12px;
            line-height: 1.7;
        }

        @media (max-width: 760px) {
            body { padding: 18px 12px; }
            .encabezado { flex-direction: column; }
            .estadisticas { grid-template-columns: 1fr; gap: 10px; }
            .tarjeta { min-height: auto; padding: 17px 19px; }
            .panel { padding: 18px 14px; border-radius: 17px; }
            .panel-cabecera, .titulo-resultados { align-items: flex-start; flex-direction: column; }
            .selector { align-items: stretch; flex-direction: column; }
            .selector .boton { width: 100%; }
        }
    </style>
</head>

<body>
<main class="contenedor">

    <header class="encabezado">
        <div>
            <p class="eyebrow">Panel de administración</p>
            <h1>Consultas y estadísticas</h1>
            <p class="subtitulo">Consulta el rendimiento de tus productos, las ventas y el estado del inventario.</p>
        </div>
        <div class="etiqueta-modulo">Reportes</div>
    </header>

    <section class="estadisticas">
        <div class="tarjeta">
            <small>TIPO DE MÓDULO</small>
            <h3>Solo lectura</h3>
            <p>Las consultas muestran información sin modificar los datos guardados.</p>
        </div>
        <div class="tarjeta">
            <small>CONSULTAS DISPONIBLES</small>
            <h3><?= count($consultas) ?> reportes</h3>
            <p>Ventas, precios, productos más vendidos y niveles de inventario.</p>
        </div>
        <div class="tarjeta">
            <small>RESULTADOS DE LA CONSULTA</small>
            <h3><?= count($resultados) ?> registro(s)</h3>
            <p>Resultados devueltos por la consulta seleccionada.</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h2>Seleccionar consulta</h2>
                <p class="descripcion">Elige el reporte que deseas revisar y ejecútalo.</p>
            </div>
        </div>

        <form action="index.php" method="GET">
            <label for="consulta">Consulta que deseas ejecutar</label>
            <div class="selector">
                <select name="consulta" id="consulta">
                    <?php foreach ($consultas as $clave => $consulta): ?>
                        <option value="<?= e($clave) ?>"
                            <?= $seleccion === $clave ? 'selected' : '' ?>>
                            <?= e($consulta['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="boton primario">Ejecutar consulta</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="titulo-resultados">
            <div>
                <h2><?= e($consultaActual['titulo']) ?></h2>
                <p class="descripcion"><?= e($consultaActual['descripcion']) ?></p>
            </div>
            <span class="contador"><?= count($resultados) ?> resultado(s)</span>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                    <tr>
                        <?php foreach ($consultaActual['columnas'] as $etiqueta): ?>
                            <th><?= e($etiqueta) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($resultados) > 0): ?>
                        <?php foreach ($resultados as $fila): ?>
                            <tr>
                                <?php foreach ($consultaActual['columnas'] as $columna => $etiqueta): ?>
                                    <?php
                                    $valor = $fila[$columna] ?? '';
                                    $esPrecio = in_array($columna, $consultaActual['moneda'], true);
                                    ?>
                                    <td class="<?= $columna === 'ID_PRODUCTO' ? 'codigo' : '' ?> <?= $esPrecio ? 'precio' : '' ?>">
                                        <?php if ($esPrecio): ?>
                                            Q <?= e(number_format((float)$valor, 2)) ?>
                                        <?php else: ?>
                                            <?= e($valor) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($consultaActual['columnas']) ?>" class="vacio">
                                No se encontraron registros para esta consulta.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="nota">
            Los resultados reflejan los datos registrados actualmente en Oracle.
            Los importes se muestran en quetzales (Q).
        </p>
    </section>

    <p><a class="volver" href="../index.php">← Volver al menú principal</a></p>
</main>
</body>
</html>

<?php
oci_close($conn);
?>

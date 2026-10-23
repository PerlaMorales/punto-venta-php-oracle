<?php
require_once __DIR__ . '/../config/conexion.php';

function e($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
$busqueda = trim($_GET['buscar'] ?? '');
$proveedorEditar = null;

/* Cargar proveedor para editar */
if (isset($_GET['editar']) && ctype_digit($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $sqlEditar = "SELECT id_proveedor, nombre, telefono, correo, direccion
                  FROM proveedores WHERE id_proveedor = :id";
    $stmtEditar = oci_parse($conn, $sqlEditar);
    oci_bind_by_name($stmtEditar, ':id', $idEditar);

    if (oci_execute($stmtEditar)) {
        $proveedorEditar = oci_fetch_assoc($stmtEditar) ?: null;
    }
    oci_free_statement($stmtEditar);
}

/* Consultar y buscar proveedores */
$sql = "SELECT id_proveedor, nombre, telefono, correo, direccion FROM proveedores";
if ($busqueda !== '') {
    $sql .= " WHERE TO_CHAR(id_proveedor) LIKE :busqueda_id
              OR LOWER(nombre) LIKE LOWER(:busqueda_nombre)
              OR LOWER(correo) LIKE LOWER(:busqueda_correo)
              OR telefono LIKE :busqueda_telefono";
}
$sql .= " ORDER BY id_proveedor";

$stmt = oci_parse($conn, $sql);
if ($busqueda !== '') {
    $buscarId = '%' . $busqueda . '%';
    $buscarNombre = '%' . $busqueda . '%';
    $buscarCorreo = '%' . $busqueda . '%';
    $buscarTelefono = '%' . $busqueda . '%';

    oci_bind_by_name($stmt, ':busqueda_id', $buscarId);
    oci_bind_by_name($stmt, ':busqueda_nombre', $buscarNombre);
    oci_bind_by_name($stmt, ':busqueda_correo', $buscarCorreo);
    oci_bind_by_name($stmt, ':busqueda_telefono', $buscarTelefono);
}

$proveedores = [];
if (oci_execute($stmt)) {
    while ($fila = oci_fetch_assoc($stmt)) {
        $proveedores[] = $fila;
    }
} else {
    $errorOracle = oci_error($stmt);
    $error = 'No se pudieron consultar los proveedores.';
}
oci_free_statement($stmt);

$editando = $proveedorEditar !== null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores | Punto de Venta</title>
    <style>
        :root {
            --fondo: #f7f1f5;
            --panel: #fffdfb;
            --panel-suave: #f5edf6;
            --borde: #e8dce9;
            --texto: #34234b;
            --secundario: #7c7188;
            --morado: #7950a1;
            --morado-hover: #623b88;
            --verde: #32866f;
            --verde-fondo: #e4f3ed;
            --rojo: #b94f70;
            --rojo-fondo: #fae9ef;
            --sombra: 0 8px 24px rgba(79, 47, 91, .06);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px;
            background: linear-gradient(135deg, #f3eaf3 0%, #faf6f1 55%, #f4eaf0 100%);
            color: var(--texto);
            font-family: "Segoe UI", Arial, sans-serif;
            min-height: 100vh;
        }

        .contenedor { max-width: 1320px; margin: 0 auto; }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }

        .titulo-con-icono { display: flex; align-items: center; gap: 15px; }

        .icono-principal {
            width: 54px; height: 54px; display: grid; place-items: center;
            background: #e9d9f1; border-radius: 17px; font-size: 27px;
        }

        h1 { margin: 0 0 7px; font-size: clamp(23px, 3vw, 30px); letter-spacing: -.5px; }
        h2 { margin: 0 0 20px; font-size: 18px; }
        .subtitulo, .ayuda { color: var(--secundario); font-size: 13px; line-height: 1.6; margin: 0; }
        .volver {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--morado); text-decoration: none; font-weight: 600;
            background: #fffdfb; border: 1px solid var(--borde);
            border-radius: 12px; padding: 12px 16px; box-shadow: var(--sombra);
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
        .indicador .etiqueta { color: var(--secundario); font-size: 13px; margin-bottom: 8px; }
        .indicador .numero { font-size: 27px; font-weight: 750; color: var(--morado); }
        .indicador .detalle { color: var(--secundario); font-size: 12px; margin-top: 5px; }

        .panel {
            background: rgba(255, 253, 251, .96);
            border: 1px solid var(--borde); border-radius: 17px;
            padding: 25px; margin-bottom: 22px; box-shadow: var(--sombra);
        }
        .titulo-panel { display: flex; align-items: center; gap: 10px; }
        .mini-icono {
            width: 35px; height: 35px; display: grid; place-items: center;
            border-radius: 11px; background: #eee1f4; font-size: 18px;
        }

        .formulario {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px;
        }
        .campo { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .campo.completo { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 600; color: #554663; }
        input {
            width: 100%; min-width: 0; padding: 12px 13px; border-radius: 10px;
            border: 1px solid #e2d6e6; background: #fff; color: var(--texto);
            font: inherit; font-size: 14px; outline: none; transition: .2s;
        }
        input:focus { border-color: #a783c2; box-shadow: 0 0 0 3px rgba(121, 80, 161, .12); }
        input::placeholder { color: #b0a5b7; }
        input[readonly] { background: #f3eef4; color: #7c7188; }
        .acciones-formulario {
            grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px;
        }

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
        .peligro { color: var(--rojo); background: var(--rojo-fondo); border-color: #f1d4df; }
        .peligro:hover { background: #f5d9e3; }

        .mensaje { padding: 13px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .exito { color: #286c59; background: var(--verde-fondo); border: 1px solid #c8e7db; }
        .error { color: #a43e60; background: var(--rojo-fondo); border: 1px solid #f0ccd9; }

        .cabecera-lista {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
        }
        .cabecera-lista h2 { margin: 0; }
        .buscador { display: flex; gap: 10px; margin-bottom: 17px; }
        .buscador input { flex: 1; }
        .contador { color: var(--secundario); font-size: 12px; margin-bottom: 13px; }

        .tabla-contenedor {
            overflow-x: auto; border: 1px solid #eee4ef; border-radius: 12px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 800px; }
        th {
            background: #f2eaf4; color: #695278; font-size: 11px;
            text-transform: uppercase; letter-spacing: .5px; text-align: left; white-space: nowrap;
        }
        th, td { padding: 14px 13px; border-bottom: 1px solid #eee6ef; }
        tbody tr:last-child td { border-bottom: 0; }
        td { color: #51445d; vertical-align: middle; }
        tbody tr:hover { background: #fcf8fd; }
        .codigo {
            color: var(--morado); font-weight: 750;
            background: #f7f0fa; border-radius: 6px;
        }
        .acciones-tabla { display: flex; gap: 7px; align-items: center; }
        .acciones-tabla .boton { padding: 8px 10px; font-size: 12px; }
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
            .formulario { grid-template-columns: 1fr; }
            .campo.completo, .acciones-formulario { grid-column: auto; }
            .buscador { flex-wrap: wrap; }
            .buscador input { flex-basis: 100%; }
            .buscador .boton { flex: 1; }
            .acciones-formulario .boton { flex: 1; }
            .titulo-con-icono { align-items: flex-start; }
        }
    </style>
</head>
<body>
<main class="contenedor">
    <header class="encabezado">
        <div class="titulo-con-icono">
            <div class="icono-principal">🚚</div>
            <div>
                <h1>Gestión de proveedores</h1>
                <p class="subtitulo">Registra y administra los proveedores de tu negocio.</p>
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
            <div class="etiqueta">Proveedores encontrados</div>
            <div class="numero"><?= count($proveedores) ?></div>
            <div class="detalle">Según la búsqueda actual</div>
        </div>
        <div class="indicador">
            <div class="etiqueta">Estado del módulo</div>
            <div class="numero" style="font-size:22px;color:#32866f">Disponible</div>
            <div class="detalle">Gestión de proveedores</div>
        </div>
        <div class="indicador">
            <div class="etiqueta">Acción actual</div>
            <div class="numero" style="font-size:22px"><?= $editando ? 'Edición' : 'Registro' ?></div>
            <div class="detalle"><?= $editando ? 'Modifica los datos seleccionados' : 'Agrega un nuevo proveedor' ?></div>
        </div>
    </section>

    <section class="panel">
        <h2 class="titulo-panel">
            <span class="mini-icono"><?= $editando ? '✏️' : '＋' ?></span>
            <?= $editando ? 'Editar proveedor' : 'Registrar proveedor' ?>
        </h2>

        <form action="guardar.php" method="POST" class="formulario">
            <input type="hidden" name="accion" value="<?= $editando ? 'modificar' : 'registrar' ?>">

            <div class="campo">
                <label for="id_proveedor">Código del proveedor *</label>
                <input type="number" id="id_proveedor" name="id_proveedor" min="1" max="999999"
                    step="1" required value="<?= e($proveedorEditar['ID_PROVEEDOR'] ?? '') ?>"
                    <?= $editando ? 'readonly' : '' ?> placeholder="Ej. 101">
                <?php if ($editando): ?>
                    <span class="ayuda">El código no se puede modificar.</span>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="nombre">Nombre del proveedor *</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                    value="<?= e($proveedorEditar['NOMBRE'] ?? '') ?>" placeholder="Nombre o razón social">
            </div>

            <div class="campo">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" maxlength="15"
                    value="<?= e($proveedorEditar['TELEFONO'] ?? '') ?>" placeholder="Número de contacto">
            </div>

            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" maxlength="100"
                    value="<?= e($proveedorEditar['CORREO'] ?? '') ?>" placeholder="proveedor@ejemplo.com">
            </div>

            <div class="campo completo">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" maxlength="150"
                    value="<?= e($proveedorEditar['DIRECCION'] ?? '') ?>" placeholder="Dirección del proveedor">
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="boton primario">
                    <?= $editando ? '✓ Guardar cambios' : '＋ Registrar proveedor' ?>
                </button>
                <?php if ($editando): ?>
                    <a href="index.php" class="boton secundario">Cancelar edición</a>
                <?php else: ?>
                    <button type="reset" class="boton secundario">Limpiar formulario</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="cabecera-lista">
            <h2 class="titulo-panel"><span class="mini-icono">▤</span> Lista de proveedores</h2>
        </div>

        <form action="index.php" method="GET" class="buscador">
            <input type="search" name="buscar" value="<?= e($busqueda) ?>" maxlength="100"
                placeholder="⌕  Buscar por código, nombre, teléfono o correo...">
            <button type="submit" class="boton primario">Buscar</button>
            <a href="index.php" class="boton secundario">Limpiar búsqueda</a>
        </form>

        <div class="contador">Proveedores encontrados: <?= count($proveedores) ?></div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Código</th><th>Nombre</th><th>Teléfono</th>
                        <th>Correo</th><th>Dirección</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($proveedores) > 0): ?>
                    <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><span class="codigo"><?= e($p['ID_PROVEEDOR']) ?></span></td>
                            <td><?= e($p['NOMBRE']) ?></td>
                            <td><?= e($p['TELEFONO'] ?: '—') ?></td>
                            <td><?= e($p['CORREO'] ?: '—') ?></td>
                            <td><?= e($p['DIRECCION'] ?: '—') ?></td>
                            <td>
                                <div class="acciones-tabla">
                                    <a class="boton secundario" href="index.php?editar=<?= (int)$p['ID_PROVEEDOR'] ?>">Editar</a>
                                    <form action="eliminar.php" method="POST" class="form-eliminar"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar este proveedor?');">
                                        <input type="hidden" name="id_proveedor" value="<?= (int)$p['ID_PROVEEDOR'] ?>">
                                        <button type="submit" class="boton peligro">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="vacio">No se encontraron proveedores.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>Sistema de Punto de Venta · PHP y Oracle Database 19c<br>Gestión de proveedores</footer>
</main>
</body>
</html>

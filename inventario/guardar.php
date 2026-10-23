
<?php
require_once __DIR__ . '/../config/conexion.php';

function volver($parametro, $mensaje) {
    header(
        'Location: index.php?' . $parametro . '=' . urlencode($mensaje)
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('error', 'Solicitud no válida.');
}

$accion = $_POST['accion'] ?? '';
$id_producto = filter_var(
    $_POST['id_producto'] ?? null,
    FILTER_VALIDATE_INT
);
$cantidad = filter_var(
    $_POST['cantidad'] ?? null,
    FILTER_VALIDATE_INT
);
$stock_minimo = filter_var(
    $_POST['stock_minimo'] ?? null,
    FILTER_VALIDATE_INT
);
$stock_maximo = filter_var(
    $_POST['stock_maximo'] ?? null,
    FILTER_VALIDATE_INT
);
$ubicacion = trim($_POST['ubicacion'] ?? '');

if (
    !in_array($accion, ['registrar', 'modificar'], true) ||
    $id_producto === false || $id_producto === null || $id_producto <= 0 ||
    $cantidad === false || $cantidad === null || $cantidad < 0 ||
    $stock_minimo === false || $stock_minimo === null || $stock_minimo < 0 ||
    $stock_maximo === false || $stock_maximo === null || $stock_maximo < 0
) {
    volver('error', 'Verifica los datos ingresados.');
}

if ($stock_minimo > $stock_maximo) {
    volver('error', 'El stock mínimo no puede superar el stock máximo.');
}

if ($cantidad > $stock_maximo) {
    volver('error', 'La cantidad no puede superar el stock máximo.');
}

if (mb_strlen($ubicacion) > 100) {
    volver('error', 'La ubicación no debe superar 100 caracteres.');
}

$id_inventario = null;

if ($accion === 'modificar') {
    $id_inventario = filter_var(
        $_POST['id_inventario'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $id_inventario === false ||
        $id_inventario === null ||
        $id_inventario <= 0
    ) {
        volver('error', 'Código de inventario no válido.');
    }
}

try {
    if ($accion === 'registrar') {
        // Evita registrar dos inventarios para el mismo producto.
        $sqlExiste = "
            SELECT COUNT(*) AS TOTAL
            FROM inventario
            WHERE id_producto = :id_producto
        ";

        $stmtExiste = oci_parse($conn, $sqlExiste);
        oci_bind_by_name($stmtExiste, ':id_producto', $id_producto);

        if (!oci_execute($stmtExiste)) {
            $err = oci_error($stmtExiste);
            throw new Exception($err['message']);
        }

        $filaExiste = oci_fetch_assoc($stmtExiste);
        oci_free_statement($stmtExiste);

        if ((int)$filaExiste['TOTAL'] > 0) {
            volver(
                'error',
                'Este producto ya tiene un registro de inventario. Usa Editar.'
            );
        }

        // Genera el siguiente ID usando el máximo existente.
        $sqlId = "
            SELECT NVL(MAX(id_inventario), 0) + 1 AS NUEVO_ID
            FROM inventario
        ";

        $stmtId = oci_parse($conn, $sqlId);

        if (!oci_execute($stmtId)) {
            $err = oci_error($stmtId);
            throw new Exception($err['message']);
        }

        $filaId = oci_fetch_assoc($stmtId);
        $id_inventario = (int)$filaId['NUEVO_ID'];
        oci_free_statement($stmtId);

        if ($id_inventario > 999999) {
            volver('error', 'Se alcanzó el límite de códigos de inventario.');
        }

        $sql = "
            INSERT INTO inventario (
                id_inventario, id_producto, cantidad,
                stock_minimo, stock_maximo, ubicacion
            ) VALUES (
                :id_inventario, :id_producto, :cantidad,
                :stock_minimo, :stock_maximo, :ubicacion
            )
        ";
    } else {
        $sql = "
            UPDATE inventario
            SET id_producto = :id_producto,
                cantidad = :cantidad,
                stock_minimo = :stock_minimo,
                stock_maximo = :stock_maximo,
                ubicacion = :ubicacion
            WHERE id_inventario = :id_inventario
        ";
    }

    $stmt = oci_parse($conn, $sql);

    if ($accion === 'registrar') {
        oci_bind_by_name($stmt, ':id_inventario', $id_inventario);
    }

    oci_bind_by_name($stmt, ':id_producto', $id_producto);
    oci_bind_by_name($stmt, ':cantidad', $cantidad);
    oci_bind_by_name($stmt, ':stock_minimo', $stock_minimo);
    oci_bind_by_name($stmt, ':stock_maximo', $stock_maximo);

    // Oracle guarda NULL si la ubicación está vacía.
    $ubicacionDB = ($ubicacion === '') ? null : $ubicacion;
    oci_bind_by_name($stmt, ':ubicacion', $ubicacionDB);

    if ($accion === 'modificar') {
        oci_bind_by_name($stmt, ':id_inventario', $id_inventario);
    }

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $err = oci_error($stmt);
        throw new Exception($err['message']);
    }

    if ($accion === 'modificar' && oci_num_rows($stmt) === 0) {
        oci_rollback($conn);
        oci_free_statement($stmt);
        volver('error', 'No se encontró el registro de inventario.');
    }

    if (!oci_commit($conn)) {
        $err = oci_error($conn);
        throw new Exception($err['message'] ?? 'No se pudieron guardar los cambios.');
    }

    oci_free_statement($stmt);
    oci_close($conn);

    $mensaje = ($accion === 'registrar')
        ? 'Inventario registrado correctamente.'
        : 'Inventario actualizado correctamente.';

    volver('mensaje', $mensaje);

} catch (Throwable $e) {
    oci_rollback($conn);

    error_log('Error en inventario/guardar.php: ' . $e->getMessage());

    volver(
        'error',
        'No se pudo guardar el inventario. Verifica los datos y las relaciones con Oracle.'
    );
}

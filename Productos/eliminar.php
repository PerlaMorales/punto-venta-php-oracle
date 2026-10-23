
<?php
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_var(
    $_POST['id_producto'] ?? null,
    FILTER_VALIDATE_INT
);

if ($id === false || $id === null || $id < 1 || $id > 999999) {
    oci_close($conn);
    header('Location: index.php?error=' . urlencode('Código de producto no válido.'));
    exit;
}

$sql = "DELETE FROM productos WHERE id_producto = :id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':id', $id);

if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $errorOracle = oci_error($stmt);
    oci_rollback($conn);

    oci_free_statement($stmt);
    oci_close($conn);

    if (isset($errorOracle['code']) && $errorOracle['code'] == 2292) {
        header(
            'Location: index.php?error=' .
            urlencode('No se puede eliminar: el producto tiene registros relacionados.')
        );
        exit;
    }

    header(
        'Location: index.php?error=' .
        urlencode('Oracle no pudo eliminar el producto.')
    );
    exit;
}

if (oci_num_rows($stmt) === 0) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    header(
        'Location: index.php?error=' .
        urlencode('El producto no existe o ya fue eliminado.')
    );
    exit;
}

if (!oci_commit($conn)) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    header(
        'Location: index.php?error=' .
        urlencode('No se pudo confirmar la eliminación.')
    );
    exit;
}

oci_free_statement($stmt);
oci_close($conn);

header(
    'Location: index.php?mensaje=' .
    urlencode('Producto eliminado correctamente.')
);
exit;
?>


<?php
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1 || $id > 999999) {
    oci_close($conn);
    header('Location: index.php?error=' . urlencode('Código de categoría no válido.'));
    exit;
}

$sql = "DELETE FROM categorias WHERE id_categoria = :id";
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
            urlencode('No se puede eliminar esta categoría porque tiene productos relacionados.')
        );
        exit;
    }

    header(
        'Location: index.php?error=' .
        urlencode('Oracle no pudo eliminar la categoría.')
    );
    exit;
}

if (oci_num_rows($stmt) === 0) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    header(
        'Location: index.php?error=' .
        urlencode('La categoría no existe o ya fue eliminada.')
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
    urlencode('Categoría eliminada correctamente.')
);
exit;
?>

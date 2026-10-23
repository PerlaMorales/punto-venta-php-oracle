
<?php
require_once __DIR__ . '/../config/conexion.php';

function volverConError($mensaje) {
    header('Location: index.php?error=' . urlencode($mensaje));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$idTexto = trim($_POST['id_proveedor'] ?? '');

if (
    $idTexto === '' ||
    !ctype_digit($idTexto) ||
    (int)$idTexto < 1 ||
    (int)$idTexto > 999999
) {
    volverConError('El código del proveedor no es válido.');
}

$id = (int)$idTexto;

$sql = "DELETE FROM proveedores WHERE id_proveedor = :id";
$stmt = oci_parse($conn, $sql);

if (!$stmt) {
    volverConError('No se pudo preparar la eliminación.');
}

oci_bind_by_name($stmt, ':id', $id);

if (oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {

    if (oci_num_rows($stmt) === 0) {
        oci_rollback($conn);
        oci_free_statement($stmt);
        volverConError('No se encontró el proveedor que deseas eliminar.');
    }

    if (oci_commit($conn)) {
        oci_free_statement($stmt);
        header('Location: index.php?mensaje=' .
            urlencode('Proveedor eliminado correctamente.'));
        exit;
    }

    oci_rollback($conn);
    oci_free_statement($stmt);
    volverConError('No se pudieron confirmar los cambios en Oracle.');

} else {
    $errorOracle = oci_error($stmt);

    oci_rollback($conn);
    oci_free_statement($stmt);

    if (isset($errorOracle['code']) && $errorOracle['code'] == 2292) {
        volverConError(
            'No se puede eliminar este proveedor porque tiene compras relacionadas.'
        );
    }

    volverConError(
        'No se pudo eliminar el proveedor. Verifica los datos e inténtalo nuevamente.'
    );
}

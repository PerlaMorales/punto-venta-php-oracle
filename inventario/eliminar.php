
<?php
require_once __DIR__ . '/../config/conexion.php';

function volver($parametro, $mensaje) {
    header(
        'Location: index.php?' . $parametro . '=' . urlencode($mensaje)
    );
    exit;
}

// Aceptar únicamente solicitudes POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('error', 'Solicitud no válida.');
}

// Validar el código del inventario.
$id_inventario = filter_var(
    $_POST['id_inventario'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $id_inventario === false ||
    $id_inventario === null ||
    $id_inventario <= 0
) {
    volver('error', 'El código de inventario no es válido.');
}

try {
    $sql = "
        DELETE FROM inventario
        WHERE id_inventario = :id_inventario
    ";

    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name(
        $stmt,
        ':id_inventario',
        $id_inventario
    );

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $err = oci_error($stmt);
        throw new Exception($err['message']);
    }

    // Verificar que realmente se haya eliminado un registro.
    if (oci_num_rows($stmt) === 0) {
        oci_rollback($conn);
        oci_free_statement($stmt);
        oci_close($conn);

        volver('error', 'No se encontró el registro de inventario.');
    }

    if (!oci_commit($conn)) {
        $err = oci_error($conn);
        throw new Exception(
            $err['message'] ?? 'No se pudo confirmar la eliminación.'
        );
    }

    oci_free_statement($stmt);
    oci_close($conn);

    volver('mensaje', 'Registro de inventario eliminado correctamente.');

} catch (Throwable $e) {
    oci_rollback($conn);

    // Guardar el detalle técnico en el registro de errores de PHP.
    error_log('Error en inventario/eliminar.php: ' . $e->getMessage());

    volver(
        'error',
        'No se pudo eliminar el registro. Verifica las relaciones con Oracle.'
    );
}


<?php
require_once __DIR__ . '/../config/conexion.php';

function volver($tipo, $mensaje) {
    header('Location: index.php?' . $tipo . '=' . urlencode($mensaje));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if (!in_array($accion, ['registrar', 'modificar'], true)) {
    volver('error', 'Acción no válida.');
}

if ($id === false || $id === null || $id < 1 || $id > 999999) {
    volver('error', 'El código debe estar entre 1 y 999999.');
}

if ($nombre === '' || strlen($nombre) > 50) {
    volver('error', 'El nombre es obligatorio y admite hasta 50 caracteres.');
}

if (strlen($descripcion) > 150) {
    volver('error', 'La descripción admite hasta 150 caracteres.');
}

if ($accion === 'registrar') {
    $sql = "INSERT INTO categorias
            (id_categoria, nombre, descripcion)
            VALUES (:id, :nombre, :descripcion)";
} else {
    $sql = "UPDATE categorias
            SET nombre = :nombre,
                descripcion = :descripcion
            WHERE id_categoria = :id";
}

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ':id', $id);
oci_bind_by_name($stmt, ':nombre', $nombre);
oci_bind_by_name($stmt, ':descripcion', $descripcion);

if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $errorOracle = oci_error($stmt);
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    if (isset($errorOracle['code']) && $errorOracle['code'] == 1) {
        volver('error', 'Ya existe una categoría con ese código.');
    }

    if (isset($errorOracle['code']) && $errorOracle['code'] == 2292) {
        volver('error', 'No se puede realizar la operación porque hay registros relacionados.');
    }

    volver('error', 'Oracle no pudo guardar la categoría. Verifica los datos.');
}

if ($accion === 'modificar' && oci_num_rows($stmt) === 0) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);
    volver('error', 'No se encontró la categoría que deseas modificar.');
}

if (!oci_commit($conn)) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);
    volver('error', 'No se pudieron confirmar los cambios.');
}

oci_free_statement($stmt);
oci_close($conn);

if ($accion === 'registrar') {
    volver('mensaje', 'Categoría registrada correctamente.');
}

volver('mensaje', 'Categoría modificada correctamente.');
?>

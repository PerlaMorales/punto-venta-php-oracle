
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

$accion = $_POST['accion'] ?? '';
$idTexto = trim($_POST['id_proveedor'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

/* Validar acción */
if (!in_array($accion, ['registrar', 'modificar'], true)) {
    volverConError('La operación solicitada no es válida.');
}

/* Validar código */
if (
    $idTexto === '' ||
    !ctype_digit($idTexto) ||
    (int)$idTexto < 1 ||
    (int)$idTexto > 999999
) {
    volverConError('El código debe ser un número entre 1 y 999999.');
}

$id = (int)$idTexto;

/* Validar campos obligatorios y longitudes */
if ($nombre === '') {
    volverConError('El nombre del proveedor es obligatorio.');
}

if (mb_strlen($nombre, 'UTF-8') > 100) {
    volverConError('El nombre no puede superar los 100 caracteres.');
}

if (mb_strlen($telefono, 'UTF-8') > 15) {
    volverConError('El teléfono no puede superar los 15 caracteres.');
}

if (mb_strlen($correo, 'UTF-8') > 100) {
    volverConError('El correo no puede superar los 100 caracteres.');
}

if (mb_strlen($direccion, 'UTF-8') > 150) {
    volverConError('La dirección no puede superar los 150 caracteres.');
}

if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    volverConError('Ingresa un correo electrónico válido.');
}

/* Convertir campos opcionales vacíos en NULL para Oracle */
$telefono = $telefono === '' ? null : $telefono;
$correo = $correo === '' ? null : $correo;
$direccion = $direccion === '' ? null : $direccion;

if ($accion === 'registrar') {

    $sql = "INSERT INTO proveedores
            (id_proveedor, nombre, telefono, correo, direccion)
            VALUES
            (:id, :nombre, :telefono, :correo, :direccion)";

} else {

    $sql = "UPDATE proveedores
            SET nombre = :nombre,
                telefono = :telefono,
                correo = :correo,
                direccion = :direccion
            WHERE id_proveedor = :id";
}

$stmt = oci_parse($conn, $sql);

if (!$stmt) {
    $errorOracle = oci_error($conn);
    volverConError('No se pudo preparar la operación en Oracle.');
}

oci_bind_by_name($stmt, ':id', $id);
oci_bind_by_name($stmt, ':nombre', $nombre);
oci_bind_by_name($stmt, ':telefono', $telefono, 15);
oci_bind_by_name($stmt, ':correo', $correo, 100);
oci_bind_by_name($stmt, ':direccion', $direccion, 150);

$resultado = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

if ($resultado) {
    if ($accion === 'modificar' && oci_num_rows($stmt) === 0) {
        oci_rollback($conn);
        oci_free_statement($stmt);
        volverConError('No se encontró el proveedor que deseas modificar.');
    }

    if (oci_commit($conn)) {
        oci_free_statement($stmt);

        $mensaje = $accion === 'registrar'
            ? 'Proveedor registrado correctamente.'
            : 'Proveedor actualizado correctamente.';

        header('Location: index.php?mensaje=' . urlencode($mensaje));
        exit;
    }

    oci_rollback($conn);
    oci_free_statement($stmt);
    volverConError('No se pudieron confirmar los cambios en Oracle.');

} else {
    $errorOracle = oci_error($stmt);

    oci_rollback($conn);
    oci_free_statement($stmt);

    if (isset($errorOracle['code']) && $errorOracle['code'] == 1) {
        volverConError('Ese código de proveedor ya existe.');
    }

    volverConError(
        'No se pudo guardar el proveedor. Verifica los datos e inténtalo nuevamente.'
    );
}

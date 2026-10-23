
<?php
require_once __DIR__ . '/../config/conexion.php';

function volver($parametro, $mensaje) {
    header(
        'Location: index.php?' . $parametro . '=' . urlencode($mensaje)
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = filter_var(
    $_POST['id_producto'] ?? null,
    FILTER_VALIDATE_INT
);
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precioTexto = trim($_POST['precio_venta'] ?? '');
$categoria = filter_var(
    $_POST['id_categoria'] ?? null,
    FILTER_VALIDATE_INT
);
$marca = filter_var(
    $_POST['id_marca'] ?? null,
    FILTER_VALIDATE_INT
);

if (!in_array($accion, ['registrar', 'modificar'], true)) {
    volver('error', 'Acción no válida.');
}

if ($id === false || $id === null || $id < 1 || $id > 999999) {
    volver('error', 'El código debe ser un entero entre 1 y 999999.');
}

if ($nombre === '' || strlen($nombre) > 100) {
    volver('error', 'El nombre es obligatorio y no debe superar 100 caracteres.');
}

if (strlen($descripcion) > 200) {
    volver('error', 'La descripción no debe superar 200 caracteres.');
}

if (
    $precioTexto === '' ||
    !is_numeric($precioTexto) ||
    !is_finite((float)$precioTexto) ||
    (float)$precioTexto <= 0 ||
    (float)$precioTexto > 99999999.99
) {
    volver('error', 'Ingresa un precio válido mayor que cero.');
}

$precio = round((float)$precioTexto, 2);

if (
    $categoria === false || $categoria === null || $categoria < 1 ||
    $marca === false || $marca === null || $marca < 1
) {
    volver('error', 'Selecciona una categoría y una marca válidas.');
}

if ($accion === 'registrar') {
    $sql = "INSERT INTO productos
            (id_producto, nombre, descripcion, precio_venta,
             id_categoria, id_marca)
            VALUES
            (:id, :nombre, :descripcion, :precio,
             :categoria, :marca)";
} else {
    $sql = "UPDATE productos
            SET nombre = :nombre,
                descripcion = :descripcion,
                precio_venta = :precio,
                id_categoria = :categoria,
                id_marca = :marca
            WHERE id_producto = :id";
}

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ':id', $id);
oci_bind_by_name($stmt, ':nombre', $nombre);
oci_bind_by_name($stmt, ':descripcion', $descripcion);
oci_bind_by_name($stmt, ':precio', $precio);
oci_bind_by_name($stmt, ':categoria', $categoria);
oci_bind_by_name($stmt, ':marca', $marca);

if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $errorOracle = oci_error($stmt);
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    if (isset($errorOracle['code']) && $errorOracle['code'] == 1) {
        volver('error', 'Ya existe un producto con ese código.');
    }

    if (isset($errorOracle['code']) &&
        in_array($errorOracle['code'], [2291, 2292], true)) {
        volver(
            'error',
            'No se pudo completar la operación por una relación con otros registros.'
        );
    }

    volver('error', 'Oracle no pudo guardar los datos. Revisa los datos y las relaciones.');
}

if ($accion === 'modificar' && oci_num_rows($stmt) === 0) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);
    volver('error', 'No se encontró el producto que deseas modificar.');
}

if (!oci_commit($conn)) {
    oci_rollback($conn);
    oci_free_statement($stmt);
    oci_close($conn);
    volver('error', 'No se pudieron confirmar los cambios en Oracle.');
}

oci_free_statement($stmt);
oci_close($conn);

if ($accion === 'registrar') {
    volver('mensaje', 'Producto registrado correctamente.');
}

volver('mensaje', 'Producto modificado correctamente.');
?>

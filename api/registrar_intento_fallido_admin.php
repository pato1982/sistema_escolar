<?php
// ============================================================
// API: Registrar Intento de Registro Fallido - Administrador
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => ''
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Datos del administrador
$admin_rut = isset($input['admin_rut']) ? trim($input['admin_rut']) : '';
$admin_nombres = isset($input['admin_nombres']) ? trim($input['admin_nombres']) : '';
$admin_apellidos = isset($input['admin_apellidos']) ? trim($input['admin_apellidos']) : '';
$admin_telefono = isset($input['admin_telefono']) ? trim($input['admin_telefono']) : '';
$admin_correo = isset($input['admin_correo']) ? trim($input['admin_correo']) : '';
$codigo_validacion_ingresado = isset($input['codigo_validacion']) ? trim($input['codigo_validacion']) : '';

// Establecimiento
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar campos requeridos
if (empty($admin_rut) || empty($admin_nombres) || empty($admin_apellidos) || $establecimiento_id <= 0) {
    $response['message'] = 'Faltan datos requeridos';
    echo json_encode($response);
    exit;
}

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Insertar el intento fallido
$sql = "INSERT INTO tb_intentos_registro_fallidos_admin
        (admin_rut, admin_nombres, admin_apellidos, admin_telefono, admin_correo, codigo_validacion_ingresado, establecimiento_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi",
    $admin_rut,
    $admin_nombres,
    $admin_apellidos,
    $admin_telefono,
    $admin_correo,
    $codigo_validacion_ingresado,
    $establecimiento_id
);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Intento registrado correctamente';
} else {
    $response['message'] = 'Error al registrar el intento';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

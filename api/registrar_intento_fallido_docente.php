<?php
// ============================================================
// API: Registrar Intento de Registro Fallido - Docente
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

// Datos del docente
$docente_rut = isset($input['docente_rut']) ? trim($input['docente_rut']) : '';
$docente_nombres = isset($input['docente_nombres']) ? trim($input['docente_nombres']) : '';
$docente_apellidos = isset($input['docente_apellidos']) ? trim($input['docente_apellidos']) : '';
$docente_telefono = isset($input['docente_telefono']) ? trim($input['docente_telefono']) : '';
$docente_correo = isset($input['docente_correo']) ? trim($input['docente_correo']) : '';

// Establecimiento
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar campos requeridos
if (empty($docente_rut) || empty($docente_nombres) || empty($docente_apellidos) || $establecimiento_id <= 0) {
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
$sql = "INSERT INTO tb_intentos_registro_fallidos_docentes
        (docente_rut, docente_nombres, docente_apellidos, docente_telefono, docente_correo, establecimiento_id)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi",
    $docente_rut,
    $docente_nombres,
    $docente_apellidos,
    $docente_telefono,
    $docente_correo,
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

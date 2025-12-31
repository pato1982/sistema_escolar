<?php
// ============================================================
// API: Registrar Intento de Registro Fallido
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

// Datos del apoderado
$apoderado_rut = isset($input['apoderado_rut']) ? trim($input['apoderado_rut']) : '';
$apoderado_nombres = isset($input['apoderado_nombres']) ? trim($input['apoderado_nombres']) : '';
$apoderado_apellidos = isset($input['apoderado_apellidos']) ? trim($input['apoderado_apellidos']) : '';
$apoderado_telefono = isset($input['apoderado_telefono']) ? trim($input['apoderado_telefono']) : '';
$apoderado_parentesco = isset($input['apoderado_parentesco']) ? trim($input['apoderado_parentesco']) : '';

// Datos del alumno
$alumno_rut = isset($input['alumno_rut']) ? trim($input['alumno_rut']) : '';
$alumno_nombres = isset($input['alumno_nombres']) ? trim($input['alumno_nombres']) : '';
$alumno_apellidos = isset($input['alumno_apellidos']) ? trim($input['alumno_apellidos']) : '';
$alumno_curso = isset($input['alumno_curso']) ? trim($input['alumno_curso']) : '';

// Establecimiento
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar campos requeridos
if (empty($apoderado_rut) || empty($apoderado_nombres) || empty($apoderado_apellidos) ||
    empty($alumno_rut) || empty($alumno_nombres) || empty($alumno_apellidos) ||
    $establecimiento_id <= 0) {
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
$sql = "INSERT INTO tb_intentos_registro_fallidos
        (apoderado_rut, apoderado_nombres, apoderado_apellidos, apoderado_telefono, apoderado_parentesco,
         alumno_rut, alumno_nombres, alumno_apellidos, alumno_curso, establecimiento_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssssi",
    $apoderado_rut,
    $apoderado_nombres,
    $apoderado_apellidos,
    $apoderado_telefono,
    $apoderado_parentesco,
    $alumno_rut,
    $alumno_nombres,
    $alumno_apellidos,
    $alumno_curso,
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

<?php
// ============================================================
// API: Eliminar Asignación - Desactiva una asignación
// ============================================================

session_start();

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

$response = [
    'success' => false,
    'message' => ''
];

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$asignacion_id = isset($input['id']) ? intval($input['id']) : 0;

if (!$asignacion_id) {
    $response['message'] = 'ID de asignación no válido';
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

// Eliminar asignación permanentemente (sin verificación previa)
$sql = "DELETE FROM tb_asignaciones WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asignacion_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Asignación eliminada correctamente';
} else {
    $response['message'] = 'Error al eliminar la asignación: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

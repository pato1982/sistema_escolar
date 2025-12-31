<?php
// ============================================================
// API: Eliminar Asignatura
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

$input = json_decode(file_get_contents('php://input'), true);
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : 0;

if (!$asignatura_id) {
    $response['message'] = 'ID de asignatura requerido';
    echo json_encode($response);
    exit;
}

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Verificar que la asignatura pertenece al establecimiento
$sql_check = "SELECT id, nombre FROM tb_asignaturas WHERE id = ? AND establecimiento_id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $asignatura_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Asignatura no encontrada';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$asignatura = $result->fetch_assoc();
$stmt->close();

// Verificar si tiene notas asociadas
$sql_notas = "SELECT COUNT(*) as total FROM tb_notas WHERE asignatura_id = ?";
$stmt = $conn->prepare($sql_notas);
$stmt->bind_param("i", $asignatura_id);
$stmt->execute();
$result = $stmt->get_result();
$notas = $result->fetch_assoc();
$stmt->close();

if ($notas['total'] > 0) {
    $response['message'] = 'No se puede eliminar: la asignatura tiene ' . $notas['total'] . ' notas registradas';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Verificar si tiene asignaciones activas
$sql_asig = "SELECT COUNT(*) as total FROM tb_asignaciones WHERE asignatura_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_asig);
$stmt->bind_param("i", $asignatura_id);
$stmt->execute();
$result = $stmt->get_result();
$asignaciones = $result->fetch_assoc();
$stmt->close();

if ($asignaciones['total'] > 0) {
    $response['message'] = 'No se puede eliminar: la asignatura tiene ' . $asignaciones['total'] . ' asignaciones activas';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Eliminar especialidades de docentes asociadas
$sql_esp = "DELETE FROM tb_docente_asignatura WHERE asignatura_id = ?";
$stmt = $conn->prepare($sql_esp);
$stmt->bind_param("i", $asignatura_id);
$stmt->execute();
$stmt->close();

// Eliminar la asignatura (o marcar como inactiva)
$sql_delete = "DELETE FROM tb_asignaturas WHERE id = ? AND establecimiento_id = ?";
$stmt = $conn->prepare($sql_delete);
$stmt->bind_param("ii", $asignatura_id, $establecimiento_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Asignatura "' . $asignatura['nombre'] . '" eliminada correctamente';
} else {
    $response['message'] = 'Error al eliminar la asignatura: ' . $conn->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

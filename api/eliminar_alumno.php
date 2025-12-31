<?php
// ============================================================
// API: Eliminar Alumno - Elimina permanentemente un alumno
// ============================================================

session_start();

require_once 'helper_auditoria.php';

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
$alumno_id = isset($input['id']) ? intval($input['id']) : 0;

if (!$alumno_id) {
    $response['message'] = 'ID de alumno no válido';
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

// Verificar que el alumno pertenece al establecimiento
$sql_check = "SELECT id, nombres, apellidos FROM tb_alumnos WHERE id = ? AND establecimiento_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $alumno_id, $establecimiento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $response['message'] = 'Alumno no encontrado';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$alumno = $result_check->fetch_assoc();
$stmt_check->close();

// Primero eliminar las notas del alumno
$sql_notas = "DELETE FROM tb_notas WHERE alumno_id = ?";
$stmt_notas = $conn->prepare($sql_notas);
$stmt_notas->bind_param("i", $alumno_id);
$stmt_notas->execute();
$stmt_notas->close();

// Eliminar alumno permanentemente
$sql = "DELETE FROM tb_alumnos WHERE id = ? AND establecimiento_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $alumno_id, $establecimiento_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Alumno "' . $alumno['nombres'] . ' ' . $alumno['apellidos'] . '" eliminado correctamente';

    // Registrar en auditoría
    $nombre_completo = $alumno['nombres'] . ' ' . $alumno['apellidos'];
    $descripcion_audit = "Eliminó alumno $nombre_completo";
    $datos_anteriores = [
        'id' => $alumno_id,
        'nombres' => $alumno['nombres'],
        'apellidos' => $alumno['apellidos']
    ];
    registrarActividad($conn, 'eliminar', 'alumno', $descripcion_audit, $alumno_id, $datos_anteriores, null);
} else {
    $response['message'] = 'Error al eliminar el alumno: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

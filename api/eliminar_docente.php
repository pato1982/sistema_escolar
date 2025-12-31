<?php
// ============================================================
// API: Eliminar Docente - Elimina permanentemente un docente
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
$docente_id = isset($input['id']) ? intval($input['id']) : 0;

if (!$docente_id) {
    $response['message'] = 'ID de docente no válido';
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

// Verificar que el docente pertenece al establecimiento
$sql_check = "SELECT id, nombres, apellidos FROM tb_docentes WHERE id = ? AND establecimiento_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $docente_id, $establecimiento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $response['message'] = 'Docente no encontrado';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$docente = $result_check->fetch_assoc();
$stmt_check->close();

// Eliminar especialidades del docente
$sql_esp = "DELETE FROM tb_docente_asignatura WHERE docente_id = ?";
$stmt_esp = $conn->prepare($sql_esp);
$stmt_esp->bind_param("i", $docente_id);
$stmt_esp->execute();
$stmt_esp->close();

// Eliminar asignaciones del docente
$sql_asig = "DELETE FROM tb_asignaciones WHERE docente_id = ?";
$stmt_asig = $conn->prepare($sql_asig);
$stmt_asig->bind_param("i", $docente_id);
$stmt_asig->execute();
$stmt_asig->close();

// Eliminar notas registradas por el docente (opcional, poner NULL)
$sql_notas = "UPDATE tb_notas SET docente_id = NULL WHERE docente_id = ?";
$stmt_notas = $conn->prepare($sql_notas);
$stmt_notas->bind_param("i", $docente_id);
$stmt_notas->execute();
$stmt_notas->close();

// Eliminar docente permanentemente
$sql = "DELETE FROM tb_docentes WHERE id = ? AND establecimiento_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $docente_id, $establecimiento_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Docente "' . $docente['nombres'] . ' ' . $docente['apellidos'] . '" eliminado correctamente';

    // Registrar en auditoría
    $nombre_completo = $docente['nombres'] . ' ' . $docente['apellidos'];
    $descripcion_audit = "Eliminó docente $nombre_completo";
    $datos_anteriores = [
        'id' => $docente_id,
        'nombres' => $docente['nombres'],
        'apellidos' => $docente['apellidos']
    ];
    registrarActividad($conn, 'eliminar', 'docente', $descripcion_audit, $docente_id, $datos_anteriores, null);
} else {
    $response['message'] = 'Error al eliminar el docente: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

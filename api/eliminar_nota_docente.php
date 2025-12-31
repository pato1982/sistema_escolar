<?php
// ============================================================
// API: Eliminar Nota - Eliminar calificación (Docente)
// ============================================================

session_start();

require_once 'helper_auditoria.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => ''
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

// Verificar que sea docente
if ($_SESSION['tipo_usuario'] !== 'docente') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$nota_id = isset($input['nota_id']) ? intval($input['nota_id']) : null;
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];

// Validaciones
if (!$nota_id || !$docente_id) {
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

// Verificar que la nota exista y pertenezca al docente, obtener datos para auditoría
$sql_verificar = "SELECT n.id, n.nota, n.trimestre, a.nombres as alumno_nombres, a.apellidos as alumno_apellidos, asig.nombre as asignatura_nombre
                  FROM tb_notas n
                  LEFT JOIN tb_alumnos a ON n.alumno_id = a.id
                  LEFT JOIN tb_asignaturas asig ON n.asignatura_id = asig.id
                  WHERE n.id = ? AND n.docente_id = ? AND n.establecimiento_id = ?";
$stmt_ver = $conn->prepare($sql_verificar);
$stmt_ver->bind_param("iii", $nota_id, $docente_id, $establecimiento_id);
$stmt_ver->execute();
$result_ver = $stmt_ver->get_result();

if ($result_ver->num_rows === 0) {
    $response['message'] = 'No tiene permisos para eliminar esta nota o no existe';
    $stmt_ver->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$nota_info = $result_ver->fetch_assoc();
$stmt_ver->close();

// Eliminar la nota
$sql = "DELETE FROM tb_notas WHERE id = ? AND docente_id = ? AND establecimiento_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $nota_id, $docente_id, $establecimiento_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Nota eliminada correctamente';

        // Registrar en auditoría
        $nombre_alumno = $nota_info['alumno_nombres'] . ' ' . $nota_info['alumno_apellidos'];
        $descripcion_audit = "Eliminó nota " . $nota_info['nota'] . " de $nombre_alumno en " . $nota_info['asignatura_nombre'];
        $datos_anteriores = [
            'nota' => $nota_info['nota'],
            'trimestre' => $nota_info['trimestre'],
            'alumno' => $nombre_alumno,
            'asignatura' => $nota_info['asignatura_nombre']
        ];
        registrarActividad($conn, 'eliminar', 'nota', $descripcion_audit, $nota_id, $datos_anteriores, null);
    } else {
        $response['message'] = 'No se pudo eliminar la nota';
    }
} else {
    $response['message'] = 'Error al eliminar la nota: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

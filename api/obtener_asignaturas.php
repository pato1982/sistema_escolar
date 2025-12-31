<?php
// ============================================================
// API: Obtener Asignaturas - Lista las asignaturas del establecimiento
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

$establecimiento_id = $_SESSION['establecimiento_id'];

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Obtener parámetros
$input = json_decode(file_get_contents('php://input'), true);
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;

// Si se especifica docente_id, obtener solo sus especialidades
if ($docente_id) {
    $sql = "SELECT a.id, a.nombre, a.codigo
            FROM tb_asignaturas a
            INNER JOIN tb_docente_asignatura da ON a.id = da.asignatura_id
            WHERE da.docente_id = ? AND a.activo = TRUE
            ORDER BY a.nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $docente_id);
}
// Si se especifica curso_id, obtener asignaturas asignadas al curso
elseif ($curso_id) {
    $anio_actual = date('Y');
    $sql = "SELECT DISTINCT a.id, a.nombre, a.codigo
            FROM tb_asignaturas a
            INNER JOIN tb_asignaciones asig ON a.id = asig.asignatura_id
            WHERE asig.curso_id = ? AND asig.anio_academico = ? AND asig.activo = TRUE AND a.activo = TRUE
            ORDER BY a.nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $curso_id, $anio_actual);
}
// Obtener todas las asignaturas del establecimiento
else {
    $sql = "SELECT id, nombre, codigo
            FROM tb_asignaturas
            WHERE establecimiento_id = ? AND activo = TRUE
            ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $establecimiento_id);
}

$stmt->execute();
$result = $stmt->get_result();

$asignaturas = [];
while ($row = $result->fetch_assoc()) {
    $asignaturas[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Asignaturas obtenidas correctamente';
$response['data'] = $asignaturas;

echo json_encode($response);
?>

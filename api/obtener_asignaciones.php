<?php
// ============================================================
// API: Obtener Asignaciones - Lista las asignaciones docente-curso-asignatura
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
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Construir consulta
$sql = "SELECT asig.id, asig.docente_id, asig.curso_id, asig.asignatura_id, asig.anio_academico,
               d.nombres as docente_nombres, d.apellidos as docente_apellidos,
               c.nombre as curso_nombre,
               a.nombre as asignatura_nombre
        FROM tb_asignaciones asig
        INNER JOIN tb_docentes d ON asig.docente_id = d.id
        INNER JOIN tb_cursos c ON asig.curso_id = c.id
        INNER JOIN tb_asignaturas a ON asig.asignatura_id = a.id
        WHERE asig.establecimiento_id = ? AND asig.anio_academico = ? AND asig.activo = TRUE";

$params = [$establecimiento_id, $anio_academico];
$types = "ii";

if ($curso_id) {
    $sql .= " AND asig.curso_id = ?";
    $params[] = $curso_id;
    $types .= "i";
}

if ($docente_id) {
    $sql .= " AND asig.docente_id = ?";
    $params[] = $docente_id;
    $types .= "i";
}

$sql .= " ORDER BY c.nombre, a.nombre, d.apellidos";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$asignaciones = [];
while ($row = $result->fetch_assoc()) {
    $row['docente_nombre_completo'] = $row['docente_nombres'] . ' ' . $row['docente_apellidos'];
    $asignaciones[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Asignaciones obtenidas correctamente';
$response['data'] = $asignaciones;

echo json_encode($response);
?>

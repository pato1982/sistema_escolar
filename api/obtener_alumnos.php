<?php
// ============================================================
// API: Obtener Alumnos - Lista los alumnos del establecimiento
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
$busqueda = isset($input['busqueda']) ? trim($input['busqueda']) : '';

// Construir consulta
$sql = "SELECT a.id, a.nombres, a.apellidos, a.rut, a.fecha_nacimiento, a.sexo,
               a.curso_id, c.nombre as curso_nombre
        FROM tb_alumnos a
        LEFT JOIN tb_cursos c ON a.curso_id = c.id
        WHERE a.establecimiento_id = ? AND a.activo = TRUE";

$params = [$establecimiento_id];
$types = "i";

if ($curso_id) {
    $sql .= " AND a.curso_id = ?";
    $params[] = $curso_id;
    $types .= "i";
}

if ($busqueda) {
    $sql .= " AND (a.nombres LIKE ? OR a.apellidos LIKE ? OR a.rut LIKE ?)";
    $busqueda_like = "%$busqueda%";
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $types .= "sss";
}

$sql .= " ORDER BY a.apellidos, a.nombres";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Alumnos obtenidos correctamente';
$response['data'] = $alumnos;

echo json_encode($response);
?>

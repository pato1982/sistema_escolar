<?php
// ============================================================
// API: Obtener Cursos - Lista los cursos del establecimiento
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

// Obtener cursos del establecimiento
$sql = "SELECT id, nombre, codigo, nivel
        FROM tb_cursos
        WHERE establecimiento_id = ? AND activo = TRUE
        ORDER BY nivel, nombre";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

$cursos = [];
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Cursos obtenidos correctamente';
$response['data'] = $cursos;

echo json_encode($response);
?>

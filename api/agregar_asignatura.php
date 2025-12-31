<?php
// ============================================================
// API: Agregar Asignatura - Crea una nueva asignatura
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

$nombre = isset($input['nombre']) ? trim($input['nombre']) : '';

// Generar código automáticamente (primeras 3 letras en mayúsculas)
$palabras = explode(' ', $nombre);
if (count($palabras) > 1) {
    // Si tiene más de una palabra, tomar primera letra de cada palabra (máx 3)
    $codigo = '';
    for ($i = 0; $i < min(3, count($palabras)); $i++) {
        $codigo .= strtoupper(mb_substr($palabras[$i], 0, 1, 'UTF-8'));
    }
} else {
    // Si es una sola palabra, tomar las primeras 3 letras
    $codigo = strtoupper(mb_substr($nombre, 0, 3, 'UTF-8'));
}

// Validar campos requeridos
if (empty($nombre)) {
    $response['message'] = 'El nombre de la asignatura es requerido';
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

// Verificar si la asignatura ya existe en el establecimiento
$sql_check = "SELECT id FROM tb_asignaturas WHERE nombre = ? AND establecimiento_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("si", $nombre, $establecimiento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['message'] = 'Ya existe una asignatura con ese nombre';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Insertar asignatura
$sql = "INSERT INTO tb_asignaturas (nombre, codigo, establecimiento_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nombre, $codigo, $establecimiento_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Asignatura agregada correctamente';
    $response['asignatura_id'] = $conn->insert_id;
} else {
    $response['message'] = 'Error al agregar la asignatura: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

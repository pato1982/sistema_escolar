<?php
// ============================================================
// API: Validar Código de Administrador
// ============================================================

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

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

$codigo = isset($input['codigo']) ? trim($input['codigo']) : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar que vengan todos los campos
if (empty($codigo) || $establecimiento_id <= 0) {
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

// Buscar el código en la tabla de códigos de validación
$sql = "SELECT id, codigo, usado
        FROM tb_codigos_validacion
        WHERE codigo = ?
        AND establecimiento_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $codigo, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si ya fue usado
    if ($row['usado']) {
        $response['success'] = false;
        $response['message'] = 'Este código ya ha sido utilizado.';
    } else {
        $response['success'] = true;
        $response['message'] = 'Código válido';
        $response['data'] = [
            'codigo_id' => $row['id']
        ];
    }
} else {
    $response['success'] = false;
    $response['message'] = 'El código de validación no es válido o no corresponde al establecimiento seleccionado. Por favor, verifique el código o comuníquese con Portal Estudiantil.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

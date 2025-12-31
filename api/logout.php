<?php
// ============================================================
// API: Logout - Cerrar sesión
// ============================================================

// Configurar cookies de sesión antes de iniciar
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
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

// Verificar si hay sesión activa
if (!isset($_SESSION['sesion_id'])) {
    $response['message'] = 'No hay sesión activa';
    echo json_encode($response);
    exit;
}

$sesion_id = $_SESSION['sesion_id'];

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Actualizar la sesión en la base de datos
$sql = "UPDATE tb_sesiones SET fecha_logout = NOW(), activa = FALSE WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sesion_id);
$stmt->execute();
$stmt->close();

$conn->close();

// Destruir la sesión PHP
session_unset();
session_destroy();

$response['success'] = true;
$response['message'] = 'Sesión cerrada correctamente';

echo json_encode($response);
?>

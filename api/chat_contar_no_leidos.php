<?php
// ============================================================
// API: Contar Mensajes No Leídos
// Retorna el total de mensajes no leídos del usuario
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'total_no_leidos' => 0
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Contar mensajes no leídos
$sql = "SELECT COUNT(*) as total
        FROM tb_chat_mensajes m
        INNER JOIN tb_chat_conversaciones c ON m.conversacion_id = c.id
        WHERE c.establecimiento_id = ?
        AND (c.usuario1_id = ? OR c.usuario2_id = ?)
        AND m.remitente_id != ?
        AND m.leido = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $establecimiento_id, $usuario_id, $usuario_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$response['success'] = true;
$response['total_no_leidos'] = (int)$row['total'];

$stmt->close();
$conn->close();

echo json_encode($response);
?>

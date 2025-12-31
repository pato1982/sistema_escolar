<?php
// ============================================================
// API: Obtener Mensajes del Chat
// Retorna los mensajes de una conversación
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
    'message' => '',
    'mensajes' => []
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener ID del contacto
$contacto_id = isset($_GET['contacto_id']) ? intval($_GET['contacto_id']) : 0;

if (!$contacto_id) {
    $response['message'] = 'ID de contacto no válido';
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

// Buscar o crear conversación
$sql_conv = "SELECT id FROM tb_chat_conversaciones
             WHERE establecimiento_id = ?
             AND ((usuario1_id = ? AND usuario2_id = ?) OR (usuario1_id = ? AND usuario2_id = ?))";
$stmt_conv = $conn->prepare($sql_conv);
$stmt_conv->bind_param("iiiii", $establecimiento_id, $usuario_id, $contacto_id, $contacto_id, $usuario_id);
$stmt_conv->execute();
$result_conv = $stmt_conv->get_result();

$conversacion_id = null;

if ($result_conv->num_rows > 0) {
    $row = $result_conv->fetch_assoc();
    $conversacion_id = $row['id'];
}
$stmt_conv->close();

$mensajes = [];

if ($conversacion_id) {
    // Obtener mensajes de la conversación
    $sql_msg = "SELECT m.id, m.remitente_id, m.mensaje, m.leido, m.fecha_envio,
                       CASE WHEN m.remitente_id = ? THEN 'enviado' ELSE 'recibido' END as tipo_mensaje
                FROM tb_chat_mensajes m
                WHERE m.conversacion_id = ?
                ORDER BY m.fecha_envio ASC";

    $stmt_msg = $conn->prepare($sql_msg);
    $stmt_msg->bind_param("ii", $usuario_id, $conversacion_id);
    $stmt_msg->execute();
    $result_msg = $stmt_msg->get_result();

    while ($row = $result_msg->fetch_assoc()) {
        $mensajes[] = [
            'id' => $row['id'],
            'remitente_id' => $row['remitente_id'],
            'mensaje' => $row['mensaje'],
            'leido' => (bool)$row['leido'],
            'fecha_envio' => $row['fecha_envio'],
            'tipo' => $row['tipo_mensaje']
        ];
    }
    $stmt_msg->close();

    // Marcar mensajes como leídos
    $sql_update = "UPDATE tb_chat_mensajes
                   SET leido = 1, fecha_lectura = NOW()
                   WHERE conversacion_id = ?
                   AND remitente_id = ?
                   AND leido = 0";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $conversacion_id, $contacto_id);
    $stmt_update->execute();
    $stmt_update->close();
}

$conn->close();

$response['success'] = true;
$response['mensajes'] = $mensajes;
$response['conversacion_id'] = $conversacion_id;

echo json_encode($response);
?>

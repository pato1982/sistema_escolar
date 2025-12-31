<?php
// ============================================================
// API: Enviar Mensaje del Chat
// Envía un nuevo mensaje en el chat
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$contacto_id = isset($input['contacto_id']) ? intval($input['contacto_id']) : 0;
$mensaje = isset($input['mensaje']) ? trim($input['mensaje']) : '';

if (!$contacto_id || empty($mensaje)) {
    $response['message'] = 'Datos incompletos';
    echo json_encode($response);
    exit;
}

// Limitar longitud del mensaje
if (strlen($mensaje) > 2000) {
    $response['message'] = 'El mensaje es demasiado largo (máximo 2000 caracteres)';
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

// Iniciar transacción
$conn->begin_transaction();

try {
    // Buscar conversación existente
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
    } else {
        // Crear nueva conversación
        $sql_new_conv = "INSERT INTO tb_chat_conversaciones (usuario1_id, usuario2_id, establecimiento_id)
                         VALUES (?, ?, ?)";
        $stmt_new_conv = $conn->prepare($sql_new_conv);
        $stmt_new_conv->bind_param("iii", $usuario_id, $contacto_id, $establecimiento_id);
        $stmt_new_conv->execute();
        $conversacion_id = $conn->insert_id;
        $stmt_new_conv->close();
    }
    $stmt_conv->close();

    // Insertar mensaje
    $sql_msg = "INSERT INTO tb_chat_mensajes (conversacion_id, remitente_id, mensaje)
                VALUES (?, ?, ?)";
    $stmt_msg = $conn->prepare($sql_msg);
    $stmt_msg->bind_param("iis", $conversacion_id, $usuario_id, $mensaje);
    $stmt_msg->execute();
    $mensaje_id = $conn->insert_id;
    $stmt_msg->close();

    // Actualizar última actividad de la conversación
    $sql_update = "UPDATE tb_chat_conversaciones SET ultima_actividad = NOW() WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $conversacion_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Mensaje enviado';
    $response['mensaje_id'] = $mensaje_id;
    $response['conversacion_id'] = $conversacion_id;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al enviar el mensaje';
    error_log('Error en chat_enviar_mensaje.php: ' . $e->getMessage());
}

$conn->close();

echo json_encode($response);
?>

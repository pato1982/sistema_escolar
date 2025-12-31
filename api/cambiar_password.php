<?php
// ============================================================
// API: Cambiar Contraseña - Actualiza contraseña con clave provisoria
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

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => ''
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$correo = isset($input['correo']) ? trim($input['correo']) : '';
$clave_provisoria = isset($input['clave_provisoria']) ? $input['clave_provisoria'] : '';
$nueva_password = isset($input['nueva_password']) ? $input['nueva_password'] : '';
$confirmar_password = isset($input['confirmar_password']) ? $input['confirmar_password'] : '';

// Validar campos requeridos
if (empty($correo) || empty($clave_provisoria) || empty($nueva_password) || empty($confirmar_password)) {
    $response['message'] = 'Todos los campos son requeridos';
    echo json_encode($response);
    exit;
}

// Validar que las contraseñas coincidan
if ($nueva_password !== $confirmar_password) {
    $response['message'] = 'Las contraseñas no coinciden';
    echo json_encode($response);
    exit;
}

// Validar longitud mínima de contraseña
if (strlen($nueva_password) < 6) {
    $response['message'] = 'La contraseña debe tener al menos 6 caracteres';
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

// Buscar usuario por correo
$sql = "SELECT id FROM tb_usuarios WHERE email = ? AND activo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Usuario no encontrado';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar clave provisoria
$sql_clave = "SELECT id, clave_provisoria_hash FROM tb_claves_provisorias
              WHERE usuario_id = ? AND usado = 0 AND fecha_expiracion > NOW()
              ORDER BY fecha_solicitud DESC LIMIT 1";
$stmt_clave = $conn->prepare($sql_clave);
$stmt_clave->bind_param("i", $usuario['id']);
$stmt_clave->execute();
$result_clave = $stmt_clave->get_result();

if ($result_clave->num_rows === 0) {
    $response['message'] = 'No hay una clave provisoria válida. Solicite una nueva.';
    $stmt_clave->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$clave_data = $result_clave->fetch_assoc();
$stmt_clave->close();

// Verificar que la clave provisoria sea correcta
if (!password_verify($clave_provisoria, $clave_data['clave_provisoria_hash'])) {
    $response['message'] = 'La clave provisoria es incorrecta';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Todo correcto, actualizar contraseña
$nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

$sql_update = "UPDATE tb_usuarios SET password_hash = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $nueva_password_hash, $usuario['id']);

if ($stmt_update->execute()) {
    // Marcar clave provisoria como usada
    $sql_marcar = "UPDATE tb_claves_provisorias SET usado = 1, fecha_uso = NOW() WHERE id = ?";
    $stmt_marcar = $conn->prepare($sql_marcar);
    $stmt_marcar->bind_param("i", $clave_data['id']);
    $stmt_marcar->execute();
    $stmt_marcar->close();

    $response['success'] = true;
    $response['message'] = 'Contraseña actualizada correctamente. Ahora puede iniciar sesión.';
} else {
    $response['message'] = 'Error al actualizar la contraseña. Intente nuevamente.';
}

$stmt_update->close();
$conn->close();

echo json_encode($response);
?>

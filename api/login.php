<?php
// ============================================================
// API: Login - Autenticación de usuarios
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

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'tipo_usuario' => null
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
$password_plain = isset($input['password']) ? $input['password'] : '';

// Obtener IP y User Agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Validar campos requeridos
if (empty($correo) || empty($password_plain)) {
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

// Función para registrar intento fallido
function registrarIntentoFallido($conn, $correo, $ip, $user_agent, $motivo) {
    $sql = "INSERT INTO tb_intentos_login_fallidos (correo_ingresado, ip_address, user_agent, motivo_fallo) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $correo, $ip, $user_agent, $motivo);
    $stmt->execute();
    $stmt->close();
}

// Función para registrar sesión exitosa
function registrarSesion($conn, $usuario_id, $tipo_usuario, $establecimiento_id, $ip, $user_agent) {
    $sql = "INSERT INTO tb_sesiones (usuario_id, tipo_usuario, establecimiento_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiss", $usuario_id, $tipo_usuario, $establecimiento_id, $ip, $user_agent);
    $stmt->execute();
    $sesion_id = $conn->insert_id;
    $stmt->close();
    return $sesion_id;
}

// Buscar usuario por correo
$sql = "SELECT id, email, password_hash, tipo_usuario, establecimiento_id, activo FROM tb_usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Correo no existe
    registrarIntentoFallido($conn, $correo, $ip_address, $user_agent, 'correo_no_existe');
    $response['message'] = 'El correo electrónico no está registrado en el sistema.';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar si la cuenta está activa
if (!$usuario['activo']) {
    registrarIntentoFallido($conn, $correo, $ip_address, $user_agent, 'cuenta_inactiva');
    $response['message'] = 'Su cuenta se encuentra inactiva. Por favor contacte al administrador.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Verificar contraseña normal
$es_clave_provisoria = false;
if (!password_verify($password_plain, $usuario['password_hash'])) {
    // La contraseña normal no coincide, verificar si es una clave provisoria
    $sql_clave = "SELECT id, clave_provisoria_hash, fecha_expiracion FROM tb_claves_provisorias
                  WHERE usuario_id = ? AND usado = 0 AND fecha_expiracion > NOW()
                  ORDER BY fecha_solicitud DESC LIMIT 1";
    $stmt_clave = $conn->prepare($sql_clave);
    $stmt_clave->bind_param("i", $usuario['id']);
    $stmt_clave->execute();
    $result_clave = $stmt_clave->get_result();

    if ($result_clave->num_rows > 0) {
        $clave_data = $result_clave->fetch_assoc();
        if (password_verify($password_plain, $clave_data['clave_provisoria_hash'])) {
            $es_clave_provisoria = true;
        }
    }
    $stmt_clave->close();

    if (!$es_clave_provisoria) {
        registrarIntentoFallido($conn, $correo, $ip_address, $user_agent, 'password_incorrecta');
        $response['message'] = 'La contraseña ingresada es incorrecta.';
        $conn->close();
        echo json_encode($response);
        exit;
    }
}

// Login exitoso - Actualizar último acceso
$sql_update = "UPDATE tb_usuarios SET ultimo_acceso = NOW() WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("i", $usuario['id']);
$stmt_update->execute();
$stmt_update->close();

// Registrar sesión
$sesion_id = registrarSesion($conn, $usuario['id'], $usuario['tipo_usuario'], $usuario['establecimiento_id'], $ip_address, $user_agent);

// Obtener datos adicionales según el tipo de usuario
$datos_usuario = [];
switch ($usuario['tipo_usuario']) {
    case 'apoderado':
        $sql_datos = "SELECT id, nombres, apellidos, rut FROM tb_apoderados WHERE usuario_id = ?";
        break;
    case 'docente':
        $sql_datos = "SELECT id, nombres, apellidos, rut FROM tb_docentes WHERE usuario_id = ?";
        break;
    case 'administrador':
        $sql_datos = "SELECT id, nombres, apellidos, rut FROM tb_administradores WHERE usuario_id = ?";
        break;
}

$stmt_datos = $conn->prepare($sql_datos);
$stmt_datos->bind_param("i", $usuario['id']);
$stmt_datos->execute();
$result_datos = $stmt_datos->get_result();
if ($result_datos->num_rows > 0) {
    $datos_usuario = $result_datos->fetch_assoc();
}
$stmt_datos->close();

// Guardar en sesión PHP
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
$_SESSION['establecimiento_id'] = $usuario['establecimiento_id'];
$_SESSION['sesion_id'] = $sesion_id;
$_SESSION['nombres'] = $datos_usuario['nombres'] ?? '';
$_SESSION['apellidos'] = $datos_usuario['apellidos'] ?? '';
$_SESSION['email'] = $usuario['email'];
$_SESSION['ultima_actividad'] = time(); // Para control de inactividad

$response['success'] = true;
$response['message'] = 'Inicio de sesión exitoso';
$response['tipo_usuario'] = $usuario['tipo_usuario'];
$response['datos'] = [
    'nombres' => $datos_usuario['nombres'] ?? '',
    'apellidos' => $datos_usuario['apellidos'] ?? ''
];
$response['requiere_cambio_password'] = $es_clave_provisoria;

$conn->close();
echo json_encode($response);
?>

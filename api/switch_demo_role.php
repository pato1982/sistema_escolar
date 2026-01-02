<?php
header('Content-Type: application/json');
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$role = $input['role'] ?? '';

if (!$role) {
    echo json_encode(['success' => false, 'message' => 'Rol no especificado']);
    exit;
}

// Configuración de conexión
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    exit;
}

// Definir el correo según el rol demo
$email = '';
if ($role === 'admin')
    $email = 'admin@demo.cl';
elseif ($role === 'docente')
    $email = 'docente@demo.cl';
elseif ($role === 'apoderado')
    $email = 'apoderado@demo.cl';
else {
    echo json_encode(['success' => false, 'message' => 'Rol no válido']);
    exit;
}

// Buscar el usuario
$sql = "SELECT id, email, password, tipo_usuario, establecimiento_id, nombres, apellidos, requiere_cambio_password 
        FROM tb_usuarios 
        WHERE email = ? AND activo = TRUE";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user_data = $result->fetch_assoc()) {
    // Para el demo, no validamos password ya que es un switch rápido

    // Limpiar sesión anterior
    session_unset();

    // Establecer nueva sesión
    $_SESSION['usuario_id'] = $user_data['id'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['tipo_usuario'] = $user_data['tipo_usuario'];
    $_SESSION['establecimiento_id'] = $user_data['establecimiento_id'];
    $_SESSION['nombres'] = $user_data['nombres'];
    $_SESSION['apellidos'] = $user_data['apellidos'];

    $redirect = 'index.php';
    if ($user_data['tipo_usuario'] === 'administrador')
        $redirect = 'colegio.php';
    elseif ($user_data['tipo_usuario'] === 'docente')
        $redirect = 'docente.php';
    elseif ($user_data['tipo_usuario'] === 'apoderado')
        $redirect = 'apoderado.php';

    echo json_encode([
        'success' => true,
        'redirect' => $redirect
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario demo no encontrado']);
}

$stmt->close();
$conn->close();

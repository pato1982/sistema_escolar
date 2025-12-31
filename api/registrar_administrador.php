<?php
// ============================================================
// API: Registrar Administrador (Usuario + Administrador + Marcar código)
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
    'message' => ''
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Función para formatear nombres: Primera letra mayúscula de cada palabra
function formatearNombre($texto) {
    if (empty($texto)) return '';
    return mb_convert_case(mb_strtolower(trim($texto), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Datos del administrador
$admin_nombres = formatearNombre(isset($input['admin_nombres']) ? $input['admin_nombres'] : '');
$admin_apellidos = formatearNombre(isset($input['admin_apellidos']) ? $input['admin_apellidos'] : '');
$admin_rut = isset($input['admin_rut']) ? trim($input['admin_rut']) : '';
$admin_telefono = isset($input['admin_telefono']) ? trim($input['admin_telefono']) : '';
$admin_correo = isset($input['admin_correo']) ? trim($input['admin_correo']) : '';
$codigo_validacion = isset($input['codigo_validacion']) ? trim($input['codigo_validacion']) : '';
$password_plain = isset($input['password']) ? $input['password'] : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar campos requeridos
if (empty($admin_nombres) || empty($admin_apellidos) || empty($admin_rut) ||
    empty($admin_correo) || empty($codigo_validacion) || empty($password_plain) || $establecimiento_id <= 0) {
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

// Función para limpiar RUT
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

// Verificar que el correo no exista
$sql_check_email = "SELECT id FROM tb_usuarios WHERE email = ?";
$stmt_check = $conn->prepare($sql_check_email);
$stmt_check->bind_param("s", $admin_correo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['message'] = 'El correo electrónico ya está registrado en el sistema.';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar que el RUT del administrador no exista
$rut_limpio = limpiarRut($admin_rut);
$sql_check_rut = "SELECT id FROM tb_administradores WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?";
$stmt_check_rut = $conn->prepare($sql_check_rut);
$stmt_check_rut->bind_param("s", $rut_limpio);
$stmt_check_rut->execute();
$result_check_rut = $stmt_check_rut->get_result();

if ($result_check_rut->num_rows > 0) {
    $response['message'] = 'El RUT del administrador ya está registrado en el sistema.';
    $stmt_check_rut->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check_rut->close();

// Verificar que el código de validación sea válido y no usado
$sql_check_codigo = "SELECT id FROM tb_codigos_validacion WHERE codigo = ? AND establecimiento_id = ? AND usado = FALSE";
$stmt_check_codigo = $conn->prepare($sql_check_codigo);
$stmt_check_codigo->bind_param("si", $codigo_validacion, $establecimiento_id);
$stmt_check_codigo->execute();
$result_codigo = $stmt_check_codigo->get_result();

if ($result_codigo->num_rows == 0) {
    $response['message'] = 'El código de validación no es válido o ya ha sido utilizado.';
    $stmt_check_codigo->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$codigo_row = $result_codigo->fetch_assoc();
$codigo_id = $codigo_row['id'];
$stmt_check_codigo->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Crear usuario
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $sql_usuario = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id) VALUES (?, ?, 'administrador', ?)";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssi", $admin_correo, $password_hash, $establecimiento_id);
    $stmt_usuario->execute();
    $usuario_id = $conn->insert_id;
    $stmt_usuario->close();

    // 2. Crear administrador
    $sql_admin = "INSERT INTO tb_administradores (usuario_id, nombres, apellidos, rut, establecimiento_id, telefono) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("isssis", $usuario_id, $admin_nombres, $admin_apellidos, $admin_rut, $establecimiento_id, $admin_telefono);
    $stmt_admin->execute();
    $admin_id = $conn->insert_id;
    $stmt_admin->close();

    // 3. Marcar código de validación como usado
    $sql_marcar_codigo = "UPDATE tb_codigos_validacion SET usado = TRUE, usado_por = ?, fecha_uso = NOW() WHERE id = ?";
    $stmt_marcar_codigo = $conn->prepare($sql_marcar_codigo);
    $stmt_marcar_codigo->bind_param("ii", $admin_id, $codigo_id);
    $stmt_marcar_codigo->execute();
    $stmt_marcar_codigo->close();

    // 4. Marcar pre-registro como usado
    $sql_marcar = "UPDATE tb_preregistro_administradores
                   SET usado = TRUE, fecha_uso = NOW()
                   WHERE establecimiento_id = ?
                   AND REPLACE(REPLACE(REPLACE(rut_admin, '.', ''), '-', ''), ' ', '') = ?";
    $stmt_marcar = $conn->prepare($sql_marcar);
    $stmt_marcar->bind_param("is", $establecimiento_id, $rut_limpio);
    $stmt_marcar->execute();
    $stmt_marcar->close();

    // Confirmar transacción
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Registro completado exitosamente';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $response['message'] = 'Error al registrar: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>

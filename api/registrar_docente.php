<?php
// ============================================================
// API: Registrar Docente (Usuario + Docente)
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

// Datos del docente
$docente_nombres = formatearNombre(isset($input['docente_nombres']) ? $input['docente_nombres'] : '');
$docente_apellidos = formatearNombre(isset($input['docente_apellidos']) ? $input['docente_apellidos'] : '');
$docente_rut = isset($input['docente_rut']) ? trim($input['docente_rut']) : '';
$docente_telefono = isset($input['docente_telefono']) ? trim($input['docente_telefono']) : '';
$docente_correo = isset($input['docente_correo']) ? trim($input['docente_correo']) : '';
$password_plain = isset($input['password']) ? $input['password'] : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar campos requeridos
if (empty($docente_nombres) || empty($docente_apellidos) || empty($docente_rut) ||
    empty($docente_correo) || empty($password_plain) || $establecimiento_id <= 0) {
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
$stmt_check->bind_param("s", $docente_correo);
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

// Verificar que el RUT del docente no exista
$rut_limpio = limpiarRut($docente_rut);
$sql_check_rut = "SELECT id FROM tb_docentes WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?";
$stmt_check_rut = $conn->prepare($sql_check_rut);
$stmt_check_rut->bind_param("s", $rut_limpio);
$stmt_check_rut->execute();
$result_check_rut = $stmt_check_rut->get_result();

if ($result_check_rut->num_rows > 0) {
    $response['message'] = 'El RUT del docente ya está registrado en el sistema.';
    $stmt_check_rut->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check_rut->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Crear usuario
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $sql_usuario = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id) VALUES (?, ?, 'docente', ?)";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssi", $docente_correo, $password_hash, $establecimiento_id);
    $stmt_usuario->execute();
    $usuario_id = $conn->insert_id;
    $stmt_usuario->close();

    // 2. Crear docente
    $sql_docente = "INSERT INTO tb_docentes (usuario_id, nombres, apellidos, rut, telefono, email, establecimiento_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_docente = $conn->prepare($sql_docente);
    $stmt_docente->bind_param("isssssi", $usuario_id, $docente_nombres, $docente_apellidos, $docente_rut, $docente_telefono, $docente_correo, $establecimiento_id);
    $stmt_docente->execute();
    $stmt_docente->close();

    // 3. Eliminar del pre-registro (ya no se necesita)
    $sql_eliminar = "DELETE FROM tb_preregistro_docentes
                     WHERE establecimiento_id = ?
                     AND REPLACE(REPLACE(REPLACE(rut_docente, '.', ''), '-', ''), ' ', '') = ?";
    $stmt_eliminar = $conn->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("is", $establecimiento_id, $rut_limpio);
    $stmt_eliminar->execute();
    $stmt_eliminar->close();

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

<?php
// ============================================================
// API: Pre-registrar Docente (Admin) - Agrega docente al pre-registro
// ============================================================

session_start();

require_once 'helper_auditoria.php';

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

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$establecimiento_id = $_SESSION['establecimiento_id'];

// Función para formatear nombres: Primera letra mayúscula de cada palabra
function formatearNombre($texto) {
    if (empty($texto)) return '';
    return mb_convert_case(mb_strtolower(trim($texto), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$nombres = formatearNombre(isset($input['nombres']) ? $input['nombres'] : '');
$apellidos = formatearNombre(isset($input['apellidos']) ? $input['apellidos'] : '');
$rut = isset($input['rut']) ? trim($input['rut']) : '';
$correo = isset($input['correo']) ? trim(strtolower($input['correo'])) : '';

// Validar campos requeridos
if (empty($nombres) || empty($apellidos) || empty($rut)) {
    $response['message'] = 'Faltan campos requeridos';
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

// Verificar si el RUT ya existe en pre-registro
$sql_check = "SELECT id FROM tb_preregistro_docentes WHERE rut_docente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $rut);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['message'] = 'El RUT ya está pre-registrado en el sistema';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar si el RUT ya existe en docentes registrados
$sql_check2 = "SELECT id FROM tb_docentes WHERE rut = ?";
$stmt_check2 = $conn->prepare($sql_check2);
$stmt_check2->bind_param("s", $rut);
$stmt_check2->execute();
$result_check2 = $stmt_check2->get_result();

if ($result_check2->num_rows > 0) {
    $response['message'] = 'El docente ya está registrado en el sistema';
    $stmt_check2->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check2->close();

// Juntar nombre y apellido
$nombre_completo = $nombres . ' ' . $apellidos;

// Insertar en pre-registro
$sql = "INSERT INTO tb_preregistro_docentes (rut_docente, nombre_docente, correo_docente, establecimiento_id)
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$correo_para_guardar = !empty($correo) ? $correo : null;
$stmt->bind_param("sssi", $rut, $nombre_completo, $correo_para_guardar, $establecimiento_id);

if ($stmt->execute()) {
    $pre_registro_id = $conn->insert_id;
    $response['success'] = true;
    $response['message'] = 'Docente pre-registrado correctamente. El docente debe completar su registro en la página de registro.';
    $response['pre_registro_id'] = $pre_registro_id;

    // Registrar en auditoría
    $descripcion_audit = "Pre-registró docente $nombre_completo (RUT: $rut)";
    $datos_nuevos = [
        'nombre' => $nombre_completo,
        'rut' => $rut,
        'correo' => $correo_para_guardar
    ];
    registrarActividad($conn, 'agregar', 'docente', $descripcion_audit, $pre_registro_id, null, $datos_nuevos);
} else {
    $response['message'] = 'Error al pre-registrar el docente: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

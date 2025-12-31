<?php
// ============================================================
// API: Editar Alumno - Modifica datos de un alumno
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

$alumno_id = isset($input['id']) ? intval($input['id']) : 0;
$nombres = formatearNombre(isset($input['nombres']) ? $input['nombres'] : '');
$apellidos = formatearNombre(isset($input['apellidos']) ? $input['apellidos'] : '');
$rut = isset($input['rut']) ? trim($input['rut']) : '';
$fecha_nacimiento = isset($input['fecha_nacimiento']) ? $input['fecha_nacimiento'] : null;
$sexo = isset($input['sexo']) ? $input['sexo'] : null;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;

// Validar campos requeridos
if (!$alumno_id || empty($nombres) || empty($apellidos) || empty($rut)) {
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

// Verificar que el alumno pertenece al establecimiento y obtener datos anteriores
$sql_check = "SELECT a.id, a.nombres, a.apellidos, a.rut, a.fecha_nacimiento, a.sexo, a.curso_id, c.nombre as curso_nombre
              FROM tb_alumnos a
              LEFT JOIN tb_cursos c ON a.curso_id = c.id
              WHERE a.id = ? AND a.establecimiento_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $alumno_id, $establecimiento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $response['message'] = 'Alumno no encontrado';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$alumno_anterior = $result_check->fetch_assoc();
$stmt_check->close();

// Verificar si el RUT ya existe en otro alumno
$sql_rut = "SELECT id FROM tb_alumnos WHERE rut = ? AND id != ?";
$stmt_rut = $conn->prepare($sql_rut);
$stmt_rut->bind_param("si", $rut, $alumno_id);
$stmt_rut->execute();
$result_rut = $stmt_rut->get_result();

if ($result_rut->num_rows > 0) {
    $response['message'] = 'El RUT ya está registrado en otro alumno';
    $stmt_rut->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_rut->close();

// Actualizar alumno
$sql = "UPDATE tb_alumnos
        SET nombres = ?, apellidos = ?, rut = ?, fecha_nacimiento = ?, sexo = ?, curso_id = ?
        WHERE id = ? AND establecimiento_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssiii", $nombres, $apellidos, $rut, $fecha_nacimiento, $sexo, $curso_id, $alumno_id, $establecimiento_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Alumno actualizado correctamente';

    // Registrar en auditoría
    $nombre_completo = $nombres . ' ' . $apellidos;
    $descripcion_audit = "Editó alumno $nombre_completo";
    $datos_anteriores = [
        'nombres' => $alumno_anterior['nombres'],
        'apellidos' => $alumno_anterior['apellidos'],
        'rut' => $alumno_anterior['rut'],
        'curso' => $alumno_anterior['curso_nombre']
    ];
    // Obtener nombre del nuevo curso
    $curso_nuevo_nombre = '';
    if ($curso_id) {
        $sql_c = "SELECT nombre FROM tb_cursos WHERE id = ?";
        $stmt_c = $conn->prepare($sql_c);
        $stmt_c->bind_param("i", $curso_id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();
        if ($row_c = $res_c->fetch_assoc()) {
            $curso_nuevo_nombre = $row_c['nombre'];
        }
        $stmt_c->close();
    }
    $datos_nuevos = [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'rut' => $rut,
        'curso' => $curso_nuevo_nombre
    ];
    registrarActividad($conn, 'editar', 'alumno', $descripcion_audit, $alumno_id, $datos_anteriores, $datos_nuevos);
} else {
    $response['message'] = 'Error al actualizar el alumno: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

<?php
// ============================================================
// API: Editar Docente - Modifica datos de un docente
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

$docente_id = isset($input['id']) ? intval($input['id']) : 0;
$nombres = formatearNombre(isset($input['nombres']) ? $input['nombres'] : '');
$apellidos = formatearNombre(isset($input['apellidos']) ? $input['apellidos'] : '');
$rut = isset($input['rut']) ? trim($input['rut']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$especialidades = isset($input['especialidades']) ? $input['especialidades'] : [];

// Validar campos requeridos
if (!$docente_id || empty($nombres) || empty($apellidos) || empty($rut)) {
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

// Verificar que el docente pertenece al establecimiento
$sql_check = "SELECT id FROM tb_docentes WHERE id = ? AND establecimiento_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $docente_id, $establecimiento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $response['message'] = 'Docente no encontrado';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar si el RUT ya existe en otro docente
$sql_rut = "SELECT id FROM tb_docentes WHERE rut = ? AND id != ?";
$stmt_rut = $conn->prepare($sql_rut);
$stmt_rut->bind_param("si", $rut, $docente_id);
$stmt_rut->execute();
$result_rut = $stmt_rut->get_result();

if ($result_rut->num_rows > 0) {
    $response['message'] = 'El RUT ya está registrado en otro docente';
    $stmt_rut->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_rut->close();

// Obtener especialidades actuales del docente
$especialidades_actuales = [];
$sql_actual = "SELECT asignatura_id FROM tb_docente_asignatura WHERE docente_id = ?";
$stmt_actual = $conn->prepare($sql_actual);
$stmt_actual->bind_param("i", $docente_id);
$stmt_actual->execute();
$result_actual = $stmt_actual->get_result();
while ($row = $result_actual->fetch_assoc()) {
    $especialidades_actuales[] = $row['asignatura_id'];
}
$stmt_actual->close();

// Identificar asignaturas que se quieren quitar
$asignaturas_a_quitar = array_diff($especialidades_actuales, $especialidades);

// Verificar si alguna asignatura a quitar tiene asignaciones activas
if (!empty($asignaturas_a_quitar)) {
    $asignaciones_bloqueantes = [];

    foreach ($asignaturas_a_quitar as $asig_id) {
        $sql_check = "SELECT a.id, c.nombre as curso_nombre, asig.nombre as asignatura_nombre
                      FROM tb_asignaciones a
                      INNER JOIN tb_cursos c ON a.curso_id = c.id
                      INNER JOIN tb_asignaturas asig ON a.asignatura_id = asig.id
                      WHERE a.docente_id = ? AND a.asignatura_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iii", $docente_id, $asig_id, $establecimiento_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        while ($row = $result_check->fetch_assoc()) {
            $asignaciones_bloqueantes[] = $row['asignatura_nombre'] . ' en ' . $row['curso_nombre'];
        }
        $stmt_check->close();
    }

    if (!empty($asignaciones_bloqueantes)) {
        $response['message'] = 'No se puede quitar la(s) asignatura(s) porque el docente las está impartiendo en: ' . implode(', ', $asignaciones_bloqueantes) . '. Primero debe eliminar esas asignaciones en la pestaña Curso/Asignaturas.';
        $conn->close();
        echo json_encode($response);
        exit;
    }
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Actualizar docente
    $sql = "UPDATE tb_docentes SET nombres = ?, apellidos = ?, rut = ?, email = ?
            WHERE id = ? AND establecimiento_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $nombres, $apellidos, $rut, $email, $docente_id, $establecimiento_id);
    $stmt->execute();
    $stmt->close();

    // Eliminar especialidades anteriores
    $sql_del = "DELETE FROM tb_docente_asignatura WHERE docente_id = ?";
    $stmt_del = $conn->prepare($sql_del);
    $stmt_del->bind_param("i", $docente_id);
    $stmt_del->execute();
    $stmt_del->close();

    // Insertar nuevas especialidades
    if (!empty($especialidades)) {
        $sql_esp = "INSERT INTO tb_docente_asignatura (docente_id, asignatura_id) VALUES (?, ?)";
        $stmt_esp = $conn->prepare($sql_esp);

        foreach ($especialidades as $asignatura_id) {
            $stmt_esp->bind_param("ii", $docente_id, $asignatura_id);
            $stmt_esp->execute();
        }
        $stmt_esp->close();
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Docente actualizado correctamente';

    // Registrar en auditoría
    $nombre_completo = $nombres . ' ' . $apellidos;
    $descripcion_audit = "Editó docente $nombre_completo";
    $datos_nuevos = [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'rut' => $rut,
        'email' => $email
    ];
    registrarActividad($conn, 'editar', 'docente', $descripcion_audit, $docente_id, null, $datos_nuevos);

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al actualizar el docente: ' . $e->getMessage();
    error_log('Error en editar_docente.php: ' . $e->getMessage());
}

$conn->close();

echo json_encode($response);
?>

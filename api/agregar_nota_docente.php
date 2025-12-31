<?php
// ============================================================
// API: Agregar Nota - Registrar nueva calificación (Docente)
// ============================================================

session_start();

require_once 'helper_auditoria.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

// Verificar que sea docente
if ($_SESSION['tipo_usuario'] !== 'docente') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Función para normalizar la nota ingresada
function normalizarNota($valor) {
    if ($valor === null || $valor === '') return null;

    // Convertir a string para procesar
    $valor = trim(strval($valor));

    // Reemplazar coma por punto
    $valor = str_replace(',', '.', $valor);

    // Si ya tiene punto decimal, convertir directamente
    if (strpos($valor, '.') !== false) {
        return floatval($valor);
    }

    // Si es un solo dígito (1-7), agregar .0
    if (strlen($valor) === 1 && is_numeric($valor)) {
        return floatval($valor . '.0');
    }

    // Si son dos dígitos sin punto (ej: 55, 67), insertar punto en medio
    if (strlen($valor) === 2 && is_numeric($valor)) {
        return floatval($valor[0] . '.' . $valor[1]);
    }

    // Caso por defecto
    return floatval($valor);
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$alumno_id = isset($input['alumno_id']) ? intval($input['alumno_id']) : null;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;
$es_pendiente = isset($input['es_pendiente']) ? (bool)$input['es_pendiente'] : false;
$nota = $es_pendiente ? null : (isset($input['nota']) ? normalizarNota($input['nota']) : null);
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : null;
$fecha_evaluacion = isset($input['fecha_evaluacion']) ? trim($input['fecha_evaluacion']) : null;
$comentario = isset($input['comentario']) ? trim($input['comentario']) : '';
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = date('Y');

// Validaciones
if (!$alumno_id || !$curso_id || !$asignatura_id || !$trimestre || !$docente_id) {
    $response['message'] = 'Faltan datos requeridos';
    echo json_encode($response);
    exit;
}

// Si no es pendiente, validar que tenga nota
if (!$es_pendiente && $nota === null) {
    $response['message'] = 'Debe ingresar una nota o marcar como pendiente';
    echo json_encode($response);
    exit;
}

// Validar rango de nota (solo si no es pendiente)
if (!$es_pendiente && ($nota < 1.0 || $nota > 7.0)) {
    $response['message'] = 'La nota debe estar entre 1.0 y 7.0';
    echo json_encode($response);
    exit;
}

// Validar trimestre
if ($trimestre < 1 || $trimestre > 3) {
    $response['message'] = 'El trimestre debe ser 1, 2 o 3';
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

// Verificar que el docente tenga asignación para este curso y asignatura
$sql_verificar = "SELECT id FROM tb_asignaciones
                  WHERE docente_id = ? AND curso_id = ? AND asignatura_id = ?
                  AND establecimiento_id = ? AND activo = TRUE";
$stmt_ver = $conn->prepare($sql_verificar);
$stmt_ver->bind_param("iiii", $docente_id, $curso_id, $asignatura_id, $establecimiento_id);
$stmt_ver->execute();
$result_ver = $stmt_ver->get_result();

if ($result_ver->num_rows === 0) {
    $response['message'] = 'No tiene asignación para este curso y asignatura';
    $stmt_ver->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_ver->close();

// Determinar el número de evaluación (siguiente número disponible)
$sql_num = "SELECT COALESCE(MAX(numero_evaluacion), 0) + 1 as siguiente
            FROM tb_notas
            WHERE alumno_id = ? AND asignatura_id = ? AND curso_id = ?
            AND trimestre = ? AND anio_academico = ? AND establecimiento_id = ?";
$stmt_num = $conn->prepare($sql_num);
$stmt_num->bind_param("iiiiii", $alumno_id, $asignatura_id, $curso_id, $trimestre, $anio_academico, $establecimiento_id);
$stmt_num->execute();
$result_num = $stmt_num->get_result();
$row_num = $result_num->fetch_assoc();
$numero_evaluacion = $row_num['siguiente'];
$stmt_num->close();

// Insertar la nota
$sql = "INSERT INTO tb_notas (alumno_id, asignatura_id, curso_id, docente_id, nota,
        tipo_evaluacion, numero_evaluacion, trimestre, anio_academico, comentario,
        fecha_evaluacion, establecimiento_id, es_pendiente)
        VALUES (?, ?, ?, ?, ?, 'Evaluación', ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$es_pendiente_int = $es_pendiente ? 1 : 0;
$stmt->bind_param("iiiidiiissii",
    $alumno_id,
    $asignatura_id,
    $curso_id,
    $docente_id,
    $nota,
    $numero_evaluacion,
    $trimestre,
    $anio_academico,
    $comentario,
    $fecha_evaluacion,
    $establecimiento_id,
    $es_pendiente_int
);

if ($stmt->execute()) {
    $nota_id = $conn->insert_id;

    // Obtener datos del alumno para la respuesta
    $sql_alumno = "SELECT a.nombres, a.apellidos, c.nombre as curso_nombre, asig.nombre as asignatura_nombre
                   FROM tb_alumnos a
                   LEFT JOIN tb_cursos c ON a.curso_id = c.id
                   LEFT JOIN tb_asignaturas asig ON asig.id = ?
                   WHERE a.id = ?";
    $stmt_alumno = $conn->prepare($sql_alumno);
    $stmt_alumno->bind_param("ii", $asignatura_id, $alumno_id);
    $stmt_alumno->execute();
    $result_alumno = $stmt_alumno->get_result();
    $datos_alumno = $result_alumno->fetch_assoc();
    $stmt_alumno->close();

    $response['success'] = true;
    $response['message'] = $es_pendiente ? 'Nota pendiente registrada correctamente' : 'Nota registrada correctamente';
    $response['data'] = [
        'id' => $nota_id,
        'alumno' => $datos_alumno['apellidos'] . ', ' . $datos_alumno['nombres'],
        'curso' => $datos_alumno['curso_nombre'],
        'asignatura' => $datos_alumno['asignatura_nombre'],
        'nota' => $es_pendiente ? 'PEND' : $nota,
        'es_pendiente' => $es_pendiente,
        'trimestre' => $trimestre,
        'numero_evaluacion' => $numero_evaluacion,
        'fecha_evaluacion' => $fecha_evaluacion
    ];

    // Registrar en auditoría
    $nombre_alumno = $datos_alumno['nombres'] . ' ' . $datos_alumno['apellidos'];
    $nota_texto = $es_pendiente ? 'PENDIENTE' : $nota;
    $descripcion_audit = "Ingresó nota $nota_texto a $nombre_alumno en " . $datos_alumno['asignatura_nombre'] . " (T$trimestre)";
    $datos_nuevos = [
        'alumno' => $nombre_alumno,
        'nota' => $nota_texto,
        'es_pendiente' => $es_pendiente,
        'asignatura' => $datos_alumno['asignatura_nombre'],
        'trimestre' => $trimestre
    ];
    registrarActividad($conn, 'agregar', 'nota', $descripcion_audit, $nota_id, null, $datos_nuevos);
} else {
    $response['message'] = 'Error al registrar la nota: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

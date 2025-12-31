<?php
// ============================================================
// API: Obtener Últimas Notas Docente - Notas registradas recientemente
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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
    'data' => []
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
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

// Obtener parámetros
$input = json_decode(file_get_contents('php://input'), true);

$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$limite = isset($input['limite']) ? intval($input['limite']) : 10;
$solo_hoy = isset($input['solo_hoy']) ? $input['solo_hoy'] : false;
$establecimiento_id = $_SESSION['establecimiento_id'];

// Validación
if (!$docente_id) {
    $response['message'] = 'ID de docente requerido';
    echo json_encode($response);
    exit;
}

// Limitar el máximo de registros
$limite = min($limite, 50);

// Verificar si existe el campo es_pendiente
$campo_pendiente = "0 as es_pendiente";
$check_col = $conn->query("SHOW COLUMNS FROM tb_notas LIKE 'es_pendiente'");
if ($check_col && $check_col->num_rows > 0) {
    $campo_pendiente = "n.es_pendiente";
}

// Construir query
$sql = "SELECT n.id, n.nota, n.trimestre, n.numero_evaluacion,
               n.fecha_registro, n.fecha_evaluacion, $campo_pendiente,
               n.curso_id, n.asignatura_id,
               a.nombres as alumno_nombres, a.apellidos as alumno_apellidos,
               c.nombre as curso_nombre,
               asig.nombre as asignatura_nombre
        FROM tb_notas n
        INNER JOIN tb_alumnos a ON n.alumno_id = a.id
        INNER JOIN tb_cursos c ON n.curso_id = c.id
        INNER JOIN tb_asignaturas asig ON n.asignatura_id = asig.id
        WHERE n.docente_id = ? AND n.establecimiento_id = ?";

if ($solo_hoy) {
    $sql .= " AND DATE(n.fecha_registro) = CURDATE()";
}

$sql .= " ORDER BY n.fecha_registro DESC LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $docente_id, $establecimiento_id, $limite);
$stmt->execute();
$result = $stmt->get_result();

$notas = [];
while ($row = $result->fetch_assoc()) {
    // Formatear fecha para mostrar
    $fecha_registro = new DateTime($row['fecha_registro']);
    $fecha_eval = $row['fecha_evaluacion'] ? new DateTime($row['fecha_evaluacion']) : null;
    $es_pendiente = isset($row['es_pendiente']) ? (bool)$row['es_pendiente'] : false;

    $notas[] = [
        'id' => $row['id'],
        'nota' => $es_pendiente ? 'PEND' : floatval($row['nota']),
        'es_pendiente' => $es_pendiente,
        'trimestre' => $row['trimestre'],
        'numero_evaluacion' => $row['numero_evaluacion'],
        'fecha_registro' => $fecha_registro->format('d/m/Y H:i'),
        'fecha_evaluacion' => $fecha_eval ? $fecha_eval->format('Y-m-d') : null,
        'fecha_evaluacion_formato' => $fecha_eval ? $fecha_eval->format('d/m/Y') : null,
        'alumno' => $row['alumno_apellidos'] . ', ' . $row['alumno_nombres'],
        'curso' => $row['curso_nombre'],
        'curso_id' => $row['curso_id'],
        'asignatura' => $row['asignatura_nombre'],
        'asignatura_id' => $row['asignatura_id'],
        // Versiones cortas para tabla responsive
        'asignatura_corta' => substr($row['asignatura_nombre'], 0, 3) . '.'
    ];
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Últimas notas obtenidas correctamente';
$response['data'] = $notas;
$response['total'] = count($notas);

echo json_encode($response);
?>

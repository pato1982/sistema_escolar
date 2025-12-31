<?php
// ============================================================
// API: Obtener Notas Docente - Buscar notas con filtros
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
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;
$alumno_id = isset($input['alumno_id']) ? intval($input['alumno_id']) : null;
$fecha = isset($input['fecha']) ? trim($input['fecha']) : null;
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Construir query
$sql = "SELECT n.id, n.nota, n.trimestre, n.numero_evaluacion, n.comentario,
               n.fecha_registro, n.fecha_evaluacion, n.es_pendiente,
               a.id as alumno_id, a.nombres as alumno_nombres, a.apellidos as alumno_apellidos,
               c.id as curso_id, c.nombre as curso_nombre,
               asig.id as asignatura_id, asig.nombre as asignatura_nombre
        FROM tb_notas n
        INNER JOIN tb_alumnos a ON n.alumno_id = a.id
        INNER JOIN tb_cursos c ON n.curso_id = c.id
        INNER JOIN tb_asignaturas asig ON n.asignatura_id = asig.id
        WHERE n.establecimiento_id = ? AND n.anio_academico = ?";

$params = [$establecimiento_id, $anio_academico];
$types = "ii";

// Filtrar por docente
if ($docente_id) {
    $sql .= " AND n.docente_id = ?";
    $params[] = $docente_id;
    $types .= "i";
}

// Filtrar por curso
if ($curso_id) {
    $sql .= " AND n.curso_id = ?";
    $params[] = $curso_id;
    $types .= "i";
}

// Filtrar por asignatura
if ($asignatura_id) {
    $sql .= " AND n.asignatura_id = ?";
    $params[] = $asignatura_id;
    $types .= "i";
}

// Filtrar por alumno (ID)
if ($alumno_id) {
    $sql .= " AND n.alumno_id = ?";
    $params[] = $alumno_id;
    $types .= "i";
}
// Filtrar por nombre de alumno (Texto) si no hay ID específico
else if (isset($input['alumno_busqueda']) && !empty($input['alumno_busqueda'])) {
    $busqueda = "%" . $input['alumno_busqueda'] . "%";
    $sql .= " AND (CONCAT(a.nombres, ' ', a.apellidos) LIKE ? OR CONCAT(a.apellidos, ' ', a.nombres) LIKE ?)";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $types .= "ss";
}

// Filtrar por fecha
if ($fecha) {
    $sql .= " AND DATE(n.fecha_evaluacion) = ?";
    $params[] = $fecha;
    $types .= "s";
}

// Filtrar por trimestre
if ($trimestre) {
    $sql .= " AND n.trimestre = ?";
    $params[] = $trimestre;
    $types .= "i";
}

$sql .= " ORDER BY n.fecha_registro DESC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$notas = [];
while ($row = $result->fetch_assoc()) {
    $es_pendiente = isset($row['es_pendiente']) ? (bool) $row['es_pendiente'] : false;
    $notas[] = [
        'id' => $row['id'],
        'nota' => $es_pendiente ? 'PEND' : floatval($row['nota']),
        'es_pendiente' => $es_pendiente,
        'trimestre' => $row['trimestre'],
        'numero_evaluacion' => $row['numero_evaluacion'],
        'comentario' => $row['comentario'],
        'fecha_registro' => $row['fecha_registro'],
        'fecha_evaluacion' => $row['fecha_evaluacion'],
        'alumno_id' => $row['alumno_id'],
        'alumno_nombre' => $row['alumno_apellidos'] . ', ' . $row['alumno_nombres'],
        'curso_id' => $row['curso_id'],
        'curso_nombre' => $row['curso_nombre'],
        'asignatura_id' => $row['asignatura_id'],
        'asignatura_nombre' => $row['asignatura_nombre']
    ];
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Notas obtenidas correctamente';
$response['data'] = $notas;
$response['total'] = count($notas);

echo json_encode($response);
?>
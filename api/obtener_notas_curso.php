<?php
// ============================================================
// API: Obtener Notas por Curso - Lista las notas de alumnos
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'alumnos' => [],
    'evaluaciones' => []
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

$establecimiento_id = $_SESSION['establecimiento_id'];

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
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : 0;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : 0;
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : null;
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

if (!$curso_id || !$asignatura_id) {
    $response['message'] = 'Debe especificar curso y asignatura';
    echo json_encode($response);
    exit;
}

// Obtener alumnos del curso
$sql_alumnos = "SELECT id, nombres, apellidos, rut
                FROM tb_alumnos
                WHERE curso_id = ? AND establecimiento_id = ? AND activo = TRUE
                ORDER BY apellidos, nombres";
$stmt = $conn->prepare($sql_alumnos);
$stmt->bind_param("ii", $curso_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $row['nombre_completo'] = $row['apellidos'] . ', ' . $row['nombres'];
    $alumnos[$row['id']] = $row;
}
$stmt->close();

// Obtener las evaluaciones únicas (para generar columnas)
$sql_eval = "SELECT DISTINCT trimestre, numero_evaluacion, tipo_evaluacion
             FROM tb_notas
             WHERE curso_id = ? AND asignatura_id = ? AND anio_academico = ? AND establecimiento_id = ?";

$params_eval = [$curso_id, $asignatura_id, $anio_academico, $establecimiento_id];
$types_eval = "iiii";

if ($trimestre) {
    $sql_eval .= " AND trimestre = ?";
    $params_eval[] = $trimestre;
    $types_eval .= "i";
}

$sql_eval .= " ORDER BY trimestre, numero_evaluacion";

$stmt = $conn->prepare($sql_eval);
$stmt->bind_param($types_eval, ...$params_eval);
$stmt->execute();
$result = $stmt->get_result();

$evaluaciones = [];
while ($row = $result->fetch_assoc()) {
    $key = "T{$row['trimestre']}_E{$row['numero_evaluacion']}";
    $evaluaciones[$key] = [
        'trimestre' => $row['trimestre'],
        'numero' => $row['numero_evaluacion'],
        'tipo' => $row['tipo_evaluacion'],
        'label' => "T{$row['trimestre']} - Eval {$row['numero_evaluacion']}"
    ];
}
$stmt->close();

// Obtener notas
$sql_notas = "SELECT n.id, n.alumno_id, n.nota, n.trimestre, n.numero_evaluacion, n.tipo_evaluacion, n.comentario, n.es_pendiente
              FROM tb_notas n
              WHERE n.curso_id = ? AND n.asignatura_id = ? AND n.anio_academico = ? AND n.establecimiento_id = ?";

$params = [$curso_id, $asignatura_id, $anio_academico, $establecimiento_id];
$types = "iiii";

if ($trimestre) {
    $sql_notas .= " AND n.trimestre = ?";
    $params[] = $trimestre;
    $types .= "i";
}

$sql_notas .= " ORDER BY n.alumno_id, n.trimestre, n.numero_evaluacion";

$stmt = $conn->prepare($sql_notas);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Organizar notas por alumno
$notas_por_alumno = [];
while ($row = $result->fetch_assoc()) {
    $alumno_id = $row['alumno_id'];
    if (!isset($notas_por_alumno[$alumno_id])) {
        $notas_por_alumno[$alumno_id] = [];
    }
    $key = "T{$row['trimestre']}_E{$row['numero_evaluacion']}";
    $es_pendiente = isset($row['es_pendiente']) ? (bool)$row['es_pendiente'] : false;
    $notas_por_alumno[$alumno_id][$key] = [
        'nota_id' => $row['id'],
        'nota' => $es_pendiente ? 'PEND' : floatval($row['nota']),
        'es_pendiente' => $es_pendiente,
        'comentario' => $row['comentario']
    ];
}
$stmt->close();

// Construir data final
$data = [];
foreach ($alumnos as $alumno_id => $alumno) {
    $alumno_data = [
        'id' => $alumno_id,
        'nombre_completo' => $alumno['nombre_completo'],
        'rut' => $alumno['rut'],
        'notas' => isset($notas_por_alumno[$alumno_id]) ? $notas_por_alumno[$alumno_id] : [],
        'promedio' => 0
    ];

    // Calcular promedio (excluyendo notas pendientes)
    if (!empty($alumno_data['notas'])) {
        $suma = 0;
        $count = 0;
        foreach ($alumno_data['notas'] as $nota_info) {
            // Solo sumar si NO es pendiente
            if (!isset($nota_info['es_pendiente']) || !$nota_info['es_pendiente']) {
                $suma += $nota_info['nota'];
                $count++;
            }
        }
        $alumno_data['promedio'] = $count > 0 ? round($suma / $count, 1) : 0;
    }

    $data[] = $alumno_data;
}

$conn->close();

$response['success'] = true;
$response['message'] = 'Notas obtenidas correctamente';
$response['data'] = $data;
$response['alumnos'] = array_values($alumnos);
$response['evaluaciones'] = array_values($evaluaciones);

echo json_encode($response);
?>

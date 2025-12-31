<?php
// ============================================================
// API: Obtener Notas Curso Completo - Tabla con todos los trimestres
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

$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Filtros opcionales
$alumno_busqueda = isset($input['alumno_busqueda']) ? trim($input['alumno_busqueda']) : '';
$nota_min = isset($input['nota_min']) ? floatval($input['nota_min']) : null;
$nota_max = isset($input['nota_max']) ? floatval($input['nota_max']) : null;

// Validaciones
if (!$curso_id || !$asignatura_id) {
    $response['message'] = 'Debe seleccionar curso y asignatura';
    echo json_encode($response);
    exit;
}

// Obtener todos los alumnos del curso
$alumno_id = isset($input['alumno_id']) ? intval($input['alumno_id']) : null;
$sql_alumnos = "SELECT id, nombres, apellidos, rut
                FROM tb_alumnos
                WHERE curso_id = ? AND establecimiento_id = ? AND activo = TRUE";

if ($alumno_id) {
    $sql_alumnos .= " AND id = ?";
} else if (!empty($alumno_busqueda)) {
    $sql_alumnos .= " AND (nombres LIKE ? OR apellidos LIKE ?)";
}

$sql_alumnos .= " ORDER BY apellidos, nombres";

$stmt_alumnos = $conn->prepare($sql_alumnos);

if ($alumno_id) {
    $stmt_alumnos->bind_param("iii", $curso_id, $establecimiento_id, $alumno_id);
} else if (!empty($alumno_busqueda)) {
    $busquedaLike = "%$alumno_busqueda%";
    $stmt_alumnos->bind_param("iiss", $curso_id, $establecimiento_id, $busquedaLike, $busquedaLike);
} else {
    $stmt_alumnos->bind_param("ii", $curso_id, $establecimiento_id);
}

$stmt_alumnos->execute();
$result_alumnos = $stmt_alumnos->get_result();

$alumnos = [];
while ($row = $result_alumnos->fetch_assoc()) {
    $alumnos[$row['id']] = [
        'id' => $row['id'],
        'nombres' => $row['nombres'],
        'apellidos' => $row['apellidos'],
        'nombre_completo' => $row['apellidos'] . ', ' . $row['nombres'],
        'rut' => $row['rut'],
        'trimestre_1' => array_fill(1, 8, null),
        'trimestre_2' => array_fill(1, 8, null),
        'trimestre_3' => array_fill(1, 8, null),
        'promedio_t1' => null,
        'promedio_t2' => null,
        'promedio_t3' => null,
        'promedio_final' => null,
        'estado' => 'Sin notas'
    ];
}
$stmt_alumnos->close();

if (empty($alumnos)) {
    $response['success'] = true;
    $response['message'] = 'No hay alumnos en este curso';
    $response['data'] = [];
    echo json_encode($response);
    exit;
}

// Obtener todas las notas de estos alumnos para la asignatura
$alumno_ids = array_keys($alumnos);
$placeholders = implode(',', array_fill(0, count($alumno_ids), '?'));

$sql_notas = "SELECT alumno_id, nota, trimestre, numero_evaluacion, es_pendiente
              FROM tb_notas
              WHERE alumno_id IN ($placeholders)
              AND asignatura_id = ?
              AND curso_id = ?
              AND anio_academico = ?
              AND establecimiento_id = ?
              ORDER BY alumno_id, trimestre, numero_evaluacion";

$stmt_notas = $conn->prepare($sql_notas);

$types = str_repeat('i', count($alumno_ids)) . 'iiii';
$params = array_merge($alumno_ids, [$asignatura_id, $curso_id, $anio_academico, $establecimiento_id]);
$stmt_notas->bind_param($types, ...$params);
$stmt_notas->execute();
$result_notas = $stmt_notas->get_result();

// Asignar notas a cada alumno
while ($row = $result_notas->fetch_assoc()) {
    $alumno_id = $row['alumno_id'];
    $trimestre = $row['trimestre'];
    $num_eval = min($row['numero_evaluacion'], 8); // Máximo 8 notas por trimestre
    $es_pendiente = isset($row['es_pendiente']) ? (bool) $row['es_pendiente'] : false;

    if (isset($alumnos[$alumno_id])) {
        // Si es pendiente, guardar 'PEND', sino el valor numérico
        $alumnos[$alumno_id]["trimestre_$trimestre"][$num_eval] = $es_pendiente ? 'PEND' : floatval($row['nota']);
    }
}
$stmt_notas->close();

// Calcular promedios (excluyendo notas pendientes 'PEND')
foreach ($alumnos as $id => &$alumno) {
    // Filtrar solo notas numéricas (excluir null y 'PEND')
    $notas_t1 = array_filter($alumno['trimestre_1'], function ($n) {
        return $n !== null && $n !== 'PEND' && is_numeric($n); });
    $notas_t2 = array_filter($alumno['trimestre_2'], function ($n) {
        return $n !== null && $n !== 'PEND' && is_numeric($n); });
    $notas_t3 = array_filter($alumno['trimestre_3'], function ($n) {
        return $n !== null && $n !== 'PEND' && is_numeric($n); });

    $alumno['promedio_t1'] = count($notas_t1) > 0 ? round(array_sum($notas_t1) / count($notas_t1), 1) : null;
    $alumno['promedio_t2'] = count($notas_t2) > 0 ? round(array_sum($notas_t2) / count($notas_t2), 1) : null;
    $alumno['promedio_t3'] = count($notas_t3) > 0 ? round(array_sum($notas_t3) / count($notas_t3), 1) : null;

    // Promedio final (promedio de los 3 trimestres)
    $promedios = array_filter([$alumno['promedio_t1'], $alumno['promedio_t2'], $alumno['promedio_t3']], function ($p) {
        return $p !== null; });
    $alumno['promedio_final'] = count($promedios) > 0 ? round(array_sum($promedios) / count($promedios), 1) : null;

    // Determinar estado
    if ($alumno['promedio_final'] !== null) {
        if ($alumno['promedio_final'] >= 4.0) {
            $alumno['estado'] = 'Aprobado';
        } else {
            $alumno['estado'] = 'Reprobado';
        }
    } else if (count($notas_t1) > 0 || count($notas_t2) > 0 || count($notas_t3) > 0) {
        $alumno['estado'] = 'En curso';
    }
}

// Aplicar filtro de nota mínima/máxima si se especificaron
if ($nota_min !== null || $nota_max !== null) {
    $alumnos = array_filter($alumnos, function ($alumno) use ($nota_min, $nota_max) {
        if ($alumno['promedio_final'] === null)
            return false;
        if ($nota_min !== null && $alumno['promedio_final'] < $nota_min)
            return false;
        if ($nota_max !== null && $alumno['promedio_final'] > $nota_max)
            return false;
        return true;
    });
}

$conn->close();

// Convertir a array indexado
$resultado = array_values($alumnos);

$response['success'] = true;
$response['message'] = 'Notas obtenidas correctamente';
$response['data'] = $resultado;
$response['total'] = count($resultado);

echo json_encode($response);
?>
<?php
// ============================================================
// API: Obtener Estadísticas Docente - KPIs y datos para gráficos
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
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : null;
$rendimiento = isset($input['rendimiento']) ? trim($input['rendimiento']) : '';
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Validaciones
if (!$curso_id || !$asignatura_id) {
    $response['message'] = 'Debe seleccionar curso y asignatura';
    echo json_encode($response);
    exit;
}

$stats = [];

// ============================================================
// 1. Total de alumnos en el curso
// ============================================================
$sql_total = "SELECT COUNT(*) as total FROM tb_alumnos
              WHERE curso_id = ? AND establecimiento_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("ii", $curso_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_alumnos'] = $result->fetch_assoc()['total'];
$stmt->close();

// ============================================================
// 2. Construir condición de trimestre
// ============================================================
$trimestre_cond = "";
$trimestre_params = [];
if ($trimestre) {
    $trimestre_cond = " AND n.trimestre = ?";
    $trimestre_params[] = $trimestre;
}

// ============================================================
// 3. Promedios por alumno (para calcular aprobados/reprobados)
// ============================================================
$sql_promedios = "SELECT a.id, a.nombres, a.apellidos, AVG(n.nota) as promedio,
                         COUNT(n.id) as total_notas,
                         SUM(CASE WHEN n.nota < 4.0 THEN 1 ELSE 0 END) as notas_rojas,
                         MIN(n.nota) as nota_minima,
                         MAX(n.nota) as nota_maxima
                  FROM tb_alumnos a
                  LEFT JOIN tb_notas n ON a.id = n.alumno_id
                      AND n.asignatura_id = ? AND n.curso_id = ?
                      AND n.anio_academico = ? AND n.establecimiento_id = ?
                      $trimestre_cond
                  WHERE a.curso_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE
                  GROUP BY a.id, a.nombres, a.apellidos
                  ORDER BY promedio DESC";

$stmt = $conn->prepare($sql_promedios);
$types = "iiiiii";
$params = [$asignatura_id, $curso_id, $anio_academico, $establecimiento_id, $curso_id, $establecimiento_id];
if ($trimestre) {
    $types .= "i";
    $params = [$asignatura_id, $curso_id, $anio_academico, $establecimiento_id, $trimestre, $curso_id, $establecimiento_id];
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$alumnos_data = [];
$aprobados = 0;
$reprobados = 0;
$suma_promedios = 0;
$count_con_notas = 0;
$nota_maxima_global = 0;
$nota_minima_global = 7;
$alumnos_atencion = [];
$top5 = [];

while ($row = $result->fetch_assoc()) {
    $promedio = $row['promedio'] ? round(floatval($row['promedio']), 1) : null;

    if ($promedio !== null) {
        $count_con_notas++;
        $suma_promedios += $promedio;

        if ($promedio >= 4.0) {
            $aprobados++;
        } else {
            $reprobados++;
        }

        if ($row['nota_maxima'] > $nota_maxima_global) {
            $nota_maxima_global = floatval($row['nota_maxima']);
        }
        if ($row['nota_minima'] < $nota_minima_global) {
            $nota_minima_global = floatval($row['nota_minima']);
        }

        // Top 5 mejores promedios
        if (count($top5) < 5) {
            $top5[] = [
                'nombre' => $row['apellidos'] . ', ' . $row['nombres'],
                'promedio' => $promedio
            ];
        }

        // Alumnos que requieren atención (promedio < 4.0 o más de 2 notas rojas)
        if ($promedio < 4.0 || $row['notas_rojas'] >= 2) {
            $tendencia = 'estable';
            $observacion = '';

            if ($promedio < 4.0) {
                $observacion = 'Promedio insuficiente';
            }
            if ($row['notas_rojas'] >= 3) {
                $tendencia = 'bajando';
                $observacion = 'Múltiples notas rojas';
            }

            $alumnos_atencion[] = [
                'nombre' => $row['apellidos'] . ', ' . $row['nombres'],
                'promedio' => $promedio,
                'notas_rojas' => intval($row['notas_rojas']),
                'tendencia' => $tendencia,
                'observacion' => $observacion
            ];
        }
    }

    $alumnos_data[] = [
        'id' => $row['id'],
        'nombre' => $row['apellidos'] . ', ' . $row['nombres'],
        'promedio' => $promedio,
        'total_notas' => intval($row['total_notas']),
        'notas_rojas' => intval($row['notas_rojas'])
    ];
}
$stmt->close();

// KPIs
$stats['aprobados'] = $aprobados;
$stats['reprobados'] = $reprobados;
$stats['promedio_curso'] = $count_con_notas > 0 ? round($suma_promedios / $count_con_notas, 1) : 0;
$stats['nota_maxima'] = $nota_maxima_global > 0 ? $nota_maxima_global : 0;
$stats['nota_minima'] = $nota_minima_global < 7 ? $nota_minima_global : 0;
$stats['porcentaje_aprobados'] = $count_con_notas > 0 ? round(($aprobados / $count_con_notas) * 100, 1) : 0;
$stats['porcentaje_reprobados'] = $count_con_notas > 0 ? round(($reprobados / $count_con_notas) * 100, 1) : 0;

// ============================================================
// 4. Distribución de notas (para gráfico de barras)
// ============================================================
$sql_dist = "SELECT
                SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
             FROM tb_notas
             WHERE asignatura_id = ? AND curso_id = ?
             AND anio_academico = ? AND establecimiento_id = ?";

if ($trimestre) {
    $sql_dist .= " AND trimestre = ?";
}

$stmt = $conn->prepare($sql_dist);
if ($trimestre) {
    $stmt->bind_param("iiiii", $asignatura_id, $curso_id, $anio_academico, $establecimiento_id, $trimestre);
} else {
    $stmt->bind_param("iiii", $asignatura_id, $curso_id, $anio_academico, $establecimiento_id);
}
$stmt->execute();
$result = $stmt->get_result();
$distribucion = $result->fetch_assoc();
$stats['distribucion'] = [
    'excelente' => intval($distribucion['excelente'] ?? 0),
    'bueno' => intval($distribucion['bueno'] ?? 0),
    'suficiente' => intval($distribucion['suficiente'] ?? 0),
    'insuficiente' => intval($distribucion['insuficiente'] ?? 0)
];
$stmt->close();

// ============================================================
// 5. Rendimiento por trimestre (para gráfico de líneas)
// ============================================================
$sql_trim = "SELECT trimestre, AVG(nota) as promedio
             FROM tb_notas
             WHERE asignatura_id = ? AND curso_id = ?
             AND anio_academico = ? AND establecimiento_id = ?
             GROUP BY trimestre
             ORDER BY trimestre";
$stmt = $conn->prepare($sql_trim);
$stmt->bind_param("iiii", $asignatura_id, $curso_id, $anio_academico, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

$trimestres = [1 => null, 2 => null, 3 => null];
while ($row = $result->fetch_assoc()) {
    $trimestres[$row['trimestre']] = round(floatval($row['promedio']), 1);
}
$stats['rendimiento_trimestral'] = $trimestres;
$stmt->close();

// ============================================================
// 6. Top 5 y alumnos que requieren atención
// ============================================================
$stats['top5'] = $top5;
$stats['alumnos_atencion'] = $alumnos_atencion;

// ============================================================
// 7. Filtro por rendimiento (si se especificó)
// ============================================================
if (!empty($rendimiento)) {
    $stats['alumnos_atencion'] = array_filter($alumnos_atencion, function($a) use ($rendimiento) {
        switch ($rendimiento) {
            case 'excelente':
                return $a['promedio'] >= 6.0;
            case 'bueno':
                return $a['promedio'] >= 5.0 && $a['promedio'] < 6.0;
            case 'suficiente':
                return $a['promedio'] >= 4.0 && $a['promedio'] < 5.0;
            case 'insuficiente':
                return $a['promedio'] < 4.0;
            default:
                return true;
        }
    });
    $stats['alumnos_atencion'] = array_values($stats['alumnos_atencion']);
}

$conn->close();

$response['success'] = true;
$response['message'] = 'Estadísticas obtenidas correctamente';
$response['data'] = $stats;

echo json_encode($response);
?>

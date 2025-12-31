<?php
// ============================================================
// API: Obtener Estadísticas - Datos para gráficos y KPIs
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
    'data' => []
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
$vista = isset($input['vista']) ? $input['vista'] : 'general';
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

$stats = [];

// ============================================================
// ESTADÍSTICAS GENERALES
// ============================================================

// Promedio general del establecimiento
$sql_prom_general = "SELECT AVG(nota) as promedio_general, COUNT(*) as total_notas
                     FROM tb_notas
                     WHERE establecimiento_id = ? AND anio_academico = ?";
$stmt = $conn->prepare($sql_prom_general);
$stmt->bind_param("ii", $establecimiento_id, $anio_academico);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['promedio_general'] = $row['promedio_general'] ? round($row['promedio_general'], 1) : 0;
$stats['total_notas'] = $row['total_notas'];
$stmt->close();

// Tasa de aprobación (nota >= 4.0)
$sql_aprobacion = "SELECT
                    COUNT(CASE WHEN promedio >= 4.0 THEN 1 END) as aprobados,
                    COUNT(*) as total
                   FROM (
                       SELECT alumno_id, AVG(nota) as promedio
                       FROM tb_notas
                       WHERE establecimiento_id = ? AND anio_academico = ?
                       GROUP BY alumno_id
                   ) as promedios";
$stmt = $conn->prepare($sql_aprobacion);
$stmt->bind_param("ii", $establecimiento_id, $anio_academico);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['aprobados'] = $row['aprobados'];
$stats['total_alumnos_con_notas'] = $row['total'];
$stats['tasa_aprobacion'] = $row['total'] > 0 ? round(($row['aprobados'] / $row['total']) * 100, 1) : 0;
$stmt->close();

// Promedios por curso
$sql_cursos = "SELECT c.id, c.nombre, AVG(n.nota) as promedio, COUNT(DISTINCT n.alumno_id) as alumnos
               FROM tb_cursos c
               LEFT JOIN tb_notas n ON c.id = n.curso_id AND n.anio_academico = ?
               WHERE c.establecimiento_id = ? AND c.activo = TRUE
               GROUP BY c.id, c.nombre
               ORDER BY promedio DESC";
$stmt = $conn->prepare($sql_cursos);
$stmt->bind_param("ii", $anio_academico, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

$cursos_stats = [];
$mejor_curso = null;
$peor_curso = null;

while ($row = $result->fetch_assoc()) {
    $row['promedio'] = $row['promedio'] ? round($row['promedio'], 1) : null;
    $cursos_stats[] = $row;

    if ($row['promedio'] !== null) {
        if ($mejor_curso === null || $row['promedio'] > $mejor_curso['promedio']) {
            $mejor_curso = $row;
        }
        if ($peor_curso === null || $row['promedio'] < $peor_curso['promedio']) {
            $peor_curso = $row;
        }
    }
}
$stmt->close();

$stats['cursos'] = $cursos_stats;
$stats['mejor_curso'] = $mejor_curso;
$stats['curso_apoyo'] = $peor_curso;

// Promedios por asignatura
$sql_asignaturas = "SELECT a.id, a.nombre, AVG(n.nota) as promedio, COUNT(n.id) as total_notas
                    FROM tb_asignaturas a
                    LEFT JOIN tb_notas n ON a.id = n.asignatura_id AND n.anio_academico = ?
                    WHERE a.establecimiento_id = ? AND a.activo = TRUE
                    GROUP BY a.id, a.nombre
                    ORDER BY promedio DESC";
$stmt = $conn->prepare($sql_asignaturas);
$stmt->bind_param("ii", $anio_academico, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();

$asignaturas_stats = [];
$mejor_asig = null;
$peor_asig = null;

while ($row = $result->fetch_assoc()) {
    $row['promedio'] = $row['promedio'] ? round($row['promedio'], 1) : null;
    $asignaturas_stats[] = $row;

    if ($row['promedio'] !== null) {
        if ($mejor_asig === null || $row['promedio'] > $mejor_asig['promedio']) {
            $mejor_asig = $row;
        }
        if ($peor_asig === null || $row['promedio'] < $peor_asig['promedio']) {
            $peor_asig = $row;
        }
    }
}
$stmt->close();

$stats['asignaturas'] = $asignaturas_stats;
$stats['mejor_asignatura'] = $mejor_asig;
$stats['asignatura_critica'] = $peor_asig;

// Distribución de notas (para gráfico de torta)
$sql_distribucion = "SELECT
                        SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                        SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                        SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                        SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
                     FROM tb_notas
                     WHERE establecimiento_id = ? AND anio_academico = ?";
$stmt = $conn->prepare($sql_distribucion);
$stmt->bind_param("ii", $establecimiento_id, $anio_academico);
$stmt->execute();
$result = $stmt->get_result();
$stats['distribucion'] = $result->fetch_assoc();
$stmt->close();

// Evolución por trimestre
$sql_trimestres = "SELECT trimestre, AVG(nota) as promedio
                   FROM tb_notas
                   WHERE establecimiento_id = ? AND anio_academico = ?
                   GROUP BY trimestre
                   ORDER BY trimestre";
$stmt = $conn->prepare($sql_trimestres);
$stmt->bind_param("ii", $establecimiento_id, $anio_academico);
$stmt->execute();
$result = $stmt->get_result();

$trimestres = [];
while ($row = $result->fetch_assoc()) {
    $trimestres[$row['trimestre']] = round($row['promedio'], 1);
}
$stats['evolucion_trimestral'] = $trimestres;
$stmt->close();

// ============================================================
// ESTADÍSTICAS POR CURSO (si se especifica)
// ============================================================
if ($vista === 'curso' && $curso_id) {
    $sql_curso = "SELECT c.nombre,
                         AVG(n.nota) as promedio,
                         COUNT(DISTINCT n.alumno_id) as total_alumnos
                  FROM tb_cursos c
                  LEFT JOIN tb_notas n ON c.id = n.curso_id AND n.anio_academico = ?
                  WHERE c.id = ? AND c.establecimiento_id = ?
                  GROUP BY c.id";
    $stmt = $conn->prepare($sql_curso);
    $stmt->bind_param("iii", $anio_academico, $curso_id, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['curso_detalle'] = $result->fetch_assoc();
    if ($stats['curso_detalle']['promedio']) {
        $stats['curso_detalle']['promedio'] = round($stats['curso_detalle']['promedio'], 1);
    }
    $stmt->close();

    // Promedios por asignatura del curso
    $sql_asig_curso = "SELECT a.nombre, AVG(n.nota) as promedio
                       FROM tb_notas n
                       INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
                       WHERE n.curso_id = ? AND n.anio_academico = ? AND n.establecimiento_id = ?
                       GROUP BY a.id, a.nombre
                       ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql_asig_curso);
    $stmt->bind_param("iii", $curso_id, $anio_academico, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $asig_curso = [];
    while ($row = $result->fetch_assoc()) {
        $row['promedio'] = round($row['promedio'], 1);
        $asig_curso[] = $row;
    }
    $stats['asignaturas_curso'] = $asig_curso;
    $stmt->close();
}

// ============================================================
// ESTADÍSTICAS POR DOCENTE (si se especifica)
// ============================================================
if ($vista === 'docente' && $docente_id) {
    $sql_docente = "SELECT d.nombres, d.apellidos,
                           AVG(n.nota) as promedio,
                           COUNT(DISTINCT n.curso_id) as cursos,
                           COUNT(DISTINCT n.asignatura_id) as asignaturas
                    FROM tb_docentes d
                    LEFT JOIN tb_notas n ON d.id = n.docente_id AND n.anio_academico = ?
                    WHERE d.id = ? AND d.establecimiento_id = ?
                    GROUP BY d.id";
    $stmt = $conn->prepare($sql_docente);
    $stmt->bind_param("iii", $anio_academico, $docente_id, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['docente_detalle'] = $result->fetch_assoc();
    if ($stats['docente_detalle'] && $stats['docente_detalle']['promedio']) {
        $stats['docente_detalle']['promedio'] = round($stats['docente_detalle']['promedio'], 1);
    }
    $stmt->close();
}

// ============================================================
// ESTADÍSTICAS POR ASIGNATURA (si se especifica)
// ============================================================
if ($vista === 'asignatura' && $asignatura_id) {
    $sql_asig = "SELECT a.nombre,
                        AVG(n.nota) as promedio,
                        COUNT(DISTINCT n.curso_id) as cursos,
                        COUNT(DISTINCT n.alumno_id) as alumnos
                 FROM tb_asignaturas a
                 LEFT JOIN tb_notas n ON a.id = n.asignatura_id AND n.anio_academico = ?
                 WHERE a.id = ? AND a.establecimiento_id = ?
                 GROUP BY a.id";
    $stmt = $conn->prepare($sql_asig);
    $stmt->bind_param("iii", $anio_academico, $asignatura_id, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['asignatura_detalle'] = $result->fetch_assoc();
    if ($stats['asignatura_detalle'] && $stats['asignatura_detalle']['promedio']) {
        $stats['asignatura_detalle']['promedio'] = round($stats['asignatura_detalle']['promedio'], 1);
    }
    $stmt->close();

    // Promedios por curso de la asignatura
    $sql_cursos_asig = "SELECT c.nombre, AVG(n.nota) as promedio
                        FROM tb_notas n
                        INNER JOIN tb_cursos c ON n.curso_id = c.id
                        WHERE n.asignatura_id = ? AND n.anio_academico = ? AND n.establecimiento_id = ?
                        GROUP BY c.id, c.nombre
                        ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql_cursos_asig);
    $stmt->bind_param("iii", $asignatura_id, $anio_academico, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cursos_asig = [];
    while ($row = $result->fetch_assoc()) {
        $row['promedio'] = round($row['promedio'], 1);
        $cursos_asig[] = $row;
    }
    $stats['cursos_asignatura'] = $cursos_asig;
    $stmt->close();
}

$conn->close();

$response['success'] = true;
$response['message'] = 'Estadísticas obtenidas correctamente';
$response['data'] = $stats;

echo json_encode($response);
?>

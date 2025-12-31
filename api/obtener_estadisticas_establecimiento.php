<?php
// ============================================================
// API: Obtener Estadísticas del Establecimiento
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

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

if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

$input = json_decode(file_get_contents('php://input'), true);

$vista = isset($input['vista']) ? $input['vista'] : 'general';
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = date('Y');

$stats = [];

// ============================================================
// ESTADÍSTICAS GENERALES
// ============================================================
if ($vista === 'general') {

    // Promedio general del establecimiento
    $sql = "SELECT AVG(nota) as promedio_general,
                   COUNT(DISTINCT alumno_id) as total_alumnos_con_notas,
                   COUNT(*) as total_notas
            FROM tb_notas
            WHERE establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['promedio_general'] = $result['promedio_general'] ? round($result['promedio_general'], 1) : 0;
    $stats['total_alumnos_con_notas'] = intval($result['total_alumnos_con_notas']);
    $stats['total_notas'] = intval($result['total_notas']);
    $stmt->close();

    // Tasa de aprobación (notas >= 4.0)
    $sql = "SELECT
                COUNT(CASE WHEN promedio >= 4.0 THEN 1 END) as aprobados,
                COUNT(*) as total
            FROM (
                SELECT alumno_id, AVG(nota) as promedio
                FROM tb_notas
                WHERE establecimiento_id = ? AND anio_academico = ?
                GROUP BY alumno_id
            ) as promedios";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aprobados'] = intval($result['aprobados']);
    $stats['total_alumnos'] = intval($result['total']);
    $stats['tasa_aprobacion'] = $result['total'] > 0 ? round(($result['aprobados'] / $result['total']) * 100, 1) : 0;
    $stmt->close();

    // Promedios por curso
    $sql = "SELECT c.id, c.nombre, AVG(n.nota) as promedio, COUNT(DISTINCT n.alumno_id) as alumnos
            FROM tb_notas n
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY c.id, c.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $cursos = [];
    while ($row = $result->fetch_assoc()) {
        $cursos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1),
            'alumnos' => intval($row['alumnos'])
        ];
    }
    $stats['cursos'] = $cursos;
    $stats['cursos_con_notas'] = count($cursos);
    $stats['mejor_curso'] = !empty($cursos) ? $cursos[0] : null;

    // Contar TODOS los cursos del establecimiento (no solo los que tienen notas)
    $sql_total_cursos = "SELECT COUNT(*) as total FROM tb_cursos WHERE establecimiento_id = ?";
    $stmt_tc = $conn->prepare($sql_total_cursos);
    $stmt_tc->bind_param("i", $establecimiento_id);
    $stmt_tc->execute();
    $result_tc = $stmt_tc->get_result()->fetch_assoc();
    $stats['total_cursos'] = intval($result_tc['total']);
    $stmt_tc->close();
    $stats['curso_apoyo'] = !empty($cursos) ? end($cursos) : null;
    $stmt->close();

    // Promedios por asignatura
    $sql = "SELECT a.id, a.nombre, AVG(n.nota) as promedio, COUNT(DISTINCT n.alumno_id) as alumnos
            FROM tb_notas n
            INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
            WHERE n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $asignaturas = [];
    while ($row = $result->fetch_assoc()) {
        $asignaturas[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1),
            'alumnos' => intval($row['alumnos'])
        ];
    }
    $stats['asignaturas'] = $asignaturas;
    $stmt->close();

    // Mejor asignatura con su curso (para vista general)
    $sql_mejor = "SELECT a.id, a.nombre as asignatura, c.nombre as curso, AVG(n.nota) as promedio
                  FROM tb_notas n
                  INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
                  INNER JOIN tb_cursos c ON n.curso_id = c.id
                  WHERE n.establecimiento_id = ? AND n.anio_academico = ?
                  GROUP BY a.id, a.nombre, c.id, c.nombre
                  ORDER BY promedio DESC
                  LIMIT 1";
    $stmt = $conn->prepare($sql_mejor);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $mejor = $result->fetch_assoc();
    $stats['mejor_asignatura'] = $mejor ? [
        'id' => $mejor['id'],
        'nombre' => $mejor['asignatura'],
        'curso' => $mejor['curso'],
        'promedio' => round($mejor['promedio'], 1)
    ] : null;
    $stmt->close();

    // Asignatura crítica con su curso (para vista general)
    $sql_critica = "SELECT a.id, a.nombre as asignatura, c.nombre as curso, AVG(n.nota) as promedio
                    FROM tb_notas n
                    INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
                    INNER JOIN tb_cursos c ON n.curso_id = c.id
                    WHERE n.establecimiento_id = ? AND n.anio_academico = ?
                    GROUP BY a.id, a.nombre, c.id, c.nombre
                    ORDER BY promedio ASC
                    LIMIT 1";
    $stmt = $conn->prepare($sql_critica);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $critica = $result->fetch_assoc();
    $stats['asignatura_critica'] = $critica ? [
        'id' => $critica['id'],
        'nombre' => $critica['asignatura'],
        'curso' => $critica['curso'],
        'promedio' => round($critica['promedio'], 1)
    ] : null;
    $stmt->close();

    // Distribución de notas
    $sql = "SELECT
                SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
            FROM tb_notas
            WHERE establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['distribucion'] = [
        'excelente' => intval($result['excelente']),
        'bueno' => intval($result['bueno']),
        'suficiente' => intval($result['suficiente']),
        'insuficiente' => intval($result['insuficiente'])
    ];
    $stmt->close();

    // Evolución trimestral
    $sql = "SELECT trimestre, AVG(nota) as promedio
            FROM tb_notas
            WHERE establecimiento_id = ? AND anio_academico = ?
            GROUP BY trimestre
            ORDER BY trimestre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion = [1 => null, 2 => null, 3 => null];
    while ($row = $result->fetch_assoc()) {
        $evolucion[$row['trimestre']] = round($row['promedio'], 1);
    }
    $stats['evolucion_trimestral'] = $evolucion;
    $stmt->close();

    // Evolución mensual (para gráfico detallado)
    // Calcula el mes basado en trimestre y numero_evaluacion
    // T1: eval 1-2=Mar, 3-4=Abr, 5+=May | T2: eval 1-2=Jun, 3-4=Jul, 5+=Ago | T3: eval 1-2=Sep, 3-4=Oct, 5+=Nov
    $sql = "SELECT
                CASE
                    WHEN trimestre = 1 AND numero_evaluacion <= 2 THEN 3
                    WHEN trimestre = 1 AND numero_evaluacion <= 4 THEN 4
                    WHEN trimestre = 1 THEN 5
                    WHEN trimestre = 2 AND numero_evaluacion <= 2 THEN 6
                    WHEN trimestre = 2 AND numero_evaluacion <= 4 THEN 7
                    WHEN trimestre = 2 THEN 8
                    WHEN trimestre = 3 AND numero_evaluacion <= 2 THEN 9
                    WHEN trimestre = 3 AND numero_evaluacion <= 4 THEN 10
                    ELSE 11
                END as mes,
                AVG(nota) as promedio
            FROM tb_notas
            WHERE establecimiento_id = ? AND anio_academico = ?
            GROUP BY mes
            ORDER BY mes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    // Inicializar los 9 meses con null
    $evolucion_mensual = [];
    for ($m = 3; $m <= 11; $m++) {
        $evolucion_mensual[$m] = null;
    }
    while ($row = $result->fetch_assoc()) {
        $evolucion_mensual[intval($row['mes'])] = round($row['promedio'], 1);
    }
    $stats['evolucion_mensual'] = $evolucion_mensual;
    $stmt->close();
}

// ============================================================
// ESTADÍSTICAS POR CURSO
// ============================================================
else if ($vista === 'curso' && $curso_id) {

    // Promedio del curso
    $sql = "SELECT AVG(nota) as promedio, COUNT(DISTINCT alumno_id) as alumnos
            FROM tb_notas
            WHERE curso_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['promedio_general'] = $result['promedio'] ? round($result['promedio'], 1) : 0;
    $stats['total_alumnos'] = intval($result['alumnos']);
    $stmt->close();

    // Tasa de aprobación del curso
    $sql = "SELECT
                COUNT(CASE WHEN promedio >= 4.0 THEN 1 END) as aprobados,
                COUNT(*) as total
            FROM (
                SELECT alumno_id, AVG(nota) as promedio
                FROM tb_notas
                WHERE curso_id = ? AND establecimiento_id = ? AND anio_academico = ?
                GROUP BY alumno_id
            ) as promedios";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aprobados'] = intval($result['aprobados']);
    $stats['tasa_aprobacion'] = $result['total'] > 0 ? round(($result['aprobados'] / $result['total']) * 100, 1) : 0;
    $stmt->close();

    // Promedios por asignatura en este curso
    $sql = "SELECT a.id, a.nombre, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
            WHERE n.curso_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $asignaturas = [];
    while ($row = $result->fetch_assoc()) {
        $asignaturas[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1)
        ];
    }
    $stats['asignaturas'] = $asignaturas;
    $stats['mejor_asignatura'] = !empty($asignaturas) ? $asignaturas[0] : null;
    $stats['asignatura_critica'] = !empty($asignaturas) ? end($asignaturas) : null;
    $stmt->close();

    // Distribución de notas del curso
    $sql = "SELECT
                SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
            FROM tb_notas
            WHERE curso_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['distribucion'] = [
        'excelente' => intval($result['excelente']),
        'bueno' => intval($result['bueno']),
        'suficiente' => intval($result['suficiente']),
        'insuficiente' => intval($result['insuficiente'])
    ];
    $stmt->close();

    // Evolución trimestral del curso
    $sql = "SELECT trimestre, AVG(nota) as promedio
            FROM tb_notas
            WHERE curso_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY trimestre
            ORDER BY trimestre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion = [1 => null, 2 => null, 3 => null];
    while ($row = $result->fetch_assoc()) {
        $evolucion[$row['trimestre']] = round($row['promedio'], 1);
    }
    $stats['evolucion_trimestral'] = $evolucion;
    $stmt->close();

    // Evolución mensual del curso (para gráfico detallado)
    $sql = "SELECT
                CASE
                    WHEN trimestre = 1 AND numero_evaluacion <= 2 THEN 3
                    WHEN trimestre = 1 AND numero_evaluacion <= 4 THEN 4
                    WHEN trimestre = 1 THEN 5
                    WHEN trimestre = 2 AND numero_evaluacion <= 2 THEN 6
                    WHEN trimestre = 2 AND numero_evaluacion <= 4 THEN 7
                    WHEN trimestre = 2 THEN 8
                    WHEN trimestre = 3 AND numero_evaluacion <= 2 THEN 9
                    WHEN trimestre = 3 AND numero_evaluacion <= 4 THEN 10
                    ELSE 11
                END as mes,
                AVG(nota) as promedio
            FROM tb_notas
            WHERE curso_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY mes
            ORDER BY mes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion_mensual = [];
    for ($m = 3; $m <= 11; $m++) {
        $evolucion_mensual[$m] = null;
    }
    while ($row = $result->fetch_assoc()) {
        $evolucion_mensual[intval($row['mes'])] = round($row['promedio'], 1);
    }
    $stats['evolucion_mensual'] = $evolucion_mensual;
    $stmt->close();

    // Top 5 alumnos del curso
    $sql = "SELECT a.id, CONCAT(a.apellidos, ', ', a.nombres) as nombre, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_alumnos a ON n.alumno_id = a.id
            WHERE n.curso_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.apellidos, a.nombres
            ORDER BY promedio DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $curso_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $top5 = [];
    while ($row = $result->fetch_assoc()) {
        $top5[] = [
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1)
        ];
    }
    $stats['top5_alumnos'] = $top5;
    $stmt->close();
}

// ============================================================
// ESTADÍSTICAS POR DOCENTE
// ============================================================
else if ($vista === 'docente' && $docente_id) {

    // Promedio de notas del docente
    $sql = "SELECT AVG(nota) as promedio, COUNT(DISTINCT alumno_id) as alumnos, COUNT(DISTINCT curso_id) as cursos
            FROM tb_notas
            WHERE docente_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['promedio_general'] = $result['promedio'] ? round($result['promedio'], 1) : 0;
    $stats['total_alumnos'] = intval($result['alumnos']);
    $stats['total_cursos'] = intval($result['cursos']);
    $stmt->close();

    // Tasa de aprobación
    $sql = "SELECT
                COUNT(CASE WHEN promedio >= 4.0 THEN 1 END) as aprobados,
                COUNT(*) as total
            FROM (
                SELECT alumno_id, AVG(nota) as promedio
                FROM tb_notas
                WHERE docente_id = ? AND establecimiento_id = ? AND anio_academico = ?
                GROUP BY alumno_id
            ) as promedios";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aprobados'] = intval($result['aprobados']);
    $stats['tasa_aprobacion'] = $result['total'] > 0 ? round(($result['aprobados'] / $result['total']) * 100, 1) : 0;
    $stmt->close();

    // Promedios por curso del docente
    $sql = "SELECT c.id, c.nombre, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.docente_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY c.id, c.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $cursos = [];
    while ($row = $result->fetch_assoc()) {
        $cursos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1)
        ];
    }
    $stats['cursos'] = $cursos;
    $stats['mejor_curso'] = !empty($cursos) ? $cursos[0] : null;
    $stats['curso_apoyo'] = !empty($cursos) ? end($cursos) : null;
    $stmt->close();

    // Distribución
    $sql = "SELECT
                SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
            FROM tb_notas
            WHERE docente_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['distribucion'] = [
        'excelente' => intval($result['excelente']),
        'bueno' => intval($result['bueno']),
        'suficiente' => intval($result['suficiente']),
        'insuficiente' => intval($result['insuficiente'])
    ];
    $stmt->close();

    // Evolución trimestral
    $sql = "SELECT trimestre, AVG(nota) as promedio
            FROM tb_notas
            WHERE docente_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY trimestre
            ORDER BY trimestre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion = [1 => null, 2 => null, 3 => null];
    while ($row = $result->fetch_assoc()) {
        $evolucion[$row['trimestre']] = round($row['promedio'], 1);
    }
    $stats['evolucion_trimestral'] = $evolucion;
    $stmt->close();

    // Evolución mensual del docente (para gráfico detallado)
    $sql = "SELECT
                CASE
                    WHEN trimestre = 1 AND numero_evaluacion <= 2 THEN 3
                    WHEN trimestre = 1 AND numero_evaluacion <= 4 THEN 4
                    WHEN trimestre = 1 THEN 5
                    WHEN trimestre = 2 AND numero_evaluacion <= 2 THEN 6
                    WHEN trimestre = 2 AND numero_evaluacion <= 4 THEN 7
                    WHEN trimestre = 2 THEN 8
                    WHEN trimestre = 3 AND numero_evaluacion <= 2 THEN 9
                    WHEN trimestre = 3 AND numero_evaluacion <= 4 THEN 10
                    ELSE 11
                END as mes,
                AVG(nota) as promedio
            FROM tb_notas
            WHERE docente_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY mes
            ORDER BY mes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion_mensual = [];
    for ($m = 3; $m <= 11; $m++) {
        $evolucion_mensual[$m] = null;
    }
    while ($row = $result->fetch_assoc()) {
        $evolucion_mensual[intval($row['mes'])] = round($row['promedio'], 1);
    }
    $stats['evolucion_mensual'] = $evolucion_mensual;
    $stmt->close();

    // Mejor asignatura del docente (con curso asociado)
    $sql = "SELECT a.id, a.nombre as asignatura, c.nombre as curso, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.docente_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.nombre, c.id, c.nombre
            ORDER BY promedio DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $mejor = $result->fetch_assoc();
    $stats['mejor_asignatura'] = $mejor ? [
        'id' => $mejor['id'],
        'nombre' => $mejor['asignatura'],
        'curso' => $mejor['curso'],
        'promedio' => round($mejor['promedio'], 1)
    ] : null;
    $stmt->close();

    // Asignatura crítica del docente (con curso asociado)
    $sql = "SELECT a.id, a.nombre as asignatura, c.nombre as curso, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.docente_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.nombre, c.id, c.nombre
            ORDER BY promedio ASC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $critica = $result->fetch_assoc();
    $stats['asignatura_critica'] = $critica ? [
        'id' => $critica['id'],
        'nombre' => $critica['asignatura'],
        'curso' => $critica['curso'],
        'promedio' => round($critica['promedio'], 1)
    ] : null;
    $stmt->close();

    // Lista de asignaturas que imparte el docente (para el gráfico benchmark)
    $sql = "SELECT a.id, a.nombre, c.nombre as curso, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.docente_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY a.id, a.nombre, c.id, c.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $docente_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $asignaturas = [];
    while ($row = $result->fetch_assoc()) {
        $asignaturas[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'] . ' (' . $row['curso'] . ')',
            'promedio' => round($row['promedio'], 1)
        ];
    }
    $stats['asignaturas'] = $asignaturas;
    $stmt->close();
}

// ============================================================
// ESTADÍSTICAS POR ASIGNATURA
// ============================================================
else if ($vista === 'asignatura' && $asignatura_id) {

    // Promedio de la asignatura
    $sql = "SELECT AVG(nota) as promedio, COUNT(DISTINCT alumno_id) as alumnos
            FROM tb_notas
            WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['promedio_general'] = $result['promedio'] ? round($result['promedio'], 1) : 0;
    $stats['total_alumnos'] = intval($result['alumnos']);
    $stmt->close();

    // Tasa de aprobación
    $sql = "SELECT
                COUNT(CASE WHEN promedio >= 4.0 THEN 1 END) as aprobados,
                COUNT(*) as total
            FROM (
                SELECT alumno_id, AVG(nota) as promedio
                FROM tb_notas
                WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?
                GROUP BY alumno_id
            ) as promedios";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aprobados'] = intval($result['aprobados']);
    $stats['tasa_aprobacion'] = $result['total'] > 0 ? round(($result['aprobados'] / $result['total']) * 100, 1) : 0;
    $stmt->close();

    // Alumnos con promedio >= 6 en esta asignatura
    $sql = "SELECT
                COUNT(CASE WHEN promedio >= 6.0 THEN 1 END) as sobre_6,
                COUNT(CASE WHEN promedio < 5.0 THEN 1 END) as bajo_5,
                COUNT(*) as total
            FROM (
                SELECT alumno_id, AVG(nota) as promedio
                FROM tb_notas
                WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?
                GROUP BY alumno_id
            ) as promedios";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_alumnos_asig = intval($result['total']);
    $alumnos_sobre_6 = intval($result['sobre_6']);
    $alumnos_bajo_5 = intval($result['bajo_5']);
    $stats['alumnos_sobre_6'] = $alumnos_sobre_6;
    $stats['alumnos_bajo_5'] = $alumnos_bajo_5;
    $stats['total_alumnos_asignatura'] = $total_alumnos_asig;
    $stats['porcentaje_sobre_6'] = $total_alumnos_asig > 0 ? round(($alumnos_sobre_6 / $total_alumnos_asig) * 100, 1) : 0;
    $stats['porcentaje_bajo_5'] = $total_alumnos_asig > 0 ? round(($alumnos_bajo_5 / $total_alumnos_asig) * 100, 1) : 0;
    $stmt->close();

    // Promedios por curso en esta asignatura
    $sql = "SELECT c.id, c.nombre, AVG(n.nota) as promedio
            FROM tb_notas n
            INNER JOIN tb_cursos c ON n.curso_id = c.id
            WHERE n.asignatura_id = ? AND n.establecimiento_id = ? AND n.anio_academico = ?
            GROUP BY c.id, c.nombre
            ORDER BY promedio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $cursos = [];
    while ($row = $result->fetch_assoc()) {
        $cursos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'promedio' => round($row['promedio'], 1)
        ];
    }
    $stats['cursos'] = $cursos;
    $stats['mejor_curso'] = !empty($cursos) ? $cursos[0] : null;
    $stats['curso_apoyo'] = !empty($cursos) ? end($cursos) : null;
    $stmt->close();

    // Distribución
    $sql = "SELECT
                SUM(CASE WHEN nota >= 6.0 THEN 1 ELSE 0 END) as excelente,
                SUM(CASE WHEN nota >= 5.0 AND nota < 6.0 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN nota >= 4.0 AND nota < 5.0 THEN 1 ELSE 0 END) as suficiente,
                SUM(CASE WHEN nota < 4.0 THEN 1 ELSE 0 END) as insuficiente
            FROM tb_notas
            WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['distribucion'] = [
        'excelente' => intval($result['excelente']),
        'bueno' => intval($result['bueno']),
        'suficiente' => intval($result['suficiente']),
        'insuficiente' => intval($result['insuficiente'])
    ];
    $stmt->close();

    // Evolución trimestral
    $sql = "SELECT trimestre, AVG(nota) as promedio
            FROM tb_notas
            WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY trimestre
            ORDER BY trimestre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion = [1 => null, 2 => null, 3 => null];
    while ($row = $result->fetch_assoc()) {
        $evolucion[$row['trimestre']] = round($row['promedio'], 1);
    }
    $stats['evolucion_trimestral'] = $evolucion;
    $stmt->close();

    // Evolución mensual de la asignatura (para gráfico detallado)
    $sql = "SELECT
                CASE
                    WHEN trimestre = 1 AND numero_evaluacion <= 2 THEN 3
                    WHEN trimestre = 1 AND numero_evaluacion <= 4 THEN 4
                    WHEN trimestre = 1 THEN 5
                    WHEN trimestre = 2 AND numero_evaluacion <= 2 THEN 6
                    WHEN trimestre = 2 AND numero_evaluacion <= 4 THEN 7
                    WHEN trimestre = 2 THEN 8
                    WHEN trimestre = 3 AND numero_evaluacion <= 2 THEN 9
                    WHEN trimestre = 3 AND numero_evaluacion <= 4 THEN 10
                    ELSE 11
                END as mes,
                AVG(nota) as promedio
            FROM tb_notas
            WHERE asignatura_id = ? AND establecimiento_id = ? AND anio_academico = ?
            GROUP BY mes
            ORDER BY mes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $asignatura_id, $establecimiento_id, $anio_academico);
    $stmt->execute();
    $result = $stmt->get_result();
    $evolucion_mensual = [];
    for ($m = 3; $m <= 11; $m++) {
        $evolucion_mensual[$m] = null;
    }
    while ($row = $result->fetch_assoc()) {
        $evolucion_mensual[intval($row['mes'])] = round($row['promedio'], 1);
    }
    $stats['evolucion_mensual'] = $evolucion_mensual;
    $stmt->close();
}

$conn->close();

$response['success'] = true;
$response['message'] = 'Estadísticas obtenidas correctamente';
$response['data'] = $stats;
$response['vista'] = $vista;

echo json_encode($response);
?>

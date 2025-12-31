<?php
// ============================================================
// API: Obtener Alumnos por Curso - Para autocompletado en docente
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
$busqueda = isset($input['busqueda']) ? trim($input['busqueda']) : '';
$establecimiento_id = $_SESSION['establecimiento_id'];

// Si hay curso_id y asignatura_id, filtrar alumnos que tengan esa asignatura asignada
if ($curso_id && $asignatura_id) {
    // Obtener alumnos del curso que tengan la asignatura (a través de las asignaciones)
    $sql = "SELECT DISTINCT a.id, a.nombres, a.apellidos, a.rut
            FROM tb_alumnos a
            WHERE a.curso_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE";

    if (!empty($busqueda)) {
        $sql .= " AND (a.nombres LIKE ? OR a.apellidos LIKE ? OR a.rut LIKE ?)";
    }

    $sql .= " ORDER BY a.apellidos, a.nombres LIMIT 50";

    $stmt = $conn->prepare($sql);

    if (!empty($busqueda)) {
        $busquedaLike = "%$busqueda%";
        $stmt->bind_param("iisss", $curso_id, $establecimiento_id, $busquedaLike, $busquedaLike, $busquedaLike);
    } else {
        $stmt->bind_param("ii", $curso_id, $establecimiento_id);
    }
} else if ($curso_id) {
    $sql = "SELECT id, nombres, apellidos, rut
            FROM tb_alumnos
            WHERE curso_id = ? AND establecimiento_id = ? AND activo = TRUE";

    if (!empty($busqueda)) {
        $sql .= " AND (nombres LIKE ? OR apellidos LIKE ? OR rut LIKE ?)";
    }

    $sql .= " ORDER BY apellidos, nombres LIMIT 50";

    $stmt = $conn->prepare($sql);

    if (!empty($busqueda)) {
        $busquedaLike = "%$busqueda%";
        $stmt->bind_param("iisss", $curso_id, $establecimiento_id, $busquedaLike, $busquedaLike, $busquedaLike);
    } else {
        $stmt->bind_param("ii", $curso_id, $establecimiento_id);
    }
} else {
    $sql = "SELECT a.id, a.nombres, a.apellidos, a.rut, c.nombre as curso_nombre
            FROM tb_alumnos a
            LEFT JOIN tb_cursos c ON a.curso_id = c.id
            WHERE a.establecimiento_id = ? AND a.activo = TRUE";

    if (!empty($busqueda)) {
        $sql .= " AND (a.nombres LIKE ? OR a.apellidos LIKE ? OR a.rut LIKE ?)";
    }

    $sql .= " ORDER BY a.apellidos, a.nombres LIMIT 50";

    $stmt = $conn->prepare($sql);

    if (!empty($busqueda)) {
        $busquedaLike = "%$busqueda%";
        $stmt->bind_param("isss", $establecimiento_id, $busquedaLike, $busquedaLike, $busquedaLike);
    } else {
        $stmt->bind_param("i", $establecimiento_id);
    }
}

$stmt->execute();
$result = $stmt->get_result();

// Función para capitalizar: primera letra mayúscula, resto minúscula
function capitalizarNombre($texto) {
    if (empty($texto)) return '';
    return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    // Capitalizar nombres y apellidos
    $nombresCapitalizados = capitalizarNombre($row['nombres']);
    $apellidosCapitalizados = capitalizarNombre($row['apellidos']);

    $alumnos[] = [
        'id' => $row['id'],
        'nombres' => $nombresCapitalizados,
        'apellidos' => $apellidosCapitalizados,
        'rut' => $row['rut'],
        'nombre_completo' => $apellidosCapitalizados . ', ' . $nombresCapitalizados,
        'curso' => isset($row['curso_nombre']) ? $row['curso_nombre'] : null
    ];
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Alumnos obtenidos correctamente';
$response['data'] = $alumnos;

echo json_encode($response);
?>

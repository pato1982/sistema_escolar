<?php
// ============================================================
// API: Obtener Comunicados - Lista los comunicados
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
$tipo_usuario = $_SESSION['tipo_usuario'];
$usuario_id = $_SESSION['usuario_id'];

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
$tipo = isset($input['tipo']) ? $input['tipo'] : null;
$limite = isset($input['limite']) ? intval($input['limite']) : 50;

// Si es apoderado, obtener los cursos de sus alumnos
$cursos_apoderado = [];
if ($tipo_usuario === 'apoderado') {
    $sql_cursos = "SELECT DISTINCT al.curso_id
                   FROM tb_apoderados ap
                   INNER JOIN tb_apoderado_alumno aa ON ap.id = aa.apoderado_id
                   INNER JOIN tb_alumnos al ON aa.alumno_id = al.id
                   WHERE ap.usuario_id = ? AND al.activo = TRUE";
    $stmt = $conn->prepare($sql_cursos);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cursos_apoderado[] = $row['curso_id'];
    }
    $stmt->close();
}

// Construir consulta base
if ($tipo_usuario === 'apoderado') {
    // Apoderados ven comunicados de sus cursos o para todos
    if (empty($cursos_apoderado)) {
        $response['success'] = true;
        $response['message'] = 'No hay comunicados disponibles';
        $response['data'] = [];
        echo json_encode($response);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($cursos_apoderado), '?'));
    $sql = "SELECT DISTINCT c.id, c.titulo, c.mensaje, c.tipo, c.fecha_envio, c.para_todos_cursos,
                   u.email as remitente_email,
                   COALESCE(adm.nombres, doc.nombres) as remitente_nombres,
                   COALESCE(adm.apellidos, doc.apellidos) as remitente_apellidos
            FROM tb_comunicados c
            INNER JOIN tb_usuarios u ON c.remitente_id = u.id
            LEFT JOIN tb_administradores adm ON u.id = adm.usuario_id
            LEFT JOIN tb_docentes doc ON u.id = doc.usuario_id
            LEFT JOIN tb_comunicado_curso cc ON c.id = cc.comunicado_id
            WHERE c.establecimiento_id = ? AND c.activo = TRUE
            AND (c.para_todos_cursos = TRUE OR cc.curso_id IN ($placeholders))";

    $params = array_merge([$establecimiento_id], $cursos_apoderado);
    $types = "i" . str_repeat("i", count($cursos_apoderado));
} else {
    // Administradores y docentes ven todos los comunicados
    $sql = "SELECT c.id, c.titulo, c.mensaje, c.tipo, c.fecha_envio, c.para_todos_cursos,
                   u.email as remitente_email,
                   COALESCE(adm.nombres, doc.nombres) as remitente_nombres,
                   COALESCE(adm.apellidos, doc.apellidos) as remitente_apellidos
            FROM tb_comunicados c
            INNER JOIN tb_usuarios u ON c.remitente_id = u.id
            LEFT JOIN tb_administradores adm ON u.id = adm.usuario_id
            LEFT JOIN tb_docentes doc ON u.id = doc.usuario_id
            WHERE c.establecimiento_id = ? AND c.activo = TRUE";

    $params = [$establecimiento_id];
    $types = "i";
}

// Filtro por tipo
if ($tipo) {
    $sql .= " AND c.tipo = ?";
    $params[] = $tipo;
    $types .= "s";
}

$sql .= " ORDER BY c.fecha_envio DESC LIMIT ?";
$params[] = $limite;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$comunicados = [];
while ($row = $result->fetch_assoc()) {
    $row['remitente_nombre'] = $row['remitente_nombres'] . ' ' . $row['remitente_apellidos'];

    // Obtener cursos destinatarios
    $sql_cursos = "SELECT cu.id, cu.nombre
                   FROM tb_comunicado_curso cc
                   INNER JOIN tb_cursos cu ON cc.curso_id = cu.id
                   WHERE cc.comunicado_id = ?";
    $stmt_cursos = $conn->prepare($sql_cursos);
    $stmt_cursos->bind_param("i", $row['id']);
    $stmt_cursos->execute();
    $result_cursos = $stmt_cursos->get_result();

    $cursos_dest = [];
    while ($curso = $result_cursos->fetch_assoc()) {
        $cursos_dest[] = $curso;
    }
    $stmt_cursos->close();

    $row['cursos'] = $cursos_dest;
    $comunicados[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Comunicados obtenidos correctamente';
$response['data'] = $comunicados;

echo json_encode($response);
?>

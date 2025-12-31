<?php
// ============================================================
// API: Obtener Docentes - Lista los docentes del establecimiento
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
$busqueda = isset($input['busqueda']) ? trim($input['busqueda']) : '';
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : null;

// Construir consulta base
$sql = "SELECT DISTINCT d.id, d.nombres, d.apellidos, d.rut, d.email, d.telefono
        FROM tb_docentes d
        LEFT JOIN tb_docente_asignatura da ON d.id = da.docente_id
        WHERE d.establecimiento_id = ? AND d.activo = TRUE";

$params = [$establecimiento_id];
$types = "i";

if ($asignatura_id) {
    $sql .= " AND da.asignatura_id = ?";
    $params[] = $asignatura_id;
    $types .= "i";
}

if ($busqueda) {
    $sql .= " AND (d.nombres LIKE ? OR d.apellidos LIKE ? OR d.rut LIKE ?)";
    $busqueda_like = "%$busqueda%";
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $types .= "sss";
}

$sql .= " ORDER BY d.apellidos, d.nombres";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$docentes = [];
while ($row = $result->fetch_assoc()) {
    // Obtener especialidades del docente
    $sql_esp = "SELECT a.id, a.nombre
                FROM tb_asignaturas a
                INNER JOIN tb_docente_asignatura da ON a.id = da.asignatura_id
                WHERE da.docente_id = ?";
    $stmt_esp = $conn->prepare($sql_esp);
    $stmt_esp->bind_param("i", $row['id']);
    $stmt_esp->execute();
    $result_esp = $stmt_esp->get_result();

    $especialidades = [];
    while ($esp = $result_esp->fetch_assoc()) {
        $especialidades[] = $esp;
    }
    $stmt_esp->close();

    $row['especialidades'] = $especialidades;
    $docentes[] = $row;
}

$stmt->close();
$conn->close();

$response['success'] = true;
$response['message'] = 'Docentes obtenidos correctamente';
$response['data'] = $docentes;

echo json_encode($response);
?>

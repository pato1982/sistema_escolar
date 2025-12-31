<?php
// ============================================================
// API: Agregar Asignación - Asigna docente a curso/asignatura
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
    'asignaciones_creadas' => 0
];

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : 0;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : 0;
$asignaturas = isset($input['asignaturas']) ? $input['asignaturas'] : [];
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Validar campos requeridos
if (!$docente_id || !$curso_id || empty($asignaturas)) {
    $response['message'] = 'Faltan campos requeridos';
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

// Verificar que el docente pertenece al establecimiento
$sql_check_doc = "SELECT id FROM tb_docentes WHERE id = ? AND establecimiento_id = ? AND activo = TRUE";
$stmt_check = $conn->prepare($sql_check_doc);
$stmt_check->bind_param("ii", $docente_id, $establecimiento_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    $response['message'] = 'Docente no válido';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar que el curso pertenece al establecimiento
$sql_check_curso = "SELECT id FROM tb_cursos WHERE id = ? AND establecimiento_id = ? AND activo = TRUE";
$stmt_check = $conn->prepare($sql_check_curso);
$stmt_check->bind_param("ii", $curso_id, $establecimiento_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    $response['message'] = 'Curso no válido';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar asignaciones existentes y crear nuevas
$asignaciones_creadas = 0;
$asignaciones_mismo_docente = [];
$asignaciones_otro_docente = [];
$ids_insertados = [];

foreach ($asignaturas as $asignatura_id) {
    $asignatura_id = intval($asignatura_id);

    // Verificar si ya existe esta asignación en el curso (con cualquier docente)
    $sql_check = "SELECT a.id, a.docente_id, asig.nombre as asignatura_nombre,
                         CONCAT(d.nombres, ' ', d.apellidos) as nombre_docente
                  FROM tb_asignaciones a
                  JOIN tb_asignaturas asig ON a.asignatura_id = asig.id
                  JOIN tb_docentes d ON a.docente_id = d.id
                  WHERE a.curso_id = ? AND a.asignatura_id = ? AND a.activo = TRUE";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $curso_id, $asignatura_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $row = $result_check->fetch_assoc();
        if ($row['docente_id'] == $docente_id) {
            // El mismo docente ya tiene esta asignatura
            $asignaciones_mismo_docente[] = $row['asignatura_nombre'];
        } else {
            // Otro docente ya tiene esta asignatura
            $asignaciones_otro_docente[] = $row['asignatura_nombre'] . ' (asignada a ' . $row['nombre_docente'] . ')';
        }
        $stmt_check->close();
        continue;
    }
    $stmt_check->close();

    // Insertar nueva asignación
    $sql = "INSERT INTO tb_asignaciones (docente_id, curso_id, asignatura_id, anio_academico, establecimiento_id, activo)
            VALUES (?, ?, ?, ?, ?, TRUE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $docente_id, $curso_id, $asignatura_id, $anio_academico, $establecimiento_id);
    if ($stmt->execute()) {
        $asignaciones_creadas++;
        $ids_insertados[] = [
            'id' => $conn->insert_id,
            'asignatura_id' => $asignatura_id
        ];
    }
    $stmt->close();
}

$conn->close();

// Construir mensaje de respuesta
$mensajes_error = [];

if (count($asignaciones_otro_docente) > 0) {
    $mensajes_error[] = 'Ya asignada(s) a otro docente: ' . implode(', ', $asignaciones_otro_docente);
}

if (count($asignaciones_mismo_docente) > 0) {
    $mensajes_error[] = 'Ya asignada(s) a este docente: ' . implode(', ', $asignaciones_mismo_docente);
}

if ($asignaciones_creadas > 0 && count($mensajes_error) > 0) {
    // Algunas se crearon, otras ya existían
    $response['success'] = true;
    $response['message'] = "Se crearon $asignaciones_creadas asignación(es). " . implode('. ', $mensajes_error);
    $response['asignaciones_creadas'] = $asignaciones_creadas;
    $response['ids_insertados'] = $ids_insertados;
} else if ($asignaciones_creadas > 0) {
    // Todas se crearon correctamente
    $response['success'] = true;
    $response['message'] = "Se crearon $asignaciones_creadas asignación(es) correctamente";
    $response['asignaciones_creadas'] = $asignaciones_creadas;
    $response['ids_insertados'] = $ids_insertados;
} else if (count($mensajes_error) > 0) {
    // Ninguna se creó, todas ya existían
    $response['success'] = false;
    $response['message'] = implode('. ', $mensajes_error);
} else {
    $response['message'] = 'No se pudo crear ninguna asignación';
}

echo json_encode($response);
?>

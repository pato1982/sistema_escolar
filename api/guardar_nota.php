<?php
// ============================================================
// API: Guardar Nota - Crea o actualiza una nota
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
    'message' => ''
];

// Verificar sesión y permisos (admin o docente)
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo_usuario'], ['administrador', 'docente'])) {
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
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$nota_id = isset($input['nota_id']) ? intval($input['nota_id']) : null;
$alumno_id = isset($input['alumno_id']) ? intval($input['alumno_id']) : 0;
$asignatura_id = isset($input['asignatura_id']) ? intval($input['asignatura_id']) : 0;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : 0;
$nota = isset($input['nota']) ? floatval($input['nota']) : 0;
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : 0;
$numero_evaluacion = isset($input['numero_evaluacion']) ? intval($input['numero_evaluacion']) : 1;
$tipo_evaluacion = isset($input['tipo_evaluacion']) ? trim($input['tipo_evaluacion']) : 'Evaluación';
$comentario = isset($input['comentario']) ? trim($input['comentario']) : null;
$anio_academico = isset($input['anio_academico']) ? intval($input['anio_academico']) : date('Y');

// Validar campos requeridos
if (!$alumno_id || !$asignatura_id || !$curso_id || !$trimestre) {
    $response['message'] = 'Faltan campos requeridos';
    echo json_encode($response);
    exit;
}

// Validar nota (escala chilena 1.0 a 7.0)
if ($nota < 1.0 || $nota > 7.0) {
    $response['message'] = 'La nota debe estar entre 1.0 y 7.0';
    echo json_encode($response);
    exit;
}

// Validar trimestre
if ($trimestre < 1 || $trimestre > 3) {
    $response['message'] = 'El trimestre debe estar entre 1 y 3';
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

// Obtener docente_id si es docente
$docente_id = null;
if ($_SESSION['tipo_usuario'] === 'docente') {
    $sql_doc = "SELECT id FROM tb_docentes WHERE usuario_id = ?";
    $stmt_doc = $conn->prepare($sql_doc);
    $stmt_doc->bind_param("i", $usuario_id);
    $stmt_doc->execute();
    $result_doc = $stmt_doc->get_result();
    if ($row_doc = $result_doc->fetch_assoc()) {
        $docente_id = $row_doc['id'];
    }
    $stmt_doc->close();
}

// Si existe nota_id, actualizar
if ($nota_id) {
    $sql = "UPDATE tb_notas
            SET nota = ?, tipo_evaluacion = ?, comentario = ?
            WHERE id = ? AND establecimiento_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dssii", $nota, $tipo_evaluacion, $comentario, $nota_id, $establecimiento_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Nota actualizada correctamente';
        $response['nota_id'] = $nota_id;
    } else {
        $response['message'] = 'Error al actualizar la nota: ' . $stmt->error;
    }
    $stmt->close();
} else {
    // Verificar si ya existe una nota para ese alumno/asignatura/trimestre/evaluación
    $sql_check = "SELECT id FROM tb_notas
                  WHERE alumno_id = ? AND asignatura_id = ? AND curso_id = ?
                  AND trimestre = ? AND numero_evaluacion = ? AND anio_academico = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iiiiii", $alumno_id, $asignatura_id, $curso_id, $trimestre, $numero_evaluacion, $anio_academico);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Actualizar nota existente
        $existing = $result_check->fetch_assoc();
        $nota_id = $existing['id'];

        $sql = "UPDATE tb_notas
                SET nota = ?, tipo_evaluacion = ?, comentario = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dssi", $nota, $tipo_evaluacion, $comentario, $nota_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Nota actualizada correctamente';
            $response['nota_id'] = $nota_id;
        } else {
            $response['message'] = 'Error al actualizar la nota: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insertar nueva nota
        $sql = "INSERT INTO tb_notas (alumno_id, asignatura_id, curso_id, docente_id, nota,
                tipo_evaluacion, numero_evaluacion, trimestre, anio_academico, comentario, establecimiento_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiidsiiisi", $alumno_id, $asignatura_id, $curso_id, $docente_id, $nota,
                          $tipo_evaluacion, $numero_evaluacion, $trimestre, $anio_academico, $comentario, $establecimiento_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Nota guardada correctamente';
            $response['nota_id'] = $conn->insert_id;
        } else {
            $response['message'] = 'Error al guardar la nota: ' . $stmt->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
}

$conn->close();

echo json_encode($response);
?>

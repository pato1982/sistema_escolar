<?php
// ============================================================
// API: Enviar Comunicado - Crea y envía un comunicado
// ============================================================

session_start();

require_once 'helper_auditoria.php';

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

$titulo = isset($input['titulo']) ? trim($input['titulo']) : null;
$mensaje = isset($input['mensaje']) ? trim($input['mensaje']) : '';
$tipo = isset($input['tipo']) ? $input['tipo'] : 'informativo';
$para_todos = isset($input['para_todos']) ? (bool)$input['para_todos'] : false;
$cursos = isset($input['cursos']) ? $input['cursos'] : [];

// Validar campos requeridos
if (empty($mensaje)) {
    $response['message'] = 'El mensaje es requerido';
    echo json_encode($response);
    exit;
}

if (!$para_todos && empty($cursos)) {
    $response['message'] = 'Debe seleccionar al menos un curso o enviar a todos';
    echo json_encode($response);
    exit;
}

// Validar tipo
$tipos_validos = ['informativo', 'urgente', 'reunion', 'evento'];
if (!in_array($tipo, $tipos_validos)) {
    $response['message'] = 'Tipo de comunicado no válido';
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

// Iniciar transacción
$conn->begin_transaction();

try {
    // Insertar comunicado
    $sql = "INSERT INTO tb_comunicados (titulo, mensaje, tipo, remitente_id, establecimiento_id, para_todos_cursos)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiii", $titulo, $mensaje, $tipo, $usuario_id, $establecimiento_id, $para_todos);
    $stmt->execute();
    $comunicado_id = $conn->insert_id;
    $stmt->close();

    // Si no es para todos, insertar relaciones con cursos
    if (!$para_todos && !empty($cursos)) {
        $sql_curso = "INSERT INTO tb_comunicado_curso (comunicado_id, curso_id) VALUES (?, ?)";
        $stmt_curso = $conn->prepare($sql_curso);

        foreach ($cursos as $curso_id) {
            $curso_id = intval($curso_id);
            $stmt_curso->bind_param("ii", $comunicado_id, $curso_id);
            $stmt_curso->execute();
        }
        $stmt_curso->close();
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Comunicado enviado correctamente';
    $response['comunicado_id'] = $comunicado_id;

    // Registrar en auditoría
    $destino = $para_todos ? 'todos los cursos' : count($cursos) . ' curso(s)';
    $titulo_corto = $titulo ? substr($titulo, 0, 50) : 'Sin título';
    $descripcion_audit = "Envió comunicado '$titulo_corto' a $destino";
    $datos_nuevos = [
        'titulo' => $titulo,
        'tipo' => $tipo,
        'para_todos' => $para_todos,
        'cursos' => $cursos
    ];
    registrarActividad($conn, 'enviar', 'comunicado', $descripcion_audit, $comunicado_id, null, $datos_nuevos);

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al enviar el comunicado: ' . $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>

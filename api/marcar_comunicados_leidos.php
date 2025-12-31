<?php
// ============================================================
// API: Marcar comunicados como leídos
// ============================================================

session_start();

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'apoderado') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Configuración de conexión
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$conn->set_charset("utf8mb4");

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$comunicado_ids = $data['comunicado_ids'] ?? [];

if (empty($comunicado_ids)) {
    echo json_encode(['success' => true, 'message' => 'No hay comunicados para marcar']);
    exit;
}

// Obtener el ID del apoderado
$usuario_id = $_SESSION['usuario_id'];
$sql_apoderado = "SELECT id FROM tb_apoderados WHERE usuario_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_apoderado);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$apoderado = $result->fetch_assoc();
$stmt->close();

if (!$apoderado) {
    echo json_encode(['success' => false, 'message' => 'Apoderado no encontrado']);
    exit;
}

$apoderado_id = $apoderado['id'];

// Insertar registros de lectura (ignorar si ya existen)
$insertados = 0;
foreach ($comunicado_ids as $comunicado_id) {
    $comunicado_id = intval($comunicado_id);

    // Verificar si ya está marcado como leído
    $sql_check = "SELECT id FROM tb_comunicado_leido WHERE comunicado_id = ? AND apoderado_id = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("ii", $comunicado_id, $apoderado_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // No existe, insertar
        $sql_insert = "INSERT INTO tb_comunicado_leido (comunicado_id, apoderado_id, fecha_lectura) VALUES (?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $comunicado_id, $apoderado_id);
        if ($stmt_insert->execute()) {
            $insertados++;
        }
        $stmt_insert->close();
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Comunicados marcados como leídos',
    'marcados' => $insertados
]);
?>

<?php
// ============================================================
// API: Obtener Contactos del Chat
// Retorna lista de contactos disponibles para chat
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => '',
    'contactos' => []
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];
$establecimiento_id = $_SESSION['establecimiento_id'];

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

$contactos = [];

if ($tipo_usuario === 'administrador') {
    // Admin ve todos los docentes de su establecimiento
    $sql = "SELECT d.id as docente_id, d.usuario_id, d.nombres, d.apellidos,
                   'docente' as tipo_contacto,
                   (SELECT COUNT(*) FROM tb_chat_mensajes m
                    INNER JOIN tb_chat_conversaciones c ON m.conversacion_id = c.id
                    WHERE ((c.usuario1_id = ? AND c.usuario2_id = d.usuario_id)
                           OR (c.usuario2_id = ? AND c.usuario1_id = d.usuario_id))
                    AND m.remitente_id = d.usuario_id
                    AND m.leido = 0) as mensajes_no_leidos
            FROM tb_docentes d
            WHERE d.establecimiento_id = ?
            AND d.activo = 1
            AND d.usuario_id IS NOT NULL
            ORDER BY d.apellidos, d.nombres";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $usuario_id, $usuario_id, $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $contactos[] = [
            'id' => $row['usuario_id'],
            'docente_id' => $row['docente_id'],
            'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
            'tipo' => 'docente',
            'es_admin' => false,
            'mensajes_no_leidos' => (int)$row['mensajes_no_leidos']
        ];
    }
    $stmt->close();

} else if ($tipo_usuario === 'docente') {
    // Docente ve al administrador y otros docentes de su establecimiento

    // Primero obtener el administrador
    $sql_admin = "SELECT a.id as admin_id, a.usuario_id, a.nombres, a.apellidos,
                         (SELECT COUNT(*) FROM tb_chat_mensajes m
                          INNER JOIN tb_chat_conversaciones c ON m.conversacion_id = c.id
                          WHERE ((c.usuario1_id = ? AND c.usuario2_id = a.usuario_id)
                                 OR (c.usuario2_id = ? AND c.usuario1_id = a.usuario_id))
                          AND m.remitente_id = a.usuario_id
                          AND m.leido = 0) as mensajes_no_leidos
                  FROM tb_administradores a
                  WHERE a.establecimiento_id = ?
                  AND a.activo = 1";

    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("iii", $usuario_id, $usuario_id, $establecimiento_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    while ($row = $result_admin->fetch_assoc()) {
        $contactos[] = [
            'id' => $row['usuario_id'],
            'admin_id' => $row['admin_id'],
            'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
            'tipo' => 'administrador',
            'es_admin' => true,
            'mensajes_no_leidos' => (int)$row['mensajes_no_leidos']
        ];
    }
    $stmt_admin->close();

    // Luego obtener otros docentes
    $sql_docentes = "SELECT d.id as docente_id, d.usuario_id, d.nombres, d.apellidos,
                            (SELECT COUNT(*) FROM tb_chat_mensajes m
                             INNER JOIN tb_chat_conversaciones c ON m.conversacion_id = c.id
                             WHERE ((c.usuario1_id = ? AND c.usuario2_id = d.usuario_id)
                                    OR (c.usuario2_id = ? AND c.usuario1_id = d.usuario_id))
                             AND m.remitente_id = d.usuario_id
                             AND m.leido = 0) as mensajes_no_leidos
                     FROM tb_docentes d
                     WHERE d.establecimiento_id = ?
                     AND d.activo = 1
                     AND d.usuario_id IS NOT NULL
                     AND d.usuario_id != ?
                     ORDER BY d.apellidos, d.nombres";

    $stmt_docentes = $conn->prepare($sql_docentes);
    $stmt_docentes->bind_param("iiii", $usuario_id, $usuario_id, $establecimiento_id, $usuario_id);
    $stmt_docentes->execute();
    $result_docentes = $stmt_docentes->get_result();

    while ($row = $result_docentes->fetch_assoc()) {
        $contactos[] = [
            'id' => $row['usuario_id'],
            'docente_id' => $row['docente_id'],
            'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
            'tipo' => 'docente',
            'es_admin' => false,
            'mensajes_no_leidos' => (int)$row['mensajes_no_leidos']
        ];
    }
    $stmt_docentes->close();
}

$conn->close();

$response['success'] = true;
$response['contactos'] = $contactos;
$response['usuario_actual'] = $usuario_id;
$response['tipo_usuario'] = $tipo_usuario;

echo json_encode($response);
?>

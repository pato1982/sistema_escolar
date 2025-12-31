<?php
// ============================================================
// API: Verificar Sesión - Controla timeout por inactividad
// ============================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Tiempo de inactividad máximo (5 minutos = 300 segundos)
define('TIEMPO_INACTIVIDAD', 300);

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'sesion_activa' => false,
    'mensaje' => '',
    'cerrada_por_inactividad' => false
];

// Verificar si hay sesión iniciada
if (!isset($_SESSION['usuario_id'])) {
    $response['mensaje'] = 'No hay sesión activa';
    echo json_encode($response);
    exit;
}

// Verificar tiempo de inactividad
if (isset($_SESSION['ultima_actividad'])) {
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];

    if ($tiempo_inactivo > TIEMPO_INACTIVIDAD) {
        // Sesión expirada por inactividad
        $sesion_id = $_SESSION['sesion_id'] ?? null;

        // Actualizar en base de datos
        if ($sesion_id) {
            $conn = new mysqli($host, $user, $password, $database, $port);
            if (!$conn->connect_error) {
                $conn->set_charset("utf8mb4");
                $sql = "UPDATE tb_sesiones SET fecha_logout = NOW(), activa = FALSE WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $sesion_id);
                $stmt->execute();
                $stmt->close();
                $conn->close();
            }
        }

        // Destruir sesión PHP
        session_unset();
        session_destroy();

        $response['mensaje'] = 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.';
        $response['cerrada_por_inactividad'] = true;
        echo json_encode($response);
        exit;
    }
}

// Actualizar tiempo de última actividad
$_SESSION['ultima_actividad'] = time();

$response['success'] = true;
$response['sesion_activa'] = true;
$response['mensaje'] = 'Sesión activa';
$response['tiempo_restante'] = TIEMPO_INACTIVIDAD - (time() - $_SESSION['ultima_actividad']);

echo json_encode($response);
?>

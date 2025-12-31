<?php
// ============================================================
// API: Validar Pre-registro de Administrador
// ============================================================

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

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

$rut_admin = isset($input['rut_admin']) ? trim($input['rut_admin']) : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar que vengan todos los campos
if (empty($rut_admin) || $establecimiento_id <= 0) {
    $response['message'] = 'Faltan datos requeridos';
    echo json_encode($response);
    exit;
}

// Función para limpiar RUT (quitar puntos y guión para comparar)
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

$rut_admin_limpio = limpiarRut($rut_admin);

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Buscar en la tabla de pre-registro de administradores
$sql = "SELECT id, rut_admin, nombre_admin, usado
        FROM tb_preregistro_administradores
        WHERE establecimiento_id = ?
        AND REPLACE(REPLACE(REPLACE(rut_admin, '.', ''), '-', ''), ' ', '') = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $establecimiento_id, $rut_admin_limpio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si ya fue usado
    if ($row['usado']) {
        $response['success'] = false;
        $response['message'] = 'Este administrador ya se encuentra registrado en el sistema.';
    } else {
        $response['success'] = true;
        $response['message'] = 'Validación exitosa';
        $response['data'] = [
            'preregistro_id' => $row['id'],
            'nombre_admin' => $row['nombre_admin']
        ];
    }
} else {
    $response['success'] = false;
    $response['message'] = 'El RUT ingresado no coincide con los registros del establecimiento. Por favor, comuníquese con el colegio para verificar su información.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

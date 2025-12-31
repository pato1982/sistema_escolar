<?php
// ============================================================
// API: Validar Pre-registro de Docente
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

$rut_docente = isset($input['rut_docente']) ? trim($input['rut_docente']) : '';
$correo_docente = isset($input['correo_docente']) ? trim(strtolower($input['correo_docente'])) : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar que vengan todos los campos
if (empty($rut_docente) || empty($correo_docente) || $establecimiento_id <= 0) {
    $response['message'] = 'Faltan datos requeridos (RUT y correo son obligatorios)';
    echo json_encode($response);
    exit;
}

// Función para limpiar RUT (quitar puntos y guión para comparar)
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

$rut_docente_limpio = limpiarRut($rut_docente);

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Primero verificar si ya está registrado como docente
$sql_check = "SELECT id FROM tb_docentes
              WHERE establecimiento_id = ?
              AND REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $establecimiento_id, $rut_docente_limpio);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['success'] = false;
    $response['message'] = 'Este docente ya se encuentra registrado en el sistema.';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Buscar en la tabla de pre-registro de docentes por RUT
$sql = "SELECT id, rut_docente, nombre_docente, correo_docente
        FROM tb_preregistro_docentes
        WHERE establecimiento_id = ?
        AND REPLACE(REPLACE(REPLACE(rut_docente, '.', ''), '-', ''), ' ', '') = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $establecimiento_id, $rut_docente_limpio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar que el correo coincida
    $correo_preregistro = strtolower(trim($row['correo_docente'] ?? ''));

    if (empty($correo_preregistro)) {
        // Si no hay correo en el pre-registro, mostrar error
        $response['success'] = false;
        $response['message'] = 'No se encontró un correo electrónico registrado para este docente. Por favor, comuníquese con el colegio para actualizar su información.';
    } else if ($correo_docente === $correo_preregistro) {
        // RUT y correo coinciden
        $response['success'] = true;
        $response['message'] = 'Validación exitosa';
        $response['data'] = [
            'preregistro_id' => $row['id'],
            'nombre_docente' => $row['nombre_docente']
        ];
    } else {
        // El RUT existe pero el correo no coincide
        $response['success'] = false;
        $response['message'] = 'El correo electrónico ingresado no coincide con el registrado para este RUT. Por favor, verifique sus datos o comuníquese con el colegio.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'El RUT ingresado no coincide con los registros del establecimiento. Por favor, comuníquese con el colegio para verificar su información.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

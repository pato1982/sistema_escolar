<?php
// ============================================================
// API: Validar Pre-registro de Apoderado-Alumno
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

$rut_apoderado = isset($input['rut_apoderado']) ? trim($input['rut_apoderado']) : '';
$correo_apoderado = isset($input['correo_apoderado']) ? trim(strtolower($input['correo_apoderado'])) : '';
$rut_alumno = isset($input['rut_alumno']) ? trim($input['rut_alumno']) : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Validar que vengan todos los campos
if (empty($rut_apoderado) || empty($correo_apoderado) || empty($rut_alumno) || $establecimiento_id <= 0) {
    $response['message'] = 'Faltan datos requeridos (RUT y correo del apoderado son obligatorios)';
    echo json_encode($response);
    exit;
}

// Función para limpiar RUT (quitar puntos y guión para comparar)
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

$rut_apoderado_limpio = limpiarRut($rut_apoderado);
$rut_alumno_limpio = limpiarRut($rut_alumno);

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Buscar en la tabla de pre-registro por RUT del apoderado y RUT del alumno
$sql = "SELECT id, rut_apoderado, nombre_apoderado, correo_apoderado, rut_alumno, nombre_alumno, usado
        FROM tb_preregistro_relaciones
        WHERE establecimiento_id = ?
        AND REPLACE(REPLACE(REPLACE(rut_apoderado, '.', ''), '-', ''), ' ', '') = ?
        AND REPLACE(REPLACE(REPLACE(rut_alumno, '.', ''), '-', ''), ' ', '') = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $establecimiento_id, $rut_apoderado_limpio, $rut_alumno_limpio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar si ya fue usado
    if ($row['usado']) {
        $response['success'] = false;
        $response['message'] = 'Este apoderado ya se encuentra registrado con este alumno.';
    } else {
        // Verificar que el correo coincida
        $correo_preregistro = strtolower(trim($row['correo_apoderado'] ?? ''));

        if (empty($correo_preregistro)) {
            // Si no hay correo en el pre-registro, mostrar error
            $response['success'] = false;
            $response['message'] = 'No se encontró un correo electrónico registrado para este apoderado. Por favor, comuníquese con el colegio para actualizar su información.';
        } else if ($correo_apoderado === $correo_preregistro) {
            // RUT y correo coinciden
            $response['success'] = true;
            $response['message'] = 'Validación exitosa';
            $response['data'] = [
                'preregistro_id' => $row['id'],
                'nombre_apoderado' => $row['nombre_apoderado'],
                'nombre_alumno' => $row['nombre_alumno']
            ];
        } else {
            // El RUT existe pero el correo no coincide
            $response['success'] = false;
            $response['message'] = 'El RUT o correo electrónico no coinciden con los datos ingresados por el establecimiento. Por favor, verifique su información o comuníquese con el colegio.';
        }
    }
} else {
    $response['success'] = false;
    $response['message'] = 'El RUT o correo electrónico no coinciden con los datos ingresados por el establecimiento. Por favor, verifique su información o comuníquese con el colegio.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

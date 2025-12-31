<?php
// ============================================================
// API: Agregar Alumno por Apoderado
// El apoderado puede vincular un alumno que esté pre-registrado
// en tb_preregistro_relaciones
// ============================================================

// Configurar cookies de sesión antes de iniciar
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
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

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'apoderado') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$rut_alumno = isset($input['rut']) ? trim($input['rut']) : '';
$parentesco = isset($input['parentesco']) ? trim($input['parentesco']) : 'Apoderado';

// Validar campos requeridos
if (empty($rut_alumno)) {
    $response['message'] = 'Debe ingresar el RUT del alumno';
    echo json_encode($response);
    exit;
}

// Función para limpiar RUT (quitar puntos, guiones y espacios)
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos';
    echo json_encode($response);
    exit;
}

$conn->set_charset("utf8mb4");

// Obtener datos del apoderado
$sql_apoderado = "SELECT id, rut FROM tb_apoderados WHERE usuario_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_apoderado);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$apoderado = $result->fetch_assoc();
$stmt->close();

if (!$apoderado) {
    $response['message'] = 'No se encontró información del apoderado';
    $conn->close();
    echo json_encode($response);
    exit;
}

$apoderado_id = $apoderado['id'];
$rut_apoderado = $apoderado['rut'];
$rut_apoderado_limpio = limpiarRut($rut_apoderado);
$rut_alumno_limpio = limpiarRut($rut_alumno);

// Verificar que exista un pre-registro para esta combinación apoderado-alumno
$sql_preregistro = "SELECT id, rut_alumno, nombre_alumno, usado
                    FROM tb_preregistro_relaciones
                    WHERE establecimiento_id = ?
                    AND REPLACE(REPLACE(REPLACE(rut_apoderado, '.', ''), '-', ''), ' ', '') = ?
                    AND REPLACE(REPLACE(REPLACE(rut_alumno, '.', ''), '-', ''), ' ', '') = ?";
$stmt = $conn->prepare($sql_preregistro);
$stmt->bind_param("iss", $establecimiento_id, $rut_apoderado_limpio, $rut_alumno_limpio);
$stmt->execute();
$result = $stmt->get_result();
$preregistro = $result->fetch_assoc();
$stmt->close();

if (!$preregistro) {
    $response['message'] = 'No se encontró un pre-registro que vincule su RUT con el RUT del alumno ingresado. Contacte al administrador del establecimiento.';
    $conn->close();
    echo json_encode($response);
    exit;
}

if ($preregistro['usado'] == 1) {
    $response['message'] = 'Este alumno ya fue vinculado anteriormente.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Buscar el alumno por RUT en el establecimiento
$sql_alumno = "SELECT id, nombres, apellidos, curso_id
               FROM tb_alumnos
               WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?
               AND establecimiento_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_alumno);
$stmt->bind_param("si", $rut_alumno_limpio, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
$alumno = $result->fetch_assoc();
$stmt->close();

if (!$alumno) {
    $response['message'] = 'No se encontró un alumno con ese RUT en el establecimiento. Contacte al administrador.';
    $conn->close();
    echo json_encode($response);
    exit;
}

$alumno_id = $alumno['id'];

// Verificar si ya existe la relación apoderado-alumno
$sql_check = "SELECT id FROM tb_apoderado_alumno WHERE apoderado_id = ? AND alumno_id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $apoderado_id, $alumno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['message'] = 'Este alumno ya está asociado a su cuenta';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // Crear la relación apoderado-alumno
    $sql_insert = "INSERT INTO tb_apoderado_alumno (apoderado_id, alumno_id, parentesco, es_titular)
                   VALUES (?, ?, ?, TRUE)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("iis", $apoderado_id, $alumno_id, $parentesco);
    $stmt->execute();
    $stmt->close();

    // Marcar el pre-registro como usado
    $sql_update = "UPDATE tb_preregistro_relaciones
                   SET usado = TRUE, fecha_uso = NOW()
                   WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("i", $preregistro['id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Alumno vinculado correctamente: ' . $alumno['nombres'] . ' ' . $alumno['apellidos'];
    $response['alumno'] = [
        'id' => $alumno_id,
        'nombres' => $alumno['nombres'],
        'apellidos' => $alumno['apellidos']
    ];

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al vincular el alumno: ' . $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>

<?php
// ============================================================
// API: Registrar Apoderado (Usuario + Apoderado + Alumnos + Relaciones)
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
    'message' => ''
];

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Función para formatear nombres: Primera letra mayúscula de cada palabra
function formatearNombre($texto) {
    if (empty($texto)) return '';
    return mb_convert_case(mb_strtolower(trim($texto), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Datos del apoderado
$apoderado_nombres = formatearNombre(isset($input['apoderado_nombres']) ? $input['apoderado_nombres'] : '');
$apoderado_apellidos = formatearNombre(isset($input['apoderado_apellidos']) ? $input['apoderado_apellidos'] : '');
$apoderado_rut = isset($input['apoderado_rut']) ? trim($input['apoderado_rut']) : '';
$apoderado_telefono = isset($input['apoderado_telefono']) ? trim($input['apoderado_telefono']) : '';
$apoderado_direccion = isset($input['apoderado_direccion']) ? trim($input['apoderado_direccion']) : '';
$apoderado_correo = isset($input['apoderado_correo']) ? trim($input['apoderado_correo']) : '';
$apoderado_parentesco = isset($input['apoderado_parentesco']) ? trim($input['apoderado_parentesco']) : '';
$password_plain = isset($input['password']) ? $input['password'] : '';
$establecimiento_id = isset($input['establecimiento_id']) ? intval($input['establecimiento_id']) : 0;

// Datos de los alumnos (array)
$alumnos = isset($input['alumnos']) ? $input['alumnos'] : [];

// Validar campos requeridos
if (empty($apoderado_nombres) || empty($apoderado_apellidos) || empty($apoderado_rut) ||
    empty($apoderado_correo) || empty($password_plain) || $establecimiento_id <= 0 || empty($alumnos)) {
    $response['message'] = 'Faltan datos requeridos';
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

// Función para limpiar RUT
function limpiarRut($rut) {
    return strtoupper(str_replace(['.', '-', ' '], '', $rut));
}

// Verificar que el correo no exista
$sql_check_email = "SELECT id FROM tb_usuarios WHERE email = ?";
$stmt_check = $conn->prepare($sql_check_email);
$stmt_check->bind_param("s", $apoderado_correo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['message'] = 'El correo electrónico ya está registrado en el sistema.';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Verificar que el RUT del apoderado no exista
$rut_limpio = limpiarRut($apoderado_rut);
$sql_check_rut = "SELECT id FROM tb_apoderados WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?";
$stmt_check_rut = $conn->prepare($sql_check_rut);
$stmt_check_rut->bind_param("s", $rut_limpio);
$stmt_check_rut->execute();
$result_check_rut = $stmt_check_rut->get_result();

if ($result_check_rut->num_rows > 0) {
    $response['message'] = 'El RUT del apoderado ya está registrado en el sistema.';
    $stmt_check_rut->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check_rut->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Crear usuario
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $sql_usuario = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id) VALUES (?, ?, 'apoderado', ?)";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssi", $apoderado_correo, $password_hash, $establecimiento_id);
    $stmt_usuario->execute();
    $usuario_id = $conn->insert_id;
    $stmt_usuario->close();

    // 2. Crear apoderado
    $sql_apoderado = "INSERT INTO tb_apoderados (usuario_id, nombres, apellidos, rut, telefono, direccion, establecimiento_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_apoderado = $conn->prepare($sql_apoderado);
    $stmt_apoderado->bind_param("isssssi", $usuario_id, $apoderado_nombres, $apoderado_apellidos, $apoderado_rut, $apoderado_telefono, $apoderado_direccion, $establecimiento_id);
    $stmt_apoderado->execute();
    $apoderado_id = $conn->insert_id;
    $stmt_apoderado->close();

    // 3. Procesar cada alumno
    foreach ($alumnos as $alumno) {
        $alumno_nombres = formatearNombre($alumno['nombres']);
        $alumno_apellidos = formatearNombre($alumno['apellidos']);
        $alumno_rut = trim($alumno['rut']);
        $alumno_curso = trim($alumno['curso']);

        // Verificar si el alumno ya existe por RUT
        $rut_alumno_limpio = limpiarRut($alumno_rut);
        $sql_check_alumno = "SELECT id FROM tb_alumnos WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?";
        $stmt_check_alumno = $conn->prepare($sql_check_alumno);
        $stmt_check_alumno->bind_param("s", $rut_alumno_limpio);
        $stmt_check_alumno->execute();
        $result_alumno = $stmt_check_alumno->get_result();

        if ($result_alumno->num_rows > 0) {
            // El alumno ya existe, obtener su ID
            $row_alumno = $result_alumno->fetch_assoc();
            $alumno_id = $row_alumno['id'];
        } else {
            // Crear nuevo alumno
            $sql_alumno = "INSERT INTO tb_alumnos (nombres, apellidos, rut, establecimiento_id) VALUES (?, ?, ?, ?)";
            $stmt_alumno = $conn->prepare($sql_alumno);
            $stmt_alumno->bind_param("sssi", $alumno_nombres, $alumno_apellidos, $alumno_rut, $establecimiento_id);
            $stmt_alumno->execute();
            $alumno_id = $conn->insert_id;
            $stmt_alumno->close();
        }
        $stmt_check_alumno->close();

        // 4. Crear relación apoderado-alumno
        $sql_relacion = "INSERT INTO tb_apoderado_alumno (apoderado_id, alumno_id, parentesco) VALUES (?, ?, ?)";
        $stmt_relacion = $conn->prepare($sql_relacion);
        $stmt_relacion->bind_param("iis", $apoderado_id, $alumno_id, $apoderado_parentesco);
        $stmt_relacion->execute();
        $stmt_relacion->close();

        // 5. Marcar pre-registro como usado
        $sql_marcar = "UPDATE tb_preregistro_relaciones
                       SET usado = TRUE, fecha_uso = NOW()
                       WHERE establecimiento_id = ?
                       AND REPLACE(REPLACE(REPLACE(rut_apoderado, '.', ''), '-', ''), ' ', '') = ?
                       AND REPLACE(REPLACE(REPLACE(rut_alumno, '.', ''), '-', ''), ' ', '') = ?";
        $stmt_marcar = $conn->prepare($sql_marcar);
        $stmt_marcar->bind_param("iss", $establecimiento_id, $rut_limpio, $rut_alumno_limpio);
        $stmt_marcar->execute();
        $stmt_marcar->close();
    }

    // Confirmar transacción
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Registro completado exitosamente';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $response['message'] = 'Error al registrar: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>

<?php
// ============================================================
// API: Agregar Alumno - Crea un nuevo alumno en el sistema
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

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
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

// Función para formatear nombres: Primera letra mayúscula de cada palabra
function formatearNombre($texto) {
    if (empty($texto)) return '';
    // Convertir a minúsculas y luego capitalizar cada palabra
    return mb_convert_case(mb_strtolower(trim($texto), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$nombres = formatearNombre(isset($input['nombres']) ? $input['nombres'] : '');
$apellidos = formatearNombre(isset($input['apellidos']) ? $input['apellidos'] : '');
$rut = isset($input['rut']) ? trim($input['rut']) : '';
$fecha_nacimiento = isset($input['fecha_nacimiento']) ? $input['fecha_nacimiento'] : null;
$sexo = isset($input['sexo']) ? $input['sexo'] : null;
$curso_id = isset($input['curso_id']) ? intval($input['curso_id']) : null;

// Datos del apoderado NUEVO (para pre-registro)
$nombres_apoderado = formatearNombre(isset($input['nombres_apoderado']) ? $input['nombres_apoderado'] : '');
$apellidos_apoderado = formatearNombre(isset($input['apellidos_apoderado']) ? $input['apellidos_apoderado'] : '');
$rut_apoderado = isset($input['rut_apoderado']) ? trim($input['rut_apoderado']) : '';
$correo_apoderado = isset($input['correo_apoderado']) ? trim($input['correo_apoderado']) : '';
$telefono_apoderado = isset($input['telefono_apoderado']) ? trim($input['telefono_apoderado']) : '';

// Nombre completo del apoderado para el pre-registro
$nombre_apoderado_completo = trim($nombres_apoderado . ' ' . $apellidos_apoderado);

// Datos del apoderado EXISTENTE
$apoderado_existe = isset($input['apoderado_existe']) ? $input['apoderado_existe'] : false;
$rut_apoderado_existe = isset($input['rut_apoderado_existe']) ? trim($input['rut_apoderado_existe']) : '';
$nombres_apoderado_existe = formatearNombre(isset($input['nombres_apoderado_existe']) ? $input['nombres_apoderado_existe'] : '');
$apellidos_apoderado_existe = formatearNombre(isset($input['apellidos_apoderado_existe']) ? $input['apellidos_apoderado_existe'] : '');
$parentesco_apoderado_existe = isset($input['parentesco_apoderado_existe']) ? trim($input['parentesco_apoderado_existe']) : 'Apoderado';

// Validar campos requeridos
if (empty($nombres) || empty($apellidos) || empty($rut)) {
    $response['message'] = 'Faltan campos requeridos';
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

// Verificar si el RUT ya existe
$sql_check = "SELECT id FROM tb_alumnos WHERE rut = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $rut);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $response['message'] = 'El RUT ya está registrado en el sistema';
    $stmt_check->close();
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Insertar alumno
$sql = "INSERT INTO tb_alumnos (nombres, apellidos, rut, fecha_nacimiento, sexo, curso_id, establecimiento_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssii", $nombres, $apellidos, $rut, $fecha_nacimiento, $sexo, $curso_id, $establecimiento_id);

if ($stmt->execute()) {
    $alumno_id = $conn->insert_id;
    $response['success'] = true;
    $response['message'] = 'Alumno agregado correctamente';
    $response['alumno_id'] = $alumno_id;

    // Nombre completo del alumno para el pre-registro
    $nombre_alumno_completo = $nombres . ' ' . $apellidos;

    // Registrar en auditoría
    $curso_nombre = '';
    if ($curso_id) {
        $sql_curso = "SELECT nombre FROM tb_cursos WHERE id = ?";
        $stmt_curso = $conn->prepare($sql_curso);
        $stmt_curso->bind_param("i", $curso_id);
        $stmt_curso->execute();
        $result_curso = $stmt_curso->get_result();
        if ($row_curso = $result_curso->fetch_assoc()) {
            $curso_nombre = $row_curso['nombre'];
        }
        $stmt_curso->close();
    }
    $descripcion_audit = "Agregó alumno $nombre_alumno_completo (RUT: $rut)" . ($curso_nombre ? " al curso $curso_nombre" : "");
    $datos_nuevos = [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'rut' => $rut,
        'curso' => $curso_nombre,
        'fecha_nacimiento' => $fecha_nacimiento
    ];
    registrarActividad($conn, 'agregar', 'alumno', $descripcion_audit, $alumno_id, null, $datos_nuevos);

    // CASO 1: Apoderado ya existe - buscar por RUT y vincular
    if ($apoderado_existe && !empty($rut_apoderado_existe)) {
        // Función para limpiar RUT
        $rut_limpio = strtoupper(str_replace(['.', '-', ' '], '', $rut_apoderado_existe));

        // Buscar si el apoderado ya está registrado en tb_apoderados
        $sql_buscar_apoderado = "SELECT id, nombres, apellidos FROM tb_apoderados
                                  WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?
                                  AND establecimiento_id = ?";
        $stmt_buscar = $conn->prepare($sql_buscar_apoderado);
        $stmt_buscar->bind_param("si", $rut_limpio, $establecimiento_id);
        $stmt_buscar->execute();
        $result_buscar = $stmt_buscar->get_result();

        if ($result_buscar->num_rows > 0) {
            // El apoderado está registrado - crear relación directa
            $apoderado_row = $result_buscar->fetch_assoc();
            $apoderado_id = $apoderado_row['id'];

            // Verificar si ya existe la relación
            $sql_check_rel = "SELECT id FROM tb_apoderado_alumno WHERE apoderado_id = ? AND alumno_id = ?";
            $stmt_check_rel = $conn->prepare($sql_check_rel);
            $stmt_check_rel->bind_param("ii", $apoderado_id, $alumno_id);
            $stmt_check_rel->execute();
            $result_check_rel = $stmt_check_rel->get_result();

            if ($result_check_rel->num_rows == 0) {
                // Crear relación apoderado-alumno con parentesco
                $sql_rel = "INSERT INTO tb_apoderado_alumno (apoderado_id, alumno_id, parentesco) VALUES (?, ?, ?)";
                $stmt_rel = $conn->prepare($sql_rel);
                $stmt_rel->bind_param("iis", $apoderado_id, $alumno_id, $parentesco_apoderado_existe);

                if ($stmt_rel->execute()) {
                    $response['message'] = 'Alumno agregado y vinculado al apoderado existente';
                    $response['apoderado_vinculado'] = true;
                    $response['apoderado_nombre'] = $apoderado_row['nombres'] . ' ' . $apoderado_row['apellidos'];
                } else {
                    $response['message'] = 'Alumno agregado, pero error al vincular con el apoderado';
                }
                $stmt_rel->close();
            } else {
                $response['message'] = 'Alumno agregado. Ya existía la relación con el apoderado';
            }
            $stmt_check_rel->close();
        } else {
            // El apoderado NO está registrado aún - crear pre-registro para que pueda registrarse
            $nombre_apoderado_existe_completo = trim($nombres_apoderado_existe . ' ' . $apellidos_apoderado_existe);

            // Verificar si ya existe este pre-registro
            $sql_check_pre = "SELECT id FROM tb_preregistro_relaciones
                              WHERE establecimiento_id = ?
                              AND REPLACE(REPLACE(REPLACE(rut_apoderado, '.', ''), '-', ''), ' ', '') = ?
                              AND REPLACE(REPLACE(REPLACE(rut_alumno, '.', ''), '-', ''), ' ', '') = ?";
            $stmt_check_pre = $conn->prepare($sql_check_pre);
            $rut_alumno_limpio = strtoupper(str_replace(['.', '-', ' '], '', $rut));
            $stmt_check_pre->bind_param("iss", $establecimiento_id, $rut_limpio, $rut_alumno_limpio);
            $stmt_check_pre->execute();
            $result_check_pre = $stmt_check_pre->get_result();

            if ($result_check_pre->num_rows == 0) {
                // Insertar pre-registro (sin correo porque viene de apoderado existente sin registrar)
                $sql_pre = "INSERT INTO tb_preregistro_relaciones (rut_apoderado, nombre_apoderado, rut_alumno, nombre_alumno, establecimiento_id)
                            VALUES (?, ?, ?, ?, ?)";
                $stmt_pre = $conn->prepare($sql_pre);
                $stmt_pre->bind_param("ssssi", $rut_apoderado_existe, $nombre_apoderado_existe_completo, $rut, $nombre_alumno_completo, $establecimiento_id);

                if ($stmt_pre->execute()) {
                    $response['message'] = 'Alumno agregado. Pre-registro creado para el apoderado (aún no registrado en el sistema)';
                    $response['preregistro_creado'] = true;
                }
                $stmt_pre->close();
            } else {
                $response['message'] = 'Alumno agregado. El pre-registro ya existía para este apoderado';
            }
            $stmt_check_pre->close();
        }
        $stmt_buscar->close();

    // CASO 2: Apoderado nuevo - crear pre-registro
    } else if (!empty($rut_apoderado) && !empty($nombre_apoderado_completo)) {
        // Verificar si ya existe este pre-registro
        $sql_check_pre = "SELECT id FROM tb_preregistro_relaciones
                          WHERE establecimiento_id = ?
                          AND REPLACE(REPLACE(REPLACE(rut_apoderado, '.', ''), '-', ''), ' ', '') = REPLACE(REPLACE(REPLACE(?, '.', ''), '-', ''), ' ', '')
                          AND REPLACE(REPLACE(REPLACE(rut_alumno, '.', ''), '-', ''), ' ', '') = REPLACE(REPLACE(REPLACE(?, '.', ''), '-', ''), ' ', '')";
        $stmt_check_pre = $conn->prepare($sql_check_pre);
        $stmt_check_pre->bind_param("iss", $establecimiento_id, $rut_apoderado, $rut);
        $stmt_check_pre->execute();
        $result_check_pre = $stmt_check_pre->get_result();

        if ($result_check_pre->num_rows == 0) {
            // Insertar pre-registro con correo del apoderado
            $correo_apoderado_guardar = !empty($correo_apoderado) ? strtolower(trim($correo_apoderado)) : null;
            $sql_pre = "INSERT INTO tb_preregistro_relaciones (rut_apoderado, nombre_apoderado, correo_apoderado, rut_alumno, nombre_alumno, establecimiento_id)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_pre = $conn->prepare($sql_pre);
            $stmt_pre->bind_param("sssssi", $rut_apoderado, $nombre_apoderado_completo, $correo_apoderado_guardar, $rut, $nombre_alumno_completo, $establecimiento_id);

            if ($stmt_pre->execute()) {
                $response['message'] = 'Alumno y pre-registro de apoderado agregados correctamente';
                $response['preregistro_creado'] = true;
            } else {
                $response['message'] = 'Alumno agregado, pero hubo un error al crear el pre-registro';
                $response['preregistro_creado'] = false;
            }
            $stmt_pre->close();
        } else {
            $response['message'] = 'Alumno agregado. El pre-registro ya existía para este apoderado-alumno';
            $response['preregistro_creado'] = false;
        }
        $stmt_check_pre->close();
    }
} else {
    $response['message'] = 'Error al agregar el alumno: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

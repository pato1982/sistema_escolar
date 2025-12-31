<?php
// ============================================================
// API: Editar Nota - Modificar calificación existente (Docente)
// ============================================================

session_start();

require_once 'helper_auditoria.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de conexión
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Sesión no válida';
    echo json_encode($response);
    exit;
}

// Verificar que sea docente
if ($_SESSION['tipo_usuario'] !== 'docente') {
    $response['message'] = 'No tiene permisos para realizar esta acción';
    echo json_encode($response);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

// Función para normalizar la nota ingresada
function normalizarNota($valor) {
    if ($valor === null || $valor === '') return null;

    // Convertir a string para procesar
    $valor = trim(strval($valor));

    // Reemplazar coma por punto
    $valor = str_replace(',', '.', $valor);

    // Si ya tiene punto decimal, convertir directamente
    if (strpos($valor, '.') !== false) {
        return floatval($valor);
    }

    // Si es un solo dígito (1-7), agregar .0
    if (strlen($valor) === 1 && is_numeric($valor)) {
        return floatval($valor . '.0');
    }

    // Si son dos dígitos sin punto (ej: 55, 67), insertar punto en medio
    if (strlen($valor) === 2 && is_numeric($valor)) {
        return floatval($valor[0] . '.' . $valor[1]);
    }

    // Caso por defecto
    return floatval($valor);
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$nota_id = isset($input['nota_id']) ? intval($input['nota_id']) : null;
$nota = isset($input['nota']) ? normalizarNota($input['nota']) : null;
$trimestre = isset($input['trimestre']) ? intval($input['trimestre']) : null;
$fecha_evaluacion = isset($input['fecha_evaluacion']) ? trim($input['fecha_evaluacion']) : null;
$comentario = isset($input['comentario']) ? trim($input['comentario']) : '';
$docente_id = isset($input['docente_id']) ? intval($input['docente_id']) : null;
$establecimiento_id = $_SESSION['establecimiento_id'];

// Validaciones
if (!$nota_id || !$nota || !$trimestre || !$docente_id) {
    $response['message'] = 'Faltan datos requeridos';
    echo json_encode($response);
    exit;
}

// Validar rango de nota
if ($nota < 1.0 || $nota > 7.0) {
    $response['message'] = 'La nota debe estar entre 1.0 y 7.0';
    echo json_encode($response);
    exit;
}

// Validar trimestre
if ($trimestre < 1 || $trimestre > 3) {
    $response['message'] = 'El trimestre debe ser 1, 2 o 3';
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

// Verificar que la nota exista y pertenezca al docente
$sql_verificar = "SELECT n.id, n.alumno_id, n.curso_id, n.asignatura_id, n.nota as nota_anterior, n.trimestre as trimestre_anterior,
                         a.nombres as alumno_nombres, a.apellidos as alumno_apellidos, asig.nombre as asignatura_nombre
                  FROM tb_notas n
                  LEFT JOIN tb_alumnos a ON n.alumno_id = a.id
                  LEFT JOIN tb_asignaturas asig ON n.asignatura_id = asig.id
                  WHERE n.id = ? AND n.docente_id = ? AND n.establecimiento_id = ?";
$stmt_ver = $conn->prepare($sql_verificar);
$stmt_ver->bind_param("iii", $nota_id, $docente_id, $establecimiento_id);
$stmt_ver->execute();
$result_ver = $stmt_ver->get_result();

if ($result_ver->num_rows === 0) {
    $response['message'] = 'No tiene permisos para editar esta nota o no existe';
    $stmt_ver->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$nota_actual = $result_ver->fetch_assoc();
$stmt_ver->close();

// Actualizar la nota (si tenía es_pendiente=1, lo cambia a 0 al asignar nota)
$sql = "UPDATE tb_notas SET
        nota = ?,
        trimestre = ?,
        fecha_evaluacion = ?,
        comentario = ?,
        es_pendiente = 0,
        fecha_modificacion = NOW()
        WHERE id = ? AND docente_id = ? AND establecimiento_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dissiii",
    $nota,
    $trimestre,
    $fecha_evaluacion,
    $comentario,
    $nota_id,
    $docente_id,
    $establecimiento_id
);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Nota actualizada correctamente';
        $response['data'] = [
            'id' => $nota_id,
            'nota' => $nota,
            'trimestre' => $trimestre,
            'fecha_evaluacion' => $fecha_evaluacion,
            'comentario' => $comentario
        ];

        // Registrar en auditoría
        $nombre_alumno = $nota_actual['alumno_nombres'] . ' ' . $nota_actual['alumno_apellidos'];
        $descripcion_audit = "Modificó nota de " . $nota_actual['nota_anterior'] . " a $nota para $nombre_alumno en " . $nota_actual['asignatura_nombre'];
        $datos_anteriores = [
            'nota' => $nota_actual['nota_anterior'],
            'trimestre' => $nota_actual['trimestre_anterior']
        ];
        $datos_nuevos = [
            'nota' => $nota,
            'trimestre' => $trimestre,
            'alumno' => $nombre_alumno,
            'asignatura' => $nota_actual['asignatura_nombre']
        ];
        registrarActividad($conn, 'editar', 'nota', $descripcion_audit, $nota_id, $datos_anteriores, $datos_nuevos);
    } else {
        $response['message'] = 'No se realizaron cambios';
    }
} else {
    $response['message'] = 'Error al actualizar la nota: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

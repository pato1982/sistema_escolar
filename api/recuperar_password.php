<?php
// ============================================================
// API: Recuperar Contraseña - Genera clave provisoria y envía por correo
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Cargar configuración de correo y PHPMailer
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../libs/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer.php';
require_once __DIR__ . '/../libs/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

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

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$correo = isset($input['correo']) ? trim($input['correo']) : '';

// Validar campo requerido
if (empty($correo)) {
    $response['message'] = 'El correo electrónico es requerido';
    echo json_encode($response);
    exit;
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'El formato del correo electrónico no es válido';
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

// Buscar usuario por correo
$sql = "SELECT id, email, activo FROM tb_usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'El correo electrónico no está registrado en el sistema.';
    $stmt->close();
    $conn->close();
    echo json_encode($response);
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar si la cuenta está activa
if (!$usuario['activo']) {
    $response['message'] = 'Su cuenta se encuentra inactiva. Por favor contacte al administrador.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Generar clave provisoria aleatoria (8 caracteres: letras y números)
$caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$clave_provisoria = substr(str_shuffle($caracteres), 0, 8);

// Hash de la clave provisoria para guardar en BD
$clave_provisoria_hash = password_hash($clave_provisoria, PASSWORD_DEFAULT);

// Fecha de expiración (24 horas desde ahora)
$fecha_expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Invalidar claves provisorias anteriores del mismo usuario
$sql_invalidar = "UPDATE tb_claves_provisorias SET usado = 1 WHERE usuario_id = ? AND usado = 0";
$stmt_invalidar = $conn->prepare($sql_invalidar);
$stmt_invalidar->bind_param("i", $usuario['id']);
$stmt_invalidar->execute();
$stmt_invalidar->close();

// Insertar nueva clave provisoria
$sql_insert = "INSERT INTO tb_claves_provisorias (usuario_id, correo, clave_provisoria_hash, fecha_expiracion) VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("isss", $usuario['id'], $correo, $clave_provisoria_hash, $fecha_expiracion);

if ($stmt_insert->execute()) {
    $clave_id = $conn->insert_id;

    // Enviar correo con PHPMailer
    $mail = new PHPMailer();

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($correo);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - Portal Estudiantil';

        // Cuerpo del correo en HTML
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="background-color: #1e3a5f; color: white; padding: 30px; text-align: center;">
                    <h1 style="margin: 0; font-size: 24px;">Portal Estudiantil</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">Recuperación de Contraseña</p>
                </div>
                <div style="padding: 30px;">
                    <p style="color: #333; font-size: 16px; margin-bottom: 20px;">
                        Hemos recibido una solicitud para restablecer la contraseña de su cuenta.
                    </p>
                    <p style="color: #333; font-size: 16px; margin-bottom: 20px;">
                        Su clave provisoria es:
                    </p>
                    <div style="background-color: #f8f9fa; border: 2px dashed #1e3a5f; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
                        <span style="font-size: 28px; font-weight: bold; color: #1e3a5f; letter-spacing: 3px;">' . $clave_provisoria . '</span>
                    </div>
                    <p style="color: #333; font-size: 14px; margin-bottom: 15px;">
                        <strong>Instrucciones:</strong>
                    </p>
                    <ol style="color: #555; font-size: 14px; line-height: 1.8;">
                        <li>Ingrese al Portal Estudiantil con su correo electrónico</li>
                        <li>Use esta clave provisoria como contraseña</li>
                        <li>El sistema le pedirá crear una nueva contraseña</li>
                    </ol>
                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                        <p style="color: #856404; font-size: 13px; margin: 0;">
                            <strong>Importante:</strong> Esta clave expira en 24 horas. Si no solicitó este cambio, ignore este correo.
                        </p>
                    </div>
                </div>
                <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
                    <p style="color: #888; font-size: 12px; margin: 0;">
                        Este es un correo automático, por favor no responda a este mensaje.
                    </p>
                    <p style="color: #888; font-size: 12px; margin: 10px 0 0 0;">
                        © ' . date('Y') . ' Portal Estudiantil - Todos los derechos reservados
                    </p>
                </div>
            </div>
        </body>
        </html>';

        // Cuerpo alternativo en texto plano
        $mail->AltBody = "Portal Estudiantil - Recuperación de Contraseña\n\n" .
                         "Su clave provisoria es: " . $clave_provisoria . "\n\n" .
                         "Instrucciones:\n" .
                         "1. Ingrese al Portal Estudiantil con su correo electrónico\n" .
                         "2. Use esta clave provisoria como contraseña\n" .
                         "3. El sistema le pedirá crear una nueva contraseña\n\n" .
                         "Esta clave expira en 24 horas.";

        if ($mail->send()) {
            // Actualizar registro indicando que el email fue enviado
            $sql_update = "UPDATE tb_claves_provisorias SET email_enviado = 1, fecha_envio_email = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $clave_id);
            $stmt_update->execute();
            $stmt_update->close();

            $response['success'] = true;
            $response['message'] = 'Se ha enviado una clave provisoria a su correo electrónico. Revise su bandeja de entrada.';
        } else {
            $response['success'] = true;
            $response['message'] = 'Se generó la clave pero hubo un problema al enviar el correo. Contacte al administrador.';
        }

    } catch (Exception $e) {
        $response['success'] = true;
        $response['message'] = 'Se generó la clave pero hubo un problema al enviar el correo. Contacte al administrador.';
    }

} else {
    $response['message'] = 'Error al generar la clave provisoria. Intente nuevamente.';
}

$stmt_insert->close();
$conn->close();

echo json_encode($response);
?>

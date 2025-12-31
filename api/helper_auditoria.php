<?php
// ============================================================
// HELPER: Función para registrar actividades en el log de auditoría
// ============================================================

/**
 * Registra una actividad en el log de auditoría
 *
 * @param mysqli $conn Conexión a la base de datos
 * @param string $accion Tipo de acción: agregar, editar, eliminar, enviar, login, logout
 * @param string $modulo Módulo afectado: alumno, docente, nota, comunicado, curso, asignatura, etc.
 * @param string $descripcion Descripción legible de la acción
 * @param int|null $entidad_id ID del registro afectado (opcional)
 * @param array|null $datos_anteriores Datos antes del cambio (opcional)
 * @param array|null $datos_nuevos Datos después del cambio (opcional)
 * @return bool True si se registró correctamente
 */
function registrarActividad($conn, $accion, $modulo, $descripcion, $entidad_id = null, $datos_anteriores = null, $datos_nuevos = null) {
    // Verificar que la sesión esté activa
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['tipo_usuario'];
    $nombre_usuario = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Usuario';
    $establecimiento_id = $_SESSION['establecimiento_id'];

    // Obtener IP del cliente
    $ip_address = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    // Obtener User Agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : '';

    // Convertir arrays a JSON
    $datos_ant_json = $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : null;
    $datos_new_json = $datos_nuevos ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : null;

    // Insertar en el log
    $sql = "INSERT INTO tb_log_actividades
            (usuario_id, tipo_usuario, nombre_usuario, accion, modulo, descripcion,
             datos_anteriores, datos_nuevos, entidad_id, ip_address, user_agent, establecimiento_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("isssssssisis",
        $usuario_id,
        $tipo_usuario,
        $nombre_usuario,
        $accion,
        $modulo,
        $descripcion,
        $datos_ant_json,
        $datos_new_json,
        $entidad_id,
        $ip_address,
        $user_agent,
        $establecimiento_id
    );

    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
?>

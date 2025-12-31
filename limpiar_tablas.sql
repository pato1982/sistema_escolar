-- ============================================================
-- LIMPIAR TODAS LAS TABLAS - PORTAL ESTUDIANTIL
-- Elimina todos los datos sin eliminar las estructuras
-- ============================================================

-- Deshabilitar verificación de foreign keys temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas del chat
TRUNCATE TABLE tb_chat_mensajes;
TRUNCATE TABLE tb_chat_conversaciones;

-- Limpiar tablas de claves y sesiones
TRUNCATE TABLE tb_claves_provisorias;
TRUNCATE TABLE tb_sesiones;

-- Limpiar tablas de comunicados
TRUNCATE TABLE tb_comunicado_leido;
TRUNCATE TABLE tb_comunicado_curso;
TRUNCATE TABLE tb_comunicados;

-- Limpiar tablas de notas
TRUNCATE TABLE tb_notas;

-- Limpiar tablas de asignaciones
TRUNCATE TABLE tb_asignaciones;
TRUNCATE TABLE tb_docente_asignatura;

-- Limpiar tablas de relaciones apoderado-alumno
TRUNCATE TABLE tb_apoderado_alumno;

-- Limpiar tablas de logs e intentos fallidos
TRUNCATE TABLE tb_log_actividades;
TRUNCATE TABLE tb_intentos_login_fallidos;
TRUNCATE TABLE tb_intentos_registro_fallidos;
TRUNCATE TABLE tb_intentos_registro_fallidos_admin;
TRUNCATE TABLE tb_intentos_registro_fallidos_docentes;

-- Limpiar tablas de preregistros
TRUNCATE TABLE tb_preregistro_relaciones;
TRUNCATE TABLE tb_preregistro_docentes;
TRUNCATE TABLE tb_preregistro_administradores;

-- Limpiar tablas de códigos de validación
TRUNCATE TABLE tb_codigos_validacion;

-- Limpiar tablas de personas
TRUNCATE TABLE tb_administradores;
TRUNCATE TABLE tb_apoderados;
TRUNCATE TABLE tb_docentes;
TRUNCATE TABLE tb_alumnos;

-- Limpiar tabla de usuarios
TRUNCATE TABLE tb_usuarios;

-- Limpiar tablas de asignaturas y cursos
TRUNCATE TABLE tb_asignaturas;
TRUNCATE TABLE tb_cursos;

-- Limpiar tabla de establecimientos
TRUNCATE TABLE tb_establecimientos;

-- Volver a habilitar verificación de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- Mensaje de confirmación
SELECT 'Todas las tablas han sido limpiadas exitosamente' AS resultado;

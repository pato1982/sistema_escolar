-- ============================================================
-- ESTRUCTURA DE TABLAS - PORTAL ESTUDIANTIL
-- ============================================================

-- ------------------------------------------------------------
-- TABLA: tb_administradores
-- ------------------------------------------------------------
CREATE TABLE `tb_administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `fk_admin_establecimiento` (`establecimiento_id`),
  CONSTRAINT `fk_admin_establecimiento` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`),
  CONSTRAINT `tb_administradores_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_alumnos
-- ------------------------------------------------------------
CREATE TABLE `tb_alumnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` enum('Femenino','Masculino','Otro') DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `curso_id` (`curso_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_alumnos_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `tb_cursos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tb_alumnos_ibfk_2` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_apoderado_alumno (relación)
-- ------------------------------------------------------------
CREATE TABLE `tb_apoderado_alumno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apoderado_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `parentesco` varchar(50) NOT NULL,
  `es_titular` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_apoderado_alumno` (`apoderado_id`,`alumno_id`),
  KEY `alumno_id` (`alumno_id`),
  CONSTRAINT `tb_apoderado_alumno_ibfk_1` FOREIGN KEY (`apoderado_id`) REFERENCES `tb_apoderados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_apoderado_alumno_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `tb_alumnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_apoderados
-- ------------------------------------------------------------
CREATE TABLE `tb_apoderados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_apoderados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_apoderados_ibfk_2` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_asignaciones
-- ------------------------------------------------------------
CREATE TABLE `tb_asignaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `anio_academico` int(11) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asignacion` (`docente_id`,`curso_id`,`asignatura_id`,`anio_academico`),
  KEY `curso_id` (`curso_id`),
  KEY `asignatura_id` (`asignatura_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_asignaciones_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `tb_docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_asignaciones_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `tb_cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_asignaciones_ibfk_3` FOREIGN KEY (`asignatura_id`) REFERENCES `tb_asignaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_asignaciones_ibfk_4` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_asignaturas
-- ------------------------------------------------------------
CREATE TABLE `tb_asignaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asignatura_establecimiento` (`nombre`,`establecimiento_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_asignaturas_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_codigos_validacion
-- ------------------------------------------------------------
CREATE TABLE `tb_codigos_validacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `usado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_uso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `establecimiento_id` (`establecimiento_id`),
  KEY `usado_por` (`usado_por`),
  CONSTRAINT `tb_codigos_validacion_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`),
  CONSTRAINT `tb_codigos_validacion_ibfk_2` FOREIGN KEY (`usado_por`) REFERENCES `tb_administradores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_comunicado_curso
-- ------------------------------------------------------------
CREATE TABLE `tb_comunicado_curso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comunicado_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_comunicado_curso` (`comunicado_id`,`curso_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `tb_comunicado_curso_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `tb_comunicados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_comunicado_curso_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `tb_cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_comunicado_leido
-- ------------------------------------------------------------
CREATE TABLE `tb_comunicado_leido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comunicado_id` int(11) NOT NULL,
  `apoderado_id` int(11) NOT NULL,
  `fecha_lectura` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_comunicado_apoderado` (`comunicado_id`,`apoderado_id`),
  KEY `apoderado_id` (`apoderado_id`),
  CONSTRAINT `tb_comunicado_leido_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `tb_comunicados` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_comunicado_leido_ibfk_2` FOREIGN KEY (`apoderado_id`) REFERENCES `tb_apoderados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_comunicados
-- ------------------------------------------------------------
CREATE TABLE `tb_comunicados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('informativo','urgente','reunion','evento') NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `para_todos_cursos` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `remitente_id` (`remitente_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_comunicados_ibfk_1` FOREIGN KEY (`remitente_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_comunicados_ibfk_2` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_cursos
-- ------------------------------------------------------------
CREATE TABLE `tb_cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nivel` varchar(20) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_curso_establecimiento` (`codigo`,`establecimiento_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_cursos_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_docente_asignatura
-- ------------------------------------------------------------
CREATE TABLE `tb_docente_asignatura` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_docente_asignatura` (`docente_id`,`asignatura_id`),
  KEY `asignatura_id` (`asignatura_id`),
  CONSTRAINT `tb_docente_asignatura_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `tb_docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_docente_asignatura_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `tb_asignaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_docentes
-- ------------------------------------------------------------
CREATE TABLE `tb_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `telefono` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_docentes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_docentes_ibfk_2` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_establecimientos
-- ------------------------------------------------------------
CREATE TABLE `tb_establecimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `rbd` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_intentos_login_fallidos
-- ------------------------------------------------------------
CREATE TABLE `tb_intentos_login_fallidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `correo_ingresado` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `motivo_fallo` enum('correo_no_existe','password_incorrecta','cuenta_inactiva','otro') NOT NULL,
  `fecha_intento` datetime DEFAULT current_timestamp(),
  `revisado` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_intentos_registro_fallidos
-- ------------------------------------------------------------
CREATE TABLE `tb_intentos_registro_fallidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apoderado_rut` varchar(12) NOT NULL,
  `apoderado_nombres` varchar(100) NOT NULL,
  `apoderado_apellidos` varchar(100) NOT NULL,
  `apoderado_telefono` varchar(20) DEFAULT NULL,
  `apoderado_parentesco` varchar(50) DEFAULT NULL,
  `alumno_rut` varchar(12) NOT NULL,
  `alumno_nombres` varchar(100) NOT NULL,
  `alumno_apellidos` varchar(100) NOT NULL,
  `alumno_curso` varchar(50) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_intento` datetime DEFAULT current_timestamp(),
  `revisado` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_intentos_registro_fallidos_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_intentos_registro_fallidos_admin
-- ------------------------------------------------------------
CREATE TABLE `tb_intentos_registro_fallidos_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_rut` varchar(12) NOT NULL,
  `admin_nombres` varchar(100) NOT NULL,
  `admin_apellidos` varchar(100) NOT NULL,
  `admin_telefono` varchar(20) DEFAULT NULL,
  `admin_correo` varchar(255) DEFAULT NULL,
  `codigo_validacion_ingresado` varchar(50) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_intento` datetime DEFAULT current_timestamp(),
  `revisado` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_intentos_registro_fallidos_admin_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_intentos_registro_fallidos_docentes
-- ------------------------------------------------------------
CREATE TABLE `tb_intentos_registro_fallidos_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_rut` varchar(12) NOT NULL,
  `docente_nombres` varchar(100) NOT NULL,
  `docente_apellidos` varchar(100) NOT NULL,
  `docente_telefono` varchar(20) DEFAULT NULL,
  `docente_correo` varchar(255) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_intento` datetime DEFAULT current_timestamp(),
  `revisado` tinyint(1) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_intentos_registro_fallidos_docentes_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_log_actividades
-- ------------------------------------------------------------
CREATE TABLE `tb_log_actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_usuario` enum('administrador','docente','apoderado') NOT NULL,
  `nombre_usuario` varchar(200) NOT NULL,
  `accion` enum('agregar','editar','eliminar','enviar','login','logout') NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `entidad_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_tipo_usuario` (`tipo_usuario`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_establecimiento` (`establecimiento_id`),
  KEY `idx_fecha` (`fecha_hora`),
  CONSTRAINT `tb_log_actividades_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: tb_notas
-- ------------------------------------------------------------
CREATE TABLE `tb_notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `nota` decimal(3,1) DEFAULT NULL,
  `tipo_evaluacion` varchar(50) DEFAULT 'Evaluación',
  `numero_evaluacion` int(11) DEFAULT 1,
  `trimestre` int(11) NOT NULL,
  `anio_academico` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `es_pendiente` tinyint(1) DEFAULT 0,
  `fecha_evaluacion` date DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alumno_id` (`alumno_id`),
  KEY `asignatura_id` (`asignatura_id`),
  KEY `curso_id` (`curso_id`),
  KEY `docente_id` (`docente_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_notas_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `tb_alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_notas_ibfk_2` FOREIGN KEY (`asignatura_id`) REFERENCES `tb_asignaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_notas_ibfk_3` FOREIGN KEY (`curso_id`) REFERENCES `tb_cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_notas_ibfk_4` FOREIGN KEY (`docente_id`) REFERENCES `tb_docentes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tb_notas_ibfk_5` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`nota` IS NULL OR (`nota` >= 1.0 AND `nota` <= 7.0)),
  CONSTRAINT `CONSTRAINT_2` CHECK (`trimestre` >= 1 and `trimestre` <= 3)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_preregistro_administradores
-- ------------------------------------------------------------
CREATE TABLE `tb_preregistro_administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut_admin` varchar(12) NOT NULL,
  `nombre_admin` varchar(200) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_uso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_preregistro_admin` (`rut_admin`,`establecimiento_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_preregistro_administradores_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_preregistro_docentes
-- ------------------------------------------------------------
CREATE TABLE `tb_preregistro_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut_docente` varchar(12) NOT NULL,
  `nombre_docente` varchar(200) NOT NULL,
  `correo_docente` varchar(255) DEFAULT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_preregistro_docente` (`rut_docente`,`establecimiento_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_preregistro_docentes_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_preregistro_relaciones
-- ------------------------------------------------------------
CREATE TABLE `tb_preregistro_relaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut_apoderado` varchar(12) NOT NULL,
  `nombre_apoderado` varchar(200) NOT NULL,
  `correo_apoderado` varchar(255) DEFAULT NULL,
  `rut_alumno` varchar(12) NOT NULL,
  `nombre_alumno` varchar(200) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_uso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_preregistro` (`rut_apoderado`,`rut_alumno`,`establecimiento_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_preregistro_relaciones_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_sesiones
-- ------------------------------------------------------------
CREATE TABLE `tb_sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_usuario` enum('apoderado','docente','administrador') NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_login` datetime DEFAULT current_timestamp(),
  `fecha_logout` datetime DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_sesiones_ibfk_2` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_usuarios
-- ------------------------------------------------------------
CREATE TABLE `tb_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `tipo_usuario` enum('apoderado','docente','administrador') NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_usuarios_ibfk_1` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_claves_provisorias
-- ------------------------------------------------------------
CREATE TABLE `tb_claves_provisorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `clave_provisoria_hash` varchar(255) NOT NULL,
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `fecha_uso` datetime DEFAULT NULL,
  `email_enviado` tinyint(1) DEFAULT 0,
  `fecha_envio_email` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `tb_claves_provisorias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ------------------------------------------------------------
-- TABLA: tb_chat_conversaciones
-- Almacena las conversaciones entre usuarios
-- ------------------------------------------------------------
CREATE TABLE `tb_chat_conversaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario1_id` int(11) NOT NULL,
  `usuario2_id` int(11) NOT NULL,
  `establecimiento_id` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultima_actividad` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conversacion` (`usuario1_id`, `usuario2_id`, `establecimiento_id`),
  KEY `usuario2_id` (`usuario2_id`),
  KEY `establecimiento_id` (`establecimiento_id`),
  CONSTRAINT `tb_chat_conversaciones_ibfk_1` FOREIGN KEY (`usuario1_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_chat_conversaciones_ibfk_2` FOREIGN KEY (`usuario2_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_chat_conversaciones_ibfk_3` FOREIGN KEY (`establecimiento_id`) REFERENCES `tb_establecimientos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: tb_chat_mensajes
-- Almacena los mensajes del chat
-- ------------------------------------------------------------
CREATE TABLE `tb_chat_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` int(11) NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `fecha_lectura` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversacion_id` (`conversacion_id`),
  KEY `remitente_id` (`remitente_id`),
  KEY `idx_fecha_envio` (`fecha_envio`),
  CONSTRAINT `tb_chat_mensajes_ibfk_1` FOREIGN KEY (`conversacion_id`) REFERENCES `tb_chat_conversaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tb_chat_mensajes_ibfk_2` FOREIGN KEY (`remitente_id`) REFERENCES `tb_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


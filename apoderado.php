<?php
// ============================================================
// PANEL DE APODERADO - Portal Estudiantil
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

// Verificar que el usuario esté logueado y sea apoderado
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'apoderado') {
    header('Location: index.php');
    exit;
}

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];
$anio_academico = date('Y');

// Obtener email del usuario
$sql_usuario = "SELECT email FROM tb_usuarios WHERE id = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_usuario = $stmt->get_result();
$usuario_data = $result_usuario->fetch_assoc();
$email_usuario = $usuario_data ? $usuario_data['email'] : '';
$stmt->close();

// ============================================================
// OBTENER DATOS DEL APODERADO
// ============================================================
$sql_apoderado = "SELECT id, nombres, apellidos, rut, telefono, direccion
                  FROM tb_apoderados
                  WHERE usuario_id = ? AND activo = TRUE";
$stmt = $conn->prepare($sql_apoderado);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_apoderado = $stmt->get_result();
$apoderado = $result_apoderado->fetch_assoc();
$stmt->close();

if (!$apoderado) {
    die("Error: No se encontró información del apoderado");
}

$apoderado_id = $apoderado['id'];

// ============================================================
// OBTENER ALUMNOS DEL APODERADO (usando tabla relacional)
// ============================================================
$sql_alumnos = "SELECT a.id, a.nombres, a.apellidos, a.rut, a.fecha_nacimiento, a.sexo,
                       c.id as curso_id, c.nombre as curso_nombre,
                       aa.parentesco
                FROM tb_alumnos a
                INNER JOIN tb_apoderado_alumno aa ON a.id = aa.alumno_id
                INNER JOIN tb_cursos c ON a.curso_id = c.id
                WHERE aa.apoderado_id = ? AND a.activo = TRUE AND a.establecimiento_id = ?
                ORDER BY a.apellidos, a.nombres";
$stmt = $conn->prepare($sql_alumnos);
$stmt->bind_param("ii", $apoderado_id, $establecimiento_id);
$stmt->execute();
$result_alumnos = $stmt->get_result();
$alumnos = [];
$parentesco_alumno_seleccionado = 'Apoderado';
while ($row = $result_alumnos->fetch_assoc()) {
    $alumnos[] = $row;
}
$stmt->close();

// Si no hay alumnos asociados
if (empty($alumnos)) {
    $alumno_seleccionado = null;
    $notas_trimestre1 = [];
    $notas_trimestre2 = [];
    $notas_trimestre3 = [];
} else {
    // Seleccionar el primer alumno o el que venga por GET
    $alumno_id_seleccionado = isset($_GET['alumno_id']) ? intval($_GET['alumno_id']) : $alumnos[0]['id'];

    // Verificar que el alumno pertenece al apoderado
    $alumno_seleccionado = null;
    foreach ($alumnos as $al) {
        if ($al['id'] == $alumno_id_seleccionado) {
            $alumno_seleccionado = $al;
            break;
        }
    }

    if (!$alumno_seleccionado) {
        $alumno_seleccionado = $alumnos[0];
        $alumno_id_seleccionado = $alumno_seleccionado['id'];
    }

    // Obtener el parentesco del alumno seleccionado
    $parentesco_alumno_seleccionado = $alumno_seleccionado['parentesco'] ?? 'Apoderado';

    // ============================================================
    // OBTENER NOTAS DEL ALUMNO SELECCIONADO
    // ============================================================
    // Verificar si existe el campo es_pendiente
    $campo_pendiente_existe = false;
    $check_col = $conn->query("SHOW COLUMNS FROM tb_notas LIKE 'es_pendiente'");
    if ($check_col && $check_col->num_rows > 0) {
        $campo_pendiente_existe = true;
    }

    if ($campo_pendiente_existe) {
        $sql_notas = "SELECT n.id, n.nota, n.numero_evaluacion, n.trimestre, n.comentario,
                             n.fecha_evaluacion, n.es_pendiente,
                             a.id as asignatura_id, a.nombre as asignatura_nombre
                      FROM tb_notas n
                      INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
                      WHERE n.alumno_id = ? AND n.anio_academico = ? AND n.establecimiento_id = ?
                      ORDER BY a.nombre, n.trimestre, n.numero_evaluacion";
    } else {
        $sql_notas = "SELECT n.id, n.nota, n.numero_evaluacion, n.trimestre, n.comentario,
                             n.fecha_evaluacion, 0 as es_pendiente,
                             a.id as asignatura_id, a.nombre as asignatura_nombre
                      FROM tb_notas n
                      INNER JOIN tb_asignaturas a ON n.asignatura_id = a.id
                      WHERE n.alumno_id = ? AND n.anio_academico = ? AND n.establecimiento_id = ?
                      ORDER BY a.nombre, n.trimestre, n.numero_evaluacion";
    }
    $stmt = $conn->prepare($sql_notas);
    $stmt->bind_param("iii", $alumno_id_seleccionado, $anio_academico, $establecimiento_id);
    $stmt->execute();
    $result_notas = $stmt->get_result();

    // Organizar notas por asignatura y trimestre
    $notas_por_asignatura = [];
    // También guardar notas por mes para el gráfico de evolución
    $notas_por_mes = [];

    while ($nota = $result_notas->fetch_assoc()) {
        $asig_nombre = $nota['asignatura_nombre'];
        $trimestre = $nota['trimestre'];
        $numero = $nota['numero_evaluacion'];
        $fecha_eval = $nota['fecha_evaluacion'];
        $es_pendiente = isset($nota['es_pendiente']) ? (bool) $nota['es_pendiente'] : false;

        if (!isset($notas_por_asignatura[$asig_nombre])) {
            $notas_por_asignatura[$asig_nombre] = [
                'asignatura' => $asig_nombre,
                'trimestre1' => array_fill(0, 8, null),
                'trimestre2' => array_fill(0, 8, null),
                'trimestre3' => array_fill(0, 8, null)
            ];
        }

        $key_trimestre = 'trimestre' . $trimestre;
        if ($numero >= 1 && $numero <= 8) {
            $notas_por_asignatura[$asig_nombre][$key_trimestre][$numero - 1] = [
                'valor' => $es_pendiente ? 'PEND' : floatval($nota['nota']),
                'es_pendiente' => $es_pendiente,
                'comentario' => $nota['comentario'] ?? ''
            ];
        }

        // Guardar nota por mes para gráfico de evolución (solo si NO es pendiente)
        if ($fecha_eval && !$es_pendiente) {
            $mes = intval(date('n', strtotime($fecha_eval))); // 1-12
            if (!isset($notas_por_mes[$asig_nombre])) {
                $notas_por_mes[$asig_nombre] = [];
            }
            if (!isset($notas_por_mes[$asig_nombre][$mes])) {
                $notas_por_mes[$asig_nombre][$mes] = [];
            }
            $notas_por_mes[$asig_nombre][$mes][] = floatval($nota['nota']);
        }
    }
    $stmt->close();

    // Calcular promedio por mes para cada asignatura
    $promedios_por_mes = [];
    foreach ($notas_por_mes as $asig => $meses) {
        $promedios_por_mes[$asig] = [];
        for ($m = 1; $m <= 12; $m++) {
            if (isset($meses[$m]) && count($meses[$m]) > 0) {
                $promedios_por_mes[$asig][$m] = round(array_sum($meses[$m]) / count($meses[$m]), 1);
            } else {
                $promedios_por_mes[$asig][$m] = null;
            }
        }
    }

    // Convertir a formato para JavaScript (array indexado por asignatura)
    // CRÍTICO: Debe tener el mismo orden y cantidad que $notas_por_asignatura para que los índices de JS coincidan
    $notas_mensuales = [];
    $asignaturas_list = array_keys($notas_por_asignatura);

    foreach ($asignaturas_list as $asig) {
        // Por defecto, 12 meses vacíos
        $meses_data = array_fill(0, 12, null);

        // Si existen promedios calculados para esta asignatura, usarlos
        if (isset($promedios_por_mes[$asig])) {
            $meses_data = array_values($promedios_por_mes[$asig]);
        }

        $notas_mensuales[] = [
            'asignatura' => $asig,
            'meses' => $meses_data
        ];
    }

    // Convertir a arrays para JavaScript
    $notas_trimestre1 = [];
    $notas_trimestre2 = [];
    $notas_trimestre3 = [];

    foreach ($notas_por_asignatura as $asig_nombre => $data) {
        $notas_trimestre1[] = [
            'asignatura' => $asig_nombre,
            'notas' => $data['trimestre1']
        ];
        $notas_trimestre2[] = [
            'asignatura' => $asig_nombre,
            'notas' => $data['trimestre2']
        ];
        $notas_trimestre3[] = [
            'asignatura' => $asig_nombre,
            'notas' => $data['trimestre3']
        ];
    }
}

// ============================================================
// OBTENER COMUNICADOS
// ============================================================
// Primero obtener los cursos de los alumnos del apoderado
$cursos_apoderado = [];
foreach ($alumnos as $al) {
    if (!in_array($al['curso_id'], $cursos_apoderado)) {
        $cursos_apoderado[] = $al['curso_id'];
    }
}

$comunicados = [];
if (!empty($cursos_apoderado)) {
    $placeholders = implode(',', array_fill(0, count($cursos_apoderado), '?'));
    $sql_comunicados = "SELECT DISTINCT c.id, c.tipo, c.titulo, c.mensaje as contenido,
                               DATE_FORMAT(c.fecha_envio, '%Y-%m-%d') as fecha,
                               DATE_FORMAT(c.fecha_envio, '%H:%i') as hora,
                               CASE WHEN cl.id IS NOT NULL THEN 1 ELSE 0 END as leido
                        FROM tb_comunicados c
                        LEFT JOIN tb_comunicado_leido cl ON c.id = cl.comunicado_id AND cl.apoderado_id = ?
                        LEFT JOIN tb_comunicado_curso cc ON c.id = cc.comunicado_id
                        WHERE c.establecimiento_id = ?
                        AND c.activo = TRUE
                        AND (c.para_todos_cursos = TRUE OR cc.curso_id IN ($placeholders))
                        ORDER BY c.fecha_envio DESC
                        LIMIT 50";

    $params = array_merge([$apoderado_id, $establecimiento_id], $cursos_apoderado);
    $types = "ii" . str_repeat("i", count($cursos_apoderado));

    $stmt = $conn->prepare($sql_comunicados);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result_comunicados = $stmt->get_result();

    while ($com = $result_comunicados->fetch_assoc()) {
        $comunicados[] = [
            'id' => $com['id'],
            'tipo' => $com['tipo'],
            'titulo' => $com['titulo'],
            'contenido' => $com['contenido'],
            'fecha' => $com['fecha'],
            'hora' => $com['hora'],
            'leido' => $com['leido'] == 1
        ];
    }
    $stmt->close();
}

$conn->close();

// Preparar datos para JavaScript
$datos_pupilo = $alumno_seleccionado ? [
    'id' => $alumno_seleccionado['id'],
    'nombres' => $alumno_seleccionado['nombres'],
    'apellidos' => $alumno_seleccionado['apellidos'],
    'rut' => $alumno_seleccionado['rut'],
    'curso' => $alumno_seleccionado['curso_nombre'],
    'curso_id' => $alumno_seleccionado['curso_id']
] : null;

$datos_apoderado = [
    'nombre' => $apoderado['nombres'] . ' ' . $apoderado['apellidos'],
    'parentesco' => $parentesco_alumno_seleccionado,
    'correo' => $email_usuario,
    'telefono' => $apoderado['telefono'] ?? ''
];

$nombre_apoderado = $apoderado['nombres'] . ' ' . $apoderado['apellidos'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Apoderado - Portal Estudiantil</title>
    <link rel="stylesheet" href="css_colegio/apoderado.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="app-container">
        <!-- Header -->
        <header class="main-header">
            <div class="header-content">
                <div class="brand">
                    <div class="logo">
                        <span class="logo-icon">E</span>
                    </div>
                    <div class="brand-text">
                        <h1>Portal Estudiantil</h1>
                        <span class="user-type">Panel de Apoderado</span>
                    </div>
                </div>
                <div class="header-user">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_apoderado); ?></span>
                        <span class="user-role">Apoderado</span>
                    </div>
                    <button class="btn-logout" onclick="cerrarSesion()" title="Cerrar Sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="main-content">
            <div class="content-container">
                <!-- Información del Pupilo (Card superior) -->
                <div class="pupilo-card">
                    <div class="pupilo-left">
                        <div class="pupilo-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="pupilo-info">
                            <h2 id="nombrePupilo">
                                <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['nombres'] . ' ' . $alumno_seleccionado['apellidos']) : 'Sin alumno asociado'; ?>
                            </h2>
                            <p id="cursoPupilo">
                                <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['curso_nombre']) : '-'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="pupilo-actions">
                        <!-- Selector de alumnos (aparece si hay más de uno) -->
                        <div class="selector-alumnos-container" id="selectorAlumnosContainer"
                            style="display: <?php echo count($alumnos) > 1 ? 'block' : 'none'; ?>;">
                            <button type="button" class="btn-cambiar-alumno" onclick="toggleSelectorAlumnos()"
                                id="btnCambiarAlumno">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>Cambiar</span>
                                <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="dropdown-alumnos" id="dropdownAlumnos">
                                <div class="dropdown-alumnos-header">
                                    <span>Seleccionar Alumno</span>
                                </div>
                                <div class="dropdown-alumnos-lista" id="listaAlumnos">
                                    <?php foreach ($alumnos as $al):
                                        $initials = strtoupper(substr($al['nombres'], 0, 1) . substr($al['apellidos'], 0, 1));
                                        ?>
                                        <div class="dropdown-alumno-item <?php echo $al['id'] == $alumno_seleccionado['id'] ? 'active' : ''; ?>"
                                            onclick="seleccionarAlumno(<?php echo $al['id']; ?>)">
                                            <div class="dropdown-alumno-avatar"><?php echo $initials; ?></div>
                                            <div class="dropdown-alumno-info">
                                                <div class="dropdown-alumno-nombre">
                                                    <?php echo htmlspecialchars($al['nombres'] . ' ' . $al['apellidos']); ?>
                                                </div>
                                                <div class="dropdown-alumno-curso">
                                                    <?php echo htmlspecialchars($al['curso_nombre']); ?>
                                                </div>
                                            </div>
                                            <div class="dropdown-alumno-check">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-agregar-alumno" onclick="abrirModalAgregarAlumno()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Agregar Alumno</span>
                        </button>
                    </div>
                </div>

                <!-- Sistema de Pestañas -->
                <div class="tabs-container">
                    <nav class="tabs-nav">
                        <button type="button" class="tab-btn active" data-tab="informacion">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Información del Alumno</span>
                        </button>
                        <button type="button" class="tab-btn" data-tab="notas">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                            <span>Notas</span>
                        </button>
                        <button type="button" class="tab-btn" data-tab="comunicados" id="tabComunicados">
                            <div class="tab-icon-container">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span class="notification-badge" id="notificationBadge"></span>
                            </div>
                            <span>Comunicados</span>
                        </button>
                        <button type="button" class="tab-btn" data-tab="progreso">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                            </svg>
                            <span>Progreso</span>
                        </button>
                    </nav>

                    <!-- Contenido de las Pestañas -->
                    <div class="tabs-content">
                        <!-- Pestaña: Información del Alumno -->
                        <div id="informacion" class="tab-panel active">
                            <div class="info-card">
                                <h3>Datos Personales</h3>
                                <div class="info-grid info-grid-4">
                                    <div class="info-item">
                                        <label>Nombres</label>
                                        <p id="infoNombres">
                                            <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['nombres']) : '-'; ?>
                                        </p>
                                    </div>
                                    <div class="info-item">
                                        <label>Apellidos</label>
                                        <p id="infoApellidos">
                                            <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['apellidos']) : '-'; ?>
                                        </p>
                                    </div>
                                    <div class="info-item">
                                        <label>RUT</label>
                                        <p id="infoRut">
                                            <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['rut']) : '-'; ?>
                                        </p>
                                    </div>
                                    <div class="info-item">
                                        <label>Curso</label>
                                        <p id="infoCurso">
                                            <?php echo $alumno_seleccionado ? htmlspecialchars($alumno_seleccionado['curso_nombre']) : '-'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="info-card">
                                <h3>Datos del Apoderado</h3>
                                <div class="info-grid info-grid-4 info-grid-apoderado">
                                    <div class="info-item item-nombre-apoderado">
                                        <label>Nombre Apoderado</label>
                                        <p id="infoApoderado">
                                            <?php echo htmlspecialchars($datos_apoderado['nombre']); ?>
                                        </p>
                                    </div>
                                    <div class="info-item item-parentesco">
                                        <label>Parentesco</label>
                                        <p id="infoParentesco">
                                            <?php echo htmlspecialchars($datos_apoderado['parentesco']); ?>
                                        </p>
                                    </div>
                                    <div class="info-item item-telefono">
                                        <label>Teléfono</label>
                                        <p id="infoTelefono">
                                            <?php echo htmlspecialchars($datos_apoderado['telefono'] ?: '-'); ?>
                                        </p>
                                    </div>
                                    <div class="info-item item-correo">
                                        <label>Correo</label>
                                        <p id="infoCorreo">
                                            <?php echo htmlspecialchars($datos_apoderado['correo'] ?: '-'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña: Notas -->
                        <div id="notas" class="tab-panel">
                            <div class="notas-header">
                                <h3>Calificaciones del Año Académico <?php echo $anio_academico; ?></h3>
                            </div>

                            <!-- Sub-pestañas de períodos -->
                            <div class="sub-tabs-nav">
                                <button type="button" class="sub-tab-btn active" data-subtab="todasNotas">Todas</button>
                                <button type="button" class="sub-tab-btn" data-subtab="trimestre1">1er
                                    Trimestre</button>
                                <button type="button" class="sub-tab-btn" data-subtab="trimestre2">2do
                                    Trimestre</button>
                                <button type="button" class="sub-tab-btn" data-subtab="trimestre3">3er
                                    Trimestre</button>
                                <button type="button" class="sub-tab-btn" data-subtab="promedios">Promedios
                                    Finales</button>
                            </div>

                            <!-- Contenido: Todas las Notas -->
                            <div id="todasNotas" class="sub-tab-panel active">
                                <div class="table-container tabla-todas-notas-container">
                                    <table class="notas-table tabla-todas-notas">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="th-asignatura">Asignatura</th>
                                                <th colspan="9" class="th-trimestre-header">Trimestre 1</th>
                                                <th colspan="9" class="th-trimestre-header">Trimestre 2</th>
                                                <th colspan="9" class="th-trimestre-header">Trimestre 3</th>
                                                <th rowspan="2" class="th-final">Prom. Final</th>
                                                <th rowspan="2" class="th-estado">Estado</th>
                                            </tr>
                                            <tr>
                                                <th class="th-nota">N1</th>
                                                <th class="th-nota">N2</th>
                                                <th class="th-nota">N3</th>
                                                <th class="th-nota">N4</th>
                                                <th class="th-nota">N5</th>
                                                <th class="th-nota">N6</th>
                                                <th class="th-nota">N7</th>
                                                <th class="th-nota">N8</th>
                                                <th class="th-prom">Prom</th>
                                                <th class="th-nota">N1</th>
                                                <th class="th-nota">N2</th>
                                                <th class="th-nota">N3</th>
                                                <th class="th-nota">N4</th>
                                                <th class="th-nota">N5</th>
                                                <th class="th-nota">N6</th>
                                                <th class="th-nota">N7</th>
                                                <th class="th-nota">N8</th>
                                                <th class="th-prom">Prom</th>
                                                <th class="th-nota">N1</th>
                                                <th class="th-nota">N2</th>
                                                <th class="th-nota">N3</th>
                                                <th class="th-nota">N4</th>
                                                <th class="th-nota">N5</th>
                                                <th class="th-nota">N6</th>
                                                <th class="th-nota">N7</th>
                                                <th class="th-nota">N8</th>
                                                <th class="th-prom">Prom</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyTodasNotas">
                                            <tr>
                                                <td colspan="31" class="text-center text-muted">Cargando notas...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="promedio-general">
                                    <span>Promedio Final Anual:</span>
                                    <span id="promedioFinalTodas" class="promedio-valor">-</span>
                                    <span class="estado-final" id="estadoFinalTodas">-</span>
                                </div>
                            </div>

                            <!-- Contenido: Primer Trimestre -->
                            <div id="trimestre1" class="sub-tab-panel">
                                <div class="table-container">
                                    <table class="notas-table" id="tablaNotas1">
                                        <thead id="theadNotas1">
                                        </thead>
                                        <tbody id="tbodyNotas1">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="promedio-general">
                                    <span>Promedio 1er Trimestre:</span>
                                    <span id="promedioTrimestre1" class="promedio-valor">-</span>
                                </div>
                            </div>

                            <!-- Contenido: Segundo Trimestre -->
                            <div id="trimestre2" class="sub-tab-panel">
                                <div class="table-container">
                                    <table class="notas-table" id="tablaNotas2">
                                        <thead id="theadNotas2">
                                        </thead>
                                        <tbody id="tbodyNotas2">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="promedio-general">
                                    <span>Promedio 2do Trimestre:</span>
                                    <span id="promedioTrimestre2" class="promedio-valor">-</span>
                                </div>
                            </div>

                            <!-- Contenido: Tercer Trimestre -->
                            <div id="trimestre3" class="sub-tab-panel">
                                <div class="table-container">
                                    <table class="notas-table" id="tablaNotas3">
                                        <thead id="theadNotas3">
                                        </thead>
                                        <tbody id="tbodyNotas3">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="promedio-general">
                                    <span>Promedio 3er Trimestre:</span>
                                    <span id="promedioTrimestre3" class="promedio-valor">-</span>
                                </div>
                            </div>

                            <!-- Contenido: Promedios Finales -->
                            <div id="promedios" class="sub-tab-panel">
                                <div class="table-container">
                                    <table class="notas-table notas-table-promedios">
                                        <thead>
                                            <tr>
                                                <th class="asignatura-col">Asignatura</th>
                                                <th>1er Trim.</th>
                                                <th>2do Trim.</th>
                                                <th>3er Trim.</th>
                                                <th class="promedio-col">Nota Final</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyPromedios">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="promedio-general">
                                    <span>Promedio Final Anual:</span>
                                    <span id="promedioFinal" class="promedio-valor">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña: Comunicados -->
                        <div id="comunicados" class="tab-panel">
                            <!-- Filtros de Comunicados -->
                            <div class="filtros-comunicados">
                                <div class="filtro-grupo">
                                    <label for="filtroFechaDesde">Desde</label>
                                    <input type="date" id="filtroFechaDesde" class="form-control">
                                </div>
                                <div class="filtro-grupo">
                                    <label for="filtroFechaHasta">Hasta</label>
                                    <input type="date" id="filtroFechaHasta" class="form-control">
                                </div>
                                <div class="filtro-grupo">
                                    <label for="filtroTipoComunicado">Tipo</label>
                                    <select id="filtroTipoComunicado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="urgente">Urgente</option>
                                        <option value="evento">Evento</option>
                                        <option value="informativo">Informativo</option>
                                    </select>
                                </div>
                                <div class="filtro-grupo filtro-acciones">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="limpiarFiltrosComunicados()">Limpiar</button>
                                </div>
                            </div>

                            <div class="comunicados-container">
                                <!-- Columna Izquierda: Recientes -->
                                <div class="comunicados-columna comunicados-recientes">
                                    <div class="comunicados-header">
                                        <h3>Comunicados Recientes</h3>
                                    </div>
                                    <div class="comunicados-lista" id="listaComunicadosRecientes">
                                        <!-- Se genera dinámicamente -->
                                    </div>
                                </div>
                                <!-- Columna Derecha: Anteriores -->
                                <div class="comunicados-columna comunicados-anteriores">
                                    <div class="comunicados-header">
                                        <h3>Comunicados Anteriores</h3>
                                    </div>
                                    <div class="comunicados-lista comunicados-scroll" id="listaComunicadosAnteriores">
                                        <!-- Se genera dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña: Progreso -->
                        <div id="progreso" class="tab-panel">
                            <div class="progreso-container">
                                <h3>Progreso Académico</h3>
                                <p class="progreso-descripcion">Seguimiento del rendimiento académico del alumno.</p>

                                <!-- Layout de 3 columnas -->
                                <div class="progreso-grid-3col">
                                    <!-- Columna Izquierda: KPIs -->
                                    <div class="progreso-col-kpis">
                                        <!-- Promedio más alto -->
                                        <div class="kpi-card kpi-alto">
                                            <div class="kpi-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg>
                                            </div>
                                            <div class="kpi-content">
                                                <span class="kpi-label">Promedio Más Alto</span>
                                                <span class="kpi-value" id="kpiMejor1">-</span>
                                                <span class="kpi-asignatura" id="kpiMejorAsig1">-</span>
                                            </div>
                                        </div>
                                        <!-- Promedio más bajo -->
                                        <div class="kpi-card kpi-bajo">
                                            <div class="kpi-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                                                    <polyline points="17 18 23 18 23 12"></polyline>
                                                </svg>
                                            </div>
                                            <div class="kpi-content">
                                                <span class="kpi-label">Promedio Más Bajo</span>
                                                <span class="kpi-value" id="kpiBajo1">-</span>
                                                <span class="kpi-asignatura" id="kpiBajoAsig1">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Columna Central: Gráfico Lineal -->
                                    <div class="progreso-col-lineal">
                                        <div class="grafico-card">
                                            <h4>Evolución del Rendimiento</h4>
                                            <div class="selector-asignatura">
                                                <label for="selectAsignatura">Asignatura:</label>
                                                <select id="selectAsignatura" onchange="actualizarGraficoAsignatura()">
                                                    <!-- Opciones generadas dinámicamente -->
                                                </select>
                                            </div>
                                            <div class="grafico-container">
                                                <canvas id="graficoLineal"></canvas>
                                            </div>
                                            <p class="grafico-glosa">* Promedio mensual de notas registradas.</p>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha: Gráfico de Barras -->
                                    <div class="progreso-col-barras">
                                        <div class="grafico-card">
                                            <h4>Promedios por Asignatura</h4>
                                            <div class="grafico-container">
                                                <canvas id="graficoBarras"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="main-footer">
            <p>Portal Estudiantil &copy; <?php echo date('Y'); ?> | Sistema de Gestión Académica</p>
            <p class="footer-brand">Desarrollado por CH SYSTEM</p>
        </footer>
    </div>

    <!-- Modal Agregar Alumno -->
    <div id="modalAgregarAlumno" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Vincular Alumno</h3>
                <button type="button" class="modal-close" onclick="cerrarModalAgregarAlumno()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px; color: #64748b; font-size: 14px;">
                    Ingrese el RUT del alumno que desea vincular a su cuenta. El alumno debe estar previamente
                    registrado en el sistema.
                </p>
                <form id="formAgregarAlumno">
                    <div class="modal-form-row">
                        <div class="modal-form-group">
                            <label for="nuevoRutAlumno">RUT del Alumno</label>
                            <input type="text" id="nuevoRutAlumno" class="form-control" placeholder="Ej: 24.567.890-1"
                                required>
                        </div>
                        <div class="modal-form-group">
                            <label for="nuevoParentesco">Parentesco</label>
                            <select id="nuevoParentesco" class="form-control" required>
                                <option value="Padre">Padre</option>
                                <option value="Madre">Madre</option>
                                <option value="Tutor">Tutor Legal</option>
                                <option value="Abuelo/a">Abuelo/a</option>
                                <option value="Tío/a">Tío/a</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalAgregarAlumno()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevoAlumno()">Vincular Alumno</button>
            </div>
        </div>
    </div>

    <!-- Modal Comentario Nota -->
    <div id="modalComentario" class="modal-overlay modal-comentario-overlay">
        <div class="modal-comentario-container">
            <div class="modal-comentario-header">
                <div class="modal-comentario-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="modal-comentario-titulo">
                    <h3>Comentario del Docente</h3>
                    <p id="modalComentarioAsignatura">-</p>
                </div>
                <button type="button" class="modal-comentario-close" onclick="cerrarModalComentario()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-comentario-body">
                <div class="modal-comentario-nota-info">
                    <span class="modal-comentario-label">Nota:</span>
                    <span class="modal-comentario-nota" id="modalComentarioValor">-</span>
                </div>
                <div class="modal-comentario-contenido">
                    <p id="modalComentarioTexto">No hay comentario</p>
                </div>
            </div>
            <div class="modal-comentario-footer">
                <button type="button" class="btn btn-primary" onclick="cerrarModalComentario()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Datos desde PHP para JavaScript -->
    <script>
        // Datos del pupilo
        const datosPupilo = <?php echo json_encode($datos_pupilo); ?>;

        // Datos del apoderado
        const datosApoderado = <?php echo json_encode($datos_apoderado); ?>;

        // Notas por trimestre
        const notasTrimestre1 = <?php echo json_encode($notas_trimestre1); ?>;
        const notasTrimestre2 = <?php echo json_encode($notas_trimestre2); ?>;
        const notasTrimestre3 = <?php echo json_encode($notas_trimestre3); ?>;

        // Notas mensuales para gráfico de evolución
        const notasMensuales = <?php echo json_encode($notas_mensuales ?? []); ?>;

        // Comunicados
        const comunicadosData = <?php echo json_encode($comunicados); ?>;

        // Alumnos del apoderado
        const alumnosDelApoderado = <?php echo json_encode($alumnos); ?>;

        // Función para cerrar sesión
        async function cerrarSesion() {
            try {
                await fetch('api/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
            }
            window.location.href = 'index.php';
        }

        // Función para seleccionar otro alumno
        function seleccionarAlumno(alumnoId) {
            window.location.href = 'apoderado.php?alumno_id=' + alumnoId;
        }

        // Toggle del selector de alumnos
        function toggleSelectorAlumnos() {
            const dropdown = document.getElementById('dropdownAlumnos');
            dropdown.classList.toggle('active');
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function (e) {
            const container = document.getElementById('selectorAlumnosContainer');
            if (container && !container.contains(e.target)) {
                const dropdown = document.getElementById('dropdownAlumnos');
                if (dropdown) dropdown.classList.remove('active');
            }
        });

        // Modal agregar alumno
        function abrirModalAgregarAlumno() {
            document.getElementById('modalAgregarAlumno').classList.add('active');
        }

        function cerrarModalAgregarAlumno() {
            document.getElementById('modalAgregarAlumno').classList.remove('active');
            document.getElementById('formAgregarAlumno').reset();
        }

        async function guardarNuevoAlumno() {
            const rut = document.getElementById('nuevoRutAlumno').value.trim();
            const parentesco = document.getElementById('nuevoParentesco').value;

            if (!rut) {
                alert('Debe ingresar el RUT del alumno');
                return;
            }

            try {
                const response = await fetch('api/agregar_alumno_apoderado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        rut: rut,
                        parentesco: parentesco
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al servidor');
            }
        }

        // Limpiar filtros de comunicados
        function limpiarFiltrosComunicados() {
            document.getElementById('filtroFechaDesde').value = '';
            document.getElementById('filtroFechaHasta').value = '';
            document.getElementById('filtroTipoComunicado').value = '';
            if (typeof cargarComunicados === 'function') {
                cargarComunicados();
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js_colegio/apoderado.js"></script>
    <?php include 'componentes/barra_presentacion.php'; ?>
</body>

</html>
<?php
// ============================================================
// COLEGIO.PHP - Panel de Administración del Establecimiento
// ============================================================

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar que sea administrador
if ($_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

// Configuración de conexión a la base de datos
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password = "vpsroot123";
$database = "portal_estudiantil";

// Conectar a la base de datos
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Obtener datos del usuario logueado
$usuario_id = $_SESSION['usuario_id'];
$establecimiento_id = $_SESSION['establecimiento_id'];
$nombres = $_SESSION['nombres'];
$apellidos = $_SESSION['apellidos'];

// Obtener nombre del establecimiento
$sql_establecimiento = "SELECT nombre FROM tb_establecimientos WHERE id = ?";
$stmt = $conn->prepare($sql_establecimiento);
$stmt->bind_param("i", $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
$establecimiento = $result->fetch_assoc();
$nombre_establecimiento = $establecimiento['nombre'] ?? 'Establecimiento';
$stmt->close();

// ============================================================
// CARGAR DATOS PARA EL PANEL DE ADMINISTRACIÓN
// ============================================================

// 1. Obtener cursos
$cursos = [];
$sql_cursos = "SELECT id, nombre, nivel FROM tb_cursos WHERE establecimiento_id = ? AND activo = TRUE ORDER BY nivel, nombre";
$stmt = $conn->prepare($sql_cursos);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }
    $stmt->close();
}

// 2. Obtener asignaturas
$asignaturas = [];
$sql_asignaturas = "SELECT id, nombre, codigo FROM tb_asignaturas WHERE establecimiento_id = ? AND activo = TRUE ORDER BY nombre";
$stmt = $conn->prepare($sql_asignaturas);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $asignaturas[] = $row;
    }
    $stmt->close();
}

// 3. Obtener docentes
$docentes = [];
$sql_docentes = "SELECT d.id, d.nombres, d.apellidos, d.rut, u.email,
                 CONCAT(d.apellidos, ', ', d.nombres) as nombre_completo
                 FROM tb_docentes d
                 LEFT JOIN tb_usuarios u ON d.usuario_id = u.id
                 WHERE d.establecimiento_id = ? AND d.activo = TRUE
                 ORDER BY d.apellidos, d.nombres";
$stmt = $conn->prepare($sql_docentes);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $docentes[] = $row;
    }
    $stmt->close();
}

// 4. Obtener especialidades de docentes (desde tb_docente_asignatura)
$docente_especialidades = [];
$sql_esp = "SELECT de.docente_id, a.id as asignatura_id, a.nombre
            FROM tb_docente_asignatura de
            INNER JOIN tb_asignaturas a ON de.asignatura_id = a.id
            WHERE a.establecimiento_id = ?
            ORDER BY a.nombre";
$stmt = $conn->prepare($sql_esp);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($docente_especialidades[$row['docente_id']])) {
            $docente_especialidades[$row['docente_id']] = [];
        }
        $docente_especialidades[$row['docente_id']][] = [
            'id' => $row['asignatura_id'],
            'nombre' => $row['nombre']
        ];
    }
    $stmt->close();
}

// 5. Obtener alumnos por curso
$alumnos_por_curso = [];
$sql_alumnos = "SELECT a.id, a.nombres, a.apellidos, a.rut, a.fecha_nacimiento, a.sexo,
                CONCAT(a.apellidos, ', ', a.nombres) as nombre_completo,
                c.nombre as curso_nombre
                FROM tb_alumnos a
                INNER JOIN tb_cursos c ON a.curso_id = c.id
                WHERE a.establecimiento_id = ? AND a.activo = TRUE
                ORDER BY c.nombre, a.apellidos, a.nombres";
$stmt = $conn->prepare($sql_alumnos);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $curso_nombre = $row['curso_nombre'];
        if (!isset($alumnos_por_curso[$curso_nombre])) {
            $alumnos_por_curso[$curso_nombre] = [];
        }
        $alumnos_por_curso[$curso_nombre][] = $row;
    }
    $stmt->close();
}

// 6. Obtener asignaciones (docente-curso-asignatura)
$asignaciones = [];
$sql_asig = "SELECT a.id, a.docente_id, a.curso_id, a.asignatura_id,
             CONCAT(d.apellidos, ', ', d.nombres) as docente,
             c.nombre as curso,
             asig.nombre as asignatura
             FROM tb_asignaciones a
             INNER JOIN tb_docentes d ON a.docente_id = d.id
             INNER JOIN tb_cursos c ON a.curso_id = c.id
             INNER JOIN tb_asignaturas asig ON a.asignatura_id = asig.id
             WHERE a.establecimiento_id = ? AND a.activo = TRUE
             ORDER BY d.apellidos, c.nombre";
$stmt = $conn->prepare($sql_asig);
if ($stmt) {
    $stmt->bind_param("i", $establecimiento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $asignaciones[] = $row;
    }
    $stmt->close();
}

// 7. Obtener trimestres
$trimestres = [
    ['id' => 1, 'nombre' => 'Primer Trimestre'],
    ['id' => 2, 'nombre' => 'Segundo Trimestre'],
    ['id' => 3, 'nombre' => 'Tercer Trimestre']
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Académica</title>
    <link rel="stylesheet" href="css_colegio/colegio.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js_colegio/session_timeout.js"></script>
    <style>
        /* ===== HEADER - Por defecto desktop ===== */
        .logout-icon {
            display: none;
        }
        .logout-text {
            display: inline;
        }

        /* ===== RESPONSIVE TABLET 700px - 900px ===== */
        @media (max-width: 900px) and (min-width: 700px) {
            .tab-btn {
                padding: 6px 3px !important;
                font-size: 11px !important;
            }
            /* Botón cerrar sesión: solo icono */
            .logout-text {
                display: none !important;
            }
            .logout-icon {
                display: block !important;
            }
            .btn-logout {
                padding: 6px !important;
                min-width: auto !important;
            }
            /* Títulos Datos del Apoderado y Apoderado ya Existe */
            .titulo-apoderado-bar {
                font-size: 9px !important;
            }
            #seccionDatosApoderado,
            #seccionApoderadoExiste {
                padding: 8px 12px !important;
            }
            /* Botones Agregar Alumno/Docente y Limpiar */
            #formGestionAlumno .form-actions,
            #formGestionDocente .form-actions {
                justify-content: center !important;
            }
            #formGestionAlumno .form-actions .btn,
            #formGestionDocente .form-actions .btn {
                font-size: 9px !important;
            }
            /* Labels e inputs de todos los formularios */
            .form-group label {
                font-size: 11px !important;
            }
            .form-control {
                font-size: 11px !important;
            }
            /* Especialidades en 3 columnas */
            .checkbox-4-columnas {
                grid-template-columns: repeat(3, 1fr) !important;
            }
            .checkbox-4-columnas .checkbox-item label {
                font-size: 9px !important;
            }
            /* Tabla listado alumnos - encabezados y datos */
            #tablaAlumnos th {
                font-size: 9px !important;
            }
            #tablaAlumnos td {
                font-size: 9px !important;
            }
            /* Sugerencias autocomplete alumnos */
            #sugerenciasAlumnos .sugerencia-item {
                font-size: 9px !important;
            }
            /* Sugerencias autocomplete docentes y asignaturas */
            #sugerenciasDocentes .sugerencia-item,
            #sugerenciasAsignaturas .sugerencia-item {
                font-size: 9px !important;
            }
            /* Tabla listado docentes - encabezados y datos */
            #tablaDocentes th {
                font-size: 9px !important;
            }
            #tablaDocentes td {
                font-size: 9px !important;
            }
            /* Tabla asignaciones actuales - encabezados y datos */
            #tablaAsignaciones th {
                font-size: 9px !important;
            }
            #tablaAsignaciones td {
                font-size: 9px !important;
            }
            /* Texto "Seleccione un docente para ver sus asignaturas" */
            #checkboxAsignaturasAsignacion .text-muted {
                font-size: 9px !important;
                white-space: nowrap !important;
            }
            /* KPIs Estadísticas - Promedio y Aprobación lado a lado */
            .stats-kpis-executive {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                grid-template-rows: auto auto !important;
                gap: 12px !important;
                align-items: stretch !important;
            }
            .stats-kpi-main,
            .stats-kpi-approval {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 200px !important;
                padding: 20px !important;
            }
            .stats-kpi-main {
                grid-column: 1 !important;
                grid-row: 1 !important;
            }
            .stats-kpi-approval {
                grid-column: 2 !important;
                grid-row: 1 !important;
            }
            /* Ocultar footers para alinear círculos */
            .gauge-footer,
            .approval-footer {
                display: none !important;
            }
            .stats-kpis-secondary {
                grid-column: 1 / -1 !important;
                grid-row: 2 !important;
            }
        }

        /* ===== RESPONSIVE MÓVIL - KPIs ===== */
        @media (max-width: 699px) {
            /* KPIs Estadísticas - Promedio y Aprobación lado a lado */
            .stats-kpis-executive {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                grid-template-rows: auto auto !important;
                gap: 12px !important;
                align-items: stretch !important;
            }
            .stats-kpi-main,
            .stats-kpi-approval {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 200px !important;
                padding: 20px !important;
            }
            .stats-kpi-main {
                grid-column: 1 !important;
                grid-row: 1 !important;
            }
            .stats-kpi-approval {
                grid-column: 2 !important;
                grid-row: 1 !important;
            }
            /* Ocultar footers para alinear círculos */
            .gauge-footer,
            .approval-footer {
                display: none !important;
            }
            .stats-kpis-secondary {
                grid-column: 1 / -1 !important;
                grid-row: 2 !important;
            }
        }

        /* ===== HEADER RESPONSIVE ===== */
        @media (max-width: 699px) {
            /* Ocultar el texto del brand, solo mostrar logo E */
            .brand-text {
                display: none !important;
            }
            /* Header con position relative para el título centrado */
            .main-header {
                position: relative;
            }
            /* Título Admin centrado */
            .main-header::after {
                content: 'Admin';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 14px;
                font-weight: 600;
                color: #ffffff;
            }
            /* Ocultar nombre de usuario y fecha en responsive */
            .user-info,
            .current-date {
                display: none !important;
            }
            /* Botón cerrar sesión: solo icono más pequeño */
            .logout-text {
                display: none !important;
            }
            .logout-icon {
                display: block !important;
                width: 14px !important;
                height: 14px !important;
            }
            .btn-logout {
                padding: 6px !important;
                min-width: auto !important;
            }

            .titulo-apoderado-bar {
                font-size: 11px !important;
                letter-spacing: 0.02em !important;
            }

            /* ===== SUB-TABS ALUMNOS - RESPONSIVE ===== */
            /* Por defecto: mostrar gestión, ocultar listado */
            #columna-gestion-alumnos {
                display: block !important;
            }
            #columna-listado-alumnos {
                display: none !important;
            }
            /* Cuando se hace clic en "Listado de Alumnos" */
            #columna-gestion-alumnos.hidden {
                display: none !important;
            }
            #columna-listado-alumnos.active {
                display: block !important;
            }

            /* ===== SUB-TABS DOCENTES - RESPONSIVE ===== */
            #columna-agregar-docente {
                display: block !important;
            }
            #columna-listado-docentes {
                display: none !important;
            }
            #columna-agregar-docente.hidden {
                display: none !important;
            }
            #columna-listado-docentes.active {
                display: block !important;
            }

            /* ===== SUB-TABS ASIGNACIONES - RESPONSIVE ===== */
            #columna-asignar-docente {
                display: block !important;
            }
            #columna-asignaciones-actuales {
                display: none !important;
            }
            #columna-asignar-docente.hidden {
                display: none !important;
            }
            #columna-asignaciones-actuales.active {
                display: block !important;
            }
        }
        @media (max-width: 480px) {
            .titulo-apoderado-bar {
                font-size: 9px !important;
                letter-spacing: 0 !important;
            }
        }

        /* ===== KPI CÍRCULOS ESTADÍSTICAS ===== */
        .kpi-gauge-container,
        .approval-ring-container {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
        }
        .kpi-circle-bg {
            width: 160px !important;
            height: 160px !important;
            border-radius: 50% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            gap: 5px !important;
        }
        .kpi-circle-azul {
            border: 5px solid #74c0fc !important;
        }
        .kpi-circle-blanco {
            border: 5px solid #ffffff !important;
        }
        /* Fondo verde para KPIs */
        .kpi-main-verde,
        .kpi-approval-verde {
            background: linear-gradient(145deg, #40c057, #2f9e44) !important;
        }
        .kpi-approval-verde {
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }
        .kpi-main-verde .gauge-number,
        .kpi-main-verde .gauge-label {
            color: #ffffff !important;
        }
        .kpi-approval-verde .approval-percent {
            color: #ffffff !important;
            font-size: 28px !important;
        }
        .kpi-approval-verde .approval-label {
            color: #ffffff !important;
        }
        .approval-footer {
            text-align: center !important;
            padding: 10px 0 5px 0 !important;
        }
        .kpi-approval-verde .approval-subtext {
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        .gauge-number,
        .approval-percent {
            color: #fff !important;
            font-size: 36px !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            text-align: center !important;
        }
        .gauge-label,
        .approval-label {
            color: rgba(255,255,255,0.85) !important;
            font-size: 10px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            font-weight: 600 !important;
            text-align: center !important;
            line-height: 1.2 !important;
        }
        .approval-subtext {
            font-size: 11px !important;
            text-align: center !important;
        }
        @media (max-width: 699px) {
            .kpi-circle-bg {
                width: 120px !important;
                height: 120px !important;
                border-width: 4px !important;
            }
            .gauge-number,
            .approval-percent {
                font-size: 26px !important;
            }
            .gauge-label,
            .approval-label {
                font-size: 8px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Variables PHP para JavaScript -->
    <script>
        const ESTABLECIMIENTO_ID = <?php echo $establecimiento_id; ?>;
        const USUARIO_ID = <?php echo $usuario_id; ?>;
        const NOMBRE_USUARIO = "<?php echo htmlspecialchars($nombres . ' ' . $apellidos); ?>";

        // Datos desde la base de datos
        const cursosDB = <?php echo json_encode($cursos, JSON_UNESCAPED_UNICODE); ?>;
        const asignaturasDB = <?php echo json_encode($asignaturas, JSON_UNESCAPED_UNICODE); ?>;
        const docentesDB = <?php echo json_encode($docentes, JSON_UNESCAPED_UNICODE); ?>;
        const docenteEspecialidadesDB = <?php echo json_encode($docente_especialidades, JSON_UNESCAPED_UNICODE); ?>;
        const alumnosPorCursoDB = <?php echo json_encode($alumnos_por_curso, JSON_UNESCAPED_UNICODE); ?>;
        const asignacionesDB = <?php echo json_encode($asignaciones, JSON_UNESCAPED_UNICODE); ?>;
        const trimestresDB = <?php echo json_encode($trimestres, JSON_UNESCAPED_UNICODE); ?>;
    </script>

    <div class="app-container">
        <!-- Header -->
        <header class="main-header">
            <div class="header-content">
                <div class="brand">
                    <div class="logo">
                        <span class="logo-icon">E</span>
                    </div>
                    <div class="brand-text">
                        <h1>Sistema de Gestión Académica</h1>
                    </div>
                </div>
                <div class="header-info">
                    <span class="user-info"><?php echo htmlspecialchars($nombres . ' ' . $apellidos); ?></span>
                    <span class="current-date" id="currentDate"></span>
                    <button class="btn-logout" onclick="cerrarSesion()" title="Cerrar Sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Panel de Control -->
            <section class="control-panel">
                <div class="panel-header">
                    <h2>Panel de Control</h2>
                    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                        <span class="arrow-icon">&#9660;</span>
                    </button>
                </div>

                <!-- Sistema de Pestañas -->
                <div class="tabs-container">
                    <nav class="tabs-nav">
                        <button type="button" class="tab-btn active" data-tab="alumnos">Alumnos</button>
                        <button type="button" class="tab-btn" data-tab="docentes">Docentes</button>
                        <button type="button" class="tab-btn" data-tab="asignacion-cursos">Curso/Asignaturas</button>
                        <button type="button" class="tab-btn" data-tab="notas-por-curso">Notas por Curso</button>
                        <button type="button" class="tab-btn" data-tab="comunicados">Comunicados</button>
                        <button type="button" class="tab-btn" data-tab="estadisticas">Estadísticas</button>
                    </nav>

                    <!-- Contenido de las Pestañas -->
                    <div class="tabs-content">
                        <div id="docentes" class="tab-panel">
                            <!-- Sub-pestañas para móvil -->
                            <div class="sub-tabs-mobile sub-tabs-docentes">
                                <button type="button" class="sub-tab-btn active" data-subtab="agregar-docente">Agregar Docente</button>
                                <button type="button" class="sub-tab-btn" data-subtab="listado-docentes">Listado Docentes</button>
                            </div>

                            <div class="two-columns">
                                <!-- Columna Izquierda: Gestión de Docentes -->
                                <div class="column" id="columna-agregar-docente">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Agregar Docente</h3>
                                        </div>
                                        <div class="card-body">
                                            <form id="formGestionDocente">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="inputNombreDocente">Nombres</label>
                                                        <input type="text" id="inputNombreDocente" class="form-control" placeholder="Ej: María José" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="inputApellidoDocente">Apellidos</label>
                                                        <input type="text" id="inputApellidoDocente" class="form-control" placeholder="Ej: González Pérez" required>
                                                    </div>
                                                </div>

                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="inputRutDocente">RUT</label>
                                                        <input type="text" id="inputRutDocente" class="form-control" placeholder="Ej: 12.345.678-9">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="inputCorreoDocente">Correo Electrónico</label>
                                                        <input type="email" id="inputCorreoDocente" class="form-control" placeholder="Ej: docente@correo.com">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="label-con-boton">
                                                        <label>Especialidades</label>
                                                        <div class="botones-asignatura">
                                                            <button type="button" class="btn-agregar-asignatura" onclick="abrirModalAgregarAsignatura()">
                                                                <span>+</span> Agregar
                                                            </button>
                                                            <button type="button" class="btn-eliminar-asignatura" onclick="abrirModalEliminarAsignatura()">
                                                                <span>-</span> Eliminar
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="checkboxEspecialidades" class="checkbox-group checkbox-4-columnas">
                                                        <!-- Se genera dinámicamente -->
                                                    </div>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="button" class="btn btn-secondary" onclick="limpiarFormularioDocente()">Limpiar</button>
                                                    <button type="submit" class="btn btn-primary" id="btnGuardarDocente">Agregar Docente</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Listado de Docentes -->
                                <div class="column" id="columna-listado-docentes">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Listado de Docentes</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="filtros-docentes">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="filtroNombreDocente">Docente</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroNombreDocente" class="form-control" placeholder="Buscar docente..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasDocentes" data-input="filtroNombreDocente">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasDocentes" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="filtroAsignaturaDocente">Asignatura</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroAsignaturaDocente" class="form-control" placeholder="Buscar asignatura..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasAsignaturas" data-input="filtroAsignaturaDocente">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasAsignaturas" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive table-scroll">
                                                <table class="data-table" id="tablaDocentes">
                                                    <thead>
                                                        <tr>
                                                            <th>Nombre Completo</th>
                                                            <th>Especialidad</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyDocentes">
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted">No hay docentes registrados</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para Editar Docente -->
                            <div id="modalEditarDocente" class="modal-overlay" style="display: none;">
                                <div class="modal" onclick="event.stopPropagation()">
                                    <div class="modal-header">
                                        <h3>Editar Docente</h3>
                                        <button class="modal-close" onclick="cerrarModalEditarDocente()">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="formEditarDocente">
                                            <input type="hidden" id="editDocenteId">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="editDocenteNombres">Nombres</label>
                                                    <input type="text" id="editDocenteNombres" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="editDocenteApellidos">Apellidos</label>
                                                    <input type="text" id="editDocenteApellidos" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="editDocenteRut">RUT</label>
                                                    <input type="text" id="editDocenteRut" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <label for="editDocenteEmail">Email</label>
                                                    <input type="email" id="editDocenteEmail" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Especialidades</label>
                                                <div id="checkboxEspecialidadesEdit" class="checkbox-group">
                                                    <!-- Se genera dinámicamente -->
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEditarDocente()">Cancelar</button>
                                        <button type="button" class="btn btn-primary" onclick="guardarEdicionDocente()">Guardar Cambios</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para Agregar Asignatura -->
                            <div id="modalAgregarAsignatura" class="modal-overlay" style="display: none;">
                                <div class="modal" onclick="event.stopPropagation()">
                                    <div class="modal-header">
                                        <h3>Agregar Nueva Asignatura</h3>
                                        <button class="modal-close" onclick="cerrarModalAgregarAsignatura()">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="formAgregarAsignaturaModal">
                                            <div class="form-group">
                                                <label for="inputNuevaAsignatura">Nombre de la Asignatura</label>
                                                <input type="text" id="inputNuevaAsignatura" class="form-control" placeholder="Ej: Filosofía" required>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" onclick="cerrarModalAgregarAsignatura()">Cancelar</button>
                                        <button type="button" class="btn btn-primary" onclick="guardarNuevaAsignatura()">Agregar</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para Eliminar Asignatura -->
                            <div id="modalEliminarAsignatura" class="modal-overlay" style="display: none;">
                                <div class="modal" onclick="event.stopPropagation()">
                                    <div class="modal-header">
                                        <h3>Eliminar Asignatura</h3>
                                        <button class="modal-close" onclick="cerrarModalEliminarAsignatura()">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="selectEliminarAsignatura">Seleccione la asignatura a eliminar</label>
                                            <select id="selectEliminarAsignatura" class="form-control">
                                                <option value="">Seleccione una asignatura...</option>
                                            </select>
                                        </div>
                                        <p class="text-warning" style="font-size: 12px; color: #d97706; margin-top: 10px;">
                                            <strong>Advertencia:</strong> Solo se pueden eliminar asignaturas que no tengan notas ni asignaciones activas.
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEliminarAsignatura()">Cancelar</button>
                                        <button type="button" class="btn btn-danger" onclick="confirmarEliminarAsignatura()">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="asignacion-cursos" class="tab-panel">
                            <!-- Sub-pestañas para móvil -->
                            <div class="sub-tabs-mobile sub-tabs-asignaciones">
                                <button type="button" class="sub-tab-btn active" data-subtab="asignar-docente">Asignar Docente</button>
                                <button type="button" class="sub-tab-btn" data-subtab="asignaciones-actuales">Asignaciones Actuales</button>
                            </div>

                            <div class="two-columns">
                                <!-- Columna Izquierda: Asignar Docente a Curso -->
                                <div class="column" id="columna-asignar-docente">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Asignar Docente a Curso</h3>
                                        </div>
                                        <div class="card-body">
                                            <form id="formAsignacionCurso">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="selectDocenteAsignacion">Docente</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="selectDocenteAsignacion" class="form-control" placeholder="Seleccionar..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasDocenteAsignar" data-input="selectDocenteAsignacion">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasDocenteAsignar" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="selectCursoAsignacion">Curso</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="selectCursoAsignacion" class="form-control" placeholder="Seleccionar..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasCursoAsignar" data-input="selectCursoAsignacion">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasCursoAsignar" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label>Asignaturas del Docente</label>
                                                    <div id="checkboxAsignaturasAsignacion" class="checkbox-group">
                                                        <p class="text-muted">Seleccione un docente para ver sus asignaturas</p>
                                                    </div>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="button" class="btn btn-secondary" onclick="limpiarFormularioAsignacion()">Limpiar</button>
                                                    <button type="submit" class="btn btn-primary">Asignar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Asignaciones Actuales -->
                                <div class="column" id="columna-asignaciones-actuales">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Asignaciones Actuales</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="filtros-asignaciones">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="filtroAsignacionCurso">Filtrar por Curso</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroAsignacionCurso" class="form-control" placeholder="Todos los cursos" autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasCursosAsignacion" data-input="filtroAsignacionCurso">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasCursosAsignacion" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="filtroAsignacionDocente">Filtrar por Docente</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroAsignacionDocente" class="form-control" placeholder="Todos los docentes" autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasDocentesAsignacion" data-input="filtroAsignacionDocente">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasDocentesAsignacion" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="data-table" id="tablaAsignaciones">
                                                    <thead>
                                                        <tr>
                                                            <th>Docente</th>
                                                            <th>Curso</th>
                                                            <th>Asignatura</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyAsignaciones">
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">No hay asignaciones registradas</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="alumnos" class="tab-panel active">
                            <!-- Sub-pestañas para móvil -->
                            <div class="sub-tabs-mobile sub-tabs-alumnos">
                                <button type="button" class="sub-tab-btn active" data-subtab="gestion-alumnos">Gestión de Alumnos</button>
                                <button type="button" class="sub-tab-btn" data-subtab="listado-alumnos">Listado de Alumnos</button>
                            </div>

                            <div class="two-columns">
                                <!-- Columna Izquierda: Gestión de Alumnos -->
                                <div class="column" id="columna-gestion-alumnos">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Gestión de Alumnos</h3>
                                        </div>
                                        <div class="card-body">
                                            <form id="formGestionAlumno">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="selectCursoAlumno">Curso</label>
                                                        <select id="selectCursoAlumno" class="form-control" required>
                                                            <option value="">Seleccionar...</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="inputRutAlumno">RUT</label>
                                                        <input type="text" id="inputRutAlumno" class="form-control" placeholder="Ej: 12.345.678-9" required>
                                                    </div>
                                                </div>

                                                <div id="seccionNuevoAlumno">
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputNombreAlumno">Nombres</label>
                                                            <input type="text" id="inputNombreAlumno" class="form-control" placeholder="Ej: Juan Pablo" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="inputApellidoAlumno">Apellidos</label>
                                                            <input type="text" id="inputApellidoAlumno" class="form-control" placeholder="Ej: Pérez González" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputFechaNacAlumno">Fecha de Nacimiento</label>
                                                            <input type="date" id="inputFechaNacAlumno" class="form-control" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="selectSexoAlumno">Sexo</label>
                                                            <select id="selectSexoAlumno" class="form-control" required>
                                                                <option value="">Seleccionar...</option>
                                                                <option value="Femenino">Femenino</option>
                                                                <option value="Masculino">Masculino</option>
                                                                <option value="Otro">Otro</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Contenedor de ambas barras de título en la misma fila -->
                                                <div style="margin-top: 20px; display: flex; gap: 10px;">
                                                    <!-- Barra: Datos del Apoderado -->
                                                    <div id="seccionDatosApoderado" onclick="toggleApoderadoSection()" style="flex: 1; background-color: #2d5a87; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border: 1px solid #e2e8f0;">
                                                        <h4 class="titulo-apoderado-bar" style="margin: 0; font-size: 13px; font-weight: 600; color: #ffffff; text-transform: uppercase; letter-spacing: 0.03em;">Datos del Apoderado</h4>
                                                        <span id="apoderadoToggleIcon" style="font-size: 12px; color: #ffffff;">&#9660;</span>
                                                    </div>

                                                    <!-- Barra: ¿Apoderado ya Existe? -->
                                                    <div id="seccionApoderadoExiste" onclick="toggleApoderadoExisteCheckbox(event)" style="flex: 1; background-color: #2d5a87; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border: 1px solid #e2e8f0;">
                                                        <h4 class="titulo-apoderado-bar" style="margin: 0; font-size: 13px; font-weight: 600; color: #ffffff; text-transform: uppercase; letter-spacing: 0.03em;">¿Apoderado ya Existe?</h4>
                                                        <div id="checkboxApoderadoExiste" onclick="event.stopPropagation(); toggleApoderadoExisteCheckbox(event);" style="width: 20px; height: 20px; border: 2px solid #ffffff; background-color: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                                            <span id="checkmarkApoderadoExiste" style="display: none; color: #ffffff; font-size: 14px; font-weight: bold;">✓</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Contenido expandible: Datos del Apoderado -->
                                                <div id="apoderadoContent" style="display: none; background-color: #ffffff; padding: 18px; border: 1px solid #e2e8f0; border-top: none;">
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputNombresApoderado">Nombres</label>
                                                            <input type="text" id="inputNombresApoderado" class="form-control" placeholder="Ej: María José" oninput="verificarDatosApoderado()">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="inputApellidosApoderado">Apellidos</label>
                                                            <input type="text" id="inputApellidosApoderado" class="form-control" placeholder="Ej: González Pérez" oninput="verificarDatosApoderado()">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputRutApoderado">RUT</label>
                                                            <input type="text" id="inputRutApoderado" class="form-control" placeholder="Ej: 12.345.678-9" oninput="verificarDatosApoderado()">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="inputCorreoApoderado">Correo</label>
                                                            <input type="email" id="inputCorreoApoderado" class="form-control" placeholder="Ej: correo@ejemplo.com">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputTelefonoApoderado">Teléfono</label>
                                                            <input type="tel" id="inputTelefonoApoderado" class="form-control" placeholder="Ej: +56 9 1234 5678">
                                                        </div>
                                                        <div class="form-group">
                                                            <!-- Espacio vacío para mantener alineación -->
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Contenido expandible: Apoderado Ya Existe -->
                                                <div id="apoderadoExisteContent" style="display: none; background-color: #ffffff; padding: 18px; border: 1px solid #e2e8f0; border-top: none;">
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputNombresApoderadoExiste">Nombres</label>
                                                            <input type="text" id="inputNombresApoderadoExiste" class="form-control" placeholder="Ej: María José">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="inputApellidosApoderadoExiste">Apellidos</label>
                                                            <input type="text" id="inputApellidosApoderadoExiste" class="form-control" placeholder="Ej: González Pérez">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group">
                                                            <label for="inputRutApoderadoExiste">RUT</label>
                                                            <input type="text" id="inputRutApoderadoExiste" class="form-control" placeholder="Ej: 12.345.678-9">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="selectParentescoExiste">Parentesco</label>
                                                            <select id="selectParentescoExiste" class="form-control">
                                                                <option value="">Seleccionar...</option>
                                                                <option value="Padre">Padre</option>
                                                                <option value="Madre">Madre</option>
                                                                <option value="Tutor Legal">Tutor Legal</option>
                                                                <option value="Abuelo/a">Abuelo/a</option>
                                                                <option value="Tío/a">Tío/a</option>
                                                                <option value="Otro">Otro</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="button" class="btn btn-secondary" onclick="limpiarFormularioAlumno()">Limpiar</button>
                                                    <button type="submit" class="btn btn-primary" id="btnGuardarAlumno">Agregar Alumno</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Listado de Alumnos -->
                                <div class="column" id="columna-listado-alumnos">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Listado de Alumnos</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="filtros-alumnos">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="filtroCursoAlumnos">Curso</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroCursoAlumnos" class="form-control" placeholder="Seleccionar curso..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasCursosAlumnos" data-input="filtroCursoAlumnos">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasCursosAlumnos" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="filtroNombreAlumno">Alumno</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="filtroNombreAlumno" class="form-control" placeholder="Buscar alumno..." autocomplete="off">
                                                            <button type="button" class="autocomplete-arrow" data-target="sugerenciasAlumnos" data-input="filtroNombreAlumno">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                            </button>
                                                            <div id="sugerenciasAlumnos" class="sugerencias-lista"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive table-scroll">
                                                <table class="data-table" id="tablaAlumnos">
                                                    <thead>
                                                        <tr>
                                                            <th>Nombre Completo</th>
                                                            <th>RUT</th>
                                                            <th>Curso</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyAlumnos">
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">Cargando alumnos...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para Editar Alumno -->
                            <div id="modalEditarAlumno" class="modal-overlay" style="display: none;">
                                <div class="modal" onclick="event.stopPropagation()">
                                    <div class="modal-header">
                                        <h3>Editar Alumno</h3>
                                        <button class="modal-close" onclick="cerrarModalEditarAlumno()">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="formEditarAlumno">
                                            <input type="hidden" id="editAlumnoId">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="editAlumnoCurso">Curso</label>
                                                    <select id="editAlumnoCurso" class="form-control" required>
                                                        <option value="">Seleccionar...</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="editAlumnoRut">RUT</label>
                                                    <input type="text" id="editAlumnoRut" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="editAlumnoNombres">Nombres</label>
                                                    <input type="text" id="editAlumnoNombres" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="editAlumnoApellidos">Apellidos</label>
                                                    <input type="text" id="editAlumnoApellidos" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="editAlumnoFechaNac">Fecha de Nacimiento</label>
                                                    <input type="date" id="editAlumnoFechaNac" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="editAlumnoSexo">Sexo</label>
                                                    <select id="editAlumnoSexo" class="form-control" required>
                                                        <option value="">Seleccionar...</option>
                                                        <option value="Femenino">Femenino</option>
                                                        <option value="Masculino">Masculino</option>
                                                        <option value="Otro">Otro</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEditarAlumno()">Cancelar</button>
                                        <button type="button" class="btn btn-primary" onclick="guardarEdicionAlumno()">Guardar Cambios</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="notas-por-curso" class="tab-panel">
                            <div class="card">
                                <div class="card-header">
                                    <h3>Notas por Curso y Asignatura</h3>
                                </div>
                                <div class="card-body">
                                    <div class="filtros-notas-curso">
                                        <div class="form-row form-row-tres">
                                            <div class="form-group">
                                                <label for="selectCursoNotasPorCurso">Curso</label>
                                                <div class="autocomplete-container">
                                                    <input type="text" id="selectCursoNotasPorCurso" class="form-control" placeholder="Seleccionar..." autocomplete="off">
                                                    <button type="button" class="autocomplete-arrow" data-target="sugerenciasCursoNotas" data-input="selectCursoNotasPorCurso">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                    </button>
                                                    <div id="sugerenciasCursoNotas" class="sugerencias-lista"></div>
                                                </div>
                                            </div>
                                            <div class="form-group" id="filtroAsignaturaContainer" style="display: none;">
                                                <label for="selectAsignaturaNotasCurso">Asignatura</label>
                                                <div class="autocomplete-container">
                                                    <input type="text" id="selectAsignaturaNotasCurso" class="form-control" placeholder="Seleccionar..." autocomplete="off">
                                                    <button type="button" class="autocomplete-arrow" data-target="sugerenciasAsignaturaNotas" data-input="selectAsignaturaNotasCurso">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                    </button>
                                                    <div id="sugerenciasAsignaturaNotas" class="sugerencias-lista"></div>
                                                </div>
                                            </div>
                                            <div class="form-group" id="filtroTrimestreContainer" style="display: none;">
                                                <label for="selectTrimestreNotasCurso">Trimestre</label>
                                                <div class="autocomplete-container">
                                                    <input type="text" id="selectTrimestreNotasCurso" class="form-control" placeholder="Seleccionar..." autocomplete="off">
                                                    <button type="button" class="autocomplete-arrow" data-target="sugerenciasTrimestreNotas" data-input="selectTrimestreNotasCurso">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                    </button>
                                                    <div id="sugerenciasTrimestreNotas" class="sugerencias-lista"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tabla-notas-curso-container" id="tablaNotasCursoContainer" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="data-table tabla-notas-amplia" id="tablaNotasCurso">
                                                <thead id="theadNotasCurso">
                                                    <!-- Se genera dinámicamente -->
                                                </thead>
                                                <tbody id="tbodyNotasCurso">
                                                    <!-- Se genera dinámicamente -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div id="mensajeSeleccion" class="text-center text-muted" style="padding: 40px;">
                                        Seleccione un curso para comenzar
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="comunicados" class="tab-panel">
                            <div class="two-columns">
                                <!-- Columna Izquierda: Opciones del Comunicado -->
                                <div class="column">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Opciones del Comunicado</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="selectTipoComunicado">Tipo de Comunicado</label>
                                                    <div class="autocomplete-container">
                                                        <input type="text" id="selectTipoComunicado" class="form-control" placeholder="Seleccionar..." autocomplete="off" readonly>
                                                        <button type="button" class="autocomplete-arrow" data-target="sugerenciasTipoComunicado" data-input="selectTipoComunicado">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                        </button>
                                                        <div id="sugerenciasTipoComunicado" class="sugerencias-lista"></div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="selectModoCurso">Curso</label>
                                                    <div class="autocomplete-container">
                                                        <input type="text" id="selectModoCurso" class="form-control" placeholder="Seleccionar..." autocomplete="off" readonly>
                                                        <button type="button" class="autocomplete-arrow" data-target="sugerenciasModoCurso" data-input="selectModoCurso">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                        </button>
                                                        <div id="sugerenciasModoCurso" class="sugerencias-lista"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="contenedorCursosEspecificos" class="cursos-grid-container" style="display: none;">
                                                <!-- Se genera dinámicamente -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Redactar Comunicado -->
                                <div class="column">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>Redactar Comunicado</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="inputTituloComunicado">Título</label>
                                                <input type="text" id="inputTituloComunicado" class="form-control" placeholder="Ej: Reunión de apoderados">
                                            </div>
                                            <div class="form-group">
                                                <label for="textareaComunicado">Mensaje</label>
                                                <textarea id="textareaComunicado" class="form-control" rows="8" placeholder="Escriba el comunicado aquí..."></textarea>
                                            </div>
                                            <div class="form-actions">
                                                <button type="button" class="btn btn-secondary" onclick="limpiarComunicado()">Limpiar</button>
                                                <button type="button" class="btn btn-primary" onclick="enviarComunicado()">Enviar Comunicado</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña: Estadísticas -->
                        <div id="estadisticas" class="tab-panel">
                            <div class="estadisticas-container">
                                <!-- Panel de Filtros -->
                                <div class="stats-filtros-panel">
                                    <div class="stats-filtros-header">
                                        <h3>Panel de Estadísticas</h3>
                                        <p>Seleccione el tipo de vista para analizar el rendimiento</p>
                                    </div>
                                    <div class="stats-filtros-grid">
                                        <div class="stats-filtro-grupo">
                                            <label>Tipo de Vista</label>
                                            <select id="statsVistaSelector" onchange="cambiarVistaEstadisticas()">
                                                <option value="general">Vista General</option>
                                                <option value="curso">Por Curso</option>
                                                <option value="docente">Por Docente</option>
                                                <option value="asignatura">Por Asignatura</option>
                                            </select>
                                        </div>
                                        <div class="stats-filtro-grupo" id="statsFiltroCursoContainer" style="display: none;">
                                            <label>Seleccionar Curso</label>
                                            <select id="statsCursoSelector" onchange="actualizarEstadisticasCurso()">
                                                <option value="">Seleccione un curso...</option>
                                            </select>
                                        </div>
                                        <div class="stats-filtro-grupo" id="statsFiltroDocenteContainer" style="display: none;">
                                            <label>Seleccionar Docente</label>
                                            <select id="statsDocenteSelector" onchange="actualizarEstadisticasDocente()">
                                                <option value="">Seleccione un docente...</option>
                                            </select>
                                        </div>
                                        <div class="stats-filtro-grupo" id="statsFiltroAsignaturaContainer" style="display: none;">
                                            <label>Seleccionar Asignatura</label>
                                            <select id="statsAsignaturaSelector" onchange="actualizarEstadisticasAsignatura()">
                                                <option value="">Seleccione una asignatura...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Título dinámico -->
                                <div class="stats-titulo-seccion">
                                    <h4 id="statsTituloActual">Estadísticas Generales del Establecimiento</h4>
                                </div>

                                <!-- KPIs Ejecutivos -->
                                <div class="stats-kpis-executive">
                                    <!-- KPI Principal con Gauge -->
                                    <div class="stats-kpi-main">
                                        <div class="kpi-gauge-container">
                                            <div class="kpi-circle-bg kpi-circle-blanco">
                                                <span class="gauge-number" id="statsPromedioGeneral">-</span>
                                                <span class="gauge-label">Promedio General</span>
                                            </div>
                                        </div>
                                        <div class="gauge-footer">
                                            <span class="gauge-subtext" id="statsTotalCursos">-</span>
                                        </div>
                                    </div>

                                    <!-- KPIs Secundarios -->
                                    <div class="stats-kpis-secondary">
                                        <div class="stats-kpi-exec-card kpi-success">
                                            <div class="kpi-exec-header">
                                                <span class="kpi-exec-label" id="labelMejorCurso">Mejor Curso Evaluado</span>
                                                <div class="kpi-exec-badge">TOP</div>
                                            </div>
                                            <div class="kpi-exec-body">
                                                <span class="kpi-exec-value" id="statsMejorCurso">-</span>
                                                <span class="kpi-exec-subtext" id="statsMejorCursoNota">-</span>
                                            </div>
                                            <div class="kpi-exec-bar">
                                                <div class="kpi-exec-bar-fill" id="barMejorCurso" style="width: 85%"></div>
                                            </div>
                                        </div>

                                        <div class="stats-kpi-exec-card kpi-danger">
                                            <div class="kpi-exec-header">
                                                <span class="kpi-exec-label" id="labelNecesitaApoyo">Necesita Apoyo</span>
                                                <div class="kpi-exec-badge">ALERTA</div>
                                            </div>
                                            <div class="kpi-exec-body">
                                                <span class="kpi-exec-value" id="statsCursoApoyo">-</span>
                                                <span class="kpi-exec-subtext" id="statsCursoApoyoNota">-</span>
                                            </div>
                                            <div class="kpi-exec-bar">
                                                <div class="kpi-exec-bar-fill" id="barCursoApoyo" style="width: 45%"></div>
                                            </div>
                                        </div>

                                        <div class="stats-kpi-exec-card kpi-info">
                                            <div class="kpi-exec-header">
                                                <span class="kpi-exec-label" id="labelMejorAsignatura">Mejor Asignatura</span>
                                                <div class="kpi-exec-badge" id="badgeMejorAsignatura">DESTACADA</div>
                                            </div>
                                            <div class="kpi-exec-body">
                                                <span class="kpi-exec-value" id="statsMejorAsig">-</span>
                                                <span class="kpi-exec-subtext" id="statsMejorAsigNota">-</span>
                                            </div>
                                            <div class="kpi-exec-bar">
                                                <div class="kpi-exec-bar-fill" id="barMejorAsig" style="width: 80%"></div>
                                            </div>
                                        </div>

                                        <div class="stats-kpi-exec-card kpi-warning">
                                            <div class="kpi-exec-header">
                                                <span class="kpi-exec-label" id="labelAsigCritica">Asignatura Crítica</span>
                                                <div class="kpi-exec-badge" id="badgeAsigCritica">REVISAR</div>
                                            </div>
                                            <div class="kpi-exec-body">
                                                <span class="kpi-exec-value" id="statsAsigCritica">-</span>
                                                <span class="kpi-exec-subtext" id="statsAsigCriticaNota">-</span>
                                            </div>
                                            <div class="kpi-exec-bar">
                                                <div class="kpi-exec-bar-fill" id="barAsigCritica" style="width: 50%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KPI Tasa de Aprobación -->
                                    <div class="stats-kpi-approval">
                                        <div class="approval-ring-container">
                                            <div class="kpi-circle-bg kpi-circle-blanco">
                                                <span class="approval-percent" id="statsTasaAprobacion">-</span>
                                                <span class="approval-label">Aprobación</span>
                                            </div>
                                        </div>
                                        <div class="approval-footer">
                                            <span class="approval-subtext" id="statsTasaAprobacionAlumnos">-</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráficos Fila 1 -->
                                <div class="stats-graficos-row">
                                    <div class="stats-grafico-card stats-grafico-wide">
                                        <div class="stats-grafico-header">
                                            <h4>Indicadores por Curso</h4>
                                            <div class="grafico-header-actions">
                                                <span class="stats-grafico-badge">KPI</span>
                                            </div>
                                        </div>
                                        <div class="stats-grafico-body stats-grafico-body-lg">
                                            <canvas id="graficoPromedioCursos"></canvas>
                                        </div>
                                    </div>
                                    <div class="stats-grafico-card">
                                        <div class="stats-grafico-header">
                                            <h4>Segmentación de Resultados</h4>
                                            <span class="stats-grafico-badge">Análisis</span>
                                        </div>
                                        <div class="stats-grafico-body">
                                            <canvas id="graficoDistribucion"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráficos Fila 2 -->
                                <div class="stats-graficos-row">
                                    <div class="stats-grafico-card">
                                        <div class="stats-grafico-header">
                                            <h4>Tendencia Trimestral</h4>
                                            <span class="stats-grafico-badge">Evolución</span>
                                        </div>
                                        <div class="stats-grafico-body">
                                            <canvas id="graficoEvolucion"></canvas>
                                        </div>
                                    </div>
                                    <div class="stats-grafico-card stats-grafico-wide">
                                        <div class="stats-grafico-header">
                                            <h4 id="tituloBenchmarkAsignatura">Benchmark por Asignatura</h4>
                                            <span class="stats-grafico-badge">Métricas</span>
                                        </div>
                                        <div class="stats-grafico-body stats-grafico-body-lg">
                                            <canvas id="graficoAsignaturas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="main-footer">
            <p>Sistema de Gestión Académica &copy; 2024 | Todos los derechos reservados</p>
        </footer>
    </div>

    <!-- Modal de Mensaje/Alerta (Global - fuera de los tab-panels) -->
    <div id="modalMensaje" class="modal-overlay" style="display: none;">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-header" id="modalMensajeHeader">
                <h3 id="modalMensajeTitulo">Mensaje</h3>
                <button class="modal-close" onclick="cerrarModalMensaje()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMensajeTexto" style="text-align: center; font-size: 14px; padding: 10px 0;"></p>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button type="button" class="btn btn-primary" onclick="cerrarModalMensaje()">Aceptar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js_colegio/colegio.js?v=<?php echo time(); ?>"></script>
    <script>
    // Variable para controlar el estado del checkbox (global)
    window.apoderadoExisteMarcado = false;
    window.datosApoderadoLlenos = false;

    // Función para expandir/colapsar sección de apoderado nuevo
    function toggleApoderadoSection() {
        // Verificar si "Apoderado ya existe" está marcado
        if (window.apoderadoExisteMarcado) {
            alert('No puede agregar datos de apoderado nuevo si ya marcó que el apoderado existe.');
            return;
        }

        var content = document.getElementById('apoderadoContent');
        var icon = document.getElementById('apoderadoToggleIcon');

        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            icon.innerHTML = '&#9650;'; // Flecha arriba
        } else {
            content.style.display = 'none';
            icon.innerHTML = '&#9660;'; // Flecha abajo
        }
    }

    // Función para toggle del checkbox "Apoderado ya existe"
    function toggleApoderadoExisteCheckbox(event) {
        // Verificar si hay datos en "Datos del Apoderado"
        if (window.datosApoderadoLlenos && !window.apoderadoExisteMarcado) {
            alert('No puede marcar esta opción si ya ingresó datos del apoderado nuevo.');
            return;
        }

        var checkmark = document.getElementById('checkmarkApoderadoExiste');
        var content = document.getElementById('apoderadoExisteContent');
        var seccionDatos = document.getElementById('seccionDatosApoderado');

        if (!window.apoderadoExisteMarcado) {
            // Marcar checkbox
            window.apoderadoExisteMarcado = true;
            checkmark.style.display = 'block';
            content.style.display = 'block';
            // Cerrar la sección "Datos del Apoderado" si estaba abierta
            var contentDatos = document.getElementById('apoderadoContent');
            var iconDatos = document.getElementById('apoderadoToggleIcon');
            if (contentDatos) contentDatos.style.display = 'none';
            if (iconDatos) iconDatos.innerHTML = '&#9660;';
            // Deshabilitar visualmente la otra sección
            seccionDatos.style.opacity = '0.5';
            seccionDatos.style.pointerEvents = 'none';
        } else {
            // Desmarcar checkbox
            window.apoderadoExisteMarcado = false;
            checkmark.style.display = 'none';
            content.style.display = 'none';
            // Limpiar campos
            document.getElementById('inputNombresApoderadoExiste').value = '';
            document.getElementById('inputApellidosApoderadoExiste').value = '';
            document.getElementById('inputRutApoderadoExiste').value = '';
            document.getElementById('selectParentescoExiste').value = '';
            // Habilitar la otra sección
            seccionDatos.style.opacity = '1';
            seccionDatos.style.pointerEvents = 'auto';
        }
    }

    // Función para verificar si hay datos en "Datos del Apoderado"
    function verificarDatosApoderado() {
        var nombres = document.getElementById('inputNombresApoderado').value.trim();
        var apellidos = document.getElementById('inputApellidosApoderado').value.trim();
        var rut = document.getElementById('inputRutApoderado').value.trim();

        var seccionExiste = document.getElementById('seccionApoderadoExiste');

        if (nombres !== '' || apellidos !== '' || rut !== '') {
            window.datosApoderadoLlenos = true;
            // Deshabilitar visualmente la sección "Apoderado ya existe"
            seccionExiste.style.opacity = '0.5';
            seccionExiste.style.pointerEvents = 'none';
        } else {
            window.datosApoderadoLlenos = false;
            // Habilitar la sección "Apoderado ya existe"
            seccionExiste.style.opacity = '1';
            seccionExiste.style.pointerEvents = 'auto';
        }
    }

    // Modificar limpiarFormularioAlumno para resetear todo
    var originalLimpiarFormularioAlumno = window.limpiarFormularioAlumno;
    window.limpiarFormularioAlumno = function() {
        if (typeof originalLimpiarFormularioAlumno === 'function') {
            originalLimpiarFormularioAlumno();
        }

        // Resetear sección apoderado nuevo
        var content = document.getElementById('apoderadoContent');
        var icon = document.getElementById('apoderadoToggleIcon');
        if (content) content.style.display = 'none';
        if (icon) icon.innerHTML = '&#9660;';

        // Resetear sección apoderado existe
        var checkmark = document.getElementById('checkmarkApoderadoExiste');
        var contentExiste = document.getElementById('apoderadoExisteContent');
        if (checkmark) checkmark.style.display = 'none';
        if (contentExiste) contentExiste.style.display = 'none';

        // Limpiar campos de apoderado existe
        var nombresExiste = document.getElementById('inputNombresApoderadoExiste');
        var apellidosExiste = document.getElementById('inputApellidosApoderadoExiste');
        var rutExiste = document.getElementById('inputRutApoderadoExiste');
        var parentescoExiste = document.getElementById('selectParentescoExiste');
        if (nombresExiste) nombresExiste.value = '';
        if (apellidosExiste) apellidosExiste.value = '';
        if (rutExiste) rutExiste.value = '';
        if (parentescoExiste) parentescoExiste.value = '';

        // Resetear estados
        window.apoderadoExisteMarcado = false;
        window.datosApoderadoLlenos = false;

        // Habilitar ambas secciones
        var seccionDatos = document.getElementById('seccionDatosApoderado');
        var seccionExiste = document.getElementById('seccionApoderadoExiste');
        if (seccionDatos) {
            seccionDatos.style.opacity = '1';
            seccionDatos.style.pointerEvents = 'auto';
        }
        if (seccionExiste) {
            seccionExiste.style.opacity = '1';
            seccionExiste.style.pointerEvents = 'auto';
        }
    };
    </script>

    <!-- ==================== CHAT FLOTANTE ==================== -->
    <style>
    /* Botón flotante del chat */
    .chat-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 9998;
    }

    .chat-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(249, 115, 22, 0.5);
    }

    .chat-fab svg {
        width: 28px;
        height: 28px;
        color: white;
    }

    .chat-fab-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 12px;
        font-weight: 600;
        min-width: 22px;
        height: 22px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }

    .chat-fab-badge.hidden {
        display: none;
    }

    /* Modal del chat */
    .chat-modal {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 420px;
        height: 520px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
    }

    .chat-modal.active {
        display: flex;
    }

    /* Header del chat */
    .chat-header {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-header-title {
        color: white;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-header-title svg {
        width: 22px;
        height: 22px;
    }

    .chat-close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .chat-close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .chat-close-btn svg {
        width: 18px;
        height: 18px;
        color: white;
    }

    /* Contenido del chat */
    .chat-body {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    /* Lista de contactos */
    .chat-contacts {
        width: 140px;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
        flex-shrink: 0;
    }

    .chat-contact-item {
        padding: 12px 10px;
        cursor: pointer;
        border-bottom: 1px solid #e2e8f0;
        transition: background 0.2s;
        position: relative;
    }

    .chat-contact-item:hover {
        background: #f1f5f9;
    }

    .chat-contact-item.active {
        background: #fff7ed;
        border-left: 3px solid #f97316;
    }

    .chat-contact-item.es-admin {
        background: #fef3c7;
    }

    .chat-contact-item.es-admin.active {
        background: #fde68a;
    }

    .chat-contact-name {
        font-size: 12px;
        font-weight: 500;
        color: #334155;
        line-height: 1.3;
    }

    .chat-contact-tipo {
        font-size: 10px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .chat-contact-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-contact-badge.hidden {
        display: none;
    }

    /* Área de conversación */
    .chat-conversation {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
    }

    .chat-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .chat-message {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.4;
    }

    .chat-message.enviado {
        align-self: flex-end;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .chat-message.recibido {
        align-self: flex-start;
        background: #f1f5f9;
        color: #334155;
        border-bottom-left-radius: 4px;
    }

    .chat-message-time {
        font-size: 10px;
        opacity: 0.7;
        margin-top: 4px;
    }

    .chat-no-messages {
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
        padding: 40px 20px;
    }

    .chat-select-contact {
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .chat-select-contact svg {
        width: 48px;
        height: 48px;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    /* Input de mensaje */
    .chat-input-area {
        padding: 12px 15px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .chat-input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 10px 16px;
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s;
    }

    .chat-input:focus {
        border-color: #f97316;
    }

    .chat-send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    }

    .chat-send-btn:hover {
        transform: scale(1.1);
    }

    .chat-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .chat-send-btn svg {
        width: 18px;
        height: 18px;
        color: white;
    }

    /* Responsive Tablet */
    @media (max-width: 1024px) {
        .chat-fab {
            width: 45px;
            height: 45px;
            right: 15px;
            bottom: 20px;
        }

        .chat-fab svg {
            width: 20px;
            height: 20px;
        }

        .chat-fab-badge {
            min-width: 18px;
            height: 18px;
            font-size: 10px;
            top: -3px;
            right: -3px;
        }

        .chat-modal {
            bottom: 75px;
            right: 15px;
        }
    }

    /* Responsive Móvil */
    @media (max-width: 500px) {
        .chat-fab {
            width: 40px;
            height: 40px;
            right: 10px;
            bottom: 15px;
        }

        .chat-fab svg {
            width: 18px;
            height: 18px;
        }

        .chat-fab-badge {
            min-width: 16px;
            height: 16px;
            font-size: 9px;
            top: -2px;
            right: -2px;
        }

        /* Modal más compacto */
        .chat-modal {
            width: 300px;
            right: 8px;
            bottom: 60px;
            height: 60vh;
            max-height: 400px;
            border-radius: 12px;
        }

        /* Header más pequeño */
        .chat-header {
            padding: 10px 12px;
        }

        .chat-header-title {
            font-size: 13px;
            gap: 6px;
        }

        .chat-header-title svg {
            width: 16px;
            height: 16px;
        }

        .chat-close-btn {
            width: 26px;
            height: 26px;
        }

        .chat-close-btn svg {
            width: 14px;
            height: 14px;
        }

        /* Lista de contactos más compacta */
        .chat-contacts {
            width: 70px;
        }

        .chat-contact-item {
            padding: 8px 6px;
        }

        .chat-contact-name {
            font-size: 10px;
        }

        .chat-contact-tipo {
            font-size: 8px;
        }

        .chat-contact-badge {
            min-width: 14px;
            height: 14px;
            font-size: 8px;
            top: 4px;
            right: 4px;
        }

        /* Mensajes más compactos */
        .chat-messages {
            padding: 8px;
            gap: 6px;
        }

        .chat-message {
            padding: 7px 10px;
            font-size: 11px;
            border-radius: 10px;
            max-width: 85%;
        }

        .chat-message-time {
            font-size: 8px;
            margin-top: 2px;
        }

        .chat-no-messages,
        .chat-select-contact {
            font-size: 11px;
            padding: 20px 10px;
        }

        .chat-select-contact svg {
            width: 32px;
            height: 32px;
            margin-bottom: 6px;
        }

        /* Input más compacto */
        .chat-input-area {
            padding: 8px 10px;
            gap: 6px;
        }

        .chat-input {
            padding: 7px 12px;
            font-size: 11px;
            border-radius: 16px;
        }

        .chat-send-btn {
            width: 32px;
            height: 32px;
        }

        .chat-send-btn svg {
            width: 14px;
            height: 14px;
        }
    }
    </style>

    <!-- Botón flotante -->
    <button class="chat-fab" id="chatFab" onclick="toggleChat()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="chat-fab-badge hidden" id="chatFabBadge">0</span>
    </button>

    <!-- Modal del chat -->
    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <div class="chat-header-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Chat con Docentes
            </div>
            <button class="chat-close-btn" onclick="toggleChat()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="chat-body">
            <div class="chat-contacts" id="chatContacts">
                <!-- Contactos se cargan dinámicamente -->
            </div>
            <div class="chat-conversation">
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-select-contact">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Selecciona un docente para iniciar una conversación
                    </div>
                </div>
                <div class="chat-input-area">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Escribe un mensaje..." disabled>
                    <button class="chat-send-btn" id="chatSendBtn" onclick="enviarMensajeChat()" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ==================== CHAT FUNCTIONALITY ====================
    let chatAbierto = false;
    let contactoActual = null;
    let chatRefreshInterval = null;

    function toggleChat() {
        chatAbierto = !chatAbierto;
        const modal = document.getElementById('chatModal');

        if (chatAbierto) {
            modal.classList.add('active');
            cargarContactosChat();
            iniciarRefreshChat();
        } else {
            modal.classList.remove('active');
            detenerRefreshChat();
        }
    }

    function cargarContactosChat() {
        fetch('api/chat_obtener_contactos.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarContactos(data.contactos);
                }
            })
            .catch(error => console.error('Error cargando contactos:', error));
    }

    function formatearNombreMovil(nombreCompleto) {
        // Separar el nombre completo en partes
        const partes = nombreCompleto.trim().split(' ');
        if (partes.length >= 2) {
            // Primera inicial del nombre + primer apellido
            const inicial = partes[0].charAt(0).toUpperCase();
            // Buscar el primer apellido (asumiendo que está después del nombre)
            // Si hay 2 palabras: nombre + apellido
            // Si hay 3+ palabras: puede ser nombre + segundo nombre + apellido o nombre + apellido + segundo apellido
            let apellido = partes.length === 2 ? partes[1] : partes[Math.floor(partes.length / 2)];
            return `${inicial}. ${apellido}`;
        }
        return nombreCompleto;
    }

    function esMovil() {
        return window.innerWidth <= 500;
    }

    function renderizarContactos(contactos) {
        const container = document.getElementById('chatContacts');
        container.innerHTML = '';

        contactos.forEach(contacto => {
            const div = document.createElement('div');
            div.className = 'chat-contact-item' + (contacto.es_admin ? ' es-admin' : '');
            div.dataset.id = contacto.id;
            div.onclick = () => seleccionarContacto(contacto);

            const nombreMostrar = formatearNombreMovil(contacto.nombre);

            const tipoHtml = contacto.tipo === 'administrador' ? '' : '<div class="chat-contact-tipo">Docente</div>';

            div.innerHTML = `
                <div class="chat-contact-name">${nombreMostrar}</div>
                ${tipoHtml}
                <span class="chat-contact-badge ${contacto.mensajes_no_leidos > 0 ? '' : 'hidden'}">${contacto.mensajes_no_leidos}</span>
            `;

            container.appendChild(div);
        });
    }

    function seleccionarContacto(contacto) {
        contactoActual = contacto;

        // Marcar activo
        document.querySelectorAll('.chat-contact-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.id == contacto.id) {
                item.classList.add('active');
            }
        });

        // Habilitar input
        document.getElementById('chatInput').disabled = false;
        document.getElementById('chatSendBtn').disabled = false;

        // Cargar mensajes
        cargarMensajesChat(contacto.id);
    }

    function cargarMensajesChat(contactoId) {
        fetch(`api/chat_obtener_mensajes.php?contacto_id=${contactoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarMensajes(data.mensajes);
                    // Actualizar badge del contacto
                    const badge = document.querySelector(`.chat-contact-item[data-id="${contactoId}"] .chat-contact-badge`);
                    if (badge) {
                        badge.classList.add('hidden');
                        badge.textContent = '0';
                    }
                    actualizarBadgeTotal();
                }
            })
            .catch(error => console.error('Error cargando mensajes:', error));
    }

    function renderizarMensajes(mensajes) {
        const container = document.getElementById('chatMessages');

        if (mensajes.length === 0) {
            container.innerHTML = '<div class="chat-no-messages">No hay mensajes aún. ¡Inicia la conversación!</div>';
            return;
        }

        container.innerHTML = '';
        mensajes.forEach(msg => {
            const div = document.createElement('div');
            div.className = `chat-message ${msg.tipo}`;

            const fecha = new Date(msg.fecha_envio);
            const hora = fecha.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });

            div.innerHTML = `
                ${msg.mensaje}
                <div class="chat-message-time">${hora}</div>
            `;
            container.appendChild(div);
        });

        // Scroll al final
        container.scrollTop = container.scrollHeight;
    }

    function enviarMensajeChat() {
        const input = document.getElementById('chatInput');
        const mensaje = input.value.trim();

        if (!mensaje || !contactoActual) return;

        fetch('api/chat_enviar_mensaje.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contacto_id: contactoActual.id,
                mensaje: mensaje
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                cargarMensajesChat(contactoActual.id);
            }
        })
        .catch(error => console.error('Error enviando mensaje:', error));
    }

    // Enviar con Enter
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            enviarMensajeChat();
        }
    });

    function iniciarRefreshChat() {
        chatRefreshInterval = setInterval(() => {
            if (contactoActual) {
                cargarMensajesChat(contactoActual.id);
            }
            cargarContactosChat();
        }, 2000);
    }

    function detenerRefreshChat() {
        if (chatRefreshInterval) {
            clearInterval(chatRefreshInterval);
            chatRefreshInterval = null;
        }
    }

    function actualizarBadgeTotal() {
        fetch('api/chat_contar_no_leidos.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('chatFabBadge');
                if (data.total_no_leidos > 0) {
                    badge.textContent = data.total_no_leidos > 99 ? '99+' : data.total_no_leidos;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
    }

    // Verificar mensajes no leídos cada 3 segundos
    setInterval(actualizarBadgeTotal, 3000);
    actualizarBadgeTotal();
    </script>
</body>
</html>

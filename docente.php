<?php
// ============================================================
// PORTAL DOCENTE - Sistema de Gestión Académica
// ============================================================
session_start();

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar que sea un docente
if ($_SESSION['tipo_usuario'] !== 'docente') {
    header('Location: login.php');
    exit;
}

// Verificar timeout de sesión (5 minutos = 300 segundos)
$timeout = 300;
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['ultima_actividad'] = time();

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

// Obtener datos del docente
$docente_id = null;
$docente_nombres = $_SESSION['nombres'] ?? '';
$docente_apellidos = $_SESSION['apellidos'] ?? '';
$establecimiento_id = $_SESSION['establecimiento_id'];

// Obtener ID del docente desde tb_docentes
$sql_docente = "SELECT id, nombres, apellidos FROM tb_docentes WHERE usuario_id = ?";
$stmt = $conn->prepare($sql_docente);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $docente_data = $result->fetch_assoc();
    $docente_id = $docente_data['id'];
    $docente_nombres = $docente_data['nombres'];
    $docente_apellidos = $docente_data['apellidos'];
}
$stmt->close();

// Obtener cursos asignados al docente
$cursos_docente = [];
$sql_cursos = "SELECT DISTINCT c.id, c.nombre
               FROM tb_cursos c
               INNER JOIN tb_asignaciones a ON c.id = a.curso_id
               WHERE a.docente_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE AND c.activo = TRUE
               ORDER BY c.nombre";
$stmt = $conn->prepare($sql_cursos);
$stmt->bind_param("ii", $docente_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cursos_docente[] = $row;
}
$stmt->close();

// Obtener asignaturas asignadas al docente
$asignaturas_docente = [];
$sql_asignaturas = "SELECT DISTINCT asig.id, asig.nombre
                    FROM tb_asignaturas asig
                    INNER JOIN tb_asignaciones a ON asig.id = a.asignatura_id
                    WHERE a.docente_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE AND asig.activo = TRUE
                    ORDER BY asig.nombre";
$stmt = $conn->prepare($sql_asignaturas);
$stmt->bind_param("ii", $docente_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $asignaturas_docente[] = $row;
}
$stmt->close();

// Obtener asignaciones completas (curso-asignatura) del docente para filtrado dinámico
$asignaciones_docente = [];
$sql_asignaciones = "SELECT a.curso_id, a.asignatura_id, c.nombre as curso_nombre, asig.nombre as asignatura_nombre
                     FROM tb_asignaciones a
                     INNER JOIN tb_cursos c ON a.curso_id = c.id
                     INNER JOIN tb_asignaturas asig ON a.asignatura_id = asig.id
                     WHERE a.docente_id = ? AND a.establecimiento_id = ? AND a.activo = TRUE
                     ORDER BY c.nombre, asig.nombre";
$stmt = $conn->prepare($sql_asignaciones);
$stmt->bind_param("ii", $docente_id, $establecimiento_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $asignaciones_docente[] = $row;
}
$stmt->close();

$conn->close();

// Generar iniciales para el avatar
$iniciales = '';
if (!empty($docente_nombres)) {
    $iniciales .= strtoupper(substr($docente_nombres, 0, 1));
}
if (!empty($docente_apellidos)) {
    $iniciales .= strtoupper(substr($docente_apellidos, 0, 1));
}
if (empty($iniciales)) {
    $iniciales = 'D';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Docente - Sistema de Gestión Académica</title>
    <link rel="stylesheet" href="css_colegio/docente.css">
    <link rel="stylesheet" href="css_colegio/modales-footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Librería para exportar a Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <!-- Librería para exportar a PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
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
                        <h1>Portal</h1>
                        <span class="brand-subtitle">Docente</span>
                    </div>
                </div>
                <div class="header-user">
                    <div class="user-avatar"><?php echo htmlspecialchars($iniciales); ?></div>
                    <div class="user-info">
                        <span
                            class="user-name"><?php echo htmlspecialchars($docente_nombres . ' ' . $docente_apellidos); ?></span>
                        <span class="user-role">Profesor</span>
                    </div>
                    <button class="btn-logout" onclick="cerrarSesion()" title="Cerrar Sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
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
                        <button class="tab-btn active" data-tab="agregar-nota">Agregar Nota</button>
                        <button class="tab-btn" data-tab="modificar-nota">Modificar Nota</button>
                        <button class="tab-btn" data-tab="ver-notas">Ver Notas</button>
                        <button class="tab-btn" data-tab="progreso">Progreso</button>
                    </nav>

                    <!-- Contenido de las Pestañas -->
                    <div class="tabs-content">

                        <!-- ==================== PESTAÑA: AGREGAR NOTA ==================== -->
                        <div id="agregar-nota" class="tab-panel active">
                            <div class="page-header">
                                <h3>Agregar Nueva Nota</h3>
                                <p>Registre las calificaciones de sus estudiantes</p>
                            </div>

                            <!-- Sub-pestañas para modo responsive -->
                            <div class="sub-tabs-container">
                                <nav class="sub-tabs-nav">
                                    <button class="sub-tab-btn active" data-subtab="registrar-nota">Registrar
                                        Nota</button>
                                    <button class="sub-tab-btn" data-subtab="ultimas-notas">Últimas Notas</button>
                                </nav>
                            </div>

                            <div class="two-columns">
                                <!-- Columna Izquierda: Formulario -->
                                <div class="column" id="subtab-registrar-nota">
                                    <div class="card">
                                        <div class="card-header hide-responsive">
                                            <h4>Registro de Calificación</h4>
                                        </div>
                                        <div class="card-body">
                                            <form id="formAgregarNota">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="cursoNuevaNota">Curso</label>
                                                        <select id="cursoNuevaNota" class="form-control" required>
                                                            <option value="">Seleccionar curso</option>
                                                            <?php foreach ($cursos_docente as $curso): ?>
                                                                <option value="<?php echo $curso['id']; ?>">
                                                                    <?php echo htmlspecialchars($curso['nombre']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="asignaturaNuevaNota">Asignatura</label>
                                                        <select id="asignaturaNuevaNota" class="form-control" required>
                                                            <option value="">Primero seleccione un curso</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="alumnoNuevaNota">Alumno</label>
                                                        <div class="autocomplete-container">
                                                            <input type="text" id="alumnoNuevaNota"
                                                                class="form-control autocomplete-input"
                                                                placeholder="Buscar alumno..." autocomplete="off"
                                                                required>
                                                            <input type="hidden" id="alumnoNuevaNotaValue">
                                                            <button type="button" class="autocomplete-arrow"
                                                                onclick="mostrarTodosAlumnos('alumnoNuevaNota', 'dropdownAlumnoNuevaNota')">
                                                                <svg width="12" height="12" viewBox="0 0 24 24"
                                                                    fill="none" stroke="currentColor" stroke-width="2">
                                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                                </svg>
                                                            </button>
                                                            <div class="autocomplete-dropdown"
                                                                id="dropdownAlumnoNuevaNota"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="trimestreNuevaNota">Trimestre</label>
                                                        <select id="trimestreNuevaNota" class="form-control" required>
                                                            <option value="">Seleccionar trimestre</option>
                                                            <option value="1">Primer Trimestre</option>
                                                            <option value="2">Segundo Trimestre</option>
                                                            <option value="3">Tercer Trimestre</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-row form-row-checkboxes">
                                                    <div class="form-group-checkbox">
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" id="notaPendiente"
                                                                onchange="toggleNotaPendiente()">
                                                            <span class="checkbox-text">Nota pendiente</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="notaNueva">Nota (1.0 - 7.0)</label>
                                                        <input type="number" id="notaNueva" class="form-control"
                                                            min="1.0" max="7.0" step="0.1" placeholder="Ej: 6.5"
                                                            required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fechaNuevaNota">Fecha</label>
                                                        <input type="date" id="fechaNuevaNota" class="form-control"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="comentarioNuevaNota">Comentario (Opcional)</label>
                                                    <textarea id="comentarioNuevaNota" class="form-control" rows="3"
                                                        placeholder="Ingrese alguna observación..."></textarea>
                                                </div>
                                                <div class="form-actions">
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="limpiarFormularioNota()">Limpiar</button>
                                                    <button type="submit" class="btn btn-primary">Registrar
                                                        Nota</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Últimas notas -->
                                <div class="column" id="subtab-ultimas-notas">
                                    <div class="card">
                                        <div class="card-header hide-responsive">
                                            <h4>Últimas Notas Registradas</h4>
                                            <span class="contador-simple" id="contadorUltimasNotas">0 registros</span>
                                        </div>
                                        <div class="card-body">
                                            <!-- Filtros de búsqueda -->
                                            <div class="filtros-ultimas-notas">
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="filtroUltCurso">Curso</label>
                                                        <select id="filtroUltCurso" class="form-control"
                                                            onchange="filtrarUltimasNotas()">
                                                            <option value="">Todos</option>
                                                            <?php foreach ($cursos_docente as $curso): ?>
                                                                <option value="<?php echo $curso['id']; ?>">
                                                                    <?php echo htmlspecialchars($curso['nombre']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="filtroUltAlumno">Alumno</label>
                                                        <input type="text" id="filtroUltAlumno" class="form-control"
                                                            placeholder="Buscar..." oninput="filtrarUltimasNotas()">
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label for="filtroUltAsignatura">Asignatura</label>
                                                        <select id="filtroUltAsignatura" class="form-control"
                                                            onchange="filtrarUltimasNotas()">
                                                            <option value="">Todas</option>
                                                            <?php foreach ($asignaturas_docente as $asig): ?>
                                                                <option value="<?php echo $asig['id']; ?>">
                                                                    <?php echo htmlspecialchars($asig['nombre']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="filtroUltFecha">Fecha</label>
                                                        <input type="date" id="filtroUltFecha" class="form-control"
                                                            onchange="filtrarUltimasNotas()">
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Tabla con scroll -->
                                            <div class="tabla-ultimas-notas-container">
                                                <table class="table-notas tabla-ultimas-notas">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Alumno</th>
                                                            <th class="col-curso-hide">Curso</th>
                                                            <th>Asig.</th>
                                                            <th>Nota</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tablaUltimasNotas">
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">No hay notas
                                                                registradas</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== PESTAÑA: MODIFICAR NOTA ==================== -->
                        <div id="modificar-nota" class="tab-panel">
                            <div class="page-header">
                                <h3>Modificar Notas</h3>
                                <p>Edite o elimine calificaciones registradas</p>
                            </div>

                            <!-- Búsqueda -->
                            <div class="card">
                                <div class="card-header">
                                    <h4>Buscar Nota</h4>
                                </div>
                                <div class="card-body">
                                    <div class="filtros-grid">
                                        <div class="form-group">
                                            <label for="buscarCurso">Curso</label>
                                            <select id="buscarCurso" class="form-control">
                                                <option value="">Todos los cursos</option>
                                                <?php foreach ($cursos_docente as $curso): ?>
                                                    <option value="<?php echo $curso['id']; ?>">
                                                        <?php echo htmlspecialchars($curso['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="buscarAsignatura">Asignatura</label>
                                            <select id="buscarAsignatura" class="form-control">
                                                <option value="">Todas</option>
                                                <?php foreach ($asignaturas_docente as $asig): ?>
                                                    <option value="<?php echo $asig['id']; ?>">
                                                        <?php echo htmlspecialchars($asig['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="buscarAlumno">Alumno</label>
                                            <div class="autocomplete-container">
                                                <input type="text" id="buscarAlumno"
                                                    class="form-control autocomplete-input"
                                                    placeholder="Buscar alumno..." autocomplete="off">
                                                <input type="hidden" id="buscarAlumnoValue">
                                                <button type="button" class="autocomplete-arrow"
                                                    onclick="mostrarTodosAlumnos('buscarAlumno', 'dropdownBuscarAlumno')">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"></polyline>
                                                    </svg>
                                                </button>
                                                <div class="autocomplete-dropdown" id="dropdownBuscarAlumno"></div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="buscarFecha">Fecha</label>
                                            <input type="date" id="buscarFecha" class="form-control">
                                        </div>
                                        <div class="form-group filtro-actions">
                                            <button class="btn btn-primary" onclick="buscarNotas()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <circle cx="11" cy="11" r="8" />
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                                </svg>
                                                Buscar
                                            </button>
                                            <button class="btn btn-secondary"
                                                onclick="limpiarBusqueda()">Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resultados -->
                            <div class="card mt-20">
                                <div class="card-header">
                                    <h4>Resultados</h4>
                                    <span class="contador-simple" id="contadorResultados">0 notas</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive tabla-modificar-notas-container">
                                        <table class="table-notas tabla-modificar-notas">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Alumno</th>
                                                    <th>Asignatura</th>
                                                    <th>Nota</th>
                                                    <th>Trim.</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaResultadosBusqueda">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Realice una búsqueda
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== PESTAÑA: VER NOTAS ==================== -->
                        <div id="ver-notas" class="tab-panel">
                            <div class="page-header">
                                <h3>Ver Notas del Curso</h3>
                                <p>Consulte las calificaciones de todos los estudiantes</p>
                            </div>

                            <!-- Filtros -->
                            <div class="card">
                                <div class="card-header">
                                    <h4>Filtros</h4>
                                </div>
                                <div class="card-body">
                                    <div class="filtros-grid filtros-ver-notas">
                                        <div class="form-group filtro-curso">
                                            <label for="verCurso">Curso</label>
                                            <select id="verCurso" class="form-control" required>
                                                <option value="">Seleccionar</option>
                                                <?php foreach ($cursos_docente as $curso): ?>
                                                    <option value="<?php echo $curso['id']; ?>">
                                                        <?php echo htmlspecialchars($curso['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group filtro-asignatura">
                                            <label for="verAsignatura">Asignatura</label>
                                            <select id="verAsignatura" class="form-control" required>
                                                <option value="">Primero seleccione un curso</option>
                                            </select>
                                        </div>
                                        <div class="form-group filtro-alumno">
                                            <label for="filtrarAlumnoVer">Alumno</label>
                                            <div class="autocomplete-container">
                                                <input type="text" id="filtrarAlumnoVer"
                                                    class="form-control autocomplete-input"
                                                    placeholder="Buscar alumno..." autocomplete="off">
                                                <input type="hidden" id="filtrarAlumnoVerValue">
                                                <button type="button" class="autocomplete-arrow"
                                                    onclick="mostrarTodosAlumnos('filtrarAlumnoVer', 'dropdownFiltrarAlumnoVer')">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"></polyline>
                                                    </svg>
                                                </button>
                                                <div class="autocomplete-dropdown" id="dropdownFiltrarAlumnoVer"></div>
                                            </div>
                                        </div>
                                        <div class="form-group filtro-nota-min">
                                            <label for="filtrarNotaMin">Nota Mín.</label>
                                            <input type="number" id="filtrarNotaMin" class="form-control" min="1"
                                                max="7" step="0.1" placeholder="1.0">
                                        </div>
                                        <div class="form-group filtro-nota-max">
                                            <label for="filtrarNotaMax">Nota Máx.</label>
                                            <input type="number" id="filtrarNotaMax" class="form-control" min="1"
                                                max="7" step="0.1" placeholder="7.0">
                                        </div>
                                        <div class="form-group filtro-actions">
                                            <button class="btn btn-primary"
                                                onclick="cargarNotasCurso()">Consultar</button>
                                            <button class="btn btn-secondary"
                                                onclick="limpiarFiltrosVer()">Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de notas -->
                            <div class="card mt-20">
                                <div class="card-header">
                                    <h4>Calificaciones del Curso</h4>
                                    <div class="header-actions">
                                        <span class="contador-simple" id="contadorNotasCurso">0 registros</span>
                                        <button class="btn btn-sm btn-outline" onclick="exportarNotasCurso('excel')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                            Excel
                                        </button>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="exportarNotasCurso('pdf')">PDF</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table-notas tabla-trimestres">
                                            <thead>
                                                <tr>
                                                    <th rowspan="2" class="th-fixed">N°</th>
                                                    <th rowspan="2" class="th-fixed th-alumno">Alumno</th>
                                                    <th colspan="9" class="th-trimestre">Trimestre 1</th>
                                                    <th colspan="9" class="th-trimestre">Trimestre 2</th>
                                                    <th colspan="9" class="th-trimestre">Trimestre 3</th>
                                                    <th rowspan="2" class="th-fixed th-final">Prom. Final</th>
                                                    <th rowspan="2" class="th-fixed">Estado</th>
                                                </tr>
                                                <tr>
                                                    <th class="th-sub">N1</th>
                                                    <th class="th-sub">N2</th>
                                                    <th class="th-sub">N3</th>
                                                    <th class="th-sub">N4</th>
                                                    <th class="th-sub">N5</th>
                                                    <th class="th-sub">N6</th>
                                                    <th class="th-sub">N7</th>
                                                    <th class="th-sub">N8</th>
                                                    <th class="th-sub th-prom">Prom</th>
                                                    <th class="th-sub">N1</th>
                                                    <th class="th-sub">N2</th>
                                                    <th class="th-sub">N3</th>
                                                    <th class="th-sub">N4</th>
                                                    <th class="th-sub">N5</th>
                                                    <th class="th-sub">N6</th>
                                                    <th class="th-sub">N7</th>
                                                    <th class="th-sub">N8</th>
                                                    <th class="th-sub th-prom">Prom</th>
                                                    <th class="th-sub">N1</th>
                                                    <th class="th-sub">N2</th>
                                                    <th class="th-sub">N3</th>
                                                    <th class="th-sub">N4</th>
                                                    <th class="th-sub">N5</th>
                                                    <th class="th-sub">N6</th>
                                                    <th class="th-sub">N7</th>
                                                    <th class="th-sub">N8</th>
                                                    <th class="th-sub th-prom">Prom</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaNotasCurso">
                                                <tr>
                                                    <td colspan="31" class="text-center text-muted">Seleccione curso y
                                                        asignatura</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== PESTAÑA: PROGRESO ==================== -->
                        <div id="progreso" class="tab-panel">
                            <div class="page-header">
                                <h3>Análisis de Progreso</h3>
                                <p>Visualice el rendimiento académico de sus estudiantes</p>
                            </div>

                            <!-- Filtros -->
                            <div class="card">
                                <div class="card-header">
                                    <h4>Parámetros de Análisis</h4>
                                </div>
                                <div class="card-body">
                                    <div class="filtros-grid">
                                        <div class="form-group">
                                            <label for="progresoCurso">Curso</label>
                                            <select id="progresoCurso" class="form-control" required>
                                                <option value="">Seleccionar</option>
                                                <?php foreach ($cursos_docente as $curso): ?>
                                                    <option value="<?php echo $curso['id']; ?>">
                                                        <?php echo htmlspecialchars($curso['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="progresoAsignatura">Asignatura</label>
                                            <select id="progresoAsignatura" class="form-control" required>
                                                <option value="">Primero seleccione un curso</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="progresoTrimestre">Trimestre</label>
                                            <select id="progresoTrimestre" class="form-control">
                                                <option value="">Todos</option>
                                                <option value="1">Primero</option>
                                                <option value="2">Segundo</option>
                                                <option value="3">Tercero</option>
                                            </select>
                                        </div>
                                        <div class="form-group filtro-actions">
                                            <button class="btn btn-primary" onclick="analizarProgreso()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <line x1="18" y1="20" x2="18" y2="10" />
                                                    <line x1="12" y1="20" x2="12" y2="4" />
                                                    <line x1="6" y1="20" x2="6" y2="14" />
                                                </svg>
                                                Analizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- KPIs -->
                            <div class="kpis-grid mt-20">
                                <div class="kpi-card kpi-primary">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiTotalAlumnos">--</span>
                                        <span class="kpi-label">Total Alumnos</span>
                                    </div>
                                </div>

                                <div class="kpi-card kpi-success">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                            <polyline points="22 4 12 14.01 9 11.01" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiAprobados">--</span>
                                        <div class="kpi-label-row">
                                            <span class="kpi-label">Aprobados</span>
                                            <span class="kpi-trend kpi-trend-up" id="kpiPorcentajeAprobados">--%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="kpi-card kpi-danger">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="12" y1="8" x2="12" y2="12" />
                                            <line x1="12" y1="16" x2="12.01" y2="16" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiReprobados">--</span>
                                        <div class="kpi-label-row">
                                            <span class="kpi-label">Necesitan Apoyo</span>
                                            <span class="kpi-trend kpi-trend-down"
                                                id="kpiPorcentajeReprobados">--%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="kpi-card kpi-info">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20V10" />
                                            <path d="M18 20V4" />
                                            <path d="M6 20v-4" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiPromedioCurso">--</span>
                                        <span class="kpi-label">Promedio Curso</span>
                                    </div>
                                </div>

                                <div class="kpi-card kpi-warning">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon
                                                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiNotaMaxima">--</span>
                                        <span class="kpi-label">Nota Maxima</span>
                                    </div>
                                </div>

                                <div class="kpi-card kpi-secondary">
                                    <div class="kpi-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 20V10" />
                                            <path d="M12 20V4" />
                                            <path d="M6 20v-6" />
                                        </svg>
                                    </div>
                                    <div class="kpi-data">
                                        <span class="kpi-value" id="kpiNotaMinima">--</span>
                                        <span class="kpi-label">Nota Minima</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráficos -->
                            <div class="charts-grid mt-20">
                                <div class="card chart-card">
                                    <div class="card-header">
                                        <h4>Distribución de Notas</h4>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="descargarGrafico('chartDistribucion', 'distribucion')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chartDistribucion"></canvas>
                                    </div>
                                </div>

                                <div class="card chart-card">
                                    <div class="card-header">
                                        <h4>Rendimiento por Trimestre</h4>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="descargarGrafico('chartTrimestre', 'trimestre')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chartTrimestre"></canvas>
                                    </div>
                                </div>

                                <div class="card chart-card">
                                    <div class="card-header">
                                        <h4>Tasa de Aprobación</h4>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="descargarGrafico('chartAprobacion', 'aprobacion')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="card-body chart-container chart-container-sm">
                                        <canvas id="chartAprobacion"></canvas>
                                    </div>
                                </div>

                                <div class="card chart-card">
                                    <div class="card-header">
                                        <h4>Top 5 Mejores Promedios</h4>
                                        <button class="btn btn-sm btn-outline"
                                            onclick="descargarGrafico('chartTop5', 'top5')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="7 10 12 15 17 10" />
                                                <line x1="12" y1="15" x2="12" y2="3" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chartTop5"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla alumnos atención -->
                            <div class="card mt-20">
                                <div class="card-header">
                                    <h4>Alumnos que Requieren Atención</h4>
                                    <button class="btn btn-sm btn-outline"
                                        onclick="exportarAlumnosAtencion()">Exportar</button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table-notas tabla-atencion">
                                            <thead>
                                                <tr>
                                                    <th>Alumno</th>
                                                    <th>Prom.</th>
                                                    <th>Rojas</th>
                                                    <th>Tendencia</th>
                                                    <th>Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaAtencion">
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Seleccione los
                                                        parámetros</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Editar Nota -->
    <div id="modalEditarNota" class="modal-overlay" onclick="cerrarModalEditar(event)">
        <div class="modal-corporativo" onclick="event.stopPropagation()">
            <div class="modal-corp-header">
                <div class="modal-corp-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                </div>
                <div class="modal-corp-title">
                    <h3>Editar Calificacion</h3>
                    <p>Modifique los datos de la nota registrada</p>
                </div>
                <button class="modal-corp-cerrar" onclick="cerrarModalEditar()" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <form id="formEditarNota">
                <div class="modal-corp-body">
                    <input type="hidden" id="editarNotaId">

                    <div class="modal-corp-section">
                        <h4 class="modal-corp-section-title">
                            <span class="section-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            Informacion del Estudiante
                        </h4>
                        <div class="modal-corp-grid">
                            <div class="modal-corp-field">
                                <label for="editarAlumno">Alumno</label>
                                <input type="text" id="editarAlumno" class="modal-corp-input readonly" readonly>
                            </div>
                            <div class="modal-corp-field">
                                <label for="editarCurso">Curso</label>
                                <input type="text" id="editarCurso" class="modal-corp-input readonly" readonly>
                            </div>
                            <div class="modal-corp-field">
                                <label for="editarAsignatura">Asignatura</label>
                                <input type="text" id="editarAsignatura" class="modal-corp-input readonly" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="modal-corp-section">
                        <h4 class="modal-corp-section-title">
                            <span class="section-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                </svg>
                            </span>
                            Datos de la Calificacion
                        </h4>
                        <div class="modal-corp-grid">
                            <div class="modal-corp-field">
                                <label for="editarNota">Nota</label>
                                <input type="number" id="editarNota" class="modal-corp-input" min="1.0" max="7.0"
                                    step="0.1" required>
                            </div>
                            <div class="modal-corp-field">
                                <label for="editarTrimestre">Trimestre</label>
                                <select id="editarTrimestre" class="modal-corp-input" required>
                                    <option value="1">Primer Trimestre</option>
                                    <option value="2">Segundo Trimestre</option>
                                    <option value="3">Tercer Trimestre</option>
                                </select>
                            </div>
                            <div class="modal-corp-field">
                                <label for="editarFecha">Fecha</label>
                                <input type="date" id="editarFecha" class="modal-corp-input" required>
                            </div>
                        </div>
                        <div class="modal-corp-field full-width">
                            <label for="editarComentario">Observaciones</label>
                            <textarea id="editarComentario" class="modal-corp-input" rows="3"
                                placeholder="Ingrese comentarios adicionales sobre la calificacion..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-corp-footer">
                    <button type="button" class="btn-corp btn-corp-secondary" onclick="cerrarModalEditar()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-corp btn-corp-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div id="modalConfirmarEliminar" class="modal-overlay" onclick="cerrarModalEliminar(event)">
        <div class="modal-corporativo modal-corp-sm" onclick="event.stopPropagation()">
            <div class="modal-corp-header modal-corp-header-danger">
                <div class="modal-corp-icon danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                        <line x1="10" y1="11" x2="10" y2="17" />
                        <line x1="14" y1="11" x2="14" y2="17" />
                    </svg>
                </div>
                <div class="modal-corp-title">
                    <h3>Eliminar Calificacion</h3>
                    <p>Esta accion requiere confirmacion</p>
                </div>
                <button class="modal-corp-cerrar" onclick="cerrarModalEliminar()" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="modal-corp-body">
                <div class="modal-corp-alert danger">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </div>
                    <div class="alert-content">
                        <h4>Confirmar eliminacion</h4>
                        <p>Esta a punto de eliminar permanentemente esta calificacion del sistema. Esta accion no se
                            puede deshacer.</p>
                    </div>
                </div>
                <input type="hidden" id="eliminarNotaId">
            </div>
            <div class="modal-corp-footer">
                <button type="button" class="btn-corp btn-corp-secondary" onclick="cerrarModalEliminar()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Cancelar
                </button>
                <button type="button" class="btn-corp btn-corp-danger" onclick="confirmarEliminarNota()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                    Eliminar Nota
                </button>
            </div>
        </div>
    </div>

    <!-- Variables PHP para JavaScript -->
    <script>
        const DOCENTE_ID = <?php echo $docente_id ? $docente_id : 'null'; ?>;
        const ESTABLECIMIENTO_ID = <?php echo $establecimiento_id; ?>;
        const USUARIO_ID = <?php echo $_SESSION['usuario_id']; ?>;
        const NOMBRE_DOCENTE = "<?php echo htmlspecialchars($docente_nombres . ' ' . $docente_apellidos); ?>";
        // Asignaciones del docente para filtrado dinámico de curso-asignatura
        const ASIGNACIONES_DOCENTE = <?php echo json_encode($asignaciones_docente); ?>;
    </script>
    <script src="js_colegio/docente.js"></script>
    <script src="js_colegio/session_timeout.js"></script>

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

        /* Estilo especial para el administrador - destacado */
        .chat-contact-item.es-admin {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }

        .chat-contact-item.es-admin::before {
            content: '★';
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
            color: #f59e0b;
        }

        .chat-contact-item.es-admin .chat-contact-name {
            font-weight: 600;
            color: #92400e;
        }

        .chat-contact-item.es-admin .chat-contact-tipo {
            color: #b45309;
            font-weight: 500;
        }

        .chat-contact-item.es-admin.active {
            background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
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

        .chat-contact-item.es-admin .chat-contact-badge {
            right: 20px;
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
                Chat Interno
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
                        Selecciona un contacto para iniciar una conversación
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
                let apellido = partes.length === 2 ? partes[1] : partes[Math.floor(partes.length / 2)];
                return `${inicial}. ${apellido}`;
            }
            return nombreCompleto;
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
        document.getElementById('chatInput').addEventListener('keypress', function (e) {
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
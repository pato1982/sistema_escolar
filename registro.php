<?php
// ============================================================
// REGISTRO DE USUARIOS - PORTAL ESTUDIANTIL
// ============================================================

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

// Obtener establecimientos desde la base de datos
$establecimientos = [];
$resultEst = $conn->query("SELECT id, nombre FROM tb_establecimientos WHERE activo = 1 ORDER BY nombre");
if ($resultEst) {
    while ($row = $resultEst->fetch_assoc()) {
        $establecimientos[] = $row;
    }
}

// Obtener cursos desde la base de datos
$cursos = [];
$resultCursos = $conn->query("SELECT id, nombre, codigo, establecimiento_id FROM tb_cursos WHERE activo = 1 ORDER BY nombre");
if ($resultCursos) {
    while ($row = $resultCursos->fetch_assoc()) {
        $cursos[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Sistema de Gestión Académica</title>
    <link rel="stylesheet" href="css_colegio/colegio.css">
    <link rel="stylesheet" href="css_colegio/registro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="registro-container">
        <!-- Header -->
        <header class="registro-header">
        </header>

        <!-- Contenido Principal -->
        <main class="registro-main">
            <div class="registro-wrapper">
                <a href="index.php" class="btn-volver-lateral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="registro-card">
                    <div class="registro-card-header">
                        <div class="brand">
                            <div class="logo">
                                <span class="logo-icon">E</span>
                            </div>
                            <div class="brand-text">
                                <h1>Sistema de Gestión Académica</h1>
                            </div>
                        </div>
                        <h2>Registro de Usuario</h2>
                    </div>

                <!-- Indicador de Pasos -->
                <div class="pasos-indicador">
                    <div class="paso-item active" data-paso="1">
                        <div class="paso-numero">1</div>
                        <span class="paso-texto">Apoderado</span>
                    </div>
                    <div class="paso-linea"></div>
                    <div class="paso-item" data-paso="2">
                        <div class="paso-numero">2</div>
                        <span class="paso-texto">Alumno</span>
                    </div>
                    <div class="paso-linea"></div>
                    <div class="paso-item" data-paso="3">
                        <div class="paso-numero">3</div>
                        <span class="paso-texto">Contraseña</span>
                    </div>
                </div>

                <!-- Paso 0: Selección de Tipo de Usuario -->
                <div id="paso-tipo" class="paso-contenido active">
                    <h3>Seleccione el tipo de usuario</h3>
                    <div class="tipo-usuario-grid">
                        <div class="tipo-usuario-card" data-tipo="apoderado">
                            <div class="tipo-icono">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <h4>Apoderado</h4>
                            <p>Padre, madre o tutor de un estudiante</p>
                        </div>
                        <div class="tipo-usuario-card" data-tipo="docente">
                            <div class="tipo-icono">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                            </div>
                            <h4>Docente</h4>
                            <p>Profesor o profesora del establecimiento</p>
                        </div>
                        <div class="tipo-usuario-card" data-tipo="administrador">
                            <div class="tipo-icono">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            </div>
                            <h4>Administrador</h4>
                            <p>Personal administrativo del colegio</p>
                        </div>
                    </div>
                </div>

                <!-- Paso 1: Datos del Apoderado -->
                <div id="paso-1" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Datos del Apoderado</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="completarPaso1()">Completar con ejemplo</button>
                    </div>
                    <form id="formPaso1">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombreApoderado">Nombres</label>
                                <input type="text" id="nombreApoderado" class="form-control" placeholder="Ej: María José" required>
                            </div>
                            <div class="form-group">
                                <label for="apellidoApoderado">Apellidos</label>
                                <input type="text" id="apellidoApoderado" class="form-control" placeholder="Ej: González Pérez" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rutApoderado">RUT</label>
                                <input type="text" id="rutApoderado" class="form-control" placeholder="Ej: 12.345.678-9" required>
                            </div>
                            <div class="form-group">
                                <label for="telefonoApoderado">Teléfono</label>
                                <input type="tel" id="telefonoApoderado" class="form-control" placeholder="Ej: +56 9 1234 5678" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="correoApoderado">Correo Electrónico</label>
                                <input type="email" id="correoApoderado" class="form-control" placeholder="Ej: correo@ejemplo.com" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarCorreoApoderado">Confirmar Correo</label>
                                <input type="email" id="confirmarCorreoApoderado" class="form-control" placeholder="Repetir correo electrónico" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="direccionApoderado">Dirección</label>
                                <input type="text" id="direccionApoderado" class="form-control" placeholder="Ej: Av. Principal 123" required>
                            </div>
                            <div class="form-group">
                                <label for="parentescoApoderado">Parentesco</label>
                                <select id="parentescoApoderado" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Padre">Padre</option>
                                    <option value="Madre">Madre</option>
                                    <option value="Tutor Legal">Tutor Legal</option>
                                    <option value="Abuelo/a">Abuelo/a</option>
                                    <option value="Tío/a">Tío/a</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row form-row-single">
                            <div class="form-group form-group-full">
                                <label for="establecimientoApoderado"><span class="label-full">Establecimiento Educativo</span><span class="label-short">Establecimiento</span></label>
                                <select id="establecimientoApoderado" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($establecimientos as $est): ?>
                                    <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverATipo()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="irAPaso2()">Siguiente</button>
                        </div>
                    </form>
                </div>

                <!-- Paso 2: Datos del Alumno -->
                <div id="paso-2" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Datos del Alumno</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="completarPaso2()">Completar con ejemplo</button>
                    </div>
                    <form id="formPaso2">
                        <!-- Contenedor de todos los alumnos -->
                        <div id="contenedorAlumnos">
                            <!-- Alumno 1 (principal) -->
                            <div class="alumno-card" data-alumno="1">
                                <div class="alumno-header">
                                    <span class="alumno-titulo">Alumno 1</span>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nombreAlumno_1">Nombres del Alumno</label>
                                        <input type="text" id="nombreAlumno_1" class="form-control" placeholder="Ej: Juan Pablo" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="apellidoAlumno_1">Apellidos del Alumno</label>
                                        <input type="text" id="apellidoAlumno_1" class="form-control" placeholder="Ej: González Muñoz" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="rutAlumno_1">RUT del Alumno</label>
                                        <input type="text" id="rutAlumno_1" class="form-control" placeholder="Ej: 23.456.789-0" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cursoAlumno_1">Curso</label>
                                        <select id="cursoAlumno_1" class="form-control" required>
                                            <option value="">Seleccionar</option>
                                            <option value="1A">1° Básico A</option>
                                            <option value="1B">1° Básico B</option>
                                            <option value="2A">2° Básico A</option>
                                            <option value="2B">2° Básico B</option>
                                            <option value="3A">3° Básico A</option>
                                            <option value="3B">3° Básico B</option>
                                            <option value="4A">4° Básico A</option>
                                            <option value="4B">4° Básico B</option>
                                            <option value="5A">5° Básico A</option>
                                            <option value="5B">5° Básico B</option>
                                            <option value="6A">6° Básico A</option>
                                            <option value="6B">6° Básico B</option>
                                            <option value="7A">7° Básico A</option>
                                            <option value="7B">7° Básico B</option>
                                            <option value="8A">8° Básico A</option>
                                            <option value="8B">8° Básico B</option>
                                            <option value="1MA">1° Medio A</option>
                                            <option value="1MB">1° Medio B</option>
                                            <option value="2MA">2° Medio A</option>
                                            <option value="2MB">2° Medio B</option>
                                            <option value="3MA">3° Medio A</option>
                                            <option value="3MB">3° Medio B</option>
                                            <option value="4MA">4° Medio A</option>
                                            <option value="4MB">4° Medio B</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opción para agregar otro alumno -->
                        <div class="agregar-alumno-section">
                            <label class="checkbox-container">
                                <input type="checkbox" id="agregarOtroAlumno" onchange="toggleAgregarAlumno()">
                                <span class="checkmark"></span>
                                <span class="checkbox-label">¿Desea agregar otro alumno?</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverAPaso1()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="irAPaso3()">Siguiente</button>
                        </div>
                    </form>
                </div>

                <!-- Paso 3: Crear Contraseña -->
                <div id="paso-3" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Crear Contraseña</h3>
                    </div>
                    <form id="formPaso3">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">
                                    Contraseña
                                    <span class="tooltip-trigger" onclick="toggleTooltip(event)" onmouseenter="showTooltip(this)" onmouseleave="hideTooltip(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <text x="12" y="17" text-anchor="middle" fill="white" stroke="none" font-size="14" font-weight="bold">?</text>
                                        </svg>
                                        <span class="tooltip-content">La contraseña debe tener exactamente 8 dígitos numéricos. No se permiten letras, signos, comas ni puntos.</span>
                                    </span>
                                </label>
                                <input type="password" id="password" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarPassword">Repetir Contraseña</label>
                                <input type="password" id="confirmarPassword" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverAPaso2()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="finalizarRegistro()">Finalizar Registro</button>
                        </div>
                    </form>
                </div>

                <!-- Paso Final: Confirmación -->
                <div id="paso-final" class="paso-contenido">
                    <div class="registro-exitoso">
                        <div class="icono-exito">✓</div>
                        <h3>¡Registro Exitoso!</h3>
                        <p>Su cuenta ha sido creada correctamente.</p>
                        <p>Hemos enviado un correo de confirmación a su dirección de email.</p>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='login.php'">Ir al Sistema</button>
                    </div>
                </div>

                <!-- ==================== REGISTRO DOCENTE ==================== -->

                <!-- Indicador de Pasos Docente -->
                <div class="pasos-indicador pasos-docente" style="display: none;">
                    <div class="paso-item active" data-paso="1">
                        <div class="paso-numero">1</div>
                        <span class="paso-texto">Datos</span>
                    </div>
                    <div class="paso-linea"></div>
                    <div class="paso-item" data-paso="2">
                        <div class="paso-numero">2</div>
                        <span class="paso-texto">Contraseña</span>
                    </div>
                </div>

                <!-- Paso Docente 1: Datos del Docente -->
                <div id="paso-docente-1" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Datos del Docente</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="completarPasoDocente1()">Completar con ejemplo</button>
                    </div>
                    <form id="formPasoDocente1">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombreDocente">Nombres</label>
                                <input type="text" id="nombreDocente" class="form-control" placeholder="Ej: Carlos Alberto" required>
                            </div>
                            <div class="form-group">
                                <label for="apellidoDocente">Apellidos</label>
                                <input type="text" id="apellidoDocente" class="form-control" placeholder="Ej: Martínez López" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rutDocente">RUT</label>
                                <input type="text" id="rutDocente" class="form-control" placeholder="Ej: 12.345.678-9" required>
                            </div>
                            <div class="form-group">
                                <label for="telefonoDocente">Teléfono</label>
                                <input type="tel" id="telefonoDocente" class="form-control" placeholder="Ej: +56 9 1234 5678" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="correoDocente">Correo Electrónico</label>
                                <input type="email" id="correoDocente" class="form-control" placeholder="Ej: correo@ejemplo.com" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarCorreoDocente">Confirmar Correo</label>
                                <input type="email" id="confirmarCorreoDocente" class="form-control" placeholder="Repetir correo electrónico" required>
                            </div>
                        </div>
                        <div class="form-row form-row-single">
                            <div class="form-group form-group-full">
                                <label for="establecimientoDocente"><span class="label-full">Establecimiento Educativo</span><span class="label-short">Establecimiento</span></label>
                                <select id="establecimientoDocente" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($establecimientos as $est): ?>
                                    <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverATipoDocente()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="irAPasoDocente2()">Siguiente</button>
                        </div>
                    </form>
                </div>

                <!-- Paso Docente 2: Crear Contraseña -->
                <div id="paso-docente-2" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Crear Contraseña</h3>
                    </div>
                    <form id="formPasoDocente2">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passwordDocente">
                                    Contraseña
                                    <span class="tooltip-trigger" onclick="toggleTooltip(event)" onmouseenter="showTooltip(this)" onmouseleave="hideTooltip(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <text x="12" y="17" text-anchor="middle" fill="white" stroke="none" font-size="14" font-weight="bold">?</text>
                                        </svg>
                                        <span class="tooltip-content">La contraseña debe tener exactamente 8 dígitos numéricos. No se permiten letras, signos, comas ni puntos.</span>
                                    </span>
                                </label>
                                <input type="password" id="passwordDocente" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarPasswordDocente">Repetir Contraseña</label>
                                <input type="password" id="confirmarPasswordDocente" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverAPasoDocente1()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="finalizarRegistroDocente()">Finalizar Registro</button>
                        </div>
                    </form>
                </div>

                <!-- ==================== REGISTRO ADMINISTRADOR ==================== -->

                <!-- Indicador de Pasos Administrador -->
                <div class="pasos-indicador pasos-administrador" style="display: none;">
                    <div class="paso-item active" data-paso="1">
                        <div class="paso-numero">1</div>
                        <span class="paso-texto">Datos</span>
                    </div>
                    <div class="paso-linea"></div>
                    <div class="paso-item" data-paso="2">
                        <div class="paso-numero">2</div>
                        <span class="paso-texto">Contraseña</span>
                    </div>
                </div>

                <!-- Paso Administrador 1: Datos del Administrador -->
                <div id="paso-admin-1" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Datos del Administrador</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="completarPasoAdmin1()">Completar con ejemplo</button>
                    </div>
                    <form id="formPasoAdmin1">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombreAdmin">Nombres</label>
                                <input type="text" id="nombreAdmin" class="form-control" placeholder="Ej: María Elena" required>
                            </div>
                            <div class="form-group">
                                <label for="apellidoAdmin">Apellidos</label>
                                <input type="text" id="apellidoAdmin" class="form-control" placeholder="Ej: Rodríguez Soto" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rutAdmin">RUT</label>
                                <input type="text" id="rutAdmin" class="form-control" placeholder="Ej: 12.345.678-9" required>
                            </div>
                            <div class="form-group">
                                <label for="telefonoAdmin">Teléfono</label>
                                <input type="tel" id="telefonoAdmin" class="form-control" placeholder="Ej: +56 9 1234 5678" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="correoAdmin">Correo Electrónico</label>
                                <input type="email" id="correoAdmin" class="form-control" placeholder="Ej: correo@ejemplo.com" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarCorreoAdmin">Confirmar Correo</label>
                                <input type="email" id="confirmarCorreoAdmin" class="form-control" placeholder="Repetir correo electrónico" required>
                            </div>
                        </div>
                        <div class="form-row form-row-single">
                            <div class="form-group form-group-full">
                                <label for="establecimientoAdmin"><span class="label-full">Establecimiento Educativo</span><span class="label-short">Establecimiento</span></label>
                                <select id="establecimientoAdmin" class="form-control" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($establecimientos as $est): ?>
                                    <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverATipoAdmin()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="irAPasoAdmin2()">Siguiente</button>
                        </div>
                    </form>
                </div>

                <!-- Paso Administrador 2: Código y Contraseña -->
                <div id="paso-admin-2" class="paso-contenido">
                    <div class="paso-header">
                        <h3>Crear Contraseña</h3>
                    </div>
                    <form id="formPasoAdmin2">
                        <div class="form-row form-row-single">
                            <div class="form-group form-group-full">
                                <label for="codigoValidacionAdmin">
                                    Código de Validación
                                    <span class="info-trigger" onclick="mostrarModalInfo('codigoValidacion')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <text x="12" y="17" text-anchor="middle" fill="white" stroke="none" font-size="14" font-weight="bold">?</text>
                                        </svg>
                                    </span>
                                </label>
                                <input type="text" id="codigoValidacionAdmin" class="form-control" placeholder="Ingrese el código de validación" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passwordAdmin">
                                    Contraseña
                                    <span class="tooltip-trigger" onclick="toggleTooltip(event)" onmouseenter="showTooltip(this)" onmouseleave="hideTooltip(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <text x="12" y="17" text-anchor="middle" fill="white" stroke="none" font-size="14" font-weight="bold">?</text>
                                        </svg>
                                        <span class="tooltip-content">La contraseña debe tener exactamente 8 dígitos numéricos. No se permiten letras, signos, comas ni puntos.</span>
                                    </span>
                                </label>
                                <input type="password" id="passwordAdmin" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmarPasswordAdmin">Repetir Contraseña</label>
                                <input type="password" id="confirmarPasswordAdmin" class="form-control" placeholder="Ej: 12345678" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="volverAPasoAdmin1()">Volver</button>
                            <button type="button" class="btn btn-primary" onclick="finalizarRegistroAdmin()">Finalizar Registro</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </main>

        <!-- Footer -->
        <footer class="registro-footer">
            <p>Sistema de Gestión Académica © 2024 | Todos los derechos reservados</p>
        </footer>
    </div>

    <!-- Modal de Información -->
    <div id="modalInfo" class="modal-info-overlay" onclick="cerrarModalInfo(event)">
        <div class="modal-info-contenido" onclick="event.stopPropagation()">
            <button class="modal-info-cerrar" onclick="cerrarModalInfo()">&times;</button>
            <div class="modal-info-body">
                <p id="modalInfoTexto"></p>
            </div>
        </div>
    </div>

    <!-- Modal de Error de Validación -->
    <div id="modalErrorValidacion" class="modal-error-overlay" onclick="cerrarModalError(event)">
        <div class="modal-error-contenido" onclick="event.stopPropagation()">
            <button class="modal-error-cerrar" onclick="cerrarModalError()">&times;</button>
            <div class="modal-error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h3 class="modal-error-titulo">Datos no encontrados</h3>
            <p id="modalErrorTexto" class="modal-error-mensaje"></p>
            <button class="btn btn-primary modal-error-btn" onclick="cerrarModalError()">Entendido</button>
        </div>
    </div>

    <!-- Datos de PHP para JavaScript -->
    <script>
        const establecimientosDB = <?php echo json_encode($establecimientos); ?>;
        const cursosDB = <?php echo json_encode($cursos); ?>;
    </script>
    <script src="js_colegio/registro.js"></script>
</body>
</html>

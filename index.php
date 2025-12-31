<?php
// ============================================================
// INDEX - PORTAL ESTUDIANTIL
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Estudiantil - Sistema de Gestión Académica</title>
    <link rel="stylesheet" href="css_colegio/index.css">
    <link rel="stylesheet" href="css_colegio/modales-footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="nav-logo">
                    <span class="logo-icon">E</span>
                </div>
                <span class="nav-title">Portal Estudiantil</span>
            </div>
            <div class="nav-actions">
                <a href="mailto:contacto@portalestudiantil.cl" class="btn btn-contact" title="Contacto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </a>
                <a href="login.php" class="btn btn-outline">Iniciar Sesión</a>
                <a href="registro.php" class="btn btn-primary">Registrarse</a>
            </div>
            <button class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <!-- Menú móvil -->
        <div class="nav-mobile" id="navMobile">
            <a href="mailto:contacto@portalestudiantil.cl" class="btn btn-contact" title="Contacto">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </a>
            <a href="login.php" class="btn btn-outline">Iniciar Sesión</a>
            <a href="registro.php" class="btn btn-primary">Registrarse</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Bienvenido al <span class="highlight">Portal Estudiantil</span></h1>
            <p class="hero-subtitle">
                La plataforma integral que conecta a apoderados, docentes y administradores
                para un seguimiento académico eficiente y transparente.
            </p>
            <div class="hero-actions">
                <a href="registro.php" class="btn btn-primary btn-lg">Comenzar Ahora</a>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-graphic">
                <!-- Cubo 3D giratorio -->
                <div class="cube-container">
                    <div class="cube">
                        <div class="cube-face cube-front"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                        <div class="cube-face cube-back"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                        <div class="cube-face cube-right"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                        <div class="cube-face cube-left"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                        <div class="cube-face cube-top"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                        <div class="cube-face cube-bottom"><span class="cube-letra">E</span><span class="cube-texto">Portal Estudiantil</span></div>
                    </div>
                </div>
                <div class="graphic-card card-1">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </div>
                    <span>Notas en línea</span>
                </div>
                <div class="graphic-card card-2">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    </div>
                    <span>Acceso móvil</span>
                </div>
                <div class="graphic-card card-3">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    </div>
                    <span>Comunicados</span>
                </div>
                <div class="graphic-card card-4">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    </div>
                    <span>Estadisticas</span>
                </div>
                <div class="graphic-card card-5">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <span>Apoderados</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Características -->
    <section class="caracteristicas" id="caracteristicas">
        <div class="section-container">
            <div class="section-header">
                <h2>¿Por qué elegir nuestro Portal?</h2>
                <p>Diseñada para facilitar la comunicación y el seguimiento académico entre todos los actores de la comunidad educativa.</p>
            </div>

            <!-- Apoderados -->
            <div class="feature-card feature-apoderados">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="feature-content">
                    <h3>Para Apoderados</h3>
                    <p class="feature-description">
                        Manténgase informado sobre el rendimiento académico de sus hijos de manera simple y directa.
                        Nuestra plataforma le permite estar al tanto del progreso escolar sin complicaciones.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Consulte las notas de sus pupilos en tiempo real</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Visualice el promedio por asignatura</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Reciba comunicados importantes del establecimiento</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Acceda desde cualquier dispositivo, en cualquier momento</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Docentes -->
            <div class="feature-card feature-docentes">
                <div class="feature-content">
                    <h3>Para Docentes</h3>
                    <p class="feature-description">
                        Simplifique su trabajo administrativo y dedique más tiempo a lo que realmente importa: enseñar.
                        Registre las calificaciones de sus estudiantes de forma rápida y eficiente.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Registre notas de manera sencilla e intuitiva</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Visualice las calificaciones de sus cursos y asignaturas</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Mantenga informados a alumnos y apoderados de forma inmediata</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Trabaje cómodamente desde su computador o teléfono móvil</span>
                        </li>
                    </ul>
                </div>
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                </div>
            </div>

            <!-- Administradores -->
            <div class="feature-card feature-admin">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </div>
                <div class="feature-content">
                    <h3>Para Administradores</h3>
                    <p class="feature-description">
                        Tenga el control total del sistema educativo. Gestione la información académica,
                        el personal docente y mantenga una comunicación efectiva con toda la comunidad escolar.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Administre la información completa de alumnos y docentes</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Gestione cursos, asignaturas y asignaciones</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Visualice y supervise todas las calificaciones del establecimiento</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Envíe comunicados importantes a los apoderados</span>
                        </li>
                        <li>
                            <span class="check-icon">✓</span>
                            <span>Genere reportes y estadísticas académicas</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- KPIs -->
            <div class="kpis-container">
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-number">+40</div>
                        <div class="kpi-label">Colegios</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-number">+8.000</div>
                        <div class="kpi-label">Usuarios Activos</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-number">+200.000</div>
                        <div class="kpi-label">Notas Registradas</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Beneficios -->
    <section class="beneficios">
        <div class="section-container">
            <div class="section-header">
                <h2>¿Por qué elegir nuestro Portal?</h2>
                <p>Tecnología al servicio de la educación</p>
            </div>
            <div class="beneficios-grid">
                <div class="beneficio-item">
                    <div class="beneficio-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    </div>
                    <h4>Acceso Multiplataforma</h4>
                    <p>Utilice el portal desde su computador de escritorio, laptop, tablet o teléfono móvil. Siempre disponible cuando lo necesite.</p>
                </div>
                <div class="beneficio-item">
                    <div class="beneficio-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <h4>Seguro y Privado</h4>
                    <p>La información de cada usuario está protegida. Los apoderados solo acceden a los datos de sus pupilos, garantizando la privacidad de todos.</p>
                </div>
                <div class="beneficio-item">
                    <div class="beneficio-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    </div>
                    <h4>Rápido y Sencillo</h4>
                    <p>Interfaz intuitiva diseñada para que cualquier persona pueda utilizarla sin complicaciones ni necesidad de capacitación.</p>
                </div>
                <div class="beneficio-item">
                    <div class="beneficio-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    </div>
                    <h4>Información Actualizada</h4>
                    <p>Las notas y comunicados se actualizan en tiempo real. Siempre tendrá acceso a la información más reciente.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="cta-final">
        <div class="section-container">
            <h2>Comience a usar el Portal Estudiantil</h2>
            <p>Únase a nuestra comunidad educativa digital y manténgase conectado con el progreso académico.</p>
            <div class="cta-actions">
                <a href="registro.php" class="btn btn-white btn-lg">Crear una Cuenta</a>
                <a href="login.php" class="btn btn-outline-white btn-lg">Ya tengo cuenta</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span class="logo-icon">E</span>
                </div>
                <span>Portal Estudiantil</span>
            </div>
            <div class="footer-links">
                <div class="footer-links-row">
                    <a href="#caracteristicas">Características</a>
                    <a href="login.php">Iniciar Sesión</a>
                    <a href="registro.php">Registrarse</a>
                </div>
                <div class="footer-links-row">
                    <a href="nosotros.php">Nosotros</a>
                    <a href="#" onclick="abrirModalPrivacidad(); return false;">Privacidad</a>
                    <a href="#" onclick="abrirModalTerminos(); return false;">Condiciones y Términos</a>
                </div>
            </div>
            <div class="footer-contacto">
                <h4>Contacto</h4>
                <div class="contacto-items">
                    <a href="mailto:contacto@portalestudiantil.cl" class="contacto-item">
                        <svg class="icon-email" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EA4335" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>contacto@portalestudiantil.cl</span>
                    </a>
                    <a href="https://wa.me/56912345678" target="_blank" class="contacto-item">
                        <svg class="icon-whatsapp" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>+56 9 1234 5678</span>
                    </a>
                </div>
            </div>
            <div class="footer-copy">
                <p>© 2024 Sistema de Gestión Académica. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Modal de Términos y Condiciones -->
    <div id="modalTerminos" class="modal-footer-overlay" onclick="cerrarModalTerminos(event)">
        <div class="modal-footer-contenido" onclick="event.stopPropagation()">
            <div class="modal-footer-header">
                <h2>Términos y Condiciones de Uso</h2>
                <button class="modal-footer-cerrar" onclick="cerrarModalTerminos()">&times;</button>
            </div>
            <div class="modal-footer-body">
                <h3>1. Aceptación de los Términos</h3>
                <p>Al acceder y utilizar Portal Estudiantil, usted acepta estar sujeto a estos Términos y Condiciones de Uso y todas las leyes y regulaciones aplicables. Usted acepta que es responsable del cumplimiento de las leyes locales aplicables. Si no está de acuerdo con alguno de estos términos, tiene prohibido usar o acceder a este sitio.</p>

                <h3>2. Definiciones</h3>
                <ul>
                    <li><strong>Plataforma:</strong> Sistema de Gestión Académica Portal Estudiantil.</li>
                    <li><strong>Usuario:</strong> Toda persona que acceda y utilice la plataforma, incluyendo apoderados, docentes y administradores.</li>
                    <li><strong>Establecimiento:</strong> Institución educacional que utiliza los servicios de la plataforma.</li>
                    <li><strong>Contenido:</strong> Toda información, datos, textos y materiales disponibles en la plataforma.</li>
                </ul>

                <h3>3. Uso de la Plataforma</h3>
                <p>El usuario se compromete a:</p>
                <ul>
                    <li>Utilizar la plataforma únicamente para los fines educativos y administrativos para los que fue diseñada.</li>
                    <li>Mantener la confidencialidad de sus credenciales de acceso.</li>
                    <li>No compartir su cuenta con terceros.</li>
                    <li>Proporcionar información veraz y actualizada.</li>
                    <li>No intentar acceder a información de otros usuarios sin autorización.</li>
                    <li>No utilizar la plataforma para actividades ilegales o no autorizadas.</li>
                </ul>

                <h3>4. Registro y Cuentas de Usuario</h3>
                <p>Para acceder a la plataforma, los usuarios deben registrarse proporcionando información personal válida. El usuario es responsable de mantener la confidencialidad de su contraseña y de todas las actividades que ocurran bajo su cuenta. Debe notificar inmediatamente cualquier uso no autorizado de su cuenta.</p>

                <h3>5. Propiedad Intelectual</h3>
                <p>Todos los contenidos de la plataforma, incluyendo pero no limitado a textos, gráficos, logotipos, iconos, imágenes, clips de audio, descargas digitales y compilaciones de datos, son propiedad de Portal Estudiantil o sus proveedores de contenido y están protegidos por las leyes chilenas e internacionales de propiedad intelectual, conforme a la <strong>Ley N° 17.336</strong> sobre Propiedad Intelectual.</p>

                <h3>6. Protección de Datos</h3>
                <p>El tratamiento de datos personales se realiza conforme a nuestra Política de Privacidad y en cumplimiento de la <strong>Ley N° 19.628</strong> sobre Protección de la Vida Privada y la <strong>Ley N° 21.719</strong> que moderniza el marco de protección de datos personales en Chile.</p>

                <h3>7. Responsabilidades del Usuario</h3>
                <p>El usuario será responsable de:</p>
                <ul>
                    <li>El uso adecuado de la plataforma conforme a estos términos.</li>
                    <li>La veracidad de la información proporcionada.</li>
                    <li>Los daños y perjuicios que pudiera causar por el uso indebido de la plataforma.</li>
                    <li>Mantener actualizada su información de contacto.</li>
                </ul>

                <h3>8. Limitación de Responsabilidad</h3>
                <p>Portal Estudiantil no será responsable por:</p>
                <ul>
                    <li>Interrupciones del servicio por mantenimiento o causas de fuerza mayor.</li>
                    <li>Pérdida de datos debido a fallos técnicos ajenos a nuestro control.</li>
                    <li>El uso indebido de la plataforma por parte de los usuarios.</li>
                    <li>Contenidos publicados por los usuarios que contravengan estos términos.</li>
                </ul>

                <h3>9. Modificaciones del Servicio</h3>
                <p>Portal Estudiantil se reserva el derecho de modificar, suspender o discontinuar, temporal o permanentemente, el servicio o cualquier parte del mismo, con o sin previo aviso. No seremos responsables ante usted ni ante terceros por cualquier modificación, suspensión o interrupción del servicio.</p>

                <h3>10. Modificaciones de los Términos</h3>
                <p>Nos reservamos el derecho de actualizar estos Términos y Condiciones en cualquier momento. Cuando se realicen modificaciones, los usuarios serán notificados a través de la plataforma o mediante correo electrónico. Las modificaciones entrarán en vigor a partir de su publicación. El uso continuado de la plataforma después de recibir dicha notificación se entenderá como la aceptación de los nuevos términos por parte del usuario.</p>

                <h3>11. Legislación Aplicable</h3>
                <p>Estos Términos y Condiciones se regirán e interpretarán de acuerdo con las leyes de la República de Chile. Cualquier disputa que surja en relación con estos términos será sometida a la jurisdicción de los tribunales ordinarios de justicia de Chile.</p>

                <h3>12. Contacto</h3>
                <p>Para cualquier consulta relacionada con estos Términos y Condiciones, puede contactarnos a través de: <strong>contacto@portalestudiantil.cl</strong></p>
            </div>
            <div class="modal-footer-pie">
                <p>Última actualización: Noviembre 2024 | Portal Estudiantil - Sistema de Gestión Académica</p>
            </div>
        </div>
    </div>

    <!-- Modal de Privacidad -->
    <div id="modalPrivacidad" class="modal-footer-overlay" onclick="cerrarModalPrivacidad(event)">
        <div class="modal-footer-contenido" onclick="event.stopPropagation()">
            <div class="modal-footer-header">
                <h2>Política de Privacidad</h2>
                <button class="modal-footer-cerrar" onclick="cerrarModalPrivacidad()">&times;</button>
            </div>
            <div class="modal-footer-body">
                <h3>1. Introducción</h3>
                <p>En Portal Estudiantil nos comprometemos a proteger la privacidad y los datos personales de nuestros usuarios. Esta política de privacidad describe cómo recopilamos, utilizamos, almacenamos y protegemos su información personal, en cumplimiento con la legislación chilena vigente.</p>

                <h3>2. Marco Legal Aplicable</h3>
                <p>Nuestra política de privacidad se rige por las siguientes normativas chilenas:</p>
                <ul>
                    <li><strong>Ley N° 19.628</strong> sobre Protección de la Vida Privada (Ley de Protección de Datos Personales), que regula el tratamiento de datos personales en registros o bancos de datos.</li>
                    <li><strong>Ley N° 21.096</strong> que consagra el derecho a la protección de datos personales como garantía constitucional.</li>
                    <li><strong>Ley N° 21.719</strong> (Nueva Ley de Protección de Datos Personales) que moderniza el marco regulatorio estableciendo nuevos estándares de protección.</li>
                    <li><strong>Ley N° 20.584</strong> sobre derechos y deberes de las personas en relación con acciones vinculadas a su atención de salud, en lo aplicable a datos sensibles.</li>
                </ul>

                <h3>3. Datos que Recopilamos</h3>
                <p>Recopilamos los siguientes tipos de datos personales:</p>
                <ul>
                    <li><strong>Datos de identificación:</strong> nombre completo, RUT, dirección, teléfono y correo electrónico.</li>
                    <li><strong>Datos académicos:</strong> calificaciones, asistencia, observaciones pedagógicas y reportes de rendimiento.</li>
                    <li><strong>Datos de uso:</strong> información sobre cómo interactúa con nuestra plataforma.</li>
                </ul>

                <h3>4. Finalidad del Tratamiento</h3>
                <p>Sus datos personales serán utilizados exclusivamente para:</p>
                <ul>
                    <li>Gestionar el registro académico de los estudiantes.</li>
                    <li>Facilitar la comunicación entre el establecimiento educacional, docentes y apoderados.</li>
                    <li>Generar reportes de rendimiento académico.</li>
                    <li>Enviar comunicados y notificaciones relevantes.</li>
                    <li>Mejorar nuestros servicios y la experiencia del usuario.</li>
                </ul>

                <h3>5. Derechos de los Titulares</h3>
                <p>De acuerdo con la legislación chilena, usted tiene derecho a:</p>
                <ul>
                    <li><strong>Acceso:</strong> conocer qué datos personales suyos están siendo tratados.</li>
                    <li><strong>Rectificación:</strong> solicitar la corrección de datos inexactos o incompletos.</li>
                    <li><strong>Cancelación:</strong> solicitar la eliminación de sus datos cuando corresponda.</li>
                    <li><strong>Oposición:</strong> oponerse al tratamiento de sus datos en determinadas circunstancias.</li>
                    <li><strong>Portabilidad:</strong> recibir sus datos en un formato estructurado y de uso común.</li>
                </ul>

                <h3>6. Seguridad de los Datos</h3>
                <p>Implementamos medidas técnicas y organizativas apropiadas para proteger sus datos personales contra el acceso no autorizado, la alteración, divulgación o destrucción. Estas medidas incluyen encriptación de datos, accesos restringidos y protocolos de seguridad actualizados.</p>

                <h3>7. Conservación de Datos</h3>
                <p>Los datos personales serán conservados durante el tiempo necesario para cumplir con las finalidades descritas y conforme a los plazos establecidos por la normativa educacional chilena.</p>

                <h3>8. Contacto</h3>
                <p>Para ejercer sus derechos o realizar consultas sobre esta política de privacidad, puede contactarnos a través de nuestros canales oficiales: <strong>contacto@portalestudiantil.cl</strong></p>
            </div>
            <div class="modal-footer-pie">
                <p>Última actualización: Noviembre 2024 | Portal Estudiantil - Sistema de Gestión Académica</p>
            </div>
        </div>
    </div>

    <script src="js_colegio/index.js"></script>
    <script src="js_colegio/modales-footer.js"></script>
</body>
</html>

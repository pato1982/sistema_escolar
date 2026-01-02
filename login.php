<?php
// ============================================================
// LOGIN - PORTAL ESTUDIANTIL
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

// Obtener establecimientos para el futuro uso si es necesario
$establecimientos = [];
$resultEst = $conn->query("SELECT id, nombre FROM tb_establecimientos WHERE activo = 1 ORDER BY nombre");
if ($resultEst) {
    while ($row = $resultEst->fetch_assoc()) {
        $establecimientos[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Portal Estudiantil</title>
    <link rel="stylesheet" href="css_colegio/colegio.css">
    <link rel="stylesheet" href="css_colegio/registro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-card {
            max-width: 420px;
        }

        .login-card .registro-card-header {
            padding: 20px 30px;
        }

        .login-card .paso-contenido {
            padding: 25px 35px 30px;
        }

        .login-card .form-group {
            margin-bottom: 15px;
        }

        .login-card .form-actions {
            margin-top: 20px;
            flex-direction: column;
            gap: 12px;
        }

        .login-card .btn-primary {
            width: 100%;
        }

        .login-footer {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            margin-top: 20px;
        }

        .login-footer p {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 0;
        }

        .login-footer a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .login-card .registro-card-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .login-card .registro-card-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-card .registro-card-header .logo {
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card .registro-card-header .logo-icon {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
        }

        .login-card .registro-card-header .brand-text h1 {
            font-size: 20px;
            color: white;
            margin: 0;
        }

        .login-card .registro-card-header h2 {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            margin: 0;
        }

        .login-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .btn-volver-lateral {
            width: 40px;
            height: 40px;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            flex-shrink: 0;
            margin-top: 20px;
        }

        .registro-header {
            padding: 10px 0 !important;
        }

        .registro-main {
            padding-top: 0 !important;
        }

        .btn-volver-lateral:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .forgot-password a {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 13px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        /* Contenedor password con ojo */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container .form-control {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: var(--accent-color);
        }

        /* Modal Cambiar Contraseña */
        .modal-cambiar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            align-items: center;
            justify-content: center;
        }

        .modal-cambiar-overlay.active {
            display: flex;
        }

        .modal-cambiar-content {
            background: white;
            border-radius: 0;
            max-width: 420px;
            width: 90%;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalIn 0.3s ease;
        }

        .modal-cambiar-header {
            background: #1e3a5f;
            margin: -30px -30px 20px -30px;
            padding: 25px 30px;
            border-radius: 0;
        }

        .modal-cambiar-header h3 {
            font-size: 16px;
            color: white;
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .modal-cambiar-header p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin: 0;
            line-height: 1.5;
        }

        .modal-cambiar-body {
            margin-bottom: 20px;
        }

        .modal-cambiar-body .form-group {
            margin-bottom: 15px;
        }

        .modal-cambiar-body label {
            color: var(--text-primary);
        }

        .modal-cambiar-actions {
            display: flex;
            gap: 10px;
        }

        .modal-cambiar-actions .btn {
            flex: 1;
            padding: 12px;
            font-size: 14px;
        }

        /* Modal Recuperar Contraseña */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 0;
            max-width: 400px;
            width: 90%;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            margin-bottom: 20px;
            background: #1e3a5f;
            margin: -30px -30px 20px -30px;
            padding: 25px 30px;
            border-radius: 0;
        }

        .modal-header h3 {
            font-size: 16px;
            color: white;
            margin: 0;
            font-weight: 600;
            float: left;
            margin-right: 10px;
            line-height: 1.5;
        }

        .modal-header p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin: 0;
            line-height: 1.5;
            text-align: justify;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body .form-group {
            margin-bottom: 0;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .modal-actions .btn {
            flex: 1;
            padding: 12px;
            font-size: 14px;
        }

        .btn-secondary {
            background: var(--background-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .modal-body label {
            color: var(--text-primary);
        }

        /* Indicador de carga para recuperar contraseña */
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 0;
            text-align: center;
        }

        /* Modal de Error */
        .modal-error-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }

        .modal-error-overlay.active {
            display: flex;
        }

        .modal-error-contenido {
            background: white;
            border-radius: 0;
            max-width: 420px;
            width: 90%;
            padding: 30px;
            position: relative;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            animation: modalErrorFadeIn 0.3s ease;
            text-align: center;
        }

        @keyframes modalErrorFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-error-cerrar {
            position: absolute;
            top: 10px;
            right: 12px;
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            padding: 5px;
            transition: color 0.2s ease;
        }

        .modal-error-cerrar:hover {
            color: #333;
        }

        .modal-error-icon {
            margin-bottom: 20px;
        }

        .modal-error-titulo {
            font-size: 20px;
            font-weight: 600;
            color: #dc2626;
            margin: 0 0 15px 0;
        }

        .modal-error-mensaje {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-secondary);
            margin: 0 0 25px 0;
        }

        .modal-error-btn {
            padding: 12px 30px;
            font-size: 14px;
        }

        /* Responsive para centrar el formulario de login */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                align-items: center;
                width: 100%;
                min-height: calc(100vh - 80px);
                justify-content: center;
            }

            .btn-volver-lateral {
                position: absolute;
                left: 15px;
                top: 15px;
                margin-top: 0;
            }

            .registro-main {
                align-items: center;
                justify-content: center;
                min-height: calc(100vh - 50px);
            }

            .modal-error-contenido {
                max-width: 320px;
                padding: 25px 20px;
                margin: 15px;
            }

            .modal-error-icon svg {
                width: 40px;
                height: 40px;
            }

            .modal-error-titulo {
                font-size: 16px;
            }

            .modal-error-mensaje {
                font-size: 12px;
            }

            .modal-error-btn {
                padding: 10px 25px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="registro-container">
        <!-- Header -->
        <header class="registro-header">
        </header>

        <!-- Contenido Principal -->
        <main class="registro-main">
            <div class="login-wrapper">
                <a href="index.php" class="btn-volver-lateral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="registro-card login-card">
                    <div class="registro-card-header">
                        <div class="brand">
                            <div class="logo">
                                <span class="logo-icon">E</span>
                            </div>
                            <div class="brand-text">
                                <h1>Portal Estudiantil</h1>
                            </div>
                        </div>
                        <h2>Iniciar Sesión</h2>
                    </div>

                    <div class="paso-contenido active">
                        <!-- BOTONES DEMO PARA PRESENTACIÓN -->
                        <div class="demo-buttons"
                            style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; justify-content: center;">
                            <button type="button" onclick="autoLogin('admin')"
                                style="font-size: 12px; padding: 6px 10px; background: #374151; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Demo
                                Admin</button>
                            <button type="button" onclick="autoLogin('docente')"
                                style="font-size: 12px; padding: 6px 10px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Demo
                                Docente</button>
                            <button type="button" onclick="autoLogin('apoderado')"
                                style="font-size: 12px; padding: 6px 10px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Demo
                                Apoderado</button>
                        </div>
                        <form id="formLogin">
                            <div class="form-group">
                                <label for="correoLogin">Correo Electrónico</label>
                                <input type="email" id="correoLogin" class="form-control"
                                    placeholder="correo@ejemplo.com" required>
                            </div>
                            <div class="form-group">
                                <label for="passwordLogin">Contraseña</label>
                                <div class="password-input-container">
                                    <input type="password" id="passwordLogin" class="form-control"
                                        placeholder="Ingrese su contraseña" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="eye-off-icon" style="display:none;"
                                            xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                            </path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="forgot-password">
                                <a href="#" onclick="abrirModalRecuperar(event)">¿Olvidó su contraseña?</a>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary"
                                    onclick="iniciarSesion()">Ingresar</button>
                            </div>
                        </form>

                        <div class="login-footer">
                            <p>¿No tiene una cuenta? <a href="registro.php">Regístrese aquí</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="registro-footer">
            <p>Sistema de Gestión Académica © 2024 | Todos los derechos reservados</p>
        </footer>
    </div>

    <!-- Modal Recuperar Contraseña -->
    <div class="modal-overlay" id="modalRecuperar">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Recuperar Contraseña</h3>
                <p>Ingrese su correo electrónico registrado. Le enviaremos una clave provisoria que podrá usar para
                    ingresar y luego cambiarla.</p>
            </div>
            <div class="modal-body">
                <div class="form-group" id="formCorreoRecuperar">
                    <label for="correoRecuperar">Correo Electrónico</label>
                    <input type="email" id="correoRecuperar" class="form-control" placeholder="correo@ejemplo.com">
                </div>
                <!-- Indicador de carga -->
                <div class="loading-container" id="loadingRecuperar" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">Enviando, espere un momento...</p>
                </div>
            </div>
            <div class="modal-actions" id="botonesRecuperar">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalRecuperar()">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEnviarRecuperar"
                    onclick="enviarRecuperacion()">Enviar</button>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal-cambiar-overlay" id="modalCambiarPassword">
        <div class="modal-cambiar-content">
            <div class="modal-cambiar-header">
                <h3>Crear Nueva Contraseña</h3>
                <p>Ha ingresado con una clave provisoria. Por seguridad, debe crear una nueva contraseña.</p>
            </div>
            <div class="modal-cambiar-body">
                <div class="form-group">
                    <label for="claveProvisoriaCambio">Clave Provisoria</label>
                    <div class="password-input-container">
                        <input type="password" id="claveProvisoriaCambio" class="form-control"
                            placeholder="Ingrese la clave provisoria">
                        <button type="button" class="toggle-password"
                            onclick="togglePasswordField('claveProvisoriaCambio')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" style="display:none;" xmlns="http://www.w3.org/2000/svg"
                                width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="nuevaPassword">Nueva Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="nuevaPassword" class="form-control"
                            placeholder="Mínimo 6 caracteres">
                        <button type="button" class="toggle-password" onclick="togglePasswordField('nuevaPassword')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" style="display:none;" xmlns="http://www.w3.org/2000/svg"
                                width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmarPassword">Repetir Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="confirmarPassword" class="form-control"
                            placeholder="Repita la nueva contraseña">
                        <button type="button" class="toggle-password"
                            onclick="togglePasswordField('confirmarPassword')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" style="display:none;" xmlns="http://www.w3.org/2000/svg"
                                width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-cambiar-actions">
                <button type="button" class="btn btn-primary" onclick="cambiarPassword()">Guardar Nueva
                    Contraseña</button>
            </div>
        </div>
    </div>

    <!-- Modal de Error de Login -->
    <div id="modalErrorLogin" class="modal-error-overlay" onclick="cerrarModalErrorLogin(event)">
        <div class="modal-error-contenido" onclick="event.stopPropagation()">
            <button class="modal-error-cerrar" onclick="cerrarModalErrorLogin()">&times;</button>
            <div class="modal-error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                    stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h3 class="modal-error-titulo">Error de inicio de sesión</h3>
            <p id="modalErrorLoginTexto" class="modal-error-mensaje"></p>
            <button class="btn btn-primary modal-error-btn" onclick="cerrarModalErrorLogin()">Entendido</button>
        </div>
    </div>

    <script>
        // Función para demo login
        function autoLogin(rol) {
            const emailField = document.getElementById('correoLogin');
            const passField = document.getElementById('passwordLogin');

            if (rol === 'admin') {
                emailField.value = 'admin@demo.cl';
            } else if (rol === 'docente') {
                emailField.value = 'docente@demo.cl';
            } else if (rol === 'apoderado') {
                emailField.value = 'apoderado@demo.cl';
            }
            passField.value = '123456';
        }

        // Variable para guardar el correo del usuario que requiere cambio de contraseña
        let correoParaCambio = '';

        // Función para mostrar/ocultar contraseña del login
        function togglePassword() {
            const passwordInput = document.getElementById('passwordLogin');
            const container = passwordInput.closest('.password-input-container');
            const eyeIcon = container.querySelector('.eye-icon');
            const eyeOffIcon = container.querySelector('.eye-off-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }

        // Función genérica para mostrar/ocultar cualquier campo de contraseña
        function togglePasswordField(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const container = passwordInput.closest('.password-input-container');
            const eyeIcon = container.querySelector('.eye-icon');
            const eyeOffIcon = container.querySelector('.eye-off-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }

        // Función principal de inicio de sesión
        async function iniciarSesion() {
            const correo = document.getElementById('correoLogin').value.trim();
            const password = document.getElementById('passwordLogin').value;

            if (!correo || !password) {
                alert('Por favor complete todos los campos');
                return;
            }

            // Validar formato de correo
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                alert('Por favor ingrese un correo electrónico válido');
                return;
            }

            // Enviar al servidor para autenticación
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        correo: correo,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Verificar si requiere cambio de contraseña (clave provisoria)
                    if (data.requiere_cambio_password) {
                        // Guardar correo para el cambio de contraseña
                        correoParaCambio = correo;
                        // Mostrar modal de cambio de contraseña
                        abrirModalCambiarPassword();
                    } else {
                        // Redirigir según el tipo de usuario
                        switch (data.tipo_usuario) {
                            case 'apoderado':
                                window.location.href = 'apoderado.php';
                                break;
                            case 'docente':
                                window.location.href = 'docente.php';
                                break;
                            case 'administrador':
                                window.location.href = 'colegio.php';
                                break;
                            default:
                                window.location.href = 'index.php';
                        }
                    }
                } else {
                    mostrarModalErrorLogin(data.message);
                }
            } catch (error) {
                console.error('Error al iniciar sesión:', error);
                mostrarModalErrorLogin('Error de conexión. Por favor intente nuevamente.');
            }
        }

        // Mostrar modal de error
        function mostrarModalErrorLogin(mensaje) {
            const modal = document.getElementById('modalErrorLogin');
            const texto = document.getElementById('modalErrorLoginTexto');

            if (modal && texto) {
                texto.textContent = mensaje;
                modal.classList.add('active');
            } else {
                alert(mensaje);
            }
        }

        // Cerrar modal de error
        function cerrarModalErrorLogin(event) {
            if (event && event.target !== event.currentTarget) {
                return;
            }
            const modal = document.getElementById('modalErrorLogin');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Permitir enviar con Enter
        document.getElementById('formLogin').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                iniciarSesion();
            }
        });

        // Funciones del modal de recuperar contraseña
        function abrirModalRecuperar(event) {
            event.preventDefault();
            document.getElementById('modalRecuperar').classList.add('active');
            document.getElementById('correoRecuperar').value = '';
            document.getElementById('correoRecuperar').focus();
        }

        function cerrarModalRecuperar() {
            document.getElementById('modalRecuperar').classList.remove('active');
            // Resetear estado del modal
            document.getElementById('formCorreoRecuperar').style.display = 'block';
            document.getElementById('botonesRecuperar').style.display = 'flex';
            document.getElementById('loadingRecuperar').style.display = 'none';
            document.getElementById('correoRecuperar').value = '';
        }

        async function enviarRecuperacion() {
            const correo = document.getElementById('correoRecuperar').value.trim();

            if (!correo) {
                alert('Por favor ingrese su correo electrónico');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                alert('Por favor ingrese un correo electrónico válido');
                return;
            }

            // Mostrar indicador de carga y ocultar formulario
            document.getElementById('formCorreoRecuperar').style.display = 'none';
            document.getElementById('botonesRecuperar').style.display = 'none';
            document.getElementById('loadingRecuperar').style.display = 'flex';

            try {
                const response = await fetch('api/recuperar_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ correo: correo })
                });

                const data = await response.json();

                // Ocultar indicador de carga
                document.getElementById('loadingRecuperar').style.display = 'none';

                if (data.success) {
                    alert('Se ha enviado una clave provisoria a su correo electrónico. Revise su bandeja de entrada.');
                    cerrarModalRecuperar();
                } else {
                    // Mostrar formulario de nuevo en caso de error
                    document.getElementById('formCorreoRecuperar').style.display = 'block';
                    document.getElementById('botonesRecuperar').style.display = 'flex';
                    mostrarModalErrorLogin(data.message);
                }
            } catch (error) {
                console.error('Error al recuperar contraseña:', error);
                // Ocultar indicador de carga y mostrar formulario
                document.getElementById('loadingRecuperar').style.display = 'none';
                document.getElementById('formCorreoRecuperar').style.display = 'block';
                document.getElementById('botonesRecuperar').style.display = 'flex';
                mostrarModalErrorLogin('Error de conexión. Por favor intente nuevamente.');
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalRecuperar').addEventListener('click', function (e) {
            if (e.target === this) {
                cerrarModalRecuperar();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalRecuperar();
                cerrarModalErrorLogin();
                cerrarModalCambiarPassword();
            }
        });

        // Funciones del modal de cambiar contraseña
        function abrirModalCambiarPassword() {
            const modal = document.getElementById('modalCambiarPassword');
            modal.classList.add('active');
            // Pre-llenar la clave provisoria con la contraseña ingresada en el login
            document.getElementById('claveProvisoriaCambio').value = document.getElementById('passwordLogin').value;
            document.getElementById('nuevaPassword').value = '';
            document.getElementById('confirmarPassword').value = '';
            document.getElementById('nuevaPassword').focus();
        }

        function cerrarModalCambiarPassword() {
            document.getElementById('modalCambiarPassword').classList.remove('active');
        }

        async function cambiarPassword() {
            const claveProvisoria = document.getElementById('claveProvisoriaCambio').value;
            const nuevaPassword = document.getElementById('nuevaPassword').value;
            const confirmarPassword = document.getElementById('confirmarPassword').value;

            // Validaciones
            if (!claveProvisoria || !nuevaPassword || !confirmarPassword) {
                alert('Por favor complete todos los campos');
                return;
            }

            if (nuevaPassword !== confirmarPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }

            if (nuevaPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }

            try {
                const response = await fetch('api/cambiar_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        correo: correoParaCambio,
                        clave_provisoria: claveProvisoria,
                        nueva_password: nuevaPassword,
                        confirmar_password: confirmarPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    cerrarModalCambiarPassword();
                    // Limpiar campos del login
                    document.getElementById('passwordLogin').value = '';
                    correoParaCambio = '';
                } else {
                    mostrarModalErrorLogin(data.message);
                }
            } catch (error) {
                console.error('Error al cambiar contraseña:', error);
                mostrarModalErrorLogin('Error de conexión. Por favor intente nuevamente.');
            }
        }

        // Cerrar modal de cambiar contraseña al hacer clic fuera
        document.getElementById('modalCambiarPassword').addEventListener('click', function (e) {
            if (e.target === this) {
                // No permitir cerrar haciendo clic fuera (es obligatorio cambiar la contraseña)
            }
        });
    </script>
</body>

</html>
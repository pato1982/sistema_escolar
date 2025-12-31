// ============================================================
// SESSION TIMEOUT - Control de inactividad
// ============================================================

// Configuración
const TIEMPO_VERIFICACION = 30000; // Verificar cada 30 segundos
const TIEMPO_INACTIVIDAD = 300000; // 5 minutos en milisegundos

let ultimaActividad = Date.now();
let intervalVerificacion = null;

// Inicializar el control de sesión
function iniciarControlSesion() {
    // Registrar actividad del usuario
    registrarEventosActividad();

    // Iniciar verificación periódica
    intervalVerificacion = setInterval(verificarSesion, TIEMPO_VERIFICACION);

    // Verificar inmediatamente al cargar
    verificarSesion();
}

// Registrar eventos que indican actividad del usuario
function registrarEventosActividad() {
    const eventos = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];

    eventos.forEach(evento => {
        document.addEventListener(evento, actualizarActividad, { passive: true });
    });
}

// Actualizar timestamp de última actividad
function actualizarActividad() {
    ultimaActividad = Date.now();
}

// Verificar estado de la sesión
async function verificarSesion() {
    try {
        const response = await fetch('api/verificar_sesion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.cerrada_por_inactividad) {
            // Detener verificaciones
            if (intervalVerificacion) {
                clearInterval(intervalVerificacion);
            }

            // Mostrar mensaje y redirigir
            mostrarModalSesionExpirada(data.mensaje);
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
    }
}

// Mostrar modal de sesión expirada
function mostrarModalSesionExpirada(mensaje) {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.id = 'modalSesionExpirada';
    overlay.style.cssText = `
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    `;

    // Crear contenido del modal
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        background: white;
        border-radius: 0;
        max-width: 400px;
        width: 90%;
        padding: 30px;
        text-align: center;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        animation: fadeIn 0.3s ease;
    `;

    contenido.innerHTML = `
        <div style="margin-bottom: 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 600; color: #1e3a5f; margin: 0 0 15px 0;">Sesión Expirada</h3>
        <p style="font-size: 14px; line-height: 1.6; color: #666; margin: 0 0 25px 0;">${mensaje}</p>
        <button onclick="redirigirAlLogin()" style="
            padding: 12px 30px;
            font-size: 14px;
            background: #1e3a5f;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
        ">Iniciar Sesión</button>
    `;

    overlay.appendChild(contenido);
    document.body.appendChild(overlay);

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
}

// Redirigir al login
function redirigirAlLogin() {
    window.location.href = 'login.php';
}

// Cerrar sesión manualmente
async function cerrarSesion() {
    try {
        await fetch('api/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        window.location.href = 'index.php';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        window.location.href = 'index.php';
    }
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', iniciarControlSesion);

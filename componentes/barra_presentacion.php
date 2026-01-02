<?php
// BARRA DE PRESENTACIÓN GLOBAL
?>
<style>
    .barra-presentacion {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.95);
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        display: flex;
        gap: 12px;
        z-index: 9999;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(0, 0, 0, 0.1);
        align-items: center;
    }

    .barra-presentacion-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-right: 5px;
    }

    .btn-demo {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        padding: 8px 16px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s ease;
        color: white;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-demo-admin {
        background: #334155;
    }

    .btn-demo-docente {
        background: #2563eb;
    }

    .btn-demo-apoderado {
        background: #059669;
    }

    .btn-demo:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-demo:active {
        transform: translateY(0);
    }

    @media (max-width: 600px) {
        .barra-presentacion {
            bottom: 10px;
            padding: 8px 12px;
            width: 90%;
            justify-content: center;
        }

        .barra-presentacion-label {
            display: none;
        }

        .btn-demo {
            padding: 6px 12px;
            font-size: 11px;
        }
    }
</style>

<div class="barra-presentacion">
    <span class="barra-presentacion-label">Modo Presentación:</span>
    <button type="button" class="btn-demo btn-demo-admin" onclick="switchRole('admin')">
        <span>Admin</span>
    </button>
    <button type="button" class="btn-demo btn-demo-docente" onclick="switchRole('docente')">
        <span>Docente</span>
    </button>
    <button type="button" class="btn-demo btn-demo-apoderado" onclick="switchRole('apoderado')">
        <span>Apoderado</span>
    </button>
</div>

<script>
    async function switchRole(role) {
        // Mostrar un indicador de carga si es necesario
        console.log("Cambiando a rol:", role);

        try {
            const response = await fetch('api/switch_demo_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ role: role })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                console.error("Error al cambiar de rol:", data.message);
                // Si falla (ej: no estamos en login), intentamos ir a login y loguear
                if (window.location.pathname.includes('login.php')) {
                    autoLogin(role);
                    iniciarSesion();
                } else {
                    window.location.href = 'login.php?autoRole=' + role;
                }
            }
        } catch (error) {
            console.error("Error:", error);
            window.location.href = 'login.php?autoRole=' + role;
        }
    }
</script>
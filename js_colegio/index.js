// ==================== PÁGINA DE INICIO ====================

document.addEventListener('DOMContentLoaded', function() {
    initNavToggle();
    initSmoothScroll();
});

// Menú móvil
function initNavToggle() {
    const navToggle = document.getElementById('navToggle');
    const navMobile = document.getElementById('navMobile');

    if (navToggle && navMobile) {
        // Toggle del menú al hacer clic en hamburguesa
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMobile.classList.toggle('active');

            // Animar las líneas del botón hamburguesa
            const spans = navToggle.querySelectorAll('span');
            spans.forEach(span => span.classList.toggle('active'));
        });

        // Cerrar menú al hacer clic en un enlace
        navMobile.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                cerrarMenuMobile();
            });
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!navMobile.contains(e.target) && !navToggle.contains(e.target)) {
                cerrarMenuMobile();
            }
        });
    }

    function cerrarMenuMobile() {
        const navMobile = document.getElementById('navMobile');
        const navToggle = document.getElementById('navToggle');

        if (navMobile && navMobile.classList.contains('active')) {
            navMobile.classList.remove('active');
            const spans = navToggle.querySelectorAll('span');
            spans.forEach(span => span.classList.remove('active'));
        }
    }
}

// Scroll suave para enlaces internos
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.offsetTop - navbarHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Efecto de navbar al hacer scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
    } else {
        navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
    }
});

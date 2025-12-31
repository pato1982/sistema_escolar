/* ==================== PANEL ADMINISTRADOR - JAVASCRIPT ==================== */
/* Versión conectada a base de datos PHP */

// ==================== DATOS DESDE PHP ====================
// Variables definidas en colegio.php:
// - cursosDB, asignaturasDB, docentesDB, alumnosPorCursoDB
// - asignacionesDB, trimestresDB, docenteEspecialidadesDB

// ==================== UTILIDADES ====================
function normalizarTexto(texto) {
    if (!texto) return '';
    return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

// Capitalizar nombre: primera letra mayúscula, resto minúscula para cada palabra
function capitalizarNombre(texto) {
    if (!texto) return '';
    return texto
        .toLowerCase()
        .split(' ')
        .map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1))
        .join(' ');
}

// Formatear nombre de docente: Primer Nombre + Primer Apellido + Inicial Segundo Apellido.
function formatearNombreDocente(nombres, apellidos) {
    if (!nombres || !apellidos) return '';

    const primerNombre = capitalizarNombre(nombres.trim().split(' ')[0]);
    const parteApellidos = apellidos.trim().split(' ');
    const primerApellido = capitalizarNombre(parteApellidos[0] || '');
    const segundoApellido = parteApellidos[1] || '';

    let nombreFormateado = primerNombre + ' ' + primerApellido;
    if (segundoApellido) {
        nombreFormateado += ' ' + segundoApellido.charAt(0).toUpperCase() + '.';
    }

    return nombreFormateado;
}

// Crear arrays locales a partir de los datos de PHP
let cursos = [];
let asignaturas = [];
let docentesData = [];
let alumnosData = {};
let asignacionesData = [];

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos desde PHP
    inicializarDatosDesdeDB();

    // Inicializar sistema de pestañas
    initTabs();
    initMenuToggle();
    initSubTabsMobile();

    // Inicializar módulos
    initModuloAlumnos();
    initModuloDocentes();
    initModuloAsignaciones();
    initModuloComunicados();
    initModuloNotasPorCurso();
    initModuloEstadisticas();

    // Inicializar flechas desplegables de autocomplete
    initAutocompleteFlechas();

    // Cerrar listas de sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        // Verificar si el clic fue dentro de algún autocomplete
        const clickEnAutocompletado = e.target.closest('.autocomplete-container') ||
                                       e.target.closest('.sugerencias-lista') ||
                                       e.target.closest('.autocomplete-arrow');

        if (!clickEnAutocompletado) {
            cerrarTodasLasSugerencias();
            // Quitar clase active de todas las flechas
            document.querySelectorAll('.autocomplete-arrow').forEach(arrow => {
                arrow.classList.remove('active');
            });
        }
    });
});

// Inicializar flechas desplegables de autocomplete
function initAutocompleteFlechas() {
    document.querySelectorAll('.autocomplete-arrow').forEach(arrow => {
        arrow.addEventListener('click', function(e) {
            e.stopPropagation();
            const targetId = this.dataset.target;
            const inputId = this.dataset.input;
            const lista = document.getElementById(targetId);
            const input = document.getElementById(inputId);

            if (!lista || !input) return;

            // Verificar si está deshabilitado
            if (input.disabled || this.disabled) return;

            // Verificar si la lista está visible
            const estaVisible = lista.style.display === 'block';

            // Cerrar todas las otras listas
            cerrarTodasLasSugerencias(targetId);

            // Quitar active de todas las flechas excepto esta
            document.querySelectorAll('.autocomplete-arrow').forEach(otherArrow => {
                if (otherArrow !== this) {
                    otherArrow.classList.remove('active');
                }
            });

            if (estaVisible) {
                // Cerrar la lista
                lista.style.display = 'none';
                this.classList.remove('active');
            } else {
                // Abrir la lista - mostrar TODOS los items (sin filtro)
                this.classList.add('active');
                if (!input.readOnly) input.focus();

                // Disparar la apertura de la lista según el tipo de filtro
                mostrarListaParaFiltro(inputId, input, lista, '');
            }
        });
    });

    // Inicializar eventos para los nuevos filtros
    initNuevosFiltrosAutocomplete();
}

// Función genérica para mostrar lista según el tipo de filtro
function mostrarListaParaFiltro(inputId, input, lista, filtro) {
    switch(inputId) {
        // Pestaña Docentes
        case 'filtroNombreDocente':
            mostrarListaDocentes(input, lista, filtro);
            break;
        case 'filtroAsignaturaDocente':
            const todasEspecialidades = [...new Set(docentesData.flatMap(d => d.especialidades))].sort();
            mostrarListaAsignaturas(input, lista, filtro, todasEspecialidades);
            break;
        // Pestaña Alumnos
        case 'filtroNombreAlumno':
            mostrarListaAlumnos(input, lista, filtro);
            break;
        case 'filtroCursoAlumnos':
            mostrarListaCursos(input, lista, filtro, 'sugerenciasCursosAlumnos');
            break;
        // Pestaña Curso/Asignaturas - Asignar Docente
        case 'selectDocenteAsignacion':
            mostrarListaDocentesAsignar(input, lista, filtro);
            break;
        case 'selectCursoAsignacion':
            mostrarListaCursosSimple(input, lista, filtro, 'sugerenciasCursoAsignar');
            break;
        // Pestaña Curso/Asignaturas - Filtros
        case 'filtroAsignacionCurso':
            mostrarListaCursos(input, lista, filtro, 'sugerenciasCursosAsignacion');
            break;
        case 'filtroAsignacionDocente':
            mostrarListaDocentesGenerico(input, lista, filtro, 'sugerenciasDocentesAsignacion');
            break;
        // Pestaña Notas por Curso
        case 'selectCursoNotasPorCurso':
            mostrarListaCursos(input, lista, filtro, 'sugerenciasCursoNotas');
            break;
        case 'selectAsignaturaNotasCurso':
            mostrarListaAsignaturasNotas(input, lista, filtro);
            break;
        case 'selectTrimestreNotasCurso':
            mostrarListaTrimestres(input, lista, filtro);
            break;
        // Pestaña Comunicados
        case 'selectTipoComunicado':
            mostrarListaTiposComunicado(input, lista);
            break;
        case 'selectModoCurso':
            mostrarListaModoCurso(input, lista);
            break;
    }
}

// Inicializar eventos para los nuevos filtros autocomplete
function initNuevosFiltrosAutocomplete() {
    // Filtro Curso Alumnos
    initFiltroGenerico('filtroCursoAlumnos', 'sugerenciasCursosAlumnos', function(input, lista, filtro) {
        mostrarListaCursos(input, lista, filtro, 'sugerenciasCursosAlumnos');
    }, function(valor) {
        renderizarTablaAlumnos();
    });

    // Select Docente para Asignar
    initFiltroGenerico('selectDocenteAsignacion', 'sugerenciasDocenteAsignar', function(input, lista, filtro) {
        mostrarListaDocentesAsignar(input, lista, filtro);
    }, function(valor) {
        mostrarAsignaturasDocente();
    });

    // Select Curso para Asignar
    initFiltroGenerico('selectCursoAsignacion', 'sugerenciasCursoAsignar', function(input, lista, filtro) {
        mostrarListaCursosSimple(input, lista, filtro, 'sugerenciasCursoAsignar');
    }, null);

    // Filtro Curso Asignaciones
    initFiltroGenerico('filtroAsignacionCurso', 'sugerenciasCursosAsignacion', function(input, lista, filtro) {
        mostrarListaCursos(input, lista, filtro, 'sugerenciasCursosAsignacion');
    }, function(valor) {
        filtrarTablaAsignaciones();
    });

    // Filtro Docente Asignaciones
    initFiltroGenerico('filtroAsignacionDocente', 'sugerenciasDocentesAsignacion', function(input, lista, filtro) {
        mostrarListaDocentesGenerico(input, lista, filtro, 'sugerenciasDocentesAsignacion');
    }, function(valor) {
        filtrarTablaAsignaciones();
    });

    // Filtro Curso Notas
    initFiltroGenerico('selectCursoNotasPorCurso', 'sugerenciasCursoNotas', function(input, lista, filtro) {
        mostrarListaCursos(input, lista, filtro, 'sugerenciasCursoNotas');
    }, function(valor) {
        onCursoNotasChange(valor);
    });

    // Filtro Asignatura Notas
    initFiltroGenerico('selectAsignaturaNotasCurso', 'sugerenciasAsignaturaNotas', function(input, lista, filtro) {
        mostrarListaAsignaturasNotas(input, lista, filtro);
    }, function(valor) {
        onAsignaturaNotasChange(valor);
    });

    // Filtro Trimestre Notas
    initFiltroGenerico('selectTrimestreNotasCurso', 'sugerenciasTrimestreNotas', function(input, lista, filtro) {
        mostrarListaTrimestres(input, lista, filtro);
    }, function(valor) {
        onTrimestreNotasChange(valor);
    });

    // Filtro Tipo Comunicado
    initFiltroGenerico('selectTipoComunicado', 'sugerenciasTipoComunicado', function(input, lista, filtro) {
        mostrarListaTiposComunicado(input, lista);
    }, null, true);

    // Filtro Modo Curso Comunicado
    initFiltroGenerico('selectModoCurso', 'sugerenciasModoCurso', function(input, lista, filtro) {
        mostrarListaModoCurso(input, lista);
    }, function(valor) {
        toggleSelectorCursos();
    }, true);
}

// Función genérica para inicializar filtros autocomplete
function initFiltroGenerico(inputId, listaId, mostrarFn, onSelectFn, readonly = false) {
    const input = document.getElementById(inputId);
    const lista = document.getElementById(listaId);
    if (!input || !lista) return;

    if (!readonly) {
        input.addEventListener('focus', () => {
            cerrarTodasLasSugerencias(listaId);
            mostrarFn(input, lista, '');
        });

        input.addEventListener('input', function() {
            mostrarFn(input, lista, this.value);
        });
    }

    input.addEventListener('click', (e) => {
        e.stopPropagation();
        cerrarTodasLasSugerencias(listaId);
        // Siempre mostrar todas las opciones al hacer clic
        mostrarFn(input, lista, '');
    });

    // Escuchar evento change para llamar al callback cuando se selecciona un item
    if (onSelectFn) {
        input.addEventListener('change', function() {
            onSelectFn(this.value);
        });
    }
}

// Mostrar lista de cursos genérica
function mostrarListaCursos(input, lista, filtro, listaId) {
    const filtroNorm = normalizarTexto(filtro);
    let cursosFiltrados = cursos.filter(c => normalizarTexto(c).includes(filtroNorm));

    let html = '';
    if (filtroNorm === '' || 'todos'.includes(filtroNorm)) {
        html = '<div class="sugerencia-item sugerencia-todos" data-valor="">Todos</div>';
    }

    if (cursosFiltrados.length === 0 && filtroNorm !== '') {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron cursos</div>';
    } else {
        html += cursosFiltrados.map(c => `
            <div class="sugerencia-item" data-valor="${c}">${c}</div>
        `).join('');
        lista.innerHTML = html;
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarItemGenerico(input, lista, this.dataset.valor, listaId);
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de docentes genérica
function mostrarListaDocentesGenerico(input, lista, filtro, listaId) {
    const filtroNorm = normalizarTexto(filtro);
    let docentesFiltrados = docentesData.filter(d =>
        normalizarTexto(d.nombreCompleto).includes(filtroNorm)
    );

    let html = '';
    if (filtroNorm === '' || 'todos'.includes(filtroNorm)) {
        html = '<div class="sugerencia-item sugerencia-todos" data-valor="">Todos</div>';
    }

    if (docentesFiltrados.length === 0 && filtroNorm !== '') {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron docentes</div>';
    } else {
        html += docentesFiltrados.map(d => `
            <div class="sugerencia-item" data-valor="${capitalizarNombre(d.nombreCompleto)}">${capitalizarNombre(d.nombreCompleto)}</div>
        `).join('');
        lista.innerHTML = html;
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarItemGenerico(input, lista, this.dataset.valor, listaId);
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de docentes para asignar (sin opción "Todos")
function mostrarListaDocentesAsignar(input, lista, filtro) {
    const filtroNorm = normalizarTexto(filtro);
    let docentesFiltrados = docentesData.filter(d =>
        normalizarTexto(d.nombreCompleto).includes(filtroNorm)
    );

    if (docentesFiltrados.length === 0) {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron docentes</div>';
    } else {
        lista.innerHTML = docentesFiltrados.map(d => `
            <div class="sugerencia-item" data-valor="${capitalizarNombre(d.nombreCompleto)}" data-id="${d.id}">${capitalizarNombre(d.nombreCompleto)}</div>
        `).join('');
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.dataset.valor;
            input.dataset.docenteId = this.dataset.id;
            lista.style.display = 'none';
            const arrow = document.querySelector(`[data-target="${lista.id}"]`);
            if (arrow) arrow.classList.remove('active');
            mostrarAsignaturasDocente();
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de cursos simple (sin opción "Todos")
function mostrarListaCursosSimple(input, lista, filtro, listaId) {
    const filtroNorm = normalizarTexto(filtro);
    let cursosFiltrados = cursos.filter(c => normalizarTexto(c).includes(filtroNorm));

    if (cursosFiltrados.length === 0) {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron cursos</div>';
    } else {
        lista.innerHTML = cursosFiltrados.map(c => `
            <div class="sugerencia-item" data-valor="${c}">${c}</div>
        `).join('');
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarItemGenerico(input, lista, this.dataset.valor, listaId);
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de asignaturas para notas
function mostrarListaAsignaturasNotas(input, lista, filtro) {
    const cursoSeleccionado = document.getElementById('selectCursoNotasPorCurso')?.value;
    if (!cursoSeleccionado) {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">Seleccione un curso primero</div>';
        lista.style.display = 'block';
        return;
    }

    // Filtrar asignaturas que tienen asignación en el curso seleccionado
    const asignaturasDelCurso = asignacionesData
        .filter(a => a.curso === cursoSeleccionado)
        .map(a => a.asignatura);

    // Eliminar duplicados
    const asignaturasUnicas = [...new Set(asignaturasDelCurso)];

    const filtroNorm = normalizarTexto(filtro);
    let asignaturasFiltradas = asignaturasUnicas.filter(a => normalizarTexto(a).includes(filtroNorm));

    if (asignaturasFiltradas.length === 0) {
        if (asignaturasUnicas.length === 0) {
            lista.innerHTML = '<div class="sugerencia-item no-resultados">No hay asignaturas asignadas a este curso</div>';
        } else {
            lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron asignaturas</div>';
        }
    } else {
        lista.innerHTML = asignaturasFiltradas.map(a => `
            <div class="sugerencia-item" data-valor="${a}">${a}</div>
        `).join('');
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarItemGenerico(input, lista, this.dataset.valor, 'sugerenciasAsignaturaNotas');
            cargarNotasPorCurso();
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de trimestres
function mostrarListaTrimestres(input, lista, filtro) {
    const trimestresOpciones = [
        { valor: '', texto: 'Todos' },
        { valor: '1', texto: 'Trimestre 1' },
        { valor: '2', texto: 'Trimestre 2' },
        { valor: '3', texto: 'Trimestre 3' }
    ];

    const filtroNorm = normalizarTexto(filtro);
    let trimestresFiltrados = trimestresOpciones.filter(t =>
        normalizarTexto(t.texto).includes(filtroNorm)
    );

    if (trimestresFiltrados.length === 0) {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron trimestres</div>';
    } else {
        lista.innerHTML = trimestresFiltrados.map(t => `
            <div class="sugerencia-item${t.valor === '' ? ' sugerencia-todos' : ''}" data-valor="${t.valor}" data-texto="${t.texto}">${t.texto}</div>
        `).join('');
    }

    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.dataset.texto;
            input.dataset.valor = this.dataset.valor;
            lista.style.display = 'none';
            const arrow = document.querySelector(`[data-target="${lista.id}"]`);
            if (arrow) arrow.classList.remove('active');
            // Disparar evento change para que el callback se encargue
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de tipos de comunicado
function mostrarListaTiposComunicado(input, lista) {
    const tipos = [
        { valor: 'informativo', texto: 'Informativo' },
        { valor: 'urgente', texto: 'Urgente' },
        { valor: 'reunion', texto: 'Reunión' },
        { valor: 'evento', texto: 'Evento' }
    ];

    lista.innerHTML = tipos.map(t => `
        <div class="sugerencia-item" data-valor="${t.valor}">${t.texto}</div>
    `).join('');

    lista.querySelectorAll('.sugerencia-item').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.textContent;
            input.dataset.valor = this.dataset.valor;
            lista.style.display = 'none';
            const arrow = document.querySelector(`[data-target="${lista.id}"]`);
            if (arrow) arrow.classList.remove('active');
        });
    });

    lista.style.display = 'block';
}

// Mostrar lista de modo curso
function mostrarListaModoCurso(input, lista) {
    const modos = [
        { valor: 'todos', texto: 'Todos los cursos' },
        { valor: 'seleccionar', texto: 'Seleccionar cursos' }
    ];

    lista.innerHTML = modos.map(m => `
        <div class="sugerencia-item" data-valor="${m.valor}">${m.texto}</div>
    `).join('');

    lista.querySelectorAll('.sugerencia-item').forEach(item => {
        item.addEventListener('click', function() {
            input.value = this.textContent;
            input.dataset.valor = this.dataset.valor;
            lista.style.display = 'none';
            const arrow = document.querySelector(`[data-target="${lista.id}"]`);
            if (arrow) arrow.classList.remove('active');
            toggleSelectorCursos();
        });
    });

    lista.style.display = 'block';
}

// Seleccionar item genérico
function seleccionarItemGenerico(input, lista, valor, listaId) {
    input.value = valor;
    lista.style.display = 'none';
    const arrow = document.querySelector(`[data-target="${listaId}"]`);
    if (arrow) arrow.classList.remove('active');

    // Disparar evento de cambio para lógica dependiente
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

// Función para manejar cambio de curso en notas
function onCursoNotasChange(valor) {
    const filtroAsignatura = document.getElementById('filtroAsignaturaContainer');
    const filtroTrimestre = document.getElementById('filtroTrimestreContainer');
    const inputAsig = document.getElementById('selectAsignaturaNotasCurso');
    const inputTrim = document.getElementById('selectTrimestreNotasCurso');
    const container = document.getElementById('tablaNotasCursoContainer');
    const mensaje = document.getElementById('mensajeSeleccion');

    if (valor) {
        // Mostrar filtro de asignatura
        if (filtroAsignatura) filtroAsignatura.style.display = 'block';
        // Limpiar valores
        if (inputAsig) inputAsig.value = '';
        if (inputTrim) inputTrim.value = '';
    } else {
        // Ocultar filtros
        if (filtroAsignatura) filtroAsignatura.style.display = 'none';
    }

    // Ocultar filtro trimestre y tabla
    if (filtroTrimestre) filtroTrimestre.style.display = 'none';
    if (container) container.style.display = 'none';
    if (mensaje) {
        mensaje.style.display = 'block';
        mensaje.textContent = 'Seleccione un curso y una asignatura para ver las notas';
    }
}

// Función para manejar cambio de asignatura en notas
function onAsignaturaNotasChange(valor) {
    const filtroTrimestre = document.getElementById('filtroTrimestreContainer');
    const inputTrim = document.getElementById('selectTrimestreNotasCurso');
    const container = document.getElementById('tablaNotasCursoContainer');
    const mensaje = document.getElementById('mensajeSeleccion');

    if (valor) {
        // Mostrar filtro de trimestre
        if (filtroTrimestre) filtroTrimestre.style.display = 'block';
        // Limpiar valor de trimestre
        if (inputTrim) inputTrim.value = '';
    } else {
        // Ocultar filtro trimestre
        if (filtroTrimestre) filtroTrimestre.style.display = 'none';
    }

    // Ocultar tabla
    if (container) container.style.display = 'none';
    if (mensaje) {
        mensaje.style.display = 'block';
        mensaje.textContent = 'Seleccione un trimestre para ver las notas';
    }
}

// Función para manejar cambio de trimestre en notas
function onTrimestreNotasChange(valor) {
    // Solo cargar notas si hay curso y asignatura seleccionados
    const cursoValor = document.getElementById('selectCursoNotasPorCurso')?.value;
    const asignaturaValor = document.getElementById('selectAsignaturaNotasCurso')?.value;
    const trimestreInput = document.getElementById('selectTrimestreNotasCurso');

    // Verificar que trimestre tenga un valor seleccionado (texto visible)
    if (cursoValor && asignaturaValor && trimestreInput?.value) {
        cargarNotasPorCurso();
    }
}

// Filtrar tabla de asignaciones
function filtrarTablaAsignaciones() {
    const filtroCurso = document.getElementById('filtroAsignacionCurso')?.value || '';
    const filtroDocente = document.getElementById('filtroAsignacionDocente')?.value || '';

    renderizarTablaAsignaciones(filtroCurso, filtroDocente);
}

function inicializarDatosDesdeDB() {
    // Verificar que los datos de PHP estén disponibles
    if (typeof cursosDB !== 'undefined' && cursosDB) {
        cursos = cursosDB.map(c => c.nombre);
    }
    if (typeof asignaturasDB !== 'undefined' && asignaturasDB) {
        asignaturas = asignaturasDB.map(a => a.nombre);
    }
    if (typeof docentesDB !== 'undefined' && docentesDB) {
        docentesData = docentesDB.map(d => ({
            id: d.id,
            nombres: d.nombres,
            apellidos: d.apellidos,
            nombreCompleto: d.nombre_completo,
            rut: d.rut || '',
            email: d.email || '',
            especialidades: (docenteEspecialidadesDB && docenteEspecialidadesDB[d.id]) ?
                docenteEspecialidadesDB[d.id].map(e => e.nombre) : []
        }));
    }
    if (typeof alumnosPorCursoDB !== 'undefined' && alumnosPorCursoDB) {
        alumnosData = {};
        for (const cursoNombre in alumnosPorCursoDB) {
            alumnosData[cursoNombre] = alumnosPorCursoDB[cursoNombre].map(a => ({
                id: a.id,
                nombres: a.nombres,
                apellidos: a.apellidos,
                nombreCompleto: a.nombre_completo,
                rut: a.rut || '',
                fechaNacimiento: a.fecha_nacimiento || '',
                sexo: a.sexo || ''
            }));
        }
    }
    if (typeof asignacionesDB !== 'undefined' && asignacionesDB) {
        asignacionesData = asignacionesDB.map(a => ({
            id: a.id,
            docente: a.docente,
            docenteId: a.docente_id,
            curso: a.curso,
            cursoId: a.curso_id,
            asignatura: a.asignatura,
            asignaturaId: a.asignatura_id
        }));
    }
}

// ==================== SISTEMA DE PESTAÑAS ====================
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    const tabsNav = document.querySelector('.tabs-nav');
    const menuToggle = document.getElementById('menuToggle');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));
            button.classList.add('active');
            const targetPanel = document.getElementById(tabId);
            if (targetPanel) targetPanel.classList.add('active');

            if (window.innerWidth <= 699) {
                tabsNav.classList.remove('open');
                menuToggle.classList.remove('active');
                document.querySelector('.panel-header')?.classList.remove('menu-open');
            }
        });
    });
}

function initMenuToggle() {
    const menuToggle = document.getElementById('menuToggle');
    const tabsNav = document.querySelector('.tabs-nav');
    const panelHeader = document.querySelector('.panel-header');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            tabsNav.classList.toggle('open');
            menuToggle.classList.toggle('active');
            panelHeader?.classList.toggle('menu-open');
        });
    }
}

function initSubTabsMobile() {
    // Sub-tabs de Alumnos
    initSubTabGroup('.sub-tabs-alumnos', 'columna-gestion-alumnos', 'columna-listado-alumnos', 'gestion-alumnos');

    // Sub-tabs de Docentes
    initSubTabGroup('.sub-tabs-docentes', 'columna-agregar-docente', 'columna-listado-docentes', 'agregar-docente');

    // Sub-tabs de Asignaciones
    initSubTabGroup('.sub-tabs-asignaciones', 'columna-asignar-docente', 'columna-asignaciones-actuales', 'asignar-docente');
}

function initSubTabGroup(containerSelector, col1Id, col2Id, defaultSubtab) {
    const subTabBtns = document.querySelectorAll(`${containerSelector} .sub-tab-btn`);
    const col1 = document.getElementById(col1Id);
    const col2 = document.getElementById(col2Id);

    if (subTabBtns.length && col1 && col2) {
        subTabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                subTabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                if (this.dataset.subtab === defaultSubtab) {
                    col1.classList.remove('hidden');
                    col2.classList.remove('active');
                } else {
                    col1.classList.add('hidden');
                    col2.classList.add('active');
                }
            });
        });
    }
}

// ==================== MÓDULO DE ALUMNOS ====================
function initModuloAlumnos() {
    cargarSelectCursos('selectCursoAlumno');
    cargarSelectCursos('editAlumnoCurso');

    const form = document.getElementById('formGestionAlumno');
    if (form) form.addEventListener('submit', agregarAlumno);

    // El filtro de curso ahora es un input autocomplete, el evento change se maneja en initNuevosFiltrosAutocomplete

    // Inicializar autocompletado de filtro de alumnos
    initAutocompletadoFiltroAlumnos();

    // Mostrar todos los alumnos al cargar (cuando el filtro está en "Todos")
    renderizarTablaAlumnos();
}

// Autocompletado para filtro de alumnos
function initAutocompletadoFiltroAlumnos() {
    const input = document.getElementById('filtroNombreAlumno');
    const lista = document.getElementById('sugerenciasAlumnos');
    if (!input || !lista) return;

    // Mostrar lista al hacer clic o focus (cerrar otras primero)
    input.addEventListener('focus', () => {
        cerrarTodasLasSugerencias('sugerenciasAlumnos');
        mostrarListaAlumnos(input, lista, '');
    });
    input.addEventListener('click', (e) => {
        e.stopPropagation(); // Evitar que el evento burbujee al document
        cerrarTodasLasSugerencias('sugerenciasAlumnos');
        // Mostrar todas las opciones al hacer clic (no filtrar por valor actual)
        mostrarListaAlumnos(input, lista, '');
    });

    // Filtrar mientras escribe
    input.addEventListener('input', function() {
        mostrarListaAlumnos(input, lista, this.value);
        renderizarTablaAlumnos();
    });

    // Navegación con teclado
    input.addEventListener('keydown', function(e) {
        navegarListaSugerenciasAlumnos(e, lista, input);
    });
}

function mostrarListaAlumnos(input, lista, filtro) {
    const cursoSeleccionado = document.getElementById('filtroCursoAlumnos')?.value || '';

    const filtroNorm = normalizarTexto(filtro);
    let alumnos = [];

    if (!cursoSeleccionado || cursoSeleccionado.toLowerCase() === 'todos') {
        // Obtener todos los alumnos de todos los cursos
        Object.keys(alumnosData).forEach(curso => {
            alumnosData[curso].forEach(alumno => {
                alumnos.push({ ...alumno, curso: curso });
            });
        });
    } else {
        alumnos = (alumnosData[cursoSeleccionado] || []).map(a => ({ ...a, curso: cursoSeleccionado }));
    }

    let alumnosFiltrados = alumnos.filter(a =>
        normalizarTexto(a.nombreCompleto).includes(filtroNorm)
    );

    if (alumnosFiltrados.length === 0 && filtroNorm !== '') {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron alumnos</div>';
    } else {
        // Agregar opción "Todos" al inicio si no hay filtro o si "todos" coincide
        let html = '';
        if (filtroNorm === '' || 'todos'.includes(filtroNorm)) {
            html = '<div class="sugerencia-item sugerencia-todos" data-valor="">Todos</div>';
        }
        // Limitar a 20 sugerencias para no saturar
        const limitados = alumnosFiltrados.slice(0, 20);
        html += limitados.map(a => `
            <div class="sugerencia-item" data-valor="${capitalizarNombre(a.nombreCompleto)}">
                ${capitalizarNombre(a.nombreCompleto)}${!cursoSeleccionado ? ` <span class="text-muted">(${a.curso})</span>` : ''}
            </div>
        `).join('');
        lista.innerHTML = html;
    }

    // Agregar eventos de clic a cada item
    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarAlumno(input, lista, this.dataset.valor);
        });
    });

    lista.style.display = 'block';
}

function seleccionarAlumno(input, lista, valor) {
    input.value = valor;
    lista.style.display = 'none';
    // Quitar clase active de la flecha correspondiente
    const arrow = document.querySelector(`[data-target="${lista.id}"]`);
    if (arrow) arrow.classList.remove('active');
    renderizarTablaAlumnos();
}

// Navegación con teclado para lista de alumnos
function navegarListaSugerenciasAlumnos(e, lista, input) {
    const items = lista.querySelectorAll('.sugerencia-item:not(.no-resultados)');
    if (items.length === 0) return;

    const activo = lista.querySelector('.sugerencia-item.activo');
    let indiceActivo = -1;

    if (activo) {
        indiceActivo = Array.from(items).indexOf(activo);
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (activo) activo.classList.remove('activo');
        indiceActivo = (indiceActivo + 1) % items.length;
        items[indiceActivo].classList.add('activo');
        items[indiceActivo].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activo) activo.classList.remove('activo');
        indiceActivo = indiceActivo <= 0 ? items.length - 1 : indiceActivo - 1;
        items[indiceActivo].classList.add('activo');
        items[indiceActivo].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activo) {
            seleccionarAlumno(input, lista, activo.dataset.valor);
        }
    } else if (e.key === 'Escape') {
        lista.style.display = 'none';
    }
}

function cargarSelectCursos(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    // Limpiar opciones existentes (excepto la primera)
    while (select.options.length > 1) select.remove(1);

    cursos.forEach(curso => {
        const option = document.createElement('option');
        option.value = curso;
        option.textContent = curso;
        select.appendChild(option);
    });
}

function renderizarTablaAlumnos() {
    const tbody = document.getElementById('tbodyAlumnos');
    if (!tbody) return;

    const cursoSeleccionado = document.getElementById('filtroCursoAlumnos')?.value || '';
    const textoBusqueda = normalizarTexto(document.getElementById('filtroNombreAlumno')?.value || '');

    let alumnos = [];

    if (!cursoSeleccionado || cursoSeleccionado.toLowerCase() === 'todos') {
        // Mostrar todos los alumnos de todos los cursos
        Object.keys(alumnosData).forEach(curso => {
            alumnosData[curso].forEach(alumno => {
                alumnos.push({ ...alumno, curso: curso });
            });
        });
        // Ordenar por nombre
        alumnos.sort((a, b) => a.nombreCompleto.localeCompare(b.nombreCompleto));
    } else {
        // Filtrar por curso específico
        alumnos = (alumnosData[cursoSeleccionado] || []).map(a => ({ ...a, curso: cursoSeleccionado }));
    }

    if (textoBusqueda) {
        alumnos = alumnos.filter(a => normalizarTexto(a.nombreCompleto).includes(textoBusqueda));
    }

    if (alumnos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay alumnos para mostrar</td></tr>';
        return;
    }

    tbody.innerHTML = alumnos.map(alumno => `
        <tr>
            <td>${capitalizarNombre(alumno.nombreCompleto)}</td>
            <td>${alumno.rut || '-'}</td>
            <td>${alumno.curso || '-'}</td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon btn-icon-edit" onclick="abrirModalEditarAlumno(${alumno.id})" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon btn-icon-delete" onclick="confirmarEliminarAlumno(${alumno.id})" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function agregarAlumno(e) {
    e.preventDefault();

    const nombres = document.getElementById('inputNombreAlumno')?.value?.trim();
    const apellidos = document.getElementById('inputApellidoAlumno')?.value?.trim();
    const rut = document.getElementById('inputRutAlumno')?.value?.trim();
    const fechaNacimiento = document.getElementById('inputFechaNacAlumno')?.value;
    const sexo = document.getElementById('selectSexoAlumno')?.value;
    const cursoNombre = document.getElementById('selectCursoAlumno')?.value;

    // Datos del apoderado NUEVO
    const nombresApoderado = document.getElementById('inputNombresApoderado')?.value?.trim() || '';
    const apellidosApoderado = document.getElementById('inputApellidosApoderado')?.value?.trim() || '';
    const rutApoderado = document.getElementById('inputRutApoderado')?.value?.trim() || '';
    const correoApoderado = document.getElementById('inputCorreoApoderado')?.value?.trim() || '';
    const telefonoApoderado = document.getElementById('inputTelefonoApoderado')?.value?.trim() || '';

    // Datos del apoderado EXISTENTE
    const apoderadoExiste = window.apoderadoExisteMarcado || false;
    const nombresApoderadoExiste = document.getElementById('inputNombresApoderadoExiste')?.value?.trim() || '';
    const apellidosApoderadoExiste = document.getElementById('inputApellidosApoderadoExiste')?.value?.trim() || '';
    const rutApoderadoExiste = document.getElementById('inputRutApoderadoExiste')?.value?.trim() || '';
    const parentescoApoderadoExiste = document.getElementById('selectParentescoExiste')?.value || '';

    if (!nombres || !apellidos || !rut) {
        mostrarModalMensaje('Campos incompletos', 'Debe completar los campos obligatorios: Nombres, Apellidos y RUT', 'warning');
        return;
    }

    if (!cursoNombre) {
        mostrarModalMensaje('Campos incompletos', 'Debe seleccionar un curso', 'warning');
        return;
    }

    // Obtener el ID del curso
    const cursoObj = cursosDB.find(c => c.nombre === cursoNombre);
    if (!cursoObj) {
        mostrarModalMensaje('Error', 'Curso no válido', 'error');
        return;
    }

    // Validar que se haya llenado al menos una opción de apoderado
    const datosApoderadoNuevo = nombresApoderado && apellidosApoderado && rutApoderado;
    const datosApoderadoExistente = apoderadoExiste && rutApoderadoExiste && parentescoApoderadoExiste;

    if (!datosApoderadoNuevo && !datosApoderadoExistente) {
        mostrarModalMensaje('Datos del Apoderado Requeridos', 'Debe completar los datos del apoderado en alguna de las dos opciones: "Datos del Apoderado" (Nombres, Apellidos y RUT) o "¿Apoderado ya Existe?" (RUT y Parentesco)', 'warning');
        return;
    }

    try {
        const response = await fetch('api/agregar_alumno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombres: nombres,
                apellidos: apellidos,
                rut: rut,
                fecha_nacimiento: fechaNacimiento || null,
                sexo: sexo || null,
                curso_id: cursoObj.id,
                // Apoderado nuevo
                nombres_apoderado: nombresApoderado,
                apellidos_apoderado: apellidosApoderado,
                rut_apoderado: rutApoderado,
                correo_apoderado: correoApoderado,
                telefono_apoderado: telefonoApoderado,
                // Apoderado existente
                apoderado_existe: apoderadoExiste,
                nombres_apoderado_existe: nombresApoderadoExiste,
                apellidos_apoderado_existe: apellidosApoderadoExiste,
                rut_apoderado_existe: rutApoderadoExiste,
                parentesco_apoderado_existe: parentescoApoderadoExiste
            })
        });

        const data = await response.json();

        if (data.success) {
            // Agregar al array local
            const nuevoAlumno = {
                id: data.alumno_id,
                nombres: nombres,
                apellidos: apellidos,
                nombreCompleto: `${nombres} ${apellidos}`,
                rut: rut,
                fechaNacimiento: fechaNacimiento,
                sexo: sexo
            };

            if (!alumnosData[cursoNombre]) {
                alumnosData[cursoNombre] = [];
            }
            alumnosData[cursoNombre].push(nuevoAlumno);

            // Limpiar formulario y refrescar tabla
            limpiarFormularioAlumno();

            // Si el curso actual está seleccionado en el filtro, refrescar
            const filtroCurso = document.getElementById('filtroCursoAlumnos')?.value;
            if (filtroCurso === cursoNombre) {
                renderizarTablaAlumnos();
            }

            mostrarModalMensaje('Registro exitoso', data.message, 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al agregar alumno:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

function limpiarFormularioAlumno() {
    const form = document.getElementById('formGestionAlumno');
    if (form) form.reset();

    // Cerrar sección de apoderado si está abierta
    const content = document.getElementById('apoderadoContent');
    const icon = document.getElementById('apoderadoToggleIcon');
    if (content) content.style.display = 'none';
    if (icon) icon.classList.remove('expanded');
}

// Función para expandir/colapsar sección de apoderado
function toggleApoderadoSection() {
    const content = document.getElementById('apoderadoContent');
    const icon = document.getElementById('apoderadoToggleIcon');

    if (content && icon) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            icon.classList.add('expanded');
        } else {
            content.style.display = 'none';
            icon.classList.remove('expanded');
        }
    }
}

// Hacer la función disponible globalmente
window.toggleApoderadoSection = toggleApoderadoSection;

function abrirModalEditarAlumno(id) {
    // Buscar alumno
    let alumnoEncontrado = null;
    let cursoEncontrado = '';

    for (const curso in alumnosData) {
        const alumno = alumnosData[curso].find(a => a.id === id);
        if (alumno) {
            alumnoEncontrado = alumno;
            cursoEncontrado = curso;
            break;
        }
    }

    if (!alumnoEncontrado) return;

    document.getElementById('editAlumnoId').value = id;
    document.getElementById('editAlumnoCurso').value = cursoEncontrado;
    document.getElementById('editAlumnoNombres').value = alumnoEncontrado.nombres;
    document.getElementById('editAlumnoApellidos').value = alumnoEncontrado.apellidos;
    document.getElementById('editAlumnoRut').value = alumnoEncontrado.rut;
    document.getElementById('editAlumnoFechaNac').value = alumnoEncontrado.fechaNacimiento;
    document.getElementById('editAlumnoSexo').value = alumnoEncontrado.sexo;

    document.getElementById('modalEditarAlumno').style.display = 'flex';
}

function cerrarModalEditarAlumno() {
    document.getElementById('modalEditarAlumno').style.display = 'none';
}

async function guardarEdicionAlumno() {
    const alumnoId = parseInt(document.getElementById('editAlumnoId')?.value);
    const cursoNombre = document.getElementById('editAlumnoCurso')?.value;
    const nombres = document.getElementById('editAlumnoNombres')?.value?.trim();
    const apellidos = document.getElementById('editAlumnoApellidos')?.value?.trim();
    const rut = document.getElementById('editAlumnoRut')?.value?.trim();
    const fechaNacimiento = document.getElementById('editAlumnoFechaNac')?.value;
    const sexo = document.getElementById('editAlumnoSexo')?.value;

    if (!alumnoId || !nombres || !apellidos || !rut) {
        mostrarModalMensaje('Campos incompletos', 'Debe completar los campos obligatorios', 'warning');
        return;
    }

    // Obtener el ID del curso
    const cursoObj = cursosDB.find(c => c.nombre === cursoNombre);
    if (!cursoObj) {
        mostrarModalMensaje('Error', 'Curso no válido', 'error');
        return;
    }

    try {
        const response = await fetch('api/editar_alumno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: alumnoId,
                nombres: nombres,
                apellidos: apellidos,
                rut: rut,
                fecha_nacimiento: fechaNacimiento || null,
                sexo: sexo || null,
                curso_id: cursoObj.id
            })
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar en el array local - buscar en todos los cursos
            for (const curso in alumnosData) {
                const index = alumnosData[curso].findIndex(a => a.id === alumnoId);
                if (index !== -1) {
                    // Si el curso cambió, mover el alumno
                    if (curso !== cursoNombre) {
                        alumnosData[curso].splice(index, 1);
                        if (!alumnosData[cursoNombre]) {
                            alumnosData[cursoNombre] = [];
                        }
                        alumnosData[cursoNombre].push({
                            id: alumnoId,
                            nombres: nombres,
                            apellidos: apellidos,
                            nombreCompleto: `${nombres} ${apellidos}`,
                            rut: rut,
                            fechaNacimiento: fechaNacimiento,
                            sexo: sexo
                        });
                    } else {
                        // Actualizar en el mismo curso
                        alumnosData[curso][index] = {
                            id: alumnoId,
                            nombres: nombres,
                            apellidos: apellidos,
                            nombreCompleto: `${nombres} ${apellidos}`,
                            rut: rut,
                            fechaNacimiento: fechaNacimiento,
                            sexo: sexo
                        };
                    }
                    break;
                }
            }

            cerrarModalEditarAlumno();
            renderizarTablaAlumnos();
            mostrarModalMensaje('Actualización exitosa', 'Alumno actualizado correctamente', 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al editar alumno:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

async function confirmarEliminarAlumno(id) {
    if (!confirm('¿Está seguro de eliminar este alumno? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch('api/eliminar_alumno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const data = await response.json();

        if (data.success) {
            // Eliminar del array local
            for (const curso in alumnosData) {
                const index = alumnosData[curso].findIndex(a => a.id === id);
                if (index !== -1) {
                    alumnosData[curso].splice(index, 1);
                    break;
                }
            }

            renderizarTablaAlumnos();
            mostrarModalMensaje('Eliminación exitosa', 'Alumno eliminado correctamente', 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar alumno:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

// ==================== MÓDULO DE DOCENTES ====================
function initModuloDocentes() {
    renderizarCheckboxEspecialidades();
    renderizarTablaDocentes();

    const form = document.getElementById('formGestionDocente');
    if (form) form.addEventListener('submit', agregarDocente);

    // Inicializar autocompletado de filtros
    initAutocompletadoFiltroDocentes();
    initAutocompletadoFiltroAsignaturas();
}

// Cerrar todas las listas de sugerencias
function cerrarTodasLasSugerencias(excepto = null) {
    const listas = [
        'sugerenciasDocentes',
        'sugerenciasAsignaturas',
        'sugerenciasAlumnos',
        'sugerenciasCursosAlumnos',
        'sugerenciasDocenteAsignar',
        'sugerenciasCursoAsignar',
        'sugerenciasCursosAsignacion',
        'sugerenciasDocentesAsignacion',
        'sugerenciasCursoNotas',
        'sugerenciasAsignaturaNotas',
        'sugerenciasTrimestreNotas',
        'sugerenciasTipoComunicado',
        'sugerenciasModoCurso'
    ];
    listas.forEach(id => {
        if (id !== excepto) {
            const lista = document.getElementById(id);
            if (lista) lista.style.display = 'none';
        }
    });
}

// Autocompletado para filtro de docentes
function initAutocompletadoFiltroDocentes() {
    const input = document.getElementById('filtroNombreDocente');
    const lista = document.getElementById('sugerenciasDocentes');
    if (!input || !lista) return;

    // Mostrar lista al hacer clic o focus (cerrar otras primero)
    input.addEventListener('focus', () => {
        cerrarTodasLasSugerencias('sugerenciasDocentes');
        mostrarListaDocentes(input, lista, '');
    });
    input.addEventListener('click', (e) => {
        e.stopPropagation(); // Evitar que el evento burbujee al document
        cerrarTodasLasSugerencias('sugerenciasDocentes');
        // Mostrar todas las opciones al hacer clic (no filtrar por valor actual)
        mostrarListaDocentes(input, lista, '');
    });

    // Filtrar mientras escribe
    input.addEventListener('input', function() {
        mostrarListaDocentes(input, lista, this.value);
        renderizarTablaDocentes();
    });

    // Navegación con teclado
    input.addEventListener('keydown', function(e) {
        navegarListaSugerencias(e, lista, input, seleccionarDocente);
    });
}

function mostrarListaDocentes(input, lista, filtro) {
    const filtroNorm = normalizarTexto(filtro);

    let docentesFiltrados = docentesData.filter(d =>
        normalizarTexto(d.nombreCompleto).includes(filtroNorm)
    );

    if (docentesFiltrados.length === 0 && filtroNorm !== '') {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron docentes</div>';
    } else {
        // Agregar opción "Todos" al inicio si no hay filtro o si "todos" coincide con el filtro
        let html = '';
        if (filtroNorm === '' || 'todos'.includes(filtroNorm)) {
            html = '<div class="sugerencia-item sugerencia-todos" data-valor="">Todos</div>';
        }
        html += docentesFiltrados.map(d => `
            <div class="sugerencia-item" data-valor="${capitalizarNombre(d.nombreCompleto)}">
                ${capitalizarNombre(d.nombreCompleto)}
            </div>
        `).join('');
        lista.innerHTML = html;
    }

    // Agregar eventos de clic a cada item
    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarDocente(input, lista, this.dataset.valor);
        });
    });

    lista.style.display = 'block';
}

function seleccionarDocente(input, lista, valor) {
    input.value = valor;
    lista.style.display = 'none';
    // Quitar clase active de la flecha correspondiente
    const arrow = document.querySelector(`[data-target="${lista.id}"]`);
    if (arrow) arrow.classList.remove('active');
    renderizarTablaDocentes();
}

// Autocompletado para filtro de asignaturas
function initAutocompletadoFiltroAsignaturas() {
    const input = document.getElementById('filtroAsignaturaDocente');
    const lista = document.getElementById('sugerenciasAsignaturas');
    if (!input || !lista) return;

    // Obtener lista única de especialidades de todos los docentes
    const todasEspecialidades = [...new Set(docentesData.flatMap(d => d.especialidades))].sort();

    // Mostrar lista al hacer clic o focus (cerrar otras primero)
    input.addEventListener('focus', () => {
        cerrarTodasLasSugerencias('sugerenciasAsignaturas');
        mostrarListaAsignaturas(input, lista, '', todasEspecialidades);
    });
    input.addEventListener('click', (e) => {
        e.stopPropagation(); // Evitar que el evento burbujee al document
        cerrarTodasLasSugerencias('sugerenciasAsignaturas');
        // Mostrar todas las opciones al hacer clic (no filtrar por valor actual)
        mostrarListaAsignaturas(input, lista, '', todasEspecialidades);
    });

    // Filtrar mientras escribe
    input.addEventListener('input', function() {
        mostrarListaAsignaturas(input, lista, this.value, todasEspecialidades);
        renderizarTablaDocentes();
    });

    // Navegación con teclado
    input.addEventListener('keydown', function(e) {
        navegarListaSugerencias(e, lista, input, seleccionarAsignatura);
    });
}

function mostrarListaAsignaturas(input, lista, filtro, todasEspecialidades) {
    const filtroNorm = normalizarTexto(filtro);

    let asigFiltradas = todasEspecialidades.filter(a =>
        normalizarTexto(a).includes(filtroNorm)
    );

    if (asigFiltradas.length === 0 && filtroNorm !== '') {
        lista.innerHTML = '<div class="sugerencia-item no-resultados">No se encontraron asignaturas</div>';
    } else {
        // Agregar opción "Todas" al inicio si no hay filtro o si "todas" coincide con el filtro
        let html = '';
        if (filtroNorm === '' || 'todas'.includes(filtroNorm)) {
            html = '<div class="sugerencia-item sugerencia-todos" data-valor="">Todas</div>';
        }
        html += asigFiltradas.map(a => `
            <div class="sugerencia-item" data-valor="${a}">
                ${a}
            </div>
        `).join('');
        lista.innerHTML = html;
    }

    // Agregar eventos de clic a cada item
    lista.querySelectorAll('.sugerencia-item:not(.no-resultados)').forEach(item => {
        item.addEventListener('click', function() {
            seleccionarAsignatura(input, lista, this.dataset.valor);
        });
    });

    lista.style.display = 'block';
}

function seleccionarAsignatura(input, lista, valor) {
    input.value = valor;
    lista.style.display = 'none';
    // Quitar clase active de la flecha correspondiente
    const arrow = document.querySelector(`[data-target="${lista.id}"]`);
    if (arrow) arrow.classList.remove('active');
    renderizarTablaDocentes();
}

// Navegación con teclado para listas de sugerencias
function navegarListaSugerencias(e, lista, input, funcionSeleccionar) {
    const items = lista.querySelectorAll('.sugerencia-item:not(.no-resultados)');
    if (items.length === 0) return;

    const activo = lista.querySelector('.sugerencia-item.activo');
    let indiceActivo = -1;

    if (activo) {
        indiceActivo = Array.from(items).indexOf(activo);
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (activo) activo.classList.remove('activo');
        indiceActivo = (indiceActivo + 1) % items.length;
        items[indiceActivo].classList.add('activo');
        items[indiceActivo].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activo) activo.classList.remove('activo');
        indiceActivo = indiceActivo <= 0 ? items.length - 1 : indiceActivo - 1;
        items[indiceActivo].classList.add('activo');
        items[indiceActivo].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activo) {
            funcionSeleccionar(input, lista, activo.dataset.valor);
        }
    } else if (e.key === 'Escape') {
        lista.style.display = 'none';
    }
}

function renderizarCheckboxEspecialidades() {
    const container = document.getElementById('checkboxEspecialidades');
    if (!container) return;

    container.innerHTML = asignaturas.map(asig => `
        <label class="checkbox-label">
            <input type="checkbox" name="especialidades" value="${asig}" title="${asig}">
            <span>${abreviarAsignatura(asig)}</span>
        </label>
    `).join('');
}

function renderizarTablaDocentes() {
    const tbody = document.getElementById('tbodyDocentes');
    if (!tbody) return;

    const filtroNombre = normalizarTexto(document.getElementById('filtroNombreDocente')?.value || '');
    const filtroAsig = normalizarTexto(document.getElementById('filtroAsignaturaDocente')?.value || '');

    let docentes = [...docentesData];

    if (filtroNombre) {
        docentes = docentes.filter(d => normalizarTexto(d.nombreCompleto).includes(filtroNombre));
    }

    if (filtroAsig) {
        docentes = docentes.filter(d =>
            d.especialidades.some(e => normalizarTexto(e).includes(filtroAsig))
        );
    }

    if (docentes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay docentes registrados</td></tr>';
        return;
    }

    tbody.innerHTML = docentes.map(docente => {
        const espCompletas = docente.especialidades.join(' - ') || '-';
        const espAbreviadas = docente.especialidades.map(e => abreviarAsignaturaResponsive(e)).join(' - ') || '-';
        return `
        <tr>
            <td>${formatearNombreDocente(docente.nombres, docente.apellidos)}</td>
            <td title="${espCompletas}">
                <span class="texto-desktop">${espCompletas}</span>
                <span class="texto-mobile">${espAbreviadas}</span>
            </td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon btn-icon-edit" onclick="abrirModalEditarDocente(${docente.id})" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon btn-icon-delete" onclick="confirmarEliminarDocente(${docente.id})" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('');
}

async function agregarDocente(e) {
    e.preventDefault();

    const nombres = document.getElementById('inputNombreDocente')?.value?.trim();
    const apellidos = document.getElementById('inputApellidoDocente')?.value?.trim();
    const rut = document.getElementById('inputRutDocente')?.value?.trim();
    const correo = document.getElementById('inputCorreoDocente')?.value?.trim() || '';

    if (!nombres || !apellidos || !rut) {
        mostrarModalMensaje('Campos incompletos', 'Debe completar los campos obligatorios: Nombres, Apellidos y RUT', 'warning');
        return;
    }

    try {
        const response = await fetch('api/agregar_docente_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombres: nombres,
                apellidos: apellidos,
                rut: rut,
                correo: correo
            })
        });

        const data = await response.json();

        if (data.success) {
            // Limpiar formulario
            limpiarFormularioDocente();
            mostrarModalMensaje('Registro exitoso', data.message, 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al pre-registrar docente:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

// ==================== MODAL DE MENSAJES ====================
function mostrarModalMensaje(titulo, mensaje, tipo = 'info') {
    const modal = document.getElementById('modalMensaje');
    const tituloElem = document.getElementById('modalMensajeTitulo');
    const textoElem = document.getElementById('modalMensajeTexto');
    const headerElem = document.getElementById('modalMensajeHeader');

    tituloElem.textContent = titulo;
    textoElem.textContent = mensaje;

    // Cambiar clase según tipo
    headerElem.className = 'modal-header';
    if (tipo === 'error') {
        headerElem.classList.add('error-header');
    } else if (tipo === 'success') {
        headerElem.classList.add('success-header');
    } else if (tipo === 'warning') {
        headerElem.classList.add('warning-header');
    }

    modal.style.display = 'flex';
}

function cerrarModalMensaje() {
    document.getElementById('modalMensaje').style.display = 'none';
}

function limpiarFormularioDocente() {
    const form = document.getElementById('formGestionDocente');
    if (form) form.reset();
}

function abrirModalEditarDocente(id) {
    const docente = docentesData.find(d => d.id === id);
    if (!docente) return;

    document.getElementById('editDocenteId').value = id;
    document.getElementById('editDocenteNombres').value = docente.nombres;
    document.getElementById('editDocenteApellidos').value = docente.apellidos;
    document.getElementById('editDocenteRut').value = docente.rut;
    document.getElementById('editDocenteEmail').value = docente.email;

    // Cargar checkboxes de especialidades
    const container = document.getElementById('checkboxEspecialidadesEdit');
    if (container) {
        container.innerHTML = asignaturas.map(asig => `
            <label class="checkbox-label">
                <input type="checkbox" name="especialidadesEdit" value="${asig}" title="${asig}" ${docente.especialidades.includes(asig) ? 'checked' : ''}>
                <span>${abreviarAsignatura(asig)}</span>
            </label>
        `).join('');
    }

    document.getElementById('modalEditarDocente').style.display = 'flex';
}

function cerrarModalEditarDocente() {
    document.getElementById('modalEditarDocente').style.display = 'none';
}

async function guardarEdicionDocente() {
    const docenteId = parseInt(document.getElementById('editDocenteId')?.value);
    const nombres = document.getElementById('editDocenteNombres')?.value?.trim();
    const apellidos = document.getElementById('editDocenteApellidos')?.value?.trim();
    const rut = document.getElementById('editDocenteRut')?.value?.trim();
    const email = document.getElementById('editDocenteEmail')?.value?.trim();

    // Obtener especialidades seleccionadas
    const checkboxes = document.querySelectorAll('input[name="especialidadesEdit"]:checked');
    const especialidades = [];

    checkboxes.forEach(cb => {
        const asigObj = asignaturasDB.find(a => a.nombre === cb.value);
        if (asigObj) {
            especialidades.push(asigObj.id);
        }
    });

    if (!docenteId || !nombres || !apellidos || !rut) {
        mostrarModalMensaje('Campos incompletos', 'Debe completar los campos obligatorios', 'warning');
        return;
    }

    try {
        const response = await fetch('api/editar_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: docenteId,
                nombres: nombres,
                apellidos: apellidos,
                rut: rut,
                email: email || '',
                especialidades: especialidades
            })
        });

        const data = await response.json();

        if (data.success) {
            // Obtener nombres de especialidades para el array local
            const nombresEspecialidades = [];
            especialidades.forEach(id => {
                const asig = asignaturasDB.find(a => a.id === id);
                if (asig) nombresEspecialidades.push(asig.nombre);
            });

            // Actualizar en el array local
            const index = docentesData.findIndex(d => d.id === docenteId);
            if (index !== -1) {
                docentesData[index] = {
                    id: docenteId,
                    nombres: nombres,
                    apellidos: apellidos,
                    nombreCompleto: `${nombres} ${apellidos}`,
                    rut: rut,
                    email: email,
                    especialidades: nombresEspecialidades
                };
            }

            // Actualizar docentesDB
            if (typeof docentesDB !== 'undefined') {
                const indexDB = docentesDB.findIndex(d => d.id === docenteId);
                if (indexDB !== -1) {
                    docentesDB[indexDB] = {
                        ...docentesDB[indexDB],
                        nombres: nombres,
                        apellidos: apellidos,
                        nombre_completo: `${nombres} ${apellidos}`,
                        rut: rut,
                        email: email
                    };
                }
            }

            cerrarModalEditarDocente();

            // Mostrar mensaje y recargar página para sincronizar con la base de datos
            mostrarModalMensaje('Actualización exitosa', 'Docente actualizado correctamente', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al editar docente:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

async function confirmarEliminarDocente(id) {
    if (!confirm('¿Está seguro de eliminar este docente? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch('api/eliminar_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const data = await response.json();

        if (data.success) {
            // Eliminar del array local
            const index = docentesData.findIndex(d => d.id === id);
            if (index !== -1) {
                docentesData.splice(index, 1);
            }

            // Eliminar de docentesDB
            if (typeof docentesDB !== 'undefined') {
                const indexDB = docentesDB.findIndex(d => d.id === id);
                if (indexDB !== -1) {
                    docentesDB.splice(indexDB, 1);
                }
            }

            renderizarTablaDocentes();
            cargarSelectDocentesAsignacion();
            cargarSelectDocentesFiltro();

            mostrarModalMensaje('Eliminación exitosa', 'Docente eliminado correctamente', 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar docente:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

function abrirModalAgregarAsignatura() {
    document.getElementById('modalAgregarAsignatura').style.display = 'flex';
}

function cerrarModalAgregarAsignatura() {
    document.getElementById('modalAgregarAsignatura').style.display = 'none';
}

function abrirModalEliminarAsignatura() {
    const select = document.getElementById('selectEliminarAsignatura');
    if (!select) return;

    // Limpiar y poblar el select
    select.innerHTML = '<option value="">Seleccione una asignatura...</option>';

    if (typeof asignaturasDB !== 'undefined' && asignaturasDB.length > 0) {
        asignaturasDB.forEach(asig => {
            const option = document.createElement('option');
            option.value = asig.id;
            option.textContent = asig.nombre;
            select.appendChild(option);
        });
    }

    document.getElementById('modalEliminarAsignatura').style.display = 'flex';
}

function cerrarModalEliminarAsignatura() {
    document.getElementById('modalEliminarAsignatura').style.display = 'none';
}

async function confirmarEliminarAsignatura() {
    const select = document.getElementById('selectEliminarAsignatura');
    const asignaturaId = select.value;

    if (!asignaturaId) {
        mostrarModalMensaje('Selección requerida', 'Debe seleccionar una asignatura', 'warning');
        return;
    }

    const asignaturaNombre = select.options[select.selectedIndex].text;

    if (!confirm(`¿Está seguro de eliminar la asignatura "${asignaturaNombre}"?`)) {
        return;
    }

    try {
        const response = await fetch('api/eliminar_asignatura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ asignatura_id: parseInt(asignaturaId) })
        });

        const data = await response.json();

        if (data.success) {
            // Eliminar de los arrays locales
            const index = asignaturasDB.findIndex(a => a.id == asignaturaId);
            if (index > -1) {
                const nombre = asignaturasDB[index].nombre;
                asignaturasDB.splice(index, 1);

                const indexNombre = asignaturas.indexOf(nombre);
                if (indexNombre > -1) {
                    asignaturas.splice(indexNombre, 1);
                }
            }

            // Refrescar los checkboxes
            renderizarCheckboxEspecialidades();

            cerrarModalEliminarAsignatura();
            mostrarModalMensaje('Eliminación exitosa', data.message, 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar asignatura:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

async function guardarNuevaAsignatura() {
    const nombre = document.getElementById('inputNuevaAsignatura').value.trim();

    if (!nombre) {
        mostrarModalMensaje('Campo requerido', 'Debe ingresar el nombre de la asignatura', 'warning');
        return;
    }

    try {
        const response = await fetch('api/agregar_asignatura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: nombre })
        });

        const data = await response.json();

        if (data.success) {
            // Agregar la nueva asignatura a los arrays locales
            const nuevaAsignatura = {
                id: data.asignatura_id,
                nombre: nombre,
                codigo: null
            };

            // Actualizar array global
            if (typeof asignaturasDB !== 'undefined') {
                asignaturasDB.push(nuevaAsignatura);
            }
            asignaturas.push(nombre);

            // Refrescar los checkboxes de especialidades
            renderizarCheckboxEspecialidades();

            // Limpiar y cerrar modal
            document.getElementById('inputNuevaAsignatura').value = '';
            cerrarModalAgregarAsignatura();

            mostrarModalMensaje('Registro exitoso', 'Asignatura agregada correctamente', 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al agregar asignatura:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

// ==================== MÓDULO DE ASIGNACIONES ====================
function initModuloAsignaciones() {
    // Los selects de docente y curso ahora son inputs autocomplete
    // Los filtros de asignaciones también son inputs autocomplete
    renderizarTablaAsignaciones();

    const form = document.getElementById('formAsignacionCurso');
    if (form) form.addEventListener('submit', crearAsignacion);

    // Los eventos change de los filtros y selects se manejan en initNuevosFiltrosAutocomplete
}

function cargarSelectDocentesAsignacion() {
    // Esta función ya no es necesaria porque usamos autocompletado con input
    // Los docentes se cargan dinámicamente en mostrarListaDocentesAsignar()
    const input = document.getElementById('selectDocenteAsignacion');
    if (input) {
        input.value = '';
        input.dataset.docenteId = '';
    }
}

function cargarSelectDocentesFiltro() {
    // Esta función ya no es necesaria porque usamos autocompletado con input
    // Los docentes se cargan dinámicamente en mostrarListaDocentesGenerico()
    const input = document.getElementById('filtroAsignacionDocente');
    if (input) {
        input.value = '';
    }
}

// Función para abreviar nombre de asignatura (primera palabra a 3 letras si hay más de una palabra)
function abreviarAsignatura(nombre) {
    if (!nombre) return '';
    const palabras = nombre.trim().split(/\s+/);
    if (palabras.length > 1) {
        // Abreviar primera palabra a 3 letras + punto
        return palabras[0].substring(0, 3) + '. ' + palabras.slice(1).join(' ');
    }
    return nombre;
}

// Función para abreviar asignaturas en modo responsive (más corto)
function abreviarAsignaturaResponsive(nombre) {
    if (!nombre) return '';

    // Diccionario de abreviaciones conocidas
    const abreviaciones = {
        'matemáticas': 'Mat.',
        'matematicas': 'Mat.',
        'lenguaje': 'Leng.',
        'inglés': 'Ing.',
        'ingles': 'Ing.',
        'historia': 'Hist.',
        'geografía': 'Geo.',
        'geografia': 'Geo.',
        'biología': 'Bio.',
        'biologia': 'Bio.',
        'química': 'Quím.',
        'quimica': 'Quím.',
        'física': 'Fís.',
        'fisica': 'Fís.',
        'música': 'Mús.',
        'musica': 'Mús.',
        'religión': 'Rel.',
        'religion': 'Rel.',
        'filosofía': 'Fil.',
        'filosofia': 'Fil.',
        'tecnología': 'Tec.',
        'tecnologia': 'Tec.',
        'artes': 'Art.',
        'orientación': 'Orient.',
        'orientacion': 'Orient.',
        'ciencias naturales': 'Cs. Natur.',
        'ciencias sociales': 'Cs. Social.',
        'educación física': 'Ed. Física',
        'educacion fisica': 'Ed. Física',
        'lengua y literatura': 'Lg. Literat.',
        'artes visuales': 'Art. Visual.',
        'artes musicales': 'Art. Music.',
        'educación tecnológica': 'Ed. Tecnol.',
        'educacion tecnologica': 'Ed. Tecnol.',
        'historia y geografía': 'Hist. Geogr.',
        'historia y geografia': 'Hist. Geogr.'
    };

    const nombreLower = nombre.toLowerCase().trim();

    // Buscar en diccionario
    if (abreviaciones[nombreLower]) {
        return abreviaciones[nombreLower];
    }

    // Si no está en el diccionario, abreviar automáticamente
    const palabras = nombre.trim().split(/\s+/);
    if (palabras.length > 1) {
        // Abreviar: primera palabra corta, segunda más larga
        const primeraPalabra = palabras[0].substring(0, 3) + '.';
        const segundaPalabra = palabras[1].substring(0, 6) + '.';
        return primeraPalabra.charAt(0).toUpperCase() + primeraPalabra.slice(1) + ' ' + segundaPalabra.charAt(0).toUpperCase() + segundaPalabra.slice(1);
    }

    // Una sola palabra: primeras 4 letras + punto
    return nombre.substring(0, 4) + '.';
}

function mostrarAsignaturasDocente() {
    const inputDocente = document.getElementById('selectDocenteAsignacion');
    const docenteId = parseInt(inputDocente?.dataset?.docenteId);
    const container = document.getElementById('checkboxAsignaturasAsignacion');
    if (!container) return;

    if (!docenteId) {
        container.innerHTML = '<p class="text-muted">Seleccione un docente para ver sus asignaturas</p>';
        return;
    }

    const docente = docentesData.find(d => d.id === docenteId);
    if (!docente || docente.especialidades.length === 0) {
        container.innerHTML = '<p class="text-muted">Este docente no tiene especialidades asignadas</p>';
        return;
    }

    container.innerHTML = docente.especialidades.map(asig => `
        <label class="checkbox-label">
            <input type="checkbox" name="asignaturasAsignacion" value="${asig}" title="${asig}">
            <span>${abreviarAsignatura(asig)}</span>
        </label>
    `).join('');
}

function renderizarTablaAsignaciones() {
    const tbody = document.getElementById('tbodyAsignaciones');
    if (!tbody) return;

    const filtroCurso = document.getElementById('filtroAsignacionCurso')?.value || '';
    const filtroDocente = document.getElementById('filtroAsignacionDocente')?.value || '';

    let asignaciones = [...asignacionesData];

    if (filtroCurso) {
        asignaciones = asignaciones.filter(a => a.curso === filtroCurso);
    }
    if (filtroDocente) {
        asignaciones = asignaciones.filter(a => a.docente === filtroDocente);
    }

    if (asignaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay asignaciones registradas</td></tr>';
        return;
    }

    tbody.innerHTML = asignaciones.map(asig => `
        <tr>
            <td>${capitalizarNombre(asig.docente)}</td>
            <td>${asig.curso}</td>
            <td title="${asig.asignatura}">${abreviarAsignatura(asig.asignatura)}</td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon btn-icon-delete" onclick="eliminarAsignacion(${asig.id})" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function crearAsignacion(e) {
    e.preventDefault();

    const inputDocente = document.getElementById('selectDocenteAsignacion');
    const inputCurso = document.getElementById('selectCursoAsignacion');
    const btnAsignar = e.target.querySelector('button[type="submit"]');

    const docenteId = inputDocente?.dataset?.docenteId ? parseInt(inputDocente.dataset.docenteId) : 0;
    const cursoNombre = inputCurso?.value || '';

    // Obtener asignaturas seleccionadas
    const checkboxes = document.querySelectorAll('input[name="asignaturasAsignacion"]:checked');
    const asignaturasSeleccionadas = [];
    checkboxes.forEach(cb => {
        const asigObj = asignaturasDB.find(a => a.nombre === cb.value);
        if (asigObj) {
            asignaturasSeleccionadas.push(asigObj.id);
        }
    });

    if (!docenteId || isNaN(docenteId)) {
        mostrarModalMensaje('Selección requerida', 'Debe seleccionar un docente', 'warning');
        return;
    }

    if (!cursoNombre) {
        mostrarModalMensaje('Selección requerida', 'Debe seleccionar un curso', 'warning');
        return;
    }

    if (asignaturasSeleccionadas.length === 0) {
        mostrarModalMensaje('Selección requerida', 'Debe seleccionar al menos una asignatura', 'warning');
        return;
    }

    // Obtener el ID del curso
    const cursoObj = cursosDB.find(c => c.nombre === cursoNombre);
    if (!cursoObj) {
        mostrarModalMensaje('Error', 'Curso no válido', 'error');
        return;
    }

    // Deshabilitar botón y mostrar procesando
    if (btnAsignar) {
        btnAsignar.disabled = true;
        btnAsignar.textContent = 'Procesando...';
    }

    try {
        const response = await fetch('api/agregar_asignacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                docente_id: docenteId,
                curso_id: cursoObj.id,
                asignaturas: asignaturasSeleccionadas,
                anio_academico: new Date().getFullYear()
            })
        });

        const data = await response.json();

        // Restaurar botón
        if (btnAsignar) {
            btnAsignar.disabled = false;
            btnAsignar.textContent = 'Asignar';
        }

        if (data.success) {
            // Obtener datos del docente
            const docente = docentesData.find(d => d.id === docenteId);

            // Agregar las nuevas asignaciones al array local usando los IDs reales del servidor
            if (data.ids_insertados && data.ids_insertados.length > 0) {
                data.ids_insertados.forEach(item => {
                    const asig = asignaturasDB.find(a => a.id === item.asignatura_id);
                    if (asig && docente) {
                        asignacionesData.push({
                            id: item.id, // ID real de la base de datos
                            docente: docente.nombreCompleto,
                            docenteId: docenteId,
                            curso: cursoNombre,
                            cursoId: cursoObj.id,
                            asignatura: asig.nombre,
                            asignaturaId: item.asignatura_id
                        });
                    }
                });
            }

            // Limpiar formulario y refrescar
            limpiarFormularioAsignacion();
            renderizarTablaAsignaciones();

            mostrarModalMensaje('Asignación exitosa', data.message, 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al crear asignación:', error);
        // Restaurar botón en caso de error
        if (btnAsignar) {
            btnAsignar.disabled = false;
            btnAsignar.textContent = 'Asignar';
        }
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

function limpiarFormularioAsignacion() {
    const inputDocente = document.getElementById('selectDocenteAsignacion');
    const inputCurso = document.getElementById('selectCursoAsignacion');

    if (inputDocente) {
        inputDocente.value = '';
        inputDocente.dataset.docenteId = '';
    }
    if (inputCurso) {
        inputCurso.value = '';
    }

    const container = document.getElementById('checkboxAsignaturasAsignacion');
    if (container) {
        container.innerHTML = '<p class="text-muted">Seleccione un docente para ver sus asignaturas</p>';
    }
}

async function eliminarAsignacion(id) {
    if (!confirm('¿Está seguro de eliminar esta asignación?')) {
        return;
    }

    try {
        const response = await fetch('api/eliminar_asignacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id: id })
        });

        const data = await response.json();

        if (data.success) {
            // Eliminar del array local
            const index = asignacionesData.findIndex(a => a.id === id);
            if (index !== -1) {
                asignacionesData.splice(index, 1);
            }

            renderizarTablaAsignaciones();
            mostrarModalMensaje('Eliminación exitosa', 'Asignación eliminada correctamente', 'success');
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar asignación:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

// ==================== MÓDULO DE COMUNICADOS ====================
function initModuloComunicados() {
    cargarCursosParaComunicados();
}

function cargarCursosParaComunicados() {
    const container = document.getElementById('contenedorCursosEspecificos');
    if (!container) return;

    container.innerHTML = `
        <div class="cursos-grid">
            ${cursos.map(curso => `
                <label class="checkbox-label">
                    <input type="checkbox" name="cursosSeleccionados" value="${curso}">
                    <span>${curso}</span>
                </label>
            `).join('')}
        </div>
    `;
}

function toggleSelectorCursos() {
    const input = document.getElementById('selectModoCurso');
    const modo = input?.dataset?.valor || '';
    const container = document.getElementById('contenedorCursosEspecificos');
    if (container) {
        container.style.display = modo === 'seleccionar' ? 'block' : 'none';
    }
}

async function enviarComunicado() {
    const tipoInput = document.getElementById('selectTipoComunicado');
    const tipo = tipoInput?.dataset?.valor || '';
    const titulo = document.getElementById('inputTituloComunicado')?.value?.trim();
    const mensaje = document.getElementById('textareaComunicado')?.value?.trim();
    const modoInput = document.getElementById('selectModoCurso');
    const modo = modoInput?.dataset?.valor || '';

    if (!tipo) {
        mostrarModalMensaje('Campo requerido', 'Debe seleccionar el tipo de comunicado', 'warning');
        return;
    }

    if (!titulo) {
        mostrarModalMensaje('Campo requerido', 'Debe ingresar el título del comunicado', 'warning');
        return;
    }

    if (!mensaje) {
        mostrarModalMensaje('Campo requerido', 'Debe ingresar el mensaje del comunicado', 'warning');
        return;
    }

    if (!modo) {
        mostrarModalMensaje('Selección requerida', 'Debe seleccionar los destinatarios', 'warning');
        return;
    }

    // Obtener cursos seleccionados si el modo es 'seleccionar'
    let cursosSeleccionados = [];
    let paraTodos = (modo === 'todos');

    if (modo === 'seleccionar') {
        const checkboxes = document.querySelectorAll('input[name="cursosSeleccionados"]:checked');
        checkboxes.forEach(cb => {
            // Buscar el ID del curso por su nombre
            const cursoObj = cursosDB.find(c => c.nombre === cb.value);
            if (cursoObj) {
                cursosSeleccionados.push(cursoObj.id);
            }
        });

        if (cursosSeleccionados.length === 0) {
            mostrarModalMensaje('Selección requerida', 'Debe seleccionar al menos un curso', 'warning');
            return;
        }
    }

    try {
        const response = await fetch('api/enviar_comunicado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo: tipo,
                titulo: titulo,
                mensaje: mensaje,
                para_todos: paraTodos,
                cursos: cursosSeleccionados
            })
        });

        const data = await response.json();

        if (data.success) {
            mostrarModalMensaje('Envío exitoso', 'Comunicado enviado correctamente', 'success');
            limpiarComunicado();
        } else {
            mostrarModalMensaje('Error', data.message, 'error');
        }
    } catch (error) {
        console.error('Error al enviar comunicado:', error);
        mostrarModalMensaje('Error de conexión', 'No se pudo conectar con el servidor', 'error');
    }
}

function limpiarComunicado() {
    const tipoInput = document.getElementById('selectTipoComunicado');
    const modoInput = document.getElementById('selectModoCurso');

    if (tipoInput) {
        tipoInput.value = '';
        tipoInput.dataset.valor = '';
    }
    document.getElementById('inputTituloComunicado').value = '';
    document.getElementById('textareaComunicado').value = '';
    if (modoInput) {
        modoInput.value = '';
        modoInput.dataset.valor = '';
    }
    document.getElementById('contenedorCursosEspecificos').style.display = 'none';
}

// ==================== MÓDULO DE NOTAS POR CURSO ====================
function initModuloNotasPorCurso() {
    // Los filtros de notas por curso ahora son inputs autocomplete
    // Los eventos se manejan en initNuevosFiltrosAutocomplete
}

// Función para cargar/mostrar notas por curso
function cargarNotasPorCurso() {
    mostrarTablaNotasCurso();
}

function cargarAsignaturasDelCurso() {
    const cursoSeleccionado = document.getElementById('selectCursoNotasPorCurso')?.value;
    const selectAsig = document.getElementById('selectAsignaturaNotasCurso');

    if (!selectAsig) return;

    // Limpiar y desactivar si no hay curso
    while (selectAsig.options.length > 1) selectAsig.remove(1);
    selectAsig.disabled = !cursoSeleccionado;

    if (!cursoSeleccionado) return;

    // Buscar asignaturas asignadas a este curso
    const asignaturasDelCurso = asignacionesData
        .filter(a => a.curso === cursoSeleccionado)
        .map(a => a.asignatura);

    const asignaturasUnicas = [...new Set(asignaturasDelCurso)];

    if (asignaturasUnicas.length === 0) {
        // Si no hay asignaciones, mostrar todas las asignaturas
        asignaturas.forEach(asig => {
            const option = document.createElement('option');
            option.value = asig;
            option.textContent = asig;
            selectAsig.appendChild(option);
        });
    } else {
        asignaturasUnicas.forEach(asig => {
            const option = document.createElement('option');
            option.value = asig;
            option.textContent = asig;
            selectAsig.appendChild(option);
        });
    }
}

async function mostrarTablaNotasCurso() {
    const cursoNombre = document.getElementById('selectCursoNotasPorCurso')?.value;
    const asignaturaNombre = document.getElementById('selectAsignaturaNotasCurso')?.value;
    const trimestreInput = document.getElementById('selectTrimestreNotasCurso');
    const trimestre = trimestreInput?.dataset?.valor || '';
    const container = document.getElementById('tablaNotasCursoContainer');
    const mensaje = document.getElementById('mensajeSeleccion');
    const tbody = document.getElementById('tbodyNotasCurso');
    const thead = document.getElementById('theadNotasCurso');

    if (!cursoNombre || !asignaturaNombre) {
        if (container) container.style.display = 'none';
        if (mensaje) mensaje.style.display = 'block';
        return;
    }

    if (container) container.style.display = 'block';
    if (mensaje) mensaje.style.display = 'none';

    // Obtener IDs desde los datos de PHP
    const cursoObj = cursosDB.find(c => c.nombre === cursoNombre);
    const asignaturaObj = asignaturasDB.find(a => a.nombre === asignaturaNombre);

    if (!cursoObj || !asignaturaObj) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Error: No se encontró el curso o asignatura</td></tr>';
        return;
    }

    // Mostrar cargando
    if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Cargando notas...</td></tr>';

    try {
        const response = await fetch('api/obtener_notas_curso_completo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id: cursoObj.id,
                asignatura_id: asignaturaObj.id
            })
        });

        const data = await response.json();

        if (!data.success) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">${data.message}</td></tr>`;
            return;
        }

        const alumnos = data.data;

        // Generar encabezados según el trimestre seleccionado
        if (thead) {
            if (!trimestre || trimestre === 'todos') {
                thead.innerHTML = `
                    <tr>
                        <th>Alumno</th>
                        <th>Prom. T1</th>
                        <th>Prom. T2</th>
                        <th>Prom. T3</th>
                        <th>Prom. Final</th>
                        <th>Estado</th>
                    </tr>
                `;
            } else {
                thead.innerHTML = `
                    <tr>
                        <th>Alumno</th>
                        <th>N1</th>
                        <th>N2</th>
                        <th>N3</th>
                        <th>N4</th>
                        <th>N5</th>
                        <th>N6</th>
                        <th>N7</th>
                        <th>N8</th>
                        <th>Prom.</th>
                    </tr>
                `;
            }
        }

        // Generar filas
        if (tbody) {
            if (alumnos.length === 0) {
                const colspan = (!trimestre || trimestre === 'todos') ? 6 : 10;
                tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted">No hay alumnos en este curso</td></tr>`;
            } else {
                if (!trimestre || trimestre === 'todos') {
                    tbody.innerHTML = alumnos.map(alumno => `
                        <tr>
                            <td>${capitalizarNombre(alumno.nombre_completo)}</td>
                            <td class="${getClaseNotaAdmin(alumno.promedio_t1)}">${formatearNotaAdmin(alumno.promedio_t1)}</td>
                            <td class="${getClaseNotaAdmin(alumno.promedio_t2)}">${formatearNotaAdmin(alumno.promedio_t2)}</td>
                            <td class="${getClaseNotaAdmin(alumno.promedio_t3)}">${formatearNotaAdmin(alumno.promedio_t3)}</td>
                            <td class="${getClaseNotaAdmin(alumno.promedio_final)}"><strong>${formatearNotaAdmin(alumno.promedio_final)}</strong></td>
                            <td class="${alumno.estado === 'Aprobado' ? 'estado-aprobado' : alumno.estado === 'Reprobado' ? 'estado-reprobado' : ''}">${alumno.estado}</td>
                        </tr>
                    `).join('');
                } else {
                    const notasTrimestre = `trimestre_${trimestre}`;
                    const promedioTrimestre = `promedio_t${trimestre}`;
                    tbody.innerHTML = alumnos.map(alumno => {
                        const notas = alumno[notasTrimestre] || {};
                        return `
                            <tr>
                                <td>${capitalizarNombre(alumno.nombre_completo)}</td>
                                <td class="${getClaseNotaAdmin(notas[1])}">${formatearNotaAdmin(notas[1])}</td>
                                <td class="${getClaseNotaAdmin(notas[2])}">${formatearNotaAdmin(notas[2])}</td>
                                <td class="${getClaseNotaAdmin(notas[3])}">${formatearNotaAdmin(notas[3])}</td>
                                <td class="${getClaseNotaAdmin(notas[4])}">${formatearNotaAdmin(notas[4])}</td>
                                <td class="${getClaseNotaAdmin(notas[5])}">${formatearNotaAdmin(notas[5])}</td>
                                <td class="${getClaseNotaAdmin(notas[6])}">${formatearNotaAdmin(notas[6])}</td>
                                <td class="${getClaseNotaAdmin(notas[7])}">${formatearNotaAdmin(notas[7])}</td>
                                <td class="${getClaseNotaAdmin(notas[8])}">${formatearNotaAdmin(notas[8])}</td>
                                <td class="${getClaseNotaAdmin(alumno[promedioTrimestre])}"><strong>${formatearNotaAdmin(alumno[promedioTrimestre])}</strong></td>
                            </tr>
                        `;
                    }).join('');
                }
            }
        }
    } catch (error) {
        console.error('Error al cargar notas:', error);
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Error de conexión al servidor</td></tr>';
    }
}

// Función para colorear notas según valor
function getClaseNotaAdmin(nota) {
    if (nota === null || nota === undefined) return 'nota-vacia';
    if (nota === 'PEND') return 'nota-pendiente-tabla';
    if (nota >= 6.0) return 'nota-excelente';
    if (nota >= 5.0) return 'nota-buena';
    if (nota >= 4.0) return 'nota-suficiente';
    return 'nota-insuficiente';
}

// Función para formatear nota con decimal
function formatearNotaAdmin(nota) {
    if (nota === null || nota === undefined) return '-';
    if (nota === 'PEND') return 'PEND';
    if (typeof nota === 'number') return nota.toFixed(1);
    return nota;
}

// ==================== CERRAR MODALES ====================
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalEditarAlumno();
        cerrarModalEditarDocente();
        cerrarModalAgregarAsignatura();
        cerrarModalMensaje();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

// ==================== MÓDULO DE ESTADÍSTICAS ====================
let chartPromedioCursos = null;
let chartDistribucion = null;
let chartEvolucion = null;
let chartAsignaturas = null;

function initModuloEstadisticas() {
    // Poblar selectores
    const selectCurso = document.getElementById('statsCursoSelector');
    const selectDocente = document.getElementById('statsDocenteSelector');
    const selectAsignatura = document.getElementById('statsAsignaturaSelector');

    if (selectCurso && cursosDB) {
        cursosDB.forEach(curso => {
            const option = document.createElement('option');
            option.value = curso.id;
            option.textContent = curso.nombre;
            selectCurso.appendChild(option);
        });
    }

    if (selectDocente && docentesDB) {
        docentesDB.forEach(docente => {
            const option = document.createElement('option');
            option.value = docente.id;
            option.textContent = capitalizarNombre(docente.nombre_completo);
            selectDocente.appendChild(option);
        });
    }

    if (selectAsignatura && asignaturasDB) {
        asignaturasDB.forEach(asig => {
            const option = document.createElement('option');
            option.value = asig.id;
            option.textContent = asig.nombre;
            selectAsignatura.appendChild(option);
        });
    }

    // Cargar estadísticas generales al inicio
    cargarEstadisticas('general');
}

function cambiarVistaEstadisticas() {
    const vista = document.getElementById('statsVistaSelector').value;

    // Mostrar/ocultar filtros según la vista
    document.getElementById('statsFiltroCursoContainer').style.display = vista === 'curso' ? 'block' : 'none';
    document.getElementById('statsFiltroDocenteContainer').style.display = vista === 'docente' ? 'block' : 'none';
    document.getElementById('statsFiltroAsignaturaContainer').style.display = vista === 'asignatura' ? 'block' : 'none';

    // Resetear selectores
    document.getElementById('statsCursoSelector').value = '';
    document.getElementById('statsDocenteSelector').value = '';
    document.getElementById('statsAsignaturaSelector').value = '';

    if (vista === 'general') {
        cargarEstadisticas('general');
    }
}

function actualizarEstadisticasCurso() {
    const cursoId = document.getElementById('statsCursoSelector').value;
    if (cursoId) {
        cargarEstadisticas('curso', { curso_id: parseInt(cursoId) });
    }
}

function actualizarEstadisticasDocente() {
    const docenteId = document.getElementById('statsDocenteSelector').value;
    if (docenteId) {
        cargarEstadisticas('docente', { docente_id: parseInt(docenteId) });
    }
}

function actualizarEstadisticasAsignatura() {
    const asignaturaId = document.getElementById('statsAsignaturaSelector').value;
    if (asignaturaId) {
        cargarEstadisticas('asignatura', { asignatura_id: parseInt(asignaturaId) });
    }
}

async function cargarEstadisticas(vista, filtros = {}) {
    try {
        const response = await fetch('api/obtener_estadisticas_establecimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vista, ...filtros })
        });

        const data = await response.json();

        if (data.success) {
            actualizarUIEstadisticas(data.data, vista);
        } else {
            console.error('Error al cargar estadísticas:', data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
    }
}

function actualizarUIEstadisticas(stats, vista) {
    // Actualizar título
    const titulos = {
        'general': 'Estadísticas Generales del Establecimiento',
        'curso': 'Estadísticas del Curso Seleccionado',
        'docente': 'Estadísticas del Docente Seleccionado',
        'asignatura': 'Estadísticas de la Asignatura Seleccionada'
    };
    document.getElementById('statsTituloActual').textContent = titulos[vista] || titulos.general;

    // Resetear todos los KPIs primero para evitar datos residuales
    document.getElementById('statsPromedioGeneral').textContent = '-';
    document.getElementById('statsTotalCursos').textContent = '0 cursos evaluados';
    document.getElementById('statsMejorCurso').textContent = '-';
    document.getElementById('statsMejorCursoNota').textContent = '-';
    document.getElementById('statsCursoApoyo').textContent = '-';
    document.getElementById('statsCursoApoyoNota').textContent = '-';
    document.getElementById('statsMejorAsig').textContent = '-';
    document.getElementById('statsMejorAsigNota').textContent = '-';
    document.getElementById('statsAsigCritica').textContent = '-';
    document.getElementById('statsAsigCriticaNota').textContent = '-';
    document.getElementById('statsTasaAprobacion').textContent = '0%';
    document.getElementById('statsTasaAprobacionAlumnos').textContent = '0 de 0 alumnos';

    // Actualizar KPIs con datos reales
    document.getElementById('statsPromedioGeneral').textContent = stats.promedio_general || '-';

    // Total de cursos considerados - diferente según la vista
    if (vista === 'asignatura') {
        // Para vista asignatura: mostrar cantidad de cursos que tienen esta asignatura
        const cursosConAsignatura = stats.cursos ? stats.cursos.length : 0;
        document.getElementById('statsTotalCursos').textContent = `${cursosConAsignatura} curso${cursosConAsignatura !== 1 ? 's' : ''} evaluado${cursosConAsignatura !== 1 ? 's' : ''}`;
    } else {
        const totalCursos = stats.total_cursos || (stats.promedios_cursos ? stats.promedios_cursos.length : 0);
        document.getElementById('statsTotalCursos').textContent = `${totalCursos} curso${totalCursos !== 1 ? 's' : ''} evaluado${totalCursos !== 1 ? 's' : ''}`;
    }

    // Actualizar label y valor del KPI "Mejor Curso" según la vista
    const labelMejorCurso = document.getElementById('labelMejorCurso');
    const elemMejorCursoValor = document.getElementById('statsMejorCurso');
    const elemMejorCursoNota = document.getElementById('statsMejorCursoNota');

    if (labelMejorCurso && elemMejorCursoValor && elemMejorCursoNota) {
        if (vista === 'curso') {
            // Vista por curso: mostrar mejor ramo del curso
            labelMejorCurso.textContent = 'Mejor Ramo del Curso';
            if (stats.mejor_asignatura) {
                elemMejorCursoValor.textContent = stats.mejor_asignatura.nombre;
                elemMejorCursoNota.textContent = `Promedio: ${stats.mejor_asignatura.promedio}`;
            } else {
                elemMejorCursoValor.textContent = '-';
                elemMejorCursoNota.textContent = '-';
            }
        } else if (vista === 'asignatura') {
            // Vista por asignatura: mostrar mejor curso en esta asignatura
            labelMejorCurso.textContent = 'Mejor Curso Evaluado';
            if (stats.mejor_curso) {
                elemMejorCursoValor.textContent = stats.mejor_curso.nombre;
                elemMejorCursoNota.textContent = `Promedio: ${stats.mejor_curso.promedio}`;
            } else {
                elemMejorCursoValor.textContent = '-';
                elemMejorCursoNota.textContent = '-';
            }
        } else {
            // Vista general u otras: mostrar mejor curso evaluado
            labelMejorCurso.textContent = 'Mejor Curso Evaluado';
            if (stats.mejor_curso) {
                elemMejorCursoValor.textContent = stats.mejor_curso.nombre;
                elemMejorCursoNota.textContent = `Promedio: ${stats.mejor_curso.promedio}`;
            } else if (stats.mejor_asignatura) {
                elemMejorCursoValor.textContent = stats.mejor_asignatura.nombre;
                elemMejorCursoNota.textContent = `Promedio: ${stats.mejor_asignatura.promedio}`;
            } else {
                elemMejorCursoValor.textContent = '-';
                elemMejorCursoNota.textContent = '-';
            }
        }
    }

    // Curso que necesita apoyo / Asignatura crítica
    const labelNecesitaApoyo = document.getElementById('labelNecesitaApoyo');
    const elemCursoApoyo = document.getElementById('statsCursoApoyo');
    const elemCursoApoyoNota = document.getElementById('statsCursoApoyoNota');

    if (vista === 'asignatura') {
        // Vista por asignatura: mostrar curso con menor promedio en esta asignatura
        if (labelNecesitaApoyo) labelNecesitaApoyo.textContent = 'Curso con Menor Promedio';
        if (stats.curso_apoyo) {
            elemCursoApoyo.textContent = stats.curso_apoyo.nombre;
            elemCursoApoyoNota.textContent = `Promedio: ${stats.curso_apoyo.promedio}`;
        } else {
            elemCursoApoyo.textContent = '-';
            elemCursoApoyoNota.textContent = '-';
        }
    } else {
        // Vista general u otras
        if (labelNecesitaApoyo) labelNecesitaApoyo.textContent = 'Necesita Apoyo';
        if (stats.curso_apoyo) {
            elemCursoApoyo.textContent = stats.curso_apoyo.nombre;
            elemCursoApoyoNota.textContent = `Promedio: ${stats.curso_apoyo.promedio}`;
        } else if (stats.asignatura_critica) {
            elemCursoApoyo.textContent = stats.asignatura_critica.nombre;
            elemCursoApoyoNota.textContent = `Promedio: ${stats.asignatura_critica.promedio}`;
        }
    }

    // Mejor asignatura / Alumnos sobre 6 (según vista)
    const labelMejorAsignatura = document.getElementById('labelMejorAsignatura');
    const badgeMejorAsignatura = document.getElementById('badgeMejorAsignatura');
    const elemMejorAsig = document.getElementById('statsMejorAsig');
    const elemMejorAsigNota = document.getElementById('statsMejorAsigNota');
    const barMejorAsig = document.getElementById('barMejorAsig');

    if (vista === 'asignatura') {
        // Vista por asignatura: mostrar alumnos sobre 6
        if (labelMejorAsignatura) labelMejorAsignatura.textContent = 'Alumnos sobre un 6';
        if (badgeMejorAsignatura) badgeMejorAsignatura.textContent = 'EXCELENTE';
        if (elemMejorAsig) elemMejorAsig.textContent = `${stats.porcentaje_sobre_6 || 0}%`;
        if (elemMejorAsigNota) elemMejorAsigNota.textContent = `${stats.alumnos_sobre_6 || 0} alumnos de ${stats.total_alumnos_asignatura || 0}`;
        if (barMejorAsig) barMejorAsig.style.width = `${stats.porcentaje_sobre_6 || 0}%`;
    } else if (vista === 'docente' && stats.mejor_asignatura) {
        // Vista por docente: mostrar mejor asignatura con el curso
        if (labelMejorAsignatura) labelMejorAsignatura.textContent = 'Mejor Asignatura';
        if (badgeMejorAsignatura) badgeMejorAsignatura.textContent = 'DESTACADA';
        if (elemMejorAsig) {
            elemMejorAsig.textContent = abreviarAsignatura(stats.mejor_asignatura.nombre);
            elemMejorAsig.title = stats.mejor_asignatura.nombre;
        }
        if (elemMejorAsigNota) elemMejorAsigNota.textContent = `${stats.mejor_asignatura.curso} • Prom: ${stats.mejor_asignatura.promedio}`;
        if (barMejorAsig) barMejorAsig.style.width = `${(stats.mejor_asignatura.promedio / 7) * 100}%`;
    } else if (stats.mejor_asignatura) {
        // Vista general u otras: mostrar mejor asignatura
        if (labelMejorAsignatura) labelMejorAsignatura.textContent = 'Mejor Asignatura';
        if (badgeMejorAsignatura) badgeMejorAsignatura.textContent = 'DESTACADA';
        if (elemMejorAsig) {
            elemMejorAsig.textContent = abreviarAsignatura(stats.mejor_asignatura.nombre);
            elemMejorAsig.title = stats.mejor_asignatura.nombre;
        }
        if (vista === 'general' && stats.mejor_asignatura.curso) {
            if (elemMejorAsigNota) elemMejorAsigNota.textContent = `${stats.mejor_asignatura.curso} • Prom: ${stats.mejor_asignatura.promedio}`;
        } else {
            if (elemMejorAsigNota) elemMejorAsigNota.textContent = `Promedio: ${stats.mejor_asignatura.promedio}`;
        }
        if (barMejorAsig) barMejorAsig.style.width = `${(stats.mejor_asignatura.promedio / 7) * 100}%`;
    }

    // Asignatura crítica / Alumnos bajo 5 (según vista)
    const labelAsigCritica = document.getElementById('labelAsigCritica');
    const badgeAsigCritica = document.getElementById('badgeAsigCritica');
    const elemAsigCritica = document.getElementById('statsAsigCritica');
    const elemAsigCriticaNota = document.getElementById('statsAsigCriticaNota');
    const barAsigCritica = document.getElementById('barAsigCritica');

    if (vista === 'asignatura') {
        // Vista por asignatura: mostrar alumnos bajo 5
        if (labelAsigCritica) labelAsigCritica.textContent = 'Alumnos bajo un 5';
        if (badgeAsigCritica) badgeAsigCritica.textContent = 'ALERTA';
        if (elemAsigCritica) elemAsigCritica.textContent = `${stats.porcentaje_bajo_5 || 0}%`;
        if (elemAsigCriticaNota) elemAsigCriticaNota.textContent = `${stats.alumnos_bajo_5 || 0} alumnos de ${stats.total_alumnos_asignatura || 0}`;
        if (barAsigCritica) barAsigCritica.style.width = `${stats.porcentaje_bajo_5 || 0}%`;
    } else if (vista === 'docente' && stats.asignatura_critica) {
        // Vista por docente: mostrar asignatura crítica con el curso
        if (labelAsigCritica) labelAsigCritica.textContent = 'Asignatura Crítica';
        if (badgeAsigCritica) badgeAsigCritica.textContent = 'REVISAR';
        if (elemAsigCritica) {
            elemAsigCritica.textContent = abreviarAsignatura(stats.asignatura_critica.nombre);
            elemAsigCritica.title = stats.asignatura_critica.nombre;
        }
        if (elemAsigCriticaNota) elemAsigCriticaNota.textContent = `${stats.asignatura_critica.curso} • Prom: ${stats.asignatura_critica.promedio}`;
        if (barAsigCritica) barAsigCritica.style.width = `${(stats.asignatura_critica.promedio / 7) * 100}%`;
    } else if (stats.asignatura_critica) {
        // Vista general u otras: mostrar asignatura crítica
        if (labelAsigCritica) labelAsigCritica.textContent = 'Asignatura Crítica';
        if (badgeAsigCritica) badgeAsigCritica.textContent = 'REVISAR';
        if (elemAsigCritica) {
            elemAsigCritica.textContent = abreviarAsignatura(stats.asignatura_critica.nombre);
            elemAsigCritica.title = stats.asignatura_critica.nombre;
        }
        if (vista === 'general' && stats.asignatura_critica.curso) {
            if (elemAsigCriticaNota) elemAsigCriticaNota.textContent = `${stats.asignatura_critica.curso} • Prom: ${stats.asignatura_critica.promedio}`;
        } else {
            if (elemAsigCriticaNota) elemAsigCriticaNota.textContent = `Promedio: ${stats.asignatura_critica.promedio}`;
        }
        if (barAsigCritica) barAsigCritica.style.width = `${(stats.asignatura_critica.promedio / 7) * 100}%`;
    }

    // Tasa de aprobación
    document.getElementById('statsTasaAprobacion').textContent = `${stats.tasa_aprobacion || 0}%`;
    document.getElementById('statsTasaAprobacionAlumnos').textContent = `${stats.aprobados || 0} de ${stats.total_alumnos || 0} alumnos`;

    // Actualizar gráficos
    actualizarGraficos(stats, vista);
}

function actualizarGraficos(stats, vista) {
    // Gráfico de promedios por curso
    const ctxCursos = document.getElementById('graficoPromedioCursos');
    if (ctxCursos) {
        if (chartPromedioCursos) chartPromedioCursos.destroy();

        const dataCursos = stats.cursos || stats.asignaturas || [];
        chartPromedioCursos = new Chart(ctxCursos, {
            type: 'bar',
            data: {
                labels: dataCursos.map(c => abreviarAsignatura(c.nombre)),
                datasets: [{
                    label: 'Promedio',
                    data: dataCursos.map(c => c.promedio),
                    backgroundColor: dataCursos.map(c => c.promedio >= 4 ? 'rgba(34, 197, 94, 0.7)' : 'rgba(239, 68, 68, 0.7)'),
                    borderColor: dataCursos.map(c => c.promedio >= 4 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)'),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 7 }
                }
            }
        });
    }

    // Gráfico de distribución
    const ctxDist = document.getElementById('graficoDistribucion');
    if (ctxDist && stats.distribucion) {
        if (chartDistribucion) chartDistribucion.destroy();

        chartDistribucion = new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: ['Excelente (6-7)', 'Bueno (5-6)', 'Suficiente (4-5)', 'Insuficiente (<4)'],
                datasets: [{
                    data: [
                        stats.distribucion.excelente,
                        stats.distribucion.bueno,
                        stats.distribucion.suficiente,
                        stats.distribucion.insuficiente
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Gráfico de evolución mensual con marcadores de trimestre
    const ctxEvol = document.getElementById('graficoEvolucion');
    if (ctxEvol && (stats.evolucion_mensual || stats.evolucion_trimestral)) {
        if (chartEvolucion) chartEvolucion.destroy();

        // Nombres de meses del año escolar (Marzo a Noviembre)
        const mesesLabels = ['Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov'];

        // Usar datos mensuales si están disponibles, sino calcular desde trimestral
        let dataMensual = [];
        if (stats.evolucion_mensual) {
            // Extraer datos de los meses 3-11
            for (let m = 3; m <= 11; m++) {
                dataMensual.push(stats.evolucion_mensual[m]);
            }
        } else {
            // Fallback: distribuir datos trimestrales en meses
            const evol = stats.evolucion_trimestral;
            dataMensual = [
                evol[1], evol[1], evol[1],  // T1: Mar, Abr, May
                evol[2], evol[2], evol[2],  // T2: Jun, Jul, Ago
                evol[3], evol[3], evol[3]   // T3: Sep, Oct, Nov
            ];
        }

        // Plugin personalizado para dibujar líneas verticales de trimestre
        const trimestrePlugin = {
            id: 'trimestreLines',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                const xAxis = chart.scales.x;
                const yAxis = chart.scales.y;

                // Posiciones de fin de trimestre: May=2, Ago=5, Nov=8
                // Las líneas se dibujan DESPUÉS del mes (entre el mes y el siguiente)
                const trimestres = [
                    { pos: 2, label: 'T1' },   // Mayo - fin trimestre 1
                    { pos: 5, label: 'T2' },   // Agosto - fin trimestre 2
                    { pos: 8, label: 'T3' }    // Noviembre - fin trimestre 3
                ];

                trimestres.forEach(t => {
                    const x = xAxis.getPixelForValue(t.pos);

                    // Calcular posición de la línea vertical (después del mes)
                    const nextX = t.pos < 8 ? xAxis.getPixelForValue(t.pos + 1) : x + 30;
                    const lineX = (x + nextX) / 2;

                    // Línea vertical punteada (entre meses)
                    ctx.save();
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(107, 114, 128, 0.4)';
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([4, 4]);
                    ctx.moveTo(lineX, yAxis.top);
                    ctx.lineTo(lineX, yAxis.bottom);
                    ctx.stroke();
                    ctx.restore();

                    // Etiqueta del trimestre (centrada arriba del mes)
                    ctx.save();
                    ctx.fillStyle = 'rgba(59, 130, 246, 0.9)';
                    ctx.font = 'bold 10px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(t.label, x, yAxis.top - 8);
                    ctx.restore();
                });
            }
        };

        chartEvolucion = new Chart(ctxEvol, {
            type: 'line',
            data: {
                labels: mesesLabels,
                datasets: [{
                    label: 'Promedio',
                    data: dataMensual,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    spanGaps: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const mesesCompletos = ['Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];
                                return mesesCompletos[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1,
                        max: 7,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20
                    }
                }
            },
            plugins: [trimestrePlugin]
        });
    }

    // Gráfico de asignaturas / Benchmark
    const ctxAsig = document.getElementById('graficoAsignaturas');
    const tituloBenchmark = document.getElementById('tituloBenchmarkAsignatura');
    if (ctxAsig) {
        if (chartAsignaturas) chartAsignaturas.destroy();

        let dataAsig = stats.asignaturas || stats.cursos || [];

        // Si la vista es por asignatura, mostrar solo los mejores 6 cursos
        if (vista === 'asignatura') {
            // Ordenar por promedio descendente y tomar los 6 mejores
            dataAsig = [...dataAsig].sort((a, b) => b.promedio - a.promedio).slice(0, 6);
            if (tituloBenchmark) {
                tituloBenchmark.textContent = 'Mejores 6 Cursos';
            }
        } else {
            // Restaurar título original
            if (tituloBenchmark) {
                tituloBenchmark.textContent = 'Benchmark por Asignatura';
            }
        }

        chartAsignaturas = new Chart(ctxAsig, {
            type: 'bar',
            data: {
                labels: dataAsig.map(a => abreviarAsignatura(a.nombre)),
                datasets: [{
                    label: 'Promedio',
                    data: dataAsig.map(a => a.promedio),
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, max: 7 }
                }
            }
        });
    }
}

// ==================== CERRAR SESIÓN ====================
async function cerrarSesion() {
    try {
        await fetch('api/logout.php', {
            method: 'POST',
            credentials: 'same-origin'
        });
        window.location.href = 'index.php';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        window.location.href = 'index.php';
    }
}

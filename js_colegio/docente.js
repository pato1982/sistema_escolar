/* ==================== PORTAL DOCENTE - JAVASCRIPT ==================== */
/* Versión conectada a APIs PHP */

// Variables globales
let chartDistribucion = null;
let chartTrimestre = null;
let chartAprobacion = null;
let chartTop5 = null;

// Datos para exportación
let datosNotasCurso = [];
let infoCursoActual = { curso: '', asignatura: '' };

// Datos de últimas notas para filtrado
let datosUltimasNotas = [];

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initSubTabs();
    initMobileMenu();
    initFormularios();
    initAutocompletados();
    initFiltrosCursoAsignatura(); // Filtros de curso->asignatura

    // Establecer fecha actual en el campo de fecha
    const fechaInput = document.getElementById('fechaNuevaNota');
    if (fechaInput) {
        fechaInput.value = new Date().toISOString().split('T')[0];
    }

    // Cargar últimas notas al inicio
    cargarUltimasNotas();

    // Cerrar dropdowns de alumnos al hacer clic fuera
    document.addEventListener('click', function (e) {
        const clickEnAutocomplete = e.target.closest('.autocomplete-container');
        if (!clickEnAutocomplete) {
            cerrarTodosLosDropdownsAlumnos();
        }
    });
});

// Cerrar todos los dropdowns de alumnos
function cerrarTodosLosDropdownsAlumnos() {
    const dropdowns = document.querySelectorAll('.autocomplete-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

// ==================== MOSTRAR TODOS LOS ALUMNOS (FLECHA) ====================

// Función para mostrar todos los alumnos cuando se hace clic en la flecha
async function mostrarTodosAlumnos(inputId, dropdownId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);

    if (!input || !dropdown) return;

    // Obtener curso seleccionado según el contexto
    let cursoId = null;

    if (inputId === 'alumnoNuevaNota') {
        cursoId = document.getElementById('cursoNuevaNota')?.value;
    } else if (inputId === 'buscarAlumno') {
        cursoId = document.getElementById('buscarCurso')?.value;
    } else if (inputId === 'filtrarAlumnoVer') {
        cursoId = document.getElementById('verCurso')?.value;
    }

    if (!cursoId) {
        dropdown.innerHTML = '<div class="autocomplete-item-empty">Primero seleccione un curso</div>';
        dropdown.classList.add('show');
        return;
    }

    // Buscar todos los alumnos (sin filtro de texto)
    const alumnos = await buscarAlumnosAPI(cursoId, '');

    if (alumnos.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item-empty">No hay alumnos en este curso</div>';
    } else {
        dropdown.innerHTML = alumnos.map(alumno => {
            const nombreFormateado = formatearNombreCompleto(alumno.nombre_completo || alumno.nombre);
            return `<div class="autocomplete-item autocomplete-item-alumno" data-id="${alumno.id}" data-nombre="${nombreFormateado}">${nombreFormateado}</div>`;
        }).join('');

        // Agregar eventos de clic
        dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', function () {
                input.value = this.dataset.nombre;
                // Guardar ID en campo hidden
                const hiddenId = inputId + 'Value';
                const hidden = document.getElementById(hiddenId);
                if (hidden) hidden.value = this.dataset.id;
                dropdown.classList.remove('show');
            });
        });
    }

    dropdown.classList.add('show');
}

// ==================== FILTROS CURSO -> ASIGNATURA ====================

// Obtener asignaturas del docente filtradas por curso usando ASIGNACIONES_DOCENTE
function getAsignaturasParaCurso(cursoId) {
    if (typeof ASIGNACIONES_DOCENTE === 'undefined' || !ASIGNACIONES_DOCENTE || ASIGNACIONES_DOCENTE.length === 0) {
        return [];
    }
    if (!cursoId) {
        return [];
    }
    return ASIGNACIONES_DOCENTE.filter(a => String(a.curso_id) === String(cursoId))
        .map(a => ({ id: a.asignatura_id, nombre: a.asignatura_nombre }));
}

// Cargar asignaturas en un select basado en el curso seleccionado
function cargarAsignaturasEnSelect(selectAsignatura, cursoId) {
    const asignaturas = getAsignaturasParaCurso(cursoId);

    // Limpiar opciones actuales
    selectAsignatura.innerHTML = '<option value="">Seleccionar asignatura</option>';

    // Agregar las asignaturas del docente para este curso
    asignaturas.forEach(asig => {
        const option = document.createElement('option');
        option.value = asig.id;
        option.textContent = asig.nombre;
        selectAsignatura.appendChild(option);
    });

    // Si solo hay una asignatura, seleccionarla automáticamente
    if (asignaturas.length === 1) {
        selectAsignatura.value = asignaturas[0].id;
    }
}

// Inicializar los eventos de cambio en los selectores de curso
function initFiltrosCursoAsignatura() {
    // Agregar Nota: cursoNuevaNota -> asignaturaNuevaNota
    const cursoNuevaNota = document.getElementById('cursoNuevaNota');
    const asignaturaNuevaNota = document.getElementById('asignaturaNuevaNota');

    if (cursoNuevaNota && asignaturaNuevaNota) {
        cursoNuevaNota.addEventListener('change', function () {
            cargarAsignaturasEnSelect(asignaturaNuevaNota, this.value);
            // Limpiar alumno
            const alumnoInput = document.getElementById('alumnoNuevaNota');
            const alumnoHidden = document.getElementById('alumnoNuevaNotaValue');
            if (alumnoInput) alumnoInput.value = '';
            if (alumnoHidden) alumnoHidden.value = '';
        });
    }

    // Ver Notas: filtroCursoVerNotas -> filtroAsignaturaVerNotas
    const filtroCursoVerNotas = document.getElementById('filtroCursoVerNotas');
    const filtroAsignaturaVerNotas = document.getElementById('filtroAsignaturaVerNotas');

    if (filtroCursoVerNotas && filtroAsignaturaVerNotas) {
        filtroCursoVerNotas.addEventListener('change', function () {
            cargarAsignaturasEnSelect(filtroAsignaturaVerNotas, this.value);
        });
    }

    // Progreso: filtroCursoProgreso -> filtroAsignaturaProgreso
    const filtroCursoProgreso = document.getElementById('filtroCursoProgreso');
    const filtroAsignaturaProgreso = document.getElementById('filtroAsignaturaProgreso');

    if (filtroCursoProgreso && filtroAsignaturaProgreso) {
        filtroCursoProgreso.addEventListener('change', function () {
            cargarAsignaturasEnSelect(filtroAsignaturaProgreso, this.value);
        });
    }

    // Comunicados: selectCursoComunicado -> selectAsignaturaComunicado (si existe)
    const selectCursoComunicado = document.getElementById('selectCursoComunicado');
    const selectAsignaturaComunicado = document.getElementById('selectAsignaturaComunicado');

    if (selectCursoComunicado && selectAsignaturaComunicado) {
        selectCursoComunicado.addEventListener('change', function () {
            cargarAsignaturasEnSelect(selectAsignaturaComunicado, this.value);
        });
    }
}

// ==================== AUTOCOMPLETADO DE ALUMNOS CON API ====================
async function buscarAlumnosAPI(cursoId, busqueda = '', asignaturaId = null) {
    try {
        const response = await fetch('api/obtener_alumnos_curso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id: cursoId || null,
                asignatura_id: asignaturaId || null,
                busqueda: busqueda
            })
        });

        const data = await response.json();
        if (data.success) {
            return data.data;
        }
        return [];
    } catch (error) {
        console.error('Error al buscar alumnos:', error);
        return [];
    }
}

function capitalizarPalabra(palabra) {
    if (!palabra) return '';
    return palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase();
}

// Capitaliza cada palabra de un texto (para nombres completos)
function capitalizarTexto(texto) {
    if (!texto) return '';
    return texto.split(' ')
        .filter(p => p.trim() !== '')
        .map(palabra => capitalizarPalabra(palabra))
        .join(' ');
}

// Formatea nombre en formato "Apellido, Nombre" o "Nombre Apellido"
function formatearNombreCompleto(nombreCompleto) {
    if (!nombreCompleto) return '';

    // Si viene en formato "APELLIDO, NOMBRE" o "Apellido, Nombre"
    if (nombreCompleto.includes(',')) {
        const partes = nombreCompleto.split(',');
        const apellidos = capitalizarTexto(partes[0].trim());
        const nombres = capitalizarTexto(partes[1]?.trim() || '');
        return `${apellidos}, ${nombres}`;
    }

    // Si viene como texto normal
    return capitalizarTexto(nombreCompleto);
}

// Formatea solo el primer nombre y apellidos (para tablas compactas)
function formatearNombreCorto(nombreCompleto) {
    if (!nombreCompleto) return '';

    // Si viene en formato "APELLIDO, NOMBRE"
    if (nombreCompleto.includes(',')) {
        const partes = nombreCompleto.split(',');
        const apellidos = capitalizarTexto(partes[0].trim());
        const nombres = partes[1]?.trim().split(' ') || [];
        const primerNombre = capitalizarPalabra(nombres[0] || '');
        return `${apellidos}, ${primerNombre}`;
    }

    // Si viene como texto normal, tomar primera palabra
    const palabras = nombreCompleto.split(' ').filter(p => p.trim() !== '');
    return palabras.map(p => capitalizarPalabra(p)).slice(0, 2).join(' ');
}

function formatearNombreAlumno(alumno) {
    // Obtener primer nombre y capitalizar
    const nombres = alumno.nombres.split(' ');
    const primerNombre = capitalizarPalabra(nombres[0]);

    // Obtener dos apellidos y capitalizar cada uno
    const apellidos = alumno.apellidos.split(' ');
    const dosApellidos = apellidos.slice(0, 2).map(ap => capitalizarPalabra(ap)).join(' ');

    return `${primerNombre} ${dosApellidos}`;
}

function mostrarDropdownAlumnos(dropdown, alumnos, busqueda, onSelect) {
    if (alumnos.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item-empty">No se encontraron alumnos</div>';
    } else {
        dropdown.innerHTML = alumnos.map(alumno => {
            const nombreFormateado = formatearNombreAlumno(alumno);
            return `<div class="autocomplete-item autocomplete-item-alumno" data-id="${alumno.id}" data-value="${nombreFormateado}">
                ${resaltarCoincidencias(nombreFormateado, busqueda)}
                ${alumno.curso ? `<small class="text-muted"> - ${alumno.curso}</small>` : ''}
            </div>`;
        }).join('');

        dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', function () {
                onSelect(this.dataset.id, this.dataset.value);
                dropdown.classList.remove('show');
            });
        });
    }
    dropdown.classList.add('show');
}

function resaltarCoincidencias(texto, busqueda) {
    if (!busqueda) return texto;
    const regex = new RegExp(`(${busqueda})`, 'gi');
    return texto.replace(regex, '<span class="autocomplete-highlight">$1</span>');
}

function initAutocompletadoCampoAPI(inputId, hiddenId, dropdownId, getCursoFn, getAsignaturaFn = null) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const dropdown = document.getElementById(dropdownId);

    if (!input || !dropdown) return;

    let timeoutId = null;

    input.addEventListener('focus', async function () {
        const cursoId = getCursoFn ? getCursoFn() : null;
        const asignaturaId = getAsignaturaFn ? getAsignaturaFn() : null;
        const alumnos = await buscarAlumnosAPI(cursoId, this.value, asignaturaId);
        mostrarDropdownAlumnos(dropdown, alumnos, this.value, (id, nombre) => {
            input.value = nombre;
            if (hidden) hidden.value = id;
        });
    });

    input.addEventListener('input', function () {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(async () => {
            const cursoId = getCursoFn ? getCursoFn() : null;
            const asignaturaId = getAsignaturaFn ? getAsignaturaFn() : null;
            const alumnos = await buscarAlumnosAPI(cursoId, this.value, asignaturaId);
            mostrarDropdownAlumnos(dropdown, alumnos, this.value, (id, nombre) => {
                input.value = nombre;
                if (hidden) hidden.value = id;
            });
        }, 300);
    });

    input.addEventListener('blur', function () {
        setTimeout(() => dropdown.classList.remove('show'), 200);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
    });
}

function initAutocompletados() {
    // Autocompletado en Agregar Nota (filtra por curso y asignatura)
    initAutocompletadoCampoAPI(
        'alumnoNuevaNota',
        'alumnoNuevaNotaValue',
        'dropdownAlumnoNuevaNota',
        () => document.getElementById('cursoNuevaNotaId')?.value || document.getElementById('cursoNuevaNota')?.value,
        () => document.getElementById('asignaturaNuevaNotaId')?.value || document.getElementById('asignaturaNuevaNota')?.value
    );

    // Autocompletado en Modificar Notas (filtra por curso y asignatura)
    initAutocompletadoCampoAPI(
        'buscarAlumno',
        'buscarAlumnoValue',
        'dropdownBuscarAlumno',
        () => document.getElementById('buscarCurso')?.value,
        () => document.getElementById('buscarAsignatura')?.value
    );

    // Autocompletado en Ver Notas (filtra por curso y asignatura)
    initAutocompletadoCampoAPI(
        'filtrarAlumnoVer',
        'filtrarAlumnoVerValue',
        'dropdownFiltrarAlumnoVer',
        () => document.getElementById('verCurso')?.value,
        () => document.getElementById('verAsignatura')?.value
    );
}

// ==================== AGREGAR NOTA ====================
async function agregarNota(e) {
    e.preventDefault();

    const cursoId = document.getElementById('cursoNuevaNotaId')?.value || document.getElementById('cursoNuevaNota').value;
    const asignaturaId = document.getElementById('asignaturaNuevaNotaId')?.value || document.getElementById('asignaturaNuevaNota').value;
    const alumnoId = document.getElementById('alumnoNuevaNotaValue').value;
    const trimestre = document.getElementById('trimestreNuevaNota').value;
    const nota = document.getElementById('notaNueva').value;
    const fecha = document.getElementById('fechaNuevaNota').value;
    const comentario = document.getElementById('comentarioNuevaNota').value;
    const esPendiente = document.getElementById('notaPendiente')?.checked || false;

    // Validaciones
    if (!cursoId || !asignaturaId || !alumnoId || !trimestre || !fecha) {
        alert('Por favor complete todos los campos requeridos');
        return;
    }

    // Si no es pendiente, validar nota
    if (!esPendiente) {
        if (!nota) {
            alert('Por favor ingrese una nota o marque como pendiente');
            return;
        }
        if (parseFloat(nota) < 1.0 || parseFloat(nota) > 7.0) {
            alert('La nota debe estar entre 1.0 y 7.0');
            return;
        }
    }

    try {
        const response = await fetch('api/agregar_nota_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                alumno_id: parseInt(alumnoId),
                curso_id: parseInt(cursoId),
                asignatura_id: parseInt(asignaturaId),
                nota: esPendiente ? null : parseFloat(nota),
                es_pendiente: esPendiente,
                trimestre: parseInt(trimestre),
                fecha_evaluacion: fecha,
                comentario: comentario,
                docente_id: DOCENTE_ID
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(esPendiente ? 'Nota pendiente registrada correctamente' : 'Nota registrada correctamente');
            limpiarFormularioNota();
            cargarUltimasNotas();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al agregar nota:', error);
        alert('Error de conexión al servidor');
    }
}

function limpiarFormularioNota() {
    // Limpiar selects y campos
    document.getElementById('cursoNuevaNota').value = '';
    document.getElementById('asignaturaNuevaNota').innerHTML = '<option value="">Primero seleccione un curso</option>';
    document.getElementById('alumnoNuevaNota').value = '';
    document.getElementById('alumnoNuevaNotaValue').value = '';
    document.getElementById('trimestreNuevaNota').value = '';
    document.getElementById('notaNueva').value = '';
    document.getElementById('fechaNuevaNota').value = new Date().toISOString().split('T')[0];
    document.getElementById('comentarioNuevaNota').value = '';

    // Resetear checkbox de nota pendiente
    const checkboxPendiente = document.getElementById('notaPendiente');
    if (checkboxPendiente) {
        checkboxPendiente.checked = false;
        toggleNotaPendiente();
    }
}

// ==================== NOTA PENDIENTE ====================
function toggleNotaPendiente() {
    const checkbox = document.getElementById('notaPendiente');
    const inputNota = document.getElementById('notaNueva');

    if (checkbox.checked) {
        // Marcar como pendiente: mostrar PEND y deshabilitar campo
        inputNota.type = 'text';
        inputNota.value = 'PEND';
        inputNota.disabled = true;
        inputNota.removeAttribute('required');
        inputNota.classList.add('nota-pendiente');
    } else {
        // Desmarcar: restaurar campo numérico
        inputNota.type = 'number';
        inputNota.value = '';
        inputNota.disabled = false;
        inputNota.setAttribute('required', 'required');
        inputNota.classList.remove('nota-pendiente');
    }
}

// ==================== ÚLTIMAS NOTAS ====================
function ajustarAlturaColumnaDerecha() {
    const columnaIzquierda = document.getElementById('subtab-registrar-nota');
    const columnaDerecha = document.getElementById('subtab-ultimas-notas');

    if (!columnaIzquierda || !columnaDerecha) return;

    // Establecer la altura de la columna derecha igual a la izquierda
    const altura = columnaIzquierda.offsetHeight;
    columnaDerecha.style.setProperty('--altura-columna-izquierda', altura + 'px');
}

async function cargarUltimasNotas() {
    try {
        // Ajustar altura antes de cargar
        ajustarAlturaColumnaDerecha();

        const response = await fetch('api/obtener_ultimas_notas_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                docente_id: DOCENTE_ID,
                limite: 50,
                solo_hoy: false
            })
        });

        const data = await response.json();

        if (data.success && data.data.length > 0) {
            // Guardar datos para filtrado
            datosUltimasNotas = data.data;
            mostrarUltimasNotasFiltradas(datosUltimasNotas);
        } else {
            datosUltimasNotas = [];
            mostrarUltimasNotasFiltradas([]);
        }
    } catch (error) {
        console.error('Error al cargar últimas notas:', error);
    }
}

function mostrarUltimasNotasFiltradas(notas) {
    const tbody = document.getElementById('tablaUltimasNotas');
    const contador = document.getElementById('contadorUltimasNotas');

    if (!tbody) return;

    if (notas.length > 0) {
        tbody.innerHTML = notas.map(nota => {
            // Usar fecha formateada si existe, sino formatear desde fecha_evaluacion
            let fechaMostrar = nota.fecha_evaluacion_formato || nota.fecha_evaluacion || '-';
            if (fechaMostrar !== '-' && fechaMostrar.includes('-')) {
                // Formato Y-m-d a d/m/Y
                const partes = fechaMostrar.split('-');
                if (partes.length === 3) {
                    fechaMostrar = `${partes[2]}/${partes[1]}/${partes[0]}`;
                }
            }

            // Manejar nota pendiente
            let notaMostrar, claseNota;
            if (nota.es_pendiente) {
                notaMostrar = 'PEND';
                claseNota = 'nota-pendiente-tabla';
            } else {
                notaMostrar = typeof nota.nota === 'number' ? nota.nota.toFixed(1) : nota.nota;
                claseNota = getClaseNota(nota.nota);
            }

            return `
            <tr>
                <td class="dato-servidor">${fechaMostrar}</td>
                <td class="dato-servidor" title="${formatearNombreCompleto(nota.alumno)}">${formatearNombreCorto(nota.alumno)}</td>
                <td class="dato-servidor col-curso-hide">${nota.curso}</td>
                <td class="dato-servidor">${nota.asignatura_corta}</td>
                <td class="dato-servidor ${claseNota}">${notaMostrar}</td>
            </tr>
        `}).join('');

        if (contador) contador.textContent = `${notas.length} registros`;
    } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay notas que coincidan</td></tr>';
        if (contador) contador.textContent = '0 registros';
    }
}

function filtrarUltimasNotas() {
    const filtroCurso = document.getElementById('filtroUltCurso')?.value || '';
    const filtroAlumno = document.getElementById('filtroUltAlumno')?.value.toLowerCase().trim() || '';
    const filtroAsignatura = document.getElementById('filtroUltAsignatura')?.value || '';
    const filtroFecha = document.getElementById('filtroUltFecha')?.value || '';

    let notasFiltradas = datosUltimasNotas.filter(nota => {
        // Filtrar por curso (usando curso_id)
        if (filtroCurso && nota.curso_id != filtroCurso) {
            return false;
        }

        // Filtrar por alumno (buscar en nombre)
        if (filtroAlumno) {
            const nombreAlumno = (nota.alumno || '').toLowerCase();
            if (!nombreAlumno.includes(filtroAlumno)) {
                return false;
            }
        }

        // Filtrar por asignatura (usando asignatura_id)
        if (filtroAsignatura && nota.asignatura_id != filtroAsignatura) {
            return false;
        }

        // Filtrar por fecha (formato Y-m-d)
        if (filtroFecha && nota.fecha_evaluacion !== filtroFecha) {
            return false;
        }

        return true;
    });

    mostrarUltimasNotasFiltradas(notasFiltradas);
}

// ==================== BUSCAR Y MODIFICAR NOTAS ====================
async function buscarNotas() {
    const cursoId = document.getElementById('buscarCurso')?.value || '';
    const asignaturaId = document.getElementById('buscarAsignatura')?.value || '';

    // Obtener ID de alumno si hay texto en el input
    const alumnoInput = document.getElementById('buscarAlumno');
    const alumnoIdInput = document.getElementById('buscarAlumnoValue');
    let alumnoId = null;

    if (alumnoInput && alumnoInput.value.trim() !== '' && alumnoIdInput && alumnoIdInput.value) {
        alumnoId = parseInt(alumnoIdInput.value);
    }

    const fecha = document.getElementById('buscarFecha')?.value || '';

    try {
        const response = await fetch('api/obtener_notas_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                docente_id: DOCENTE_ID,
                curso_id: cursoId ? parseInt(cursoId) : null,
                asignatura_id: asignaturaId ? parseInt(asignaturaId) : null,
                alumno_id: alumnoId,
                alumno_busqueda: (!alumnoId && alumnoInput) ? alumnoInput.value.trim() : null,
                fecha: fecha || null
            })
        });

        const data = await response.json();
        mostrarResultadosBusqueda(data.success ? data.data : []);
    } catch (error) {
        console.error('Error al buscar notas:', error);
        alert('Error de conexión al servidor');
    }
}

function mostrarResultadosBusqueda(resultados) {
    const tbody = document.getElementById('tablaResultadosBusqueda');
    const contador = document.getElementById('contadorResultados');

    if (!tbody) return;

    if (resultados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron resultados</td></tr>';
        if (contador) contador.textContent = '0 notas';
        return;
    }

    tbody.innerHTML = resultados.map(nota => {
        const nombreFormateado = formatearNombreCompleto(nota.alumno_nombre);
        const nombreCorto = formatearNombreCorto(nota.alumno_nombre);
        const esPendiente = nota.es_pendiente || nota.nota === 'PEND';
        const notaMostrar = esPendiente ? 'PEND' : (typeof nota.nota === 'number' ? nota.nota.toFixed(1) : nota.nota);
        const claseNota = esPendiente ? 'nota-pendiente-tabla' : getClaseNota(nota.nota);
        const notaParaEditar = esPendiente ? 'null' : nota.nota;
        return `
        <tr>
            <td class="dato-servidor">${formatearFecha(nota.fecha_evaluacion)}</td>
            <td class="dato-servidor" title="${nombreFormateado}">${nombreCorto}</td>
            <td class="dato-servidor">${nota.asignatura_nombre}</td>
            <td class="dato-servidor ${claseNota}">${notaMostrar}</td>
            <td class="dato-servidor">T${nota.trimestre}</td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon btn-icon-edit" onclick="abrirModalEditar(${nota.id}, '${nombreFormateado}', '${nota.curso_nombre}', '${nota.asignatura_nombre}', ${notaParaEditar}, ${nota.trimestre}, '${nota.fecha_evaluacion || ''}', '${(nota.comentario || '').replace(/'/g, "\\'")}', ${esPendiente})" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon btn-icon-delete" onclick="abrirModalEliminar(${nota.id})" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('');

    if (contador) contador.textContent = `${resultados.length} notas`;
}

function limpiarBusqueda() {
    document.getElementById('buscarCurso').value = '';
    document.getElementById('buscarAsignatura').value = '';
    document.getElementById('buscarAlumno').value = '';
    document.getElementById('buscarFecha').value = '';

    const tbody = document.getElementById('tablaResultadosBusqueda');
    const contador = document.getElementById('contadorResultados');

    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Realice una búsqueda</td></tr>';
    }
    if (contador) {
        contador.textContent = '0 notas';
    }
}

// ==================== MODAL EDITAR ====================
function abrirModalEditar(id, alumno, curso, asignatura, nota, trimestre, fecha, comentario, esPendiente = false) {
    document.getElementById('editarNotaId').value = id;
    document.getElementById('editarAlumno').value = alumno;
    document.getElementById('editarCurso').value = curso;
    document.getElementById('editarAsignatura').value = asignatura;

    const inputNota = document.getElementById('editarNota');
    if (esPendiente) {
        inputNota.value = '';
        inputNota.placeholder = 'Era PEND - Ingrese nota';
    } else {
        inputNota.value = nota;
        inputNota.placeholder = 'Ej: 6.5';
    }

    document.getElementById('editarTrimestre').value = trimestre;
    document.getElementById('editarFecha').value = fecha || '';
    document.getElementById('editarComentario').value = comentario || '';

    document.getElementById('modalEditarNota').classList.add('active');
}

function cerrarModalEditar(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('modalEditarNota').classList.remove('active');
}

async function guardarEdicionNota(e) {
    e.preventDefault();

    const notaId = document.getElementById('editarNotaId').value;
    const nota = document.getElementById('editarNota').value;
    const trimestre = document.getElementById('editarTrimestre').value;
    const fecha = document.getElementById('editarFecha').value;
    const comentario = document.getElementById('editarComentario').value;

    try {
        const response = await fetch('api/editar_nota_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nota_id: parseInt(notaId),
                nota: parseFloat(nota),
                trimestre: parseInt(trimestre),
                fecha_evaluacion: fecha,
                comentario: comentario,
                docente_id: DOCENTE_ID
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Nota actualizada correctamente');
            cerrarModalEditar();
            buscarNotas();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al editar nota:', error);
        alert('Error de conexión al servidor');
    }
}

// ==================== MODAL ELIMINAR ====================
let notaIdAEliminar = null;

function abrirModalEliminar(id) {
    notaIdAEliminar = id;
    document.getElementById('eliminarNotaId').value = id;
    document.getElementById('modalConfirmarEliminar').classList.add('active');
}

function cerrarModalEliminar(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('modalConfirmarEliminar').classList.remove('active');
    notaIdAEliminar = null;
}

async function confirmarEliminarNota() {
    if (!notaIdAEliminar) return;

    try {
        const response = await fetch('api/eliminar_nota_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nota_id: notaIdAEliminar,
                docente_id: DOCENTE_ID
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Nota eliminada correctamente');
            cerrarModalEliminar();
            buscarNotas();
            cargarUltimasNotas();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al eliminar nota:', error);
        alert('Error de conexión al servidor');
    }
}

// ==================== VER NOTAS DEL CURSO ====================
async function cargarNotasCurso() {
    const cursoId = document.getElementById('verCurso')?.value;
    const asignaturaId = document.getElementById('verAsignatura')?.value;

    if (!cursoId || !asignaturaId) {
        alert('Por favor seleccione un curso y una asignatura');
        return;
    }

    // Obtener filtros de alumno
    const alumnoInput = document.getElementById('filtrarAlumnoVer');
    const alumnoIdInput = document.getElementById('filtrarAlumnoVerValue');
    let alumnoId = null;
    let alumnoBusqueda = null;

    if (alumnoInput && alumnoInput.value.trim() !== '') {
        if (alumnoIdInput && alumnoIdInput.value) {
            alumnoId = parseInt(alumnoIdInput.value);
        } else {
            alumnoBusqueda = alumnoInput.value.trim();
        }
    }

    const notaMin = document.getElementById('filtrarNotaMin')?.value || null;
    const notaMax = document.getElementById('filtrarNotaMax')?.value || null;

    try {
        const response = await fetch('api/obtener_notas_curso_completo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id: parseInt(cursoId),
                asignatura_id: parseInt(asignaturaId),
                docente_id: DOCENTE_ID,
                alumno_id: alumnoId,
                alumno_busqueda: alumnoBusqueda,
                nota_min: notaMin ? parseFloat(notaMin) : null,
                nota_max: notaMax ? parseFloat(notaMax) : null
            })
        });

        const data = await response.json();
        mostrarTablaNotasCurso(data.success ? data.data : []);
    } catch (error) {
        console.error('Error al cargar notas del curso:', error);
        alert('Error de conexión al servidor');
    }
}

function mostrarTablaNotasCurso(alumnos) {
    const tbody = document.getElementById('tablaNotasCurso');
    const contador = document.getElementById('contadorNotasCurso');

    if (!tbody) return;

    // Guardar datos para exportación
    datosNotasCurso = alumnos;

    // Obtener nombres de curso y asignatura para el título del reporte
    const selectCurso = document.getElementById('verCurso');
    const selectAsignatura = document.getElementById('verAsignatura');
    infoCursoActual.curso = selectCurso?.options[selectCurso.selectedIndex]?.text || '';
    infoCursoActual.asignatura = selectAsignatura?.options[selectAsignatura.selectedIndex]?.text || '';

    if (alumnos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="31" class="text-center text-muted">No hay alumnos en este curso</td></tr>';
        if (contador) contador.textContent = '0 registros';
        datosNotasCurso = [];
        return;
    }

    tbody.innerHTML = alumnos.map((alumno, index) => {
        const apellidosFormateados = capitalizarTexto(alumno.apellidos);
        const primerNombre = capitalizarPalabra(alumno.nombres.split(' ')[0]);
        const nombreCompletoFormateado = `${apellidosFormateados}, ${capitalizarTexto(alumno.nombres)}`;

        let celdas = `<td class="td-numero dato-servidor">${index + 1}</td>`;
        celdas += `<td class="td-alumno dato-servidor" title="${nombreCompletoFormateado}">${apellidosFormateados}, ${primerNombre}</td>`;

        // Función helper para formatear nota (incluyendo PEND)
        const formatearNotaCelda = (nota) => {
            if (nota === null || nota === undefined) return { texto: '-', clase: '' };
            if (nota === 'PEND') return { texto: 'PEND', clase: 'nota-pendiente-tabla' };
            return { texto: nota.toFixed(1), clase: getClaseNota(nota) };
        };

        // Trimestre 1 (8 notas + promedio)
        for (let i = 1; i <= 8; i++) {
            const nota = alumno.trimestre_1[i];
            const { texto, clase } = formatearNotaCelda(nota);
            celdas += `<td class="td-nota dato-servidor ${clase}">${texto}</td>`;
        }
        celdas += `<td class="td-prom dato-servidor ${alumno.promedio_t1 ? getClaseNota(alumno.promedio_t1) : ''}">${alumno.promedio_t1 ? alumno.promedio_t1.toFixed(1) : '-'}</td>`;

        // Trimestre 2 (8 notas + promedio)
        for (let i = 1; i <= 8; i++) {
            const nota = alumno.trimestre_2[i];
            const { texto, clase } = formatearNotaCelda(nota);
            celdas += `<td class="td-nota dato-servidor ${clase}">${texto}</td>`;
        }
        celdas += `<td class="td-prom dato-servidor ${alumno.promedio_t2 ? getClaseNota(alumno.promedio_t2) : ''}">${alumno.promedio_t2 ? alumno.promedio_t2.toFixed(1) : '-'}</td>`;

        // Trimestre 3 (8 notas + promedio)
        for (let i = 1; i <= 8; i++) {
            const nota = alumno.trimestre_3[i];
            const { texto, clase } = formatearNotaCelda(nota);
            celdas += `<td class="td-nota dato-servidor ${clase}">${texto}</td>`;
        }
        celdas += `<td class="td-prom dato-servidor ${alumno.promedio_t3 ? getClaseNota(alumno.promedio_t3) : ''}">${alumno.promedio_t3 ? alumno.promedio_t3.toFixed(1) : '-'}</td>`;

        // Promedio final y estado
        celdas += `<td class="td-final dato-servidor ${alumno.promedio_final ? getClaseNota(alumno.promedio_final) : ''}">${alumno.promedio_final ? alumno.promedio_final.toFixed(1) : '-'}</td>`;

        let estadoClase = 'estado-sinnotas';
        if (alumno.estado === 'Aprobado') estadoClase = 'estado-aprobado';
        else if (alumno.estado === 'Reprobado') estadoClase = 'estado-reprobado';
        else if (alumno.estado === 'En curso') estadoClase = 'estado-encurso';

        celdas += `<td class="td-estado dato-servidor"><span class="${estadoClase}">${alumno.estado}</span></td>`;

        return `<tr>${celdas}</tr>`;
    }).join('');

    if (contador) contador.textContent = `${alumnos.length} alumnos`;
}

function limpiarFiltrosVer() {
    document.getElementById('verCurso').value = '';
    document.getElementById('verAsignatura').value = '';
    document.getElementById('filtrarAlumnoVer').value = '';
    document.getElementById('filtrarNotaMin').value = '';
    document.getElementById('filtrarNotaMax').value = '';

    const tbody = document.getElementById('tablaNotasCurso');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="31" class="text-center text-muted">Seleccione curso y asignatura</td></tr>';
    }
}

// ==================== ANÁLISIS DE PROGRESO ====================
async function analizarProgreso() {
    const cursoId = document.getElementById('progresoCurso')?.value;
    const asignaturaId = document.getElementById('progresoAsignatura')?.value;
    const trimestre = document.getElementById('progresoTrimestre')?.value || null;

    if (!cursoId || !asignaturaId) {
        alert('Por favor seleccione un curso y una asignatura');
        return;
    }

    try {
        const response = await fetch('api/obtener_estadisticas_docente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                curso_id: parseInt(cursoId),
                asignatura_id: parseInt(asignaturaId),
                docente_id: DOCENTE_ID,
                trimestre: trimestre ? parseInt(trimestre) : null
            })
        });

        const data = await response.json();

        if (data.success) {
            actualizarKPIs(data.data);
            actualizarGraficos(data.data);
            mostrarAlumnosAtencion(data.data.alumnos_atencion || []);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al analizar progreso:', error);
        alert('Error de conexión al servidor');
    }
}

function actualizarKPIs(stats) {
    document.getElementById('kpiTotalAlumnos').textContent = stats.total_alumnos || '--';
    document.getElementById('kpiAprobados').textContent = stats.aprobados || '--';
    document.getElementById('kpiReprobados').textContent = stats.reprobados || '--';
    document.getElementById('kpiPromedioCurso').textContent = stats.promedio_curso || '--';
    document.getElementById('kpiNotaMaxima').textContent = stats.nota_maxima || '--';
    document.getElementById('kpiNotaMinima').textContent = stats.nota_minima || '--';
    document.getElementById('kpiPorcentajeAprobados').textContent = (stats.porcentaje_aprobados || 0) + '%';
    document.getElementById('kpiPorcentajeReprobados').textContent = (stats.porcentaje_reprobados || 0) + '%';
}

function actualizarGraficos(stats) {
    // Destruir gráficos existentes
    if (chartDistribucion) chartDistribucion.destroy();
    if (chartTrimestre) chartTrimestre.destroy();
    if (chartAprobacion) chartAprobacion.destroy();
    if (chartTop5) chartTop5.destroy();

    const corporativo = {
        primario: '#1a365d',
        acento: '#2b6cb0',
        exito: '#276749',
        peligro: '#9b2c2c',
        advertencia: '#975a16',
        info: '#2c5282',
        lineas: '#e2e8f0',
        texto: '#2d3748',
        textoClaro: '#718096'
    };

    // Gráfico de distribución
    const dist = stats.distribucion || {};
    const canvasDistribucion = document.getElementById('chartDistribucion');
    if (canvasDistribucion) {
        chartDistribucion = new Chart(canvasDistribucion, {
            type: 'bar',
            data: {
                labels: ['Excelente (6+)', 'Bueno (5-5.9)', 'Suficiente (4-4.9)', 'Insuficiente (<4)'],
                datasets: [{
                    label: 'Alumnos',
                    data: [dist.excelente || 0, dist.bueno || 0, dist.suficiente || 0, dist.insuficiente || 0],
                    backgroundColor: [corporativo.exito, corporativo.acento, corporativo.advertencia, corporativo.peligro],
                    borderRadius: 4,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: corporativo.lineas } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Gráfico de rendimiento por trimestre (con meses y marcadores)
    const trim = stats.rendimiento_trimestral || {};
    const canvasTrimestre = document.getElementById('chartTrimestre');
    if (canvasTrimestre) {
        // Nombres de meses del año escolar (Marzo a Noviembre)
        const mesesLabels = ['Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov'];

        // Distribuir datos trimestrales en meses
        const dataMensual = [
            trim[1] || null, trim[1] || null, trim[1] || null,  // T1: Mar, Abr, May
            trim[2] || null, trim[2] || null, trim[2] || null,  // T2: Jun, Jul, Ago
            trim[3] || null, trim[3] || null, trim[3] || null   // T3: Sep, Oct, Nov
        ];

        // Plugin personalizado para dibujar líneas verticales de trimestre
        const trimestrePluginDocente = {
            id: 'trimestreLinesDocente',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                const xAxis = chart.scales.x;
                const yAxis = chart.scales.y;

                // Posiciones de fin de trimestre: May=2, Ago=5, Nov=8
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
                    ctx.fillStyle = corporativo.acento;
                    ctx.font = 'bold 10px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(t.label, x, yAxis.top - 8);
                    ctx.restore();
                });
            }
        };

        chartTrimestre = new Chart(canvasTrimestre, {
            type: 'line',
            data: {
                labels: mesesLabels,
                datasets: [{
                    label: 'Promedio',
                    data: dataMensual,
                    borderColor: corporativo.acento,
                    backgroundColor: 'rgba(43, 108, 176, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: corporativo.acento,
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
                            title: function (context) {
                                const mesesCompletos = ['Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre'];
                                return mesesCompletos[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 1,
                        max: 7,
                        grid: { color: corporativo.lineas },
                        ticks: { stepSize: 1 }
                    },
                    x: { grid: { display: false } }
                },
                layout: {
                    padding: { top: 20 }
                }
            },
            plugins: [trimestrePluginDocente]
        });
    }

    // Gráfico de aprobación
    const canvasAprobacion = document.getElementById('chartAprobacion');
    if (canvasAprobacion) {
        const aprobados = stats.aprobados || 0;
        const reprobados = stats.reprobados || 0;

        chartAprobacion = new Chart(canvasAprobacion, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Reprobados'],
                datasets: [{
                    data: [aprobados, reprobados],
                    backgroundColor: [corporativo.exito, corporativo.peligro],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Gráfico Top 5
    const top5 = stats.top5 || [];
    const canvasTop5 = document.getElementById('chartTop5');
    if (canvasTop5 && top5.length > 0) {
        chartTop5 = new Chart(canvasTop5, {
            type: 'bar',
            data: {
                labels: top5.map(a => formatearNombreCorto(a.nombre)),
                datasets: [{
                    label: 'Promedio',
                    data: top5.map(a => a.promedio),
                    backgroundColor: corporativo.acento,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { min: 1, max: 7, grid: { color: corporativo.lineas } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
}

function mostrarAlumnosAtencion(alumnos) {
    const tbody = document.getElementById('tablaAtencion');
    if (!tbody) return;

    if (alumnos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay alumnos que requieran atención especial</td></tr>';
        return;
    }

    tbody.innerHTML = alumnos.map(a => `
        <tr>
            <td class="dato-servidor">${formatearNombreCorto(a.nombre)}</td>
            <td class="dato-servidor ${getClaseNota(a.promedio)}">${a.promedio.toFixed(1)}</td>
            <td class="dato-servidor">${a.notas_rojas}</td>
            <td class="dato-servidor"><span class="tendencia-${a.tendencia}">${a.tendencia === 'bajando' ? '↓' : a.tendencia === 'subiendo' ? '↑' : '→'}</span></td>
            <td class="dato-servidor">${a.observacion || '-'}</td>
        </tr>
    `).join('');
}

// ==================== EXPORTAR ====================
function exportarNotasCurso(formato) {
    if (datosNotasCurso.length === 0) {
        alert('No hay datos para exportar. Primero cargue las notas de un curso.');
        return;
    }

    if (formato === 'excel') {
        exportarNotasExcel();
    } else if (formato === 'pdf') {
        exportarNotasPDF();
    }
}

function exportarNotasExcel() {
    // Preparar datos para Excel
    const datosExcel = [];

    // Fila de encabezados principal
    const encabezado1 = ['N°', 'Alumno'];
    for (let t = 1; t <= 3; t++) {
        encabezado1.push(`Trimestre ${t}`, '', '', '', '', '', '', '', '');
    }
    encabezado1.push('Prom. Final', 'Estado');

    // Fila de sub-encabezados
    const encabezado2 = ['', ''];
    for (let t = 1; t <= 3; t++) {
        encabezado2.push('N1', 'N2', 'N3', 'N4', 'N5', 'N6', 'N7', 'N8', 'Prom');
    }
    encabezado2.push('', '');

    datosExcel.push(encabezado1);
    datosExcel.push(encabezado2);

    // Datos de alumnos
    datosNotasCurso.forEach((alumno, index) => {
        const fila = [
            index + 1,
            `${capitalizarTexto(alumno.apellidos)}, ${capitalizarTexto(alumno.nombres)}`
        ];

        // Trimestre 1
        for (let i = 1; i <= 8; i++) {
            fila.push(alumno.trimestre_1[i] ? alumno.trimestre_1[i].toFixed(1) : '-');
        }
        fila.push(alumno.promedio_t1 ? alumno.promedio_t1.toFixed(1) : '-');

        // Trimestre 2
        for (let i = 1; i <= 8; i++) {
            fila.push(alumno.trimestre_2[i] ? alumno.trimestre_2[i].toFixed(1) : '-');
        }
        fila.push(alumno.promedio_t2 ? alumno.promedio_t2.toFixed(1) : '-');

        // Trimestre 3
        for (let i = 1; i <= 8; i++) {
            fila.push(alumno.trimestre_3[i] ? alumno.trimestre_3[i].toFixed(1) : '-');
        }
        fila.push(alumno.promedio_t3 ? alumno.promedio_t3.toFixed(1) : '-');

        // Final y estado
        fila.push(alumno.promedio_final ? alumno.promedio_final.toFixed(1) : '-');
        fila.push(alumno.estado);

        datosExcel.push(fila);
    });

    // Crear libro de Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(datosExcel);

    // Ajustar anchos de columna
    ws['!cols'] = [
        { wch: 4 },   // N°
        { wch: 30 },  // Alumno
        ...Array(27).fill({ wch: 5 }), // Notas
        { wch: 10 },  // Prom Final
        { wch: 12 }   // Estado
    ];

    // Combinar celdas de encabezados de trimestre
    ws['!merges'] = [
        { s: { r: 0, c: 2 }, e: { r: 0, c: 10 } },   // Trimestre 1
        { s: { r: 0, c: 11 }, e: { r: 0, c: 19 } },  // Trimestre 2
        { s: { r: 0, c: 20 }, e: { r: 0, c: 28 } },  // Trimestre 3
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Notas');

    // Generar nombre del archivo
    const nombreArchivo = `Notas_${infoCursoActual.curso}_${infoCursoActual.asignatura}_${new Date().toISOString().split('T')[0]}.xlsx`;

    // Descargar
    XLSX.writeFile(wb, nombreArchivo.replace(/\s+/g, '_'));
}

function exportarNotasPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape', 'mm', 'a4');

    // Título
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text(`Reporte de Calificaciones`, 14, 15);

    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(`Curso: ${infoCursoActual.curso}`, 14, 22);
    doc.text(`Asignatura: ${infoCursoActual.asignatura}`, 14, 28);
    doc.text(`Fecha: ${new Date().toLocaleDateString('es-CL')}`, 14, 34);

    // Preparar datos para la tabla
    const encabezados = [['N°', 'Alumno', 'T1', 'T2', 'T3', 'Final', 'Estado']];

    const datos = datosNotasCurso.map((alumno, index) => [
        index + 1,
        `${capitalizarTexto(alumno.apellidos)}, ${capitalizarTexto(alumno.nombres)}`,
        alumno.promedio_t1 ? alumno.promedio_t1.toFixed(1) : '-',
        alumno.promedio_t2 ? alumno.promedio_t2.toFixed(1) : '-',
        alumno.promedio_t3 ? alumno.promedio_t3.toFixed(1) : '-',
        alumno.promedio_final ? alumno.promedio_final.toFixed(1) : '-',
        alumno.estado
    ]);

    // Generar tabla
    doc.autoTable({
        head: encabezados,
        body: datos,
        startY: 40,
        theme: 'grid',
        styles: {
            fontSize: 9,
            cellPadding: 2
        },
        headStyles: {
            fillColor: [45, 90, 135],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { cellWidth: 60 },
            2: { halign: 'center', cellWidth: 15 },
            3: { halign: 'center', cellWidth: 15 },
            4: { halign: 'center', cellWidth: 15 },
            5: { halign: 'center', cellWidth: 15 },
            6: { halign: 'center', cellWidth: 20 }
        },
        didParseCell: function (data) {
            // Colorear según estado
            if (data.column.index === 6 && data.section === 'body') {
                if (data.cell.raw === 'Aprobado') {
                    data.cell.styles.textColor = [39, 103, 73];
                    data.cell.styles.fontStyle = 'bold';
                } else if (data.cell.raw === 'Reprobado') {
                    data.cell.styles.textColor = [155, 44, 44];
                    data.cell.styles.fontStyle = 'bold';
                }
            }
            // Colorear notas bajo 4.0
            if (data.column.index >= 2 && data.column.index <= 5 && data.section === 'body') {
                const valor = parseFloat(data.cell.raw);
                if (!isNaN(valor) && valor < 4.0) {
                    data.cell.styles.textColor = [155, 44, 44];
                }
            }
        }
    });

    // Pie de página
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(128);
        doc.text(`Página ${i} de ${pageCount}`, doc.internal.pageSize.width - 25, doc.internal.pageSize.height - 10);
    }

    // Generar nombre del archivo
    const nombreArchivo = `Notas_${infoCursoActual.curso}_${infoCursoActual.asignatura}_${new Date().toISOString().split('T')[0]}.pdf`;

    // Descargar
    doc.save(nombreArchivo.replace(/\s+/g, '_'));
}

function exportarAlumnosAtencion() {
    const tbody = document.getElementById('tablaAtencion');
    if (!tbody || tbody.querySelector('.text-muted')) {
        alert('No hay datos para exportar.');
        return;
    }

    // Obtener datos de la tabla
    const filas = tbody.querySelectorAll('tr');
    const datos = [];

    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length >= 5) {
            datos.push([
                celdas[0].textContent.trim(),
                celdas[1].textContent.trim(),
                celdas[2].textContent.trim(),
                celdas[3].textContent.trim(),
                celdas[4].textContent.trim()
            ]);
        }
    });

    if (datos.length === 0) {
        alert('No hay datos para exportar.');
        return;
    }

    // Crear Excel
    const datosExcel = [['Alumno', 'Promedio', 'Notas Rojas', 'Tendencia', 'Observación'], ...datos];
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(datosExcel);

    ws['!cols'] = [
        { wch: 30 },
        { wch: 10 },
        { wch: 12 },
        { wch: 12 },
        { wch: 30 }
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Alumnos Atención');
    XLSX.writeFile(wb, `Alumnos_Atencion_${new Date().toISOString().split('T')[0]}.xlsx`);
}

function descargarGrafico(canvasId, nombre) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
        const link = document.createElement('a');
        link.download = `grafico_${nombre}.png`;
        link.href = canvas.toDataURL();
        link.click();
    }
}

// ==================== SUB-PESTAÑAS (Responsive) ====================
function initSubTabs() {
    const subTabButtons = document.querySelectorAll('.sub-tab-btn');

    subTabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const subTabId = this.getAttribute('data-subtab');

            subTabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            showSubTab(subTabId);
        });
    });
}

function showSubTab(subTabId) {
    const registrarNota = document.getElementById('subtab-registrar-nota');
    const ultimasNotas = document.getElementById('subtab-ultimas-notas');

    if (subTabId === 'registrar-nota') {
        registrarNota.classList.remove('hidden');
        ultimasNotas.classList.remove('active');
    } else if (subTabId === 'ultimas-notas') {
        registrarNota.classList.add('hidden');
        ultimasNotas.classList.add('active');
    }
}

// ==================== NAVEGACIÓN POR PESTAÑAS ====================
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const tabId = this.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            showTab(tabId);
            closeMobileMenu();
        });
    });
}

function showTab(tabId) {
    document.querySelectorAll('.tab-panel').forEach(tab => {
        tab.classList.remove('active');
    });

    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
}

// ==================== MENÚ MÓVIL ====================
function initMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const tabsNav = document.querySelector('.tabs-nav');
    const panelHeader = document.querySelector('.panel-header');

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            this.classList.toggle('active');
            tabsNav.classList.toggle('open');
            panelHeader.classList.toggle('menu-open');
        });
    }
}

function closeMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const tabsNav = document.querySelector('.tabs-nav');
    const panelHeader = document.querySelector('.panel-header');

    if (menuToggle) menuToggle.classList.remove('active');
    if (tabsNav) tabsNav.classList.remove('open');
    if (panelHeader) panelHeader.classList.remove('menu-open');
}

// ==================== FORMULARIOS ====================
function initFormularios() {
    // Formulario agregar nota
    const formAgregarNota = document.getElementById('formAgregarNota');
    if (formAgregarNota) {
        formAgregarNota.addEventListener('submit', agregarNota);
    }

    // Formulario editar nota
    const formEditarNota = document.getElementById('formEditarNota');
    if (formEditarNota) {
        formEditarNota.addEventListener('submit', guardarEdicionNota);
    }

    // Limpieza en cascada para Agregar Nota y filtrado de asignaturas por curso
    const cursoNuevaNota = document.getElementById('cursoNuevaNota');
    if (cursoNuevaNota) {
        cursoNuevaNota.addEventListener('change', function () {
            document.getElementById('alumnoNuevaNota').value = '';
            document.getElementById('alumnoNuevaNotaValue').value = '';
            filtrarAsignaturasPorCurso('cursoNuevaNota', 'asignaturaNuevaNota');
        });
    }

    // Filtrado de asignaturas por curso en Ver Notas
    const verCurso = document.getElementById('verCurso');
    if (verCurso) {
        verCurso.addEventListener('change', function () {
            filtrarAsignaturasPorCurso('verCurso', 'verAsignatura');
        });
    }

    // Filtrado de asignaturas por curso en Progreso
    const progresoCurso = document.getElementById('progresoCurso');
    if (progresoCurso) {
        progresoCurso.addEventListener('change', function () {
            filtrarAsignaturasPorCurso('progresoCurso', 'progresoAsignatura');
        });
    }

    // Filtrado de asignaturas por curso en Buscar Notas (Modificar)
    const buscarCurso = document.getElementById('buscarCurso');
    if (buscarCurso) {
        buscarCurso.addEventListener('change', function () {
            filtrarAsignaturasPorCursoConTodos('buscarCurso', 'buscarAsignatura');
        });
    }

    // Filtrado de asignaturas por curso en Últimas Notas
    const filtroUltCurso = document.getElementById('filtroUltCurso');
    if (filtroUltCurso) {
        filtroUltCurso.addEventListener('change', function () {
            filtrarAsignaturasPorCursoConTodos('filtroUltCurso', 'filtroUltAsignatura');
        });
    }
}

// ==================== UTILIDADES ====================

// Filtrar asignaturas según el curso seleccionado usando ASIGNACIONES_DOCENTE
function filtrarAsignaturasPorCurso(cursoSelectId, asignaturaSelectId) {
    const cursoSelect = document.getElementById(cursoSelectId);
    const asignaturaSelect = document.getElementById(asignaturaSelectId);

    if (!cursoSelect || !asignaturaSelect) return;

    const cursoId = parseInt(cursoSelect.value);

    // Guardar el valor actual seleccionado (si existe)
    const valorActual = asignaturaSelect.value;

    // Limpiar opciones excepto la primera (placeholder)
    while (asignaturaSelect.options.length > 1) {
        asignaturaSelect.remove(1);
    }

    // Actualizar placeholder
    asignaturaSelect.options[0].textContent = cursoId ? 'Seleccionar asignatura' : 'Primero seleccione un curso';

    // Resetear a placeholder
    asignaturaSelect.value = '';

    if (!cursoId || !ASIGNACIONES_DOCENTE) return;

    // Filtrar asignaciones por curso seleccionado
    const asignaturasFiltradas = ASIGNACIONES_DOCENTE.filter(a => parseInt(a.curso_id) === cursoId);

    // Agregar las asignaturas filtradas
    asignaturasFiltradas.forEach(asig => {
        const option = document.createElement('option');
        option.value = asig.asignatura_id;
        option.textContent = asig.asignatura_nombre;
        asignaturaSelect.appendChild(option);
    });

    // Si había un valor seleccionado y aún existe, restaurarlo
    if (valorActual) {
        const existeValor = Array.from(asignaturaSelect.options).some(opt => opt.value === valorActual);
        if (existeValor) {
            asignaturaSelect.value = valorActual;
        }
    }

    // Si solo hay una asignatura, seleccionarla automáticamente
    if (asignaturasFiltradas.length === 1) {
        asignaturaSelect.value = asignaturasFiltradas[0].asignatura_id;
    }
}

// Filtrar asignaturas con opción "Todas" para filtros de búsqueda
function filtrarAsignaturasPorCursoConTodos(cursoSelectId, asignaturaSelectId) {
    const cursoSelect = document.getElementById(cursoSelectId);
    const asignaturaSelect = document.getElementById(asignaturaSelectId);

    if (!cursoSelect || !asignaturaSelect) return;

    const cursoId = parseInt(cursoSelect.value);

    // Guardar el valor actual seleccionado (si existe)
    const valorActual = asignaturaSelect.value;

    // Limpiar todas las opciones
    asignaturaSelect.innerHTML = '';

    // Agregar opción "Todas"
    const optionTodas = document.createElement('option');
    optionTodas.value = '';
    optionTodas.textContent = 'Todas';
    asignaturaSelect.appendChild(optionTodas);

    if (!ASIGNACIONES_DOCENTE) return;

    // Si hay curso seleccionado, filtrar solo las asignaturas de ese curso
    // Si no hay curso, mostrar todas las asignaturas del docente
    let asignaturasFiltradas;
    if (cursoId) {
        asignaturasFiltradas = ASIGNACIONES_DOCENTE.filter(a => parseInt(a.curso_id) === cursoId);
    } else {
        // Obtener asignaturas únicas
        const asignaturasUnicas = new Map();
        ASIGNACIONES_DOCENTE.forEach(a => {
            if (!asignaturasUnicas.has(a.asignatura_id)) {
                asignaturasUnicas.set(a.asignatura_id, a);
            }
        });
        asignaturasFiltradas = Array.from(asignaturasUnicas.values());
    }

    // Agregar las asignaturas filtradas
    asignaturasFiltradas.forEach(asig => {
        const option = document.createElement('option');
        option.value = asig.asignatura_id;
        option.textContent = asig.asignatura_nombre;
        asignaturaSelect.appendChild(option);
    });

    // Si había un valor seleccionado y aún existe, restaurarlo
    if (valorActual) {
        const existeValor = Array.from(asignaturaSelect.options).some(opt => opt.value === valorActual);
        if (existeValor) {
            asignaturaSelect.value = valorActual;
        }
    }
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}

function getClaseNota(nota) {
    const n = parseFloat(nota);
    if (isNaN(n)) return '';
    if (n >= 6.0) return 'nota-excelente';
    if (n >= 5.0) return 'nota-buena';
    if (n >= 4.0) return 'nota-suficiente';
    return 'nota-insuficiente';
}

// Cerrar modales con Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        cerrarModalEditar();
        cerrarModalEliminar();
    }
});

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

/* ==================== PORTAL APODERADO - JAVASCRIPT ==================== */
/* Versión conectada a base de datos PHP */

// ==================== DATOS DESDE PHP ====================
// Las siguientes variables son definidas en apoderado.php antes de cargar este script:
// - datosPupilo: { nombres, apellidos, rut, curso }
// - datosApoderado: { nombre, parentesco, correo, telefono }
// - notasTrimestre1, notasTrimestre2, notasTrimestre3: arrays de notas
// - comunicadosData: array de comunicados
// - alumnosDelApoderado: array de alumnos

// Variables para gráficos
let graficoBarras = null;
let graficoLineal = null;

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que los datos de PHP estén disponibles
    if (typeof datosPupilo === 'undefined') window.datosPupilo = { nombres: '', apellidos: '', rut: '', curso: '' };
    if (typeof datosApoderado === 'undefined') window.datosApoderado = { nombre: '', parentesco: '', correo: '', telefono: '' };
    if (typeof notasTrimestre1 === 'undefined') window.notasTrimestre1 = [];
    if (typeof notasTrimestre2 === 'undefined') window.notasTrimestre2 = [];
    if (typeof notasTrimestre3 === 'undefined') window.notasTrimestre3 = [];
    if (typeof comunicadosData === 'undefined') window.comunicadosData = [];
    if (typeof alumnosDelApoderado === 'undefined') window.alumnosDelApoderado = [];

    initTabs();
    initSubTabs();
    cargarTodasLasNotas();
    initFiltrosComunicados();
    cargarComunicados();
    initNotificaciones();
    initProgreso();
});

// ==================== SISTEMA DE PESTAÑAS PRINCIPALES ====================
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// ==================== SISTEMA DE SUB-PESTAÑAS (TRIMESTRES) ====================
function initSubTabs() {
    const subTabBtns = document.querySelectorAll('.sub-tab-btn');

    subTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const subTabId = this.getAttribute('data-subtab');

            document.querySelectorAll('.sub-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.sub-tab-panel').forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(subTabId).classList.add('active');
        });
    });
}

// ==================== CARGAR TODAS LAS NOTAS ====================
function cargarTodasLasNotas() {
    cargarTablaTodasNotas(); // Nueva tabla completa
    cargarNotasTrimestre(notasTrimestre1, 'theadNotas1', 'tbodyNotas1', 'promedioTrimestre1');
    cargarNotasTrimestre(notasTrimestre2, 'theadNotas2', 'tbodyNotas2', 'promedioTrimestre2');
    cargarNotasTrimestre(notasTrimestre3, 'theadNotas3', 'tbodyNotas3', 'promedioTrimestre3');
    cargarPromediosFinales();
}

// ==================== CARGAR TABLA TODAS LAS NOTAS (NUEVA) ====================
function cargarTablaTodasNotas() {
    const tbody = document.getElementById('tbodyTodasNotas');
    if (!tbody) return;

    tbody.innerHTML = '';

    // Verificar si hay datos
    if (!notasTrimestre1 || notasTrimestre1.length === 0) {
        tbody.innerHTML = '<tr><td colspan="31" class="text-center text-muted">No hay notas registradas</td></tr>';
        return;
    }

    let sumaPromediosFinales = 0;
    let cantidadAsignaturas = 0;

    // Iterar por cada asignatura
    for (let i = 0; i < notasTrimestre1.length; i++) {
        const tr = document.createElement('tr');

        // Columna Asignatura
        const tdAsignatura = document.createElement('td');
        tdAsignatura.className = 'td-asignatura';
        tdAsignatura.textContent = notasTrimestre1[i].asignatura;
        tr.appendChild(tdAsignatura);

        // Variables para promedios
        let sumaTrimestre1 = 0, cantTrimestre1 = 0;
        let sumaTrimestre2 = 0, cantTrimestre2 = 0;
        let sumaTrimestre3 = 0, cantTrimestre3 = 0;

        // Notas Trimestre 1 (N1-N8)
        for (let j = 0; j < 8; j++) {
            const td = document.createElement('td');
            td.className = 'td-nota';
            const notaObj = notasTrimestre1[i]?.notas[j];
            const valorNota = obtenerValorNota(notaObj);
            const comentario = obtenerComentarioNota(notaObj);
            const pendiente = esNotaPendiente(notaObj);

            if (pendiente) {
                td.textContent = 'PEND';
                td.classList.add('nota-pendiente-tabla');
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', 'PEND');
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T1');
                td.onclick = function() { abrirModalComentario(this); };
            } else if (valorNota !== null && typeof valorNota === 'number') {
                td.textContent = valorNota.toFixed(1);
                td.classList.add(getClaseNota(valorNota));
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', valorNota.toFixed(1));
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T1');
                td.onclick = function() { abrirModalComentario(this); };
                sumaTrimestre1 += valorNota;
                cantTrimestre1++;
            } else {
                td.textContent = '-';
                td.classList.add('nota-vacia');
            }
            tr.appendChild(td);
        }

        // Promedio Trimestre 1
        const tdProm1 = document.createElement('td');
        tdProm1.className = 'td-prom';
        const promT1 = cantTrimestre1 > 0 ? sumaTrimestre1 / cantTrimestre1 : null;
        if (promT1 !== null) {
            tdProm1.textContent = promT1.toFixed(1);
            tdProm1.classList.add(getClaseNota(promT1));
        } else {
            tdProm1.textContent = '-';
        }
        tr.appendChild(tdProm1);

        // Notas Trimestre 2 (N1-N8)
        for (let j = 0; j < 8; j++) {
            const td = document.createElement('td');
            td.className = 'td-nota';
            const notaObj = notasTrimestre2[i]?.notas[j];
            const valorNota = obtenerValorNota(notaObj);
            const comentario = obtenerComentarioNota(notaObj);
            const pendiente = esNotaPendiente(notaObj);

            if (pendiente) {
                td.textContent = 'PEND';
                td.classList.add('nota-pendiente-tabla');
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre2[i]?.asignatura || notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', 'PEND');
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T2');
                td.onclick = function() { abrirModalComentario(this); };
            } else if (valorNota !== null && typeof valorNota === 'number') {
                td.textContent = valorNota.toFixed(1);
                td.classList.add(getClaseNota(valorNota));
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre2[i]?.asignatura || notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', valorNota.toFixed(1));
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T2');
                td.onclick = function() { abrirModalComentario(this); };
                sumaTrimestre2 += valorNota;
                cantTrimestre2++;
            } else {
                td.textContent = '-';
                td.classList.add('nota-vacia');
            }
            tr.appendChild(td);
        }

        // Promedio Trimestre 2
        const tdProm2 = document.createElement('td');
        tdProm2.className = 'td-prom';
        const promT2 = cantTrimestre2 > 0 ? sumaTrimestre2 / cantTrimestre2 : null;
        if (promT2 !== null) {
            tdProm2.textContent = promT2.toFixed(1);
            tdProm2.classList.add(getClaseNota(promT2));
        } else {
            tdProm2.textContent = '-';
        }
        tr.appendChild(tdProm2);

        // Notas Trimestre 3 (N1-N8)
        for (let j = 0; j < 8; j++) {
            const td = document.createElement('td');
            td.className = 'td-nota';
            const notaObj = notasTrimestre3[i]?.notas[j];
            const valorNota = obtenerValorNota(notaObj);
            const comentario = obtenerComentarioNota(notaObj);
            const pendiente = esNotaPendiente(notaObj);

            if (pendiente) {
                td.textContent = 'PEND';
                td.classList.add('nota-pendiente-tabla');
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre3[i]?.asignatura || notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', 'PEND');
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T3');
                td.onclick = function() { abrirModalComentario(this); };
            } else if (valorNota !== null && typeof valorNota === 'number') {
                td.textContent = valorNota.toFixed(1);
                td.classList.add(getClaseNota(valorNota));
                td.classList.add('nota-clickeable');
                td.setAttribute('data-asignatura', notasTrimestre3[i]?.asignatura || notasTrimestre1[i].asignatura);
                td.setAttribute('data-nota', valorNota.toFixed(1));
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (j + 1) + ' - T3');
                td.onclick = function() { abrirModalComentario(this); };
                sumaTrimestre3 += valorNota;
                cantTrimestre3++;
            } else {
                td.textContent = '-';
                td.classList.add('nota-vacia');
            }
            tr.appendChild(td);
        }

        // Promedio Trimestre 3
        const tdProm3 = document.createElement('td');
        tdProm3.className = 'td-prom';
        const promT3 = cantTrimestre3 > 0 ? sumaTrimestre3 / cantTrimestre3 : null;
        if (promT3 !== null) {
            tdProm3.textContent = promT3.toFixed(1);
            tdProm3.classList.add(getClaseNota(promT3));
        } else {
            tdProm3.textContent = '-';
        }
        tr.appendChild(tdProm3);

        // Promedio Final (promedio de los 3 trimestres)
        const promediosValidos = [promT1, promT2, promT3].filter(p => p !== null);
        const tdFinal = document.createElement('td');
        tdFinal.className = 'td-final';

        let promedioFinalAsig = null;
        if (promediosValidos.length > 0) {
            promedioFinalAsig = promediosValidos.reduce((a, b) => a + b, 0) / promediosValidos.length;
            tdFinal.textContent = promedioFinalAsig.toFixed(1);
            tdFinal.classList.add(getClaseNota(promedioFinalAsig));
            sumaPromediosFinales += promedioFinalAsig;
            cantidadAsignaturas++;
        } else {
            tdFinal.textContent = '-';
        }
        tr.appendChild(tdFinal);

        // Estado (Aprobado/Reprobado)
        const tdEstado = document.createElement('td');
        tdEstado.className = 'td-estado';
        if (promedioFinalAsig !== null) {
            if (promedioFinalAsig >= 4.0) {
                tdEstado.textContent = 'Aprobado';
                tdEstado.classList.add('estado-aprobado');
            } else {
                tdEstado.textContent = 'Reprobado';
                tdEstado.classList.add('estado-reprobado');
            }
        } else {
            tdEstado.textContent = '-';
        }
        tr.appendChild(tdEstado);

        tbody.appendChild(tr);
    }

    // Actualizar promedio final general
    const promedioFinalGeneral = cantidadAsignaturas > 0 ? sumaPromediosFinales / cantidadAsignaturas : 0;

    const elementoPromedioFinal = document.getElementById('promedioFinalTodas');
    if (elementoPromedioFinal) {
        elementoPromedioFinal.textContent = promedioFinalGeneral > 0 ? promedioFinalGeneral.toFixed(1) : '-';
        elementoPromedioFinal.className = 'promedio-valor ' + (promedioFinalGeneral > 0 ? getClaseNota(promedioFinalGeneral) : '');
    }

    const elementoEstadoFinal = document.getElementById('estadoFinalTodas');
    if (elementoEstadoFinal && promedioFinalGeneral > 0) {
        if (promedioFinalGeneral >= 4.0) {
            elementoEstadoFinal.textContent = 'Aprobado';
            elementoEstadoFinal.className = 'estado-final aprobado';
        } else {
            elementoEstadoFinal.textContent = 'Reprobado';
            elementoEstadoFinal.className = 'estado-final reprobado';
        }
    }
}

// ==================== OBTENER VALOR DE NOTA ====================
function obtenerValorNota(nota) {
    if (nota === null || nota === undefined) return null;
    if (typeof nota === 'object' && nota.valor !== undefined) return nota.valor;
    return nota;
}

function obtenerComentarioNota(nota) {
    if (nota === null || nota === undefined) return '';
    if (typeof nota === 'object' && nota.comentario !== undefined) return nota.comentario;
    return '';
}

function esNotaPendiente(nota) {
    if (nota === null || nota === undefined) return false;
    if (typeof nota === 'object' && nota.es_pendiente !== undefined) return nota.es_pendiente;
    if (typeof nota === 'object' && nota.valor === 'PEND') return true;
    return false;
}

// ==================== GENERAR ENCABEZADOS ====================
function generarEncabezados(theadId, maxNotas) {
    const thead = document.getElementById(theadId);
    if (!thead) return;
    thead.innerHTML = '';

    const tr = document.createElement('tr');

    const thAsignatura = document.createElement('th');
    thAsignatura.className = 'asignatura-col';
    thAsignatura.textContent = 'Asignatura';
    tr.appendChild(thAsignatura);

    for (let i = 1; i <= maxNotas; i++) {
        const th = document.createElement('th');
        th.textContent = 'N' + i;
        tr.appendChild(th);
    }

    const thPromedio = document.createElement('th');
    thPromedio.className = 'promedio-col';
    thPromedio.textContent = 'Promedio';
    tr.appendChild(thPromedio);

    thead.appendChild(tr);
}

// ==================== CARGAR NOTAS DE UN TRIMESTRE ====================
function cargarNotasTrimestre(notasData, theadId, tbodyId, promedioId) {
    const maxNotas = 8;
    generarEncabezados(theadId, maxNotas);

    const tbody = document.getElementById(tbodyId);
    if (!tbody) return 0;
    tbody.innerHTML = '';

    if (!notasData || notasData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No hay notas registradas</td></tr>';
        const elementoPromedio = document.getElementById(promedioId);
        if (elementoPromedio) elementoPromedio.textContent = '-';
        return 0;
    }

    let sumaPromedios = 0;
    let cantidadAsignaturas = 0;

    notasData.forEach(asignatura => {
        const tr = document.createElement('tr');

        const tdAsignatura = document.createElement('td');
        tdAsignatura.className = 'asignatura';
        tdAsignatura.textContent = asignatura.asignatura;
        tr.appendChild(tdAsignatura);

        let sumaNotas = 0;
        let cantidadNotas = 0;

        for (let i = 0; i < maxNotas; i++) {
            const td = document.createElement('td');
            const notaObj = asignatura.notas[i];
            const valorNota = obtenerValorNota(notaObj);
            const comentario = obtenerComentarioNota(notaObj);
            const pendiente = esNotaPendiente(notaObj);

            if (pendiente) {
                // Nota pendiente: mostrar PEND con estilo especial
                td.textContent = 'PEND';
                td.className = 'nota-valor nota-clickeable nota-pendiente-tabla';
                td.setAttribute('data-asignatura', asignatura.asignatura);
                td.setAttribute('data-nota', 'PEND');
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (i + 1));
                td.onclick = function() { abrirModalComentario(this); };
                // NO sumar al promedio
            } else if (valorNota !== null) {
                td.textContent = valorNota.toFixed(1);
                td.className = 'nota-valor nota-clickeable ' + getClaseNota(valorNota);
                td.setAttribute('data-asignatura', asignatura.asignatura);
                td.setAttribute('data-nota', valorNota.toFixed(1));
                td.setAttribute('data-comentario', comentario);
                td.setAttribute('data-numero', 'N' + (i + 1));
                td.onclick = function() { abrirModalComentario(this); };
                sumaNotas += valorNota;
                cantidadNotas++;
            } else {
                td.textContent = '-';
                td.className = 'nota-vacia';
            }

            tr.appendChild(td);
        }

        const tdPromedio = document.createElement('td');
        tdPromedio.className = 'promedio-cell';

        if (cantidadNotas > 0) {
            const promedio = sumaNotas / cantidadNotas;
            tdPromedio.textContent = promedio.toFixed(1);
            tdPromedio.classList.add(getClaseNota(promedio));
            sumaPromedios += promedio;
            cantidadAsignaturas++;
        } else {
            tdPromedio.textContent = '-';
            tdPromedio.className += ' nota-vacia';
        }

        tr.appendChild(tdPromedio);
        tbody.appendChild(tr);
    });

    const promedioTrimestre = cantidadAsignaturas > 0 ? sumaPromedios / cantidadAsignaturas : 0;
    const elementoPromedio = document.getElementById(promedioId);
    if (elementoPromedio) {
        elementoPromedio.textContent = promedioTrimestre > 0 ? promedioTrimestre.toFixed(1) : '-';
        elementoPromedio.className = 'promedio-valor ' + (promedioTrimestre > 0 ? getClaseNota(promedioTrimestre) : '');
    }

    return promedioTrimestre;
}

// ==================== CARGAR PROMEDIOS FINALES ====================
function cargarPromediosFinales() {
    const tbody = document.getElementById('tbodyPromedios');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!notasTrimestre1 || notasTrimestre1.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay notas registradas</td></tr>';
        return;
    }

    let sumaPromediosFinales = 0;
    let cantidadAsignaturas = 0;

    for (let i = 0; i < notasTrimestre1.length; i++) {
        const tr = document.createElement('tr');

        const tdAsignatura = document.createElement('td');
        tdAsignatura.className = 'asignatura';
        tdAsignatura.textContent = notasTrimestre1[i].asignatura;
        tr.appendChild(tdAsignatura);

        const prom1 = calcularPromedioAsignatura(notasTrimestre1[i].notas);
        const prom2 = notasTrimestre2[i] ? calcularPromedioAsignatura(notasTrimestre2[i].notas) : null;
        const prom3 = notasTrimestre3[i] ? calcularPromedioAsignatura(notasTrimestre3[i].notas) : null;

        [prom1, prom2, prom3].forEach(prom => {
            const td = document.createElement('td');
            if (prom !== null) {
                td.textContent = prom.toFixed(1);
                td.className = 'nota-valor ' + getClaseNota(prom);
            } else {
                td.textContent = '-';
                td.className = 'nota-vacia';
            }
            tr.appendChild(td);
        });

        const tdFinal = document.createElement('td');
        tdFinal.className = 'promedio-cell';

        const promediosValidos = [prom1, prom2, prom3].filter(p => p !== null);
        if (promediosValidos.length > 0) {
            const notaFinal = promediosValidos.reduce((a, b) => a + b, 0) / promediosValidos.length;
            tdFinal.textContent = notaFinal.toFixed(1);
            tdFinal.classList.add(getClaseNota(notaFinal));
            sumaPromediosFinales += notaFinal;
            cantidadAsignaturas++;
        } else {
            tdFinal.textContent = '-';
            tdFinal.className += ' nota-vacia';
        }
        tr.appendChild(tdFinal);

        tbody.appendChild(tr);
    }

    const promedioFinal = cantidadAsignaturas > 0 ? sumaPromediosFinales / cantidadAsignaturas : 0;
    const elementoPromedioFinal = document.getElementById('promedioFinal');
    if (elementoPromedioFinal) {
        elementoPromedioFinal.textContent = promedioFinal > 0 ? promedioFinal.toFixed(1) : '-';
        elementoPromedioFinal.className = 'promedio-valor ' + (promedioFinal > 0 ? getClaseNota(promedioFinal) : '');
    }
}

function calcularPromedioAsignatura(notas) {
    if (!notas) return null;
    // Filtrar notas válidas excluyendo las pendientes
    const notasValidas = notas.filter(n => {
        if (n === null || n === undefined) return false;
        if (esNotaPendiente(n)) return false; // Excluir notas pendientes
        const valor = obtenerValorNota(n);
        return valor !== null && typeof valor === 'number';
    });
    if (notasValidas.length === 0) return null;

    const suma = notasValidas.reduce((acc, n) => {
        const valor = obtenerValorNota(n);
        return acc + valor;
    }, 0);

    return suma / notasValidas.length;
}

function getClaseNota(nota) {
    if (nota >= 4.0) return 'nota-aprobada';
    return 'nota-reprobada';
}

// ==================== CARGAR COMUNICADOS ====================
function esMobileView() {
    return window.innerWidth <= 767;
}

function cargarComunicados() {
    const listaRecientes = document.getElementById('listaComunicadosRecientes');
    const listaAnteriores = document.getElementById('listaComunicadosAnteriores');

    if (!listaRecientes) return;

    listaRecientes.innerHTML = '';
    if (listaAnteriores) listaAnteriores.innerHTML = '';

    const tipoFiltro = document.getElementById('filtroTipoComunicado')?.value || '';

    let comunicadosFiltrados = [...comunicadosData];

    if (tipoFiltro) {
        comunicadosFiltrados = comunicadosFiltrados.filter(c => c.tipo === tipoFiltro);
    }

    if (comunicadosFiltrados.length === 0) {
        listaRecientes.innerHTML = `
            <div class="sin-comunicados">
                <p>No hay comunicados disponibles</p>
            </div>
        `;
        if (listaAnteriores) {
            listaAnteriores.innerHTML = `<div class="sin-comunicados"><p>No hay comunicados anteriores</p></div>`;
        }
        return;
    }

    const comunicadosOrdenados = comunicadosFiltrados.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

    // En móvil mostrar todos en una sola lista, en desktop dividir
    if (esMobileView()) {
        comunicadosOrdenados.forEach(comunicado => {
            listaRecientes.appendChild(crearElementoComunicado(comunicado));
        });
    } else {
        const comunicadosRecientes = comunicadosOrdenados.slice(0, 3);
        const comunicadosAnteriores = comunicadosOrdenados.slice(3);

        comunicadosRecientes.forEach(comunicado => {
            listaRecientes.appendChild(crearElementoComunicado(comunicado));
        });

        if (listaAnteriores && comunicadosAnteriores.length > 0) {
            comunicadosAnteriores.forEach(comunicado => {
                listaAnteriores.appendChild(crearElementoComunicado(comunicado));
            });
        } else if (listaAnteriores) {
            listaAnteriores.innerHTML = `<div class="sin-comunicados"><p>No hay comunicados anteriores</p></div>`;
        }
    }
}

function initFiltrosComunicados() {
    const filtroTipo = document.getElementById('filtroTipoComunicado');
    if (filtroTipo) {
        filtroTipo.addEventListener('change', cargarComunicados);
    }

    // Recargar comunicados al cambiar tamaño de ventana
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(cargarComunicados, 250);
    });
}

function crearElementoComunicado(comunicado) {
    const item = document.createElement('div');
    item.className = `comunicado-item ${comunicado.tipo}`;

    const fechaFormateada = formatearFecha(comunicado.fecha);
    const tipoTexto = getTipoTexto(comunicado.tipo);

    item.innerHTML = `
        <div class="comunicado-meta">
            <span class="comunicado-fecha">${fechaFormateada} - ${comunicado.hora || ''}</span>
            <span class="comunicado-tipo ${comunicado.tipo}">${tipoTexto}</span>
        </div>
        <h4 class="comunicado-titulo">${comunicado.titulo}</h4>
        <p class="comunicado-contenido">${comunicado.contenido}</p>
    `;

    return item;
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const fecha = new Date(fechaStr + 'T00:00:00');
    const opciones = { day: '2-digit', month: 'long', year: 'numeric' };
    return fecha.toLocaleDateString('es-CL', opciones);
}

function getTipoTexto(tipo) {
    const tipos = { 'urgente': 'Urgente', 'evento': 'Evento', 'informativo': 'Informativo' };
    return tipos[tipo] || 'General';
}

// ==================== MODAL COMENTARIO DE NOTA ====================
function abrirModalComentario(elemento) {
    const asignatura = elemento.getAttribute('data-asignatura');
    const nota = elemento.getAttribute('data-nota');
    const comentario = elemento.getAttribute('data-comentario');

    const asigElement = document.getElementById('modalComentarioAsignatura');
    if (asigElement) asigElement.textContent = asignatura;

    const notaElement = document.getElementById('modalComentarioValor');
    if (notaElement) {
        notaElement.textContent = nota;
        notaElement.className = 'modal-comentario-nota ' + getClaseNota(parseFloat(nota));
    }

    const textoElement = document.getElementById('modalComentarioTexto');
    if (textoElement) {
        textoElement.textContent = comentario && comentario.trim() !== '' ? comentario : 'No hay comentario';
    }

    const modal = document.getElementById('modalComentario');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalComentario() {
    const modal = document.getElementById('modalComentario');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ==================== SISTEMA DE NOTIFICACIONES ====================
function initNotificaciones() {
    actualizarBadgeNotificaciones();

    const tabComunicados = document.getElementById('tabComunicados');
    if (tabComunicados) {
        tabComunicados.addEventListener('click', function() {
            setTimeout(marcarComunicadosComoLeidos, 100);
        });
    }
}

function contarComunicadosNoLeidos() {
    return comunicadosData.filter(c => !c.leido).length;
}

function actualizarBadgeNotificaciones() {
    const badge = document.getElementById('notificationBadge');
    const tabComunicados = document.getElementById('tabComunicados');
    const noLeidos = contarComunicadosNoLeidos();

    if (badge) {
        if (noLeidos > 0) {
            badge.textContent = noLeidos;
            badge.classList.add('show');
        } else {
            badge.classList.remove('show');
        }
    }

    // Para móvil: agregar/quitar clase que pone la campana en rojo
    if (tabComunicados) {
        if (noLeidos > 0) {
            tabComunicados.classList.add('tiene-notificaciones');
        } else {
            tabComunicados.classList.remove('tiene-notificaciones');
        }
    }
}

function marcarComunicadosComoLeidos() {
    // Obtener IDs de comunicados no leídos
    const noLeidos = comunicadosData.filter(c => !c.leido);

    if (noLeidos.length === 0) return;

    const ids = noLeidos.map(c => c.id);

    // Guardar en base de datos
    fetch('api/marcar_comunicados_leidos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ comunicado_ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Marcar como leídos en memoria
            comunicadosData.forEach(c => c.leido = true);
            actualizarBadgeNotificaciones();
        }
    })
    .catch(error => {
        console.error('Error al marcar comunicados como leídos:', error);
    });
}

// ==================== SISTEMA DE PROGRESO ====================
function esMobile() {
    return window.innerWidth <= 768;
}

function initProgreso() {
    cargarKPIs();
    cargarSelectorAsignaturas();

    const tabProgreso = document.querySelector('[data-tab="progreso"]');
    if (tabProgreso) {
        tabProgreso.addEventListener('click', function() {
            setTimeout(() => {
                crearGraficoBarras();
                crearGraficoLineal(0);
            }, 100);
        });
    }
}

function calcularPromediosFinalesProgreso() {
    const promedios = [];

    for (let i = 0; i < notasTrimestre1.length; i++) {
        const asignatura = notasTrimestre1[i].asignatura;

        const prom1 = calcularPromedioAsignatura(notasTrimestre1[i].notas);
        const prom2 = notasTrimestre2[i] ? calcularPromedioAsignatura(notasTrimestre2[i].notas) : null;
        const prom3 = notasTrimestre3[i] ? calcularPromedioAsignatura(notasTrimestre3[i].notas) : null;

        const promediosValidos = [prom1, prom2, prom3].filter(p => p !== null);

        if (promediosValidos.length > 0) {
            const promedioFinal = promediosValidos.reduce((a, b) => a + b, 0) / promediosValidos.length;
            promedios.push({
                asignatura: asignatura,
                promedio: promedioFinal,
                trimestres: { t1: prom1, t2: prom2, t3: prom3 }
            });
        }
    }

    return promedios;
}

function cargarKPIs() {
    const promedios = calcularPromediosFinalesProgreso();

    if (promedios.length === 0) return;

    const ordenadosDesc = [...promedios].sort((a, b) => b.promedio - a.promedio);
    const ordenadosAsc = [...promedios].sort((a, b) => a.promedio - b.promedio);

    const setKPI = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    if (ordenadosDesc.length >= 1) {
        setKPI('kpiMejor1', ordenadosDesc[0].promedio.toFixed(1));
        setKPI('kpiMejorAsig1', ordenadosDesc[0].asignatura);
    }
    if (ordenadosDesc.length >= 2) {
        setKPI('kpiMejor2', ordenadosDesc[1].promedio.toFixed(1));
        setKPI('kpiMejorAsig2', ordenadosDesc[1].asignatura);
    }
    if (ordenadosAsc.length >= 1) {
        setKPI('kpiBajo1', ordenadosAsc[0].promedio.toFixed(1));
        setKPI('kpiBajoAsig1', ordenadosAsc[0].asignatura);
    }
    if (ordenadosAsc.length >= 2) {
        setKPI('kpiBajo2', ordenadosAsc[1].promedio.toFixed(1));
        setKPI('kpiBajoAsig2', ordenadosAsc[1].asignatura);
    }
}

function crearGraficoBarras() {
    const canvas = document.getElementById('graficoBarras');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const promedios = calcularPromediosFinalesProgreso();

    if (graficoBarras) graficoBarras.destroy();

    if (promedios.length === 0) return;

    const labels = promedios.map(p => p.asignatura);
    const datos = promedios.map(p => p.promedio);

    const obtenerColor = (valor) => {
        if (valor >= 6.0) return 'rgba(5, 150, 105, 0.85)';
        if (valor >= 5.0) return 'rgba(14, 116, 144, 0.85)';
        if (valor >= 4.0) return 'rgba(234, 88, 12, 0.85)';
        return 'rgba(220, 38, 38, 0.85)';
    };

    const colores = datos.map(nota => obtenerColor(nota));

    graficoBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.map(l => l.length > 12 ? l.substring(0, 12) + '...' : l),
            datasets: [{
                label: 'Promedio',
                data: datos,
                backgroundColor: colores,
                borderRadius: 4,
                barPercentage: 0.75
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { min: 1, max: 7, ticks: { stepSize: 1 } },
                y: { ticks: { font: { size: esMobile() ? 9 : 10 } } }
            }
        }
    });
}

function cargarSelectorAsignaturas() {
    const select = document.getElementById('selectAsignatura');
    if (!select) return;

    select.innerHTML = '';

    notasTrimestre1.forEach((asig, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = asig.asignatura;
        select.appendChild(option);
    });
}

function actualizarGraficoAsignatura() {
    const select = document.getElementById('selectAsignatura');
    if (!select) return;

    const indice = parseInt(select.value);
    crearGraficoLineal(indice);
}

function calcularPromedioTrimestre(notas) {
    if (!notas || notas.length === 0) return null;
    const notasValidas = notas.filter(n => obtenerValorNota(n) !== null);
    if (notasValidas.length === 0) return null;
    const suma = notasValidas.reduce((acc, n) => acc + obtenerValorNota(n), 0);
    return suma / notasValidas.length;
}

function obtenerPromediosPorTrimestre(indice) {
    const promT1 = calcularPromedioTrimestre(notasTrimestre1[indice]?.notas);
    const promT2 = calcularPromedioTrimestre(notasTrimestre2[indice]?.notas);
    const promT3 = calcularPromedioTrimestre(notasTrimestre3[indice]?.notas);

    const promedios = [];
    const etiquetas = [];

    // Trimestre 1: Mar - Jun
    etiquetas.push('Mar', 'Abr', 'May', 'Jun', 'T1');
    if (promT1 !== null) {
        promedios.push(null, null, null, null, promT1);
    } else {
        promedios.push(null, null, null, null, null);
    }

    // Trimestre 2: Jul - Sep
    etiquetas.push('Jul', 'Ago', 'Sep', 'T2');
    if (promT2 !== null) {
        promedios.push(null, null, null, promT2);
    } else {
        promedios.push(null, null, null, null);
    }

    // Trimestre 3: Oct - Dic
    etiquetas.push('Oct', 'Nov', 'Dic', 'T3');
    if (promT3 !== null) {
        promedios.push(null, null, null, promT3);
    } else {
        promedios.push(null, null, null, null);
    }

    return { promedios, etiquetas };
}

function crearGraficoLineal(indiceAsignatura = 0) {
    const canvas = document.getElementById('graficoLineal');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    if (graficoLineal) graficoLineal.destroy();

    // Obtener datos mensuales de la asignatura seleccionada
    const asignaturaData = notasMensuales[indiceAsignatura];

    // Etiquetas: Mar a Dic (índices 2 a 11 del array meses)
    const etiquetas = ['Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    // Datos: obtener meses de Marzo (índice 2) a Diciembre (índice 11)
    let datos = [];
    if (asignaturaData && asignaturaData.meses) {
        datos = asignaturaData.meses.slice(2, 12); // Marzo a Diciembre
    } else {
        datos = [null, null, null, null, null, null, null, null, null, null];
    }

    // Calcular variaciones entre meses con datos (saltando meses vacíos)
    const variaciones = [];
    let ultimoIndiceConDatos = -1;
    let ultimoValorConDatos = null;

    for (let i = 0; i < datos.length; i++) {
        if (datos[i] !== null) {
            if (ultimoValorConDatos !== null) {
                // Hay un valor anterior, calcular variación
                const variacion = ((datos[i] - ultimoValorConDatos) / ultimoValorConDatos * 100);
                variaciones.push({
                    indexDesde: ultimoIndiceConDatos,
                    indexHasta: i,
                    valor: variacion,
                    positivo: variacion >= 0
                });
            }
            ultimoIndiceConDatos = i;
            ultimoValorConDatos = datos[i];
        }
    }

    const puntosColores = datos.map(val => {
        if (val === null) return 'transparent';
        return val < 4.0 ? 'rgba(197, 48, 48, 1)' : 'rgba(45, 90, 135, 1)';
    });

    // Plugin para dibujar T1, T2, T3 y variaciones
    const customLabelsPlugin = {
        id: 'customLabels',
        afterDraw: function(chart) {
            const ctx = chart.ctx;
            const xAxis = chart.scales.x;
            const yAxis = chart.scales.y;
            const dataset = chart.data.datasets[0].data;

            ctx.save();

            // Dibujar T1, T2, T3 arriba de las líneas de fin de trimestre
            ctx.font = 'bold 10px Arial';
            ctx.fillStyle = '#1e3a5f';
            ctx.textAlign = 'center';

            // T1 en Jun (índice 3)
            const x1 = xAxis.getPixelForValue(3);
            ctx.fillText('T1', x1, yAxis.top - 5);

            // T2 en Sep (índice 6)
            const x2 = xAxis.getPixelForValue(6);
            ctx.fillText('T2', x2, yAxis.top - 5);

            // T3 en Dic (índice 9)
            const x3 = xAxis.getPixelForValue(9);
            ctx.fillText('T3', x3, yAxis.top - 5);

            // Dibujar variaciones entre puntos (incluso con meses vacíos en medio)
            ctx.font = 'bold 9px Arial';
            variaciones.forEach(v => {
                const xPrev = xAxis.getPixelForValue(v.indexDesde);
                const xCurr = xAxis.getPixelForValue(v.indexHasta);
                const xMid = (xPrev + xCurr) / 2;

                const yPrev = yAxis.getPixelForValue(dataset[v.indexDesde]);
                const yCurr = yAxis.getPixelForValue(dataset[v.indexHasta]);
                const yMid = (yPrev + yCurr) / 2;

                // Mostrar variación positiva o negativa
                if (!v.positivo) {
                    ctx.fillStyle = '#c53030'; // Rojo para negativo
                    const texto = v.valor.toFixed(0) + '%';
                    ctx.fillText(texto, xMid, yMid + 15);
                } else if (v.valor > 0) {
                    ctx.fillStyle = '#276749'; // Verde para positivo
                    const texto = '+' + v.valor.toFixed(0) + '%';
                    ctx.fillText(texto, xMid, yMid - 8);
                }
                // Si es 0% no mostramos nada
            });

            ctx.restore();
        }
    };

    graficoLineal = new Chart(ctx, {
        type: 'line',
        data: {
            labels: etiquetas,
            datasets: [{
                label: asignaturaData?.asignatura || notasTrimestre1[indiceAsignatura]?.asignatura || 'Asignatura',
                data: datos,
                borderColor: 'rgba(45, 90, 135, 1)',
                backgroundColor: 'rgba(45, 90, 135, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                spanGaps: true,
                pointBackgroundColor: puntosColores,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: datos.map(val => val !== null ? 6 : 0)
            }]
        },
        plugins: [customLabelsPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 20,
                    bottom: 10
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.raw === null) return '';
                            return 'Promedio: ' + context.raw.toFixed(1);
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 1,
                    max: 8,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value <= 7 ? value : '';
                        }
                    }
                },
                x: {
                    ticks: {
                        font: { size: esMobile() ? 8 : 10 }
                    },
                    grid: {
                        color: function(context) {
                            // Líneas más marcadas al final de cada trimestre (Jun, Sep, Dic)
                            if (context.index === 3 || context.index === 6 || context.index === 9) {
                                return 'rgba(45, 90, 135, 0.5)';
                            }
                            return 'rgba(0, 0, 0, 0.05)';
                        },
                        lineWidth: function(context) {
                            if (context.index === 3 || context.index === 6 || context.index === 9) {
                                return 2;
                            }
                            return 1;
                        }
                    }
                }
            }
        }
    });
}

// Cerrar modal al hacer clic fuera o con Escape
document.addEventListener('DOMContentLoaded', function() {
    const modalComentario = document.getElementById('modalComentario');
    if (modalComentario) {
        modalComentario.addEventListener('click', function(e) {
            if (e.target === this) cerrarModalComentario();
        });
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalComentario();
    }
});

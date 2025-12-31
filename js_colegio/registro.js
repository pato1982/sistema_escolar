// ==================== REGISTRO DE USUARIOS ====================

let tipoUsuarioSeleccionado = null;
let contadorAlumnos = 1;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initTipoUsuarioCards();
});

// Inicializar las tarjetas de tipo de usuario
function initTipoUsuarioCards() {
    const cards = document.querySelectorAll('.tipo-usuario-card');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            // Quitar selección anterior
            cards.forEach(c => c.classList.remove('selected'));

            // Seleccionar esta tarjeta
            this.classList.add('selected');
            tipoUsuarioSeleccionado = this.getAttribute('data-tipo');

            // Ir al paso correspondiente después de un pequeño delay
            setTimeout(() => {
                // Ocultar todos los indicadores primero
                document.querySelector('.pasos-indicador:not(.pasos-docente):not(.pasos-administrador)').style.display = 'none';
                document.querySelector('.pasos-docente').style.display = 'none';
                document.querySelector('.pasos-administrador').style.display = 'none';

                if (tipoUsuarioSeleccionado === 'apoderado') {
                    // Mostrar indicador de apoderado
                    document.querySelector('.pasos-indicador:not(.pasos-docente):not(.pasos-administrador)').style.display = 'flex';
                    mostrarPaso('paso-1');
                    actualizarIndicadorPaso(1);
                } else if (tipoUsuarioSeleccionado === 'docente') {
                    // Mostrar indicador de docente
                    document.querySelector('.pasos-docente').style.display = 'flex';
                    mostrarPaso('paso-docente-1');
                    actualizarIndicadorPasoDocente(1);
                } else if (tipoUsuarioSeleccionado === 'administrador') {
                    // Mostrar indicador de administrador
                    document.querySelector('.pasos-administrador').style.display = 'flex';
                    mostrarPaso('paso-admin-1');
                    actualizarIndicadorPasoAdmin(1);
                }
            }, 300);
        });
    });
}

// Mostrar un paso específico
function mostrarPaso(pasoId) {
    // Ocultar todos los pasos
    document.querySelectorAll('.paso-contenido').forEach(paso => {
        paso.classList.remove('active');
    });

    // Mostrar el paso seleccionado
    document.getElementById(pasoId).classList.add('active');
}

// Actualizar el indicador de pasos
function actualizarIndicadorPaso(numeroPaso) {
    const pasos = document.querySelectorAll('.paso-item');

    pasos.forEach((paso, index) => {
        paso.classList.remove('active', 'completed');

        if (index + 1 < numeroPaso) {
            paso.classList.add('completed');
        } else if (index + 1 === numeroPaso) {
            paso.classList.add('active');
        }
    });
}

// Volver a la selección de tipo
function volverATipo() {
    mostrarPaso('paso-tipo');
    // Quitar selección de tipo
    document.querySelectorAll('.tipo-usuario-card').forEach(c => c.classList.remove('selected'));
    tipoUsuarioSeleccionado = null;
}

// Ir al paso 2
function irAPaso2() {
    // Validar paso 1
    const nombre = document.getElementById('nombreApoderado').value.trim();
    const apellido = document.getElementById('apellidoApoderado').value.trim();
    const rut = document.getElementById('rutApoderado').value.trim();
    const direccion = document.getElementById('direccionApoderado').value.trim();
    const correo = document.getElementById('correoApoderado').value.trim();
    const confirmarCorreo = document.getElementById('confirmarCorreoApoderado').value.trim();
    const parentesco = document.getElementById('parentescoApoderado').value;
    const telefono = document.getElementById('telefonoApoderado').value.trim();

    if (!nombre || !apellido || !rut || !direccion || !correo || !confirmarCorreo || !parentesco || !telefono) {
        alert('Por favor complete todos los campos');
        return;
    }

    if (correo !== confirmarCorreo) {
        alert('Los correos electrónicos no coinciden');
        return;
    }

    // Validar formato de correo
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        alert('Por favor ingrese un correo electrónico válido');
        return;
    }

    mostrarPaso('paso-2');
    actualizarIndicadorPaso(2);
}

// Volver al paso 1
function volverAPaso1() {
    mostrarPaso('paso-1');
    actualizarIndicadorPaso(1);
}

// Ir al paso 3
async function irAPaso3() {
    // Validar todos los alumnos
    const alumnosCards = document.querySelectorAll('.alumno-card');
    const rutApoderado = document.getElementById('rutApoderado').value.trim();
    const correoApoderado = document.getElementById('correoApoderado').value.trim();
    const establecimientoId = document.getElementById('establecimientoApoderado').value;

    for (let card of alumnosCards) {
        const numAlumno = card.getAttribute('data-alumno');
        const nombre = document.getElementById(`nombreAlumno_${numAlumno}`).value.trim();
        const apellido = document.getElementById(`apellidoAlumno_${numAlumno}`).value.trim();
        const rut = document.getElementById(`rutAlumno_${numAlumno}`).value.trim();
        const curso = document.getElementById(`cursoAlumno_${numAlumno}`).value;

        if (!nombre || !apellido || !rut || !curso) {
            alert(`Por favor complete todos los campos del Alumno ${numAlumno}`);
            return;
        }
    }

    // Obtener datos del apoderado para posible registro de intento fallido
    const datosApoderado = {
        rut: rutApoderado,
        nombres: document.getElementById('nombreApoderado').value.trim(),
        apellidos: document.getElementById('apellidoApoderado').value.trim(),
        telefono: document.getElementById('telefonoApoderado').value.trim(),
        parentesco: document.getElementById('parentescoApoderado').value
    };

    // Validar cada alumno contra el pre-registro (RUT y correo del apoderado)
    for (let card of alumnosCards) {
        const numAlumno = card.getAttribute('data-alumno');
        const rutAlumno = document.getElementById(`rutAlumno_${numAlumno}`).value.trim();
        const nombreAlumno = document.getElementById(`nombreAlumno_${numAlumno}`).value.trim();
        const apellidoAlumno = document.getElementById(`apellidoAlumno_${numAlumno}`).value.trim();
        const cursoAlumno = document.getElementById(`cursoAlumno_${numAlumno}`).value;

        const validacion = await validarPreregistro(rutApoderado, correoApoderado, rutAlumno, establecimientoId);

        if (!validacion.success) {
            // Registrar el intento fallido en la base de datos
            await registrarIntentoFallido(datosApoderado, {
                rut: rutAlumno,
                nombres: nombreAlumno,
                apellidos: apellidoAlumno,
                curso: cursoAlumno
            }, establecimientoId);

            mostrarModalError(validacion.message);
            return;
        }
    }

    mostrarPaso('paso-3');
    actualizarIndicadorPaso(3);
}

// Función para validar pre-registro contra la base de datos (RUT y correo del apoderado)
async function validarPreregistro(rutApoderado, correoApoderado, rutAlumno, establecimientoId) {
    try {
        const response = await fetch('api/validar_preregistro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                rut_apoderado: rutApoderado,
                correo_apoderado: correoApoderado,
                rut_alumno: rutAlumno,
                establecimiento_id: establecimientoId
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al validar pre-registro:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Mostrar modal de error de validación
function mostrarModalError(mensaje) {
    const modal = document.getElementById('modalErrorValidacion');
    const texto = document.getElementById('modalErrorTexto');

    if (modal && texto) {
        texto.textContent = mensaje;
        modal.classList.add('active');
    } else {
        alert(mensaje);
    }
}

// Cerrar modal de error
function cerrarModalError(event) {
    if (event && event.target !== event.currentTarget) {
        return;
    }
    const modal = document.getElementById('modalErrorValidacion');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Función para registrar intento fallido de apoderado en la base de datos
async function registrarIntentoFallido(datosApoderado, datosAlumno, establecimientoId) {
    try {
        await fetch('api/registrar_intento_fallido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                apoderado_rut: datosApoderado.rut,
                apoderado_nombres: datosApoderado.nombres,
                apoderado_apellidos: datosApoderado.apellidos,
                apoderado_telefono: datosApoderado.telefono,
                apoderado_parentesco: datosApoderado.parentesco,
                alumno_rut: datosAlumno.rut,
                alumno_nombres: datosAlumno.nombres,
                alumno_apellidos: datosAlumno.apellidos,
                alumno_curso: datosAlumno.curso,
                establecimiento_id: establecimientoId
            })
        });
    } catch (error) {
        console.error('Error al registrar intento fallido:', error);
    }
}

// Función para registrar intento fallido de docente en la base de datos
async function registrarIntentoFallidoDocente(datosDocente, establecimientoId) {
    try {
        await fetch('api/registrar_intento_fallido_docente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                docente_rut: datosDocente.rut,
                docente_nombres: datosDocente.nombres,
                docente_apellidos: datosDocente.apellidos,
                docente_telefono: datosDocente.telefono,
                docente_correo: datosDocente.correo,
                establecimiento_id: establecimientoId
            })
        });
    } catch (error) {
        console.error('Error al registrar intento fallido docente:', error);
    }
}

// Función para registrar intento fallido de administrador en la base de datos
async function registrarIntentoFallidoAdmin(datosAdmin, establecimientoId) {
    try {
        await fetch('api/registrar_intento_fallido_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                admin_rut: datosAdmin.rut,
                admin_nombres: datosAdmin.nombres,
                admin_apellidos: datosAdmin.apellidos,
                admin_telefono: datosAdmin.telefono,
                admin_correo: datosAdmin.correo,
                codigo_validacion: datosAdmin.codigoValidacion,
                establecimiento_id: establecimientoId
            })
        });
    } catch (error) {
        console.error('Error al registrar intento fallido admin:', error);
    }
}

// Volver al paso 2
function volverAPaso2() {
    mostrarPaso('paso-2');
    actualizarIndicadorPaso(2);
}

// Finalizar registro
async function finalizarRegistro() {
    // Validar paso 3
    const password = document.getElementById('password').value;
    const confirmarPassword = document.getElementById('confirmarPassword').value;

    if (!password || !confirmarPassword) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Validar que sea exactamente 8 dígitos numéricos
    const passwordRegex = /^\d{8}$/;
    if (!passwordRegex.test(password)) {
        alert('La contraseña debe tener exactamente 8 dígitos numéricos');
        return;
    }

    if (password !== confirmarPassword) {
        alert('Las contraseñas no coinciden');
        return;
    }

    // Recopilar datos del apoderado
    const datosApoderado = {
        apoderado_nombres: document.getElementById('nombreApoderado').value.trim(),
        apoderado_apellidos: document.getElementById('apellidoApoderado').value.trim(),
        apoderado_rut: document.getElementById('rutApoderado').value.trim(),
        apoderado_telefono: document.getElementById('telefonoApoderado').value.trim(),
        apoderado_direccion: document.getElementById('direccionApoderado').value.trim(),
        apoderado_correo: document.getElementById('correoApoderado').value.trim(),
        apoderado_parentesco: document.getElementById('parentescoApoderado').value,
        establecimiento_id: document.getElementById('establecimientoApoderado').value,
        password: password,
        alumnos: []
    };

    // Recopilar datos de todos los alumnos
    const alumnosCards = document.querySelectorAll('.alumno-card');
    for (let card of alumnosCards) {
        const numAlumno = card.getAttribute('data-alumno');
        datosApoderado.alumnos.push({
            nombres: document.getElementById(`nombreAlumno_${numAlumno}`).value.trim(),
            apellidos: document.getElementById(`apellidoAlumno_${numAlumno}`).value.trim(),
            rut: document.getElementById(`rutAlumno_${numAlumno}`).value.trim(),
            curso: document.getElementById(`cursoAlumno_${numAlumno}`).value
        });
    }

    // Enviar registro al servidor
    const resultado = await registrarApoderado(datosApoderado);

    if (resultado.success) {
        // Mostrar paso final (éxito)
        mostrarPaso('paso-final');
        // Ocultar indicador de pasos
        document.querySelector('.pasos-indicador').style.display = 'none';
    } else {
        mostrarModalError(resultado.message);
    }
}

// Función para registrar apoderado en la base de datos
async function registrarApoderado(datos) {
    try {
        const response = await fetch('api/registrar_apoderado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al registrar apoderado:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Funciones del tooltip
function toggleTooltip(event) {
    event.stopPropagation();
    const trigger = event.currentTarget;
    trigger.classList.toggle('active');
}

function showTooltip(element) {
    element.classList.add('active');
}

function hideTooltip(element) {
    element.classList.remove('active');
}

// Cerrar tooltip al hacer clic fuera
document.addEventListener('click', function(event) {
    const tooltips = document.querySelectorAll('.tooltip-trigger');
    tooltips.forEach(tooltip => {
        if (!tooltip.contains(event.target)) {
            tooltip.classList.remove('active');
        }
    });
});

// Funciones de autocompletado deshabilitadas - datos se cargarán desde la base de datos
function completarPaso1() {
    // Función deshabilitada - los datos se cargarán desde la base de datos
}

function completarPaso2() {
    // Función deshabilitada - los datos se cargarán desde la base de datos
}

// Toggle para agregar otro alumno
function toggleAgregarAlumno() {
    const checkbox = document.getElementById('agregarOtroAlumno');

    if (checkbox.checked) {
        agregarNuevoAlumno();
        checkbox.checked = false;
    }
}

// Agregar nuevo alumno
function agregarNuevoAlumno() {
    contadorAlumnos++;
    const contenedor = document.getElementById('contenedorAlumnos');

    const nuevoAlumnoHTML = `
        <div class="alumno-card" data-alumno="${contadorAlumnos}">
            <div class="alumno-header">
                <span class="alumno-titulo">Alumno ${contadorAlumnos}</span>
                <button type="button" class="btn-eliminar-alumno" onclick="eliminarAlumno(${contadorAlumnos})">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Eliminar
                </button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="nombreAlumno_${contadorAlumnos}">Nombres del Alumno</label>
                    <input type="text" id="nombreAlumno_${contadorAlumnos}" class="form-control" placeholder="Ej: Juan Pablo" required>
                </div>
                <div class="form-group">
                    <label for="apellidoAlumno_${contadorAlumnos}">Apellidos del Alumno</label>
                    <input type="text" id="apellidoAlumno_${contadorAlumnos}" class="form-control" placeholder="Ej: González Muñoz" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="rutAlumno_${contadorAlumnos}">RUT del Alumno</label>
                    <input type="text" id="rutAlumno_${contadorAlumnos}" class="form-control" placeholder="Ej: 23.456.789-0" required>
                </div>
                <div class="form-group">
                    <label for="cursoAlumno_${contadorAlumnos}">Curso</label>
                    <select id="cursoAlumno_${contadorAlumnos}" class="form-control" required>
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
    `;

    contenedor.insertAdjacentHTML('beforeend', nuevoAlumnoHTML);

    // Scroll hacia el nuevo alumno
    const nuevoAlumno = contenedor.lastElementChild;
    nuevoAlumno.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Eliminar alumno
function eliminarAlumno(numAlumno) {
    const card = document.querySelector(`.alumno-card[data-alumno="${numAlumno}"]`);
    if (card) {
        card.remove();
        renumerarAlumnos();
    }
}

// Renumerar alumnos después de eliminar
function renumerarAlumnos() {
    const cards = document.querySelectorAll('.alumno-card');
    cards.forEach((card, index) => {
        const nuevoNum = index + 1;
        const viejoNum = card.getAttribute('data-alumno');

        card.setAttribute('data-alumno', nuevoNum);
        card.querySelector('.alumno-titulo').textContent = `Alumno ${nuevoNum}`;

        // Actualizar IDs de los campos
        const campos = ['nombreAlumno', 'apellidoAlumno', 'rutAlumno', 'cursoAlumno'];
        campos.forEach(campo => {
            const input = card.querySelector(`#${campo}_${viejoNum}`);
            if (input) {
                input.id = `${campo}_${nuevoNum}`;
                const label = card.querySelector(`label[for="${campo}_${viejoNum}"]`);
                if (label) label.setAttribute('for', `${campo}_${nuevoNum}`);
            }
        });

        // Actualizar botón eliminar si existe (no existe en el primer alumno)
        const btnEliminar = card.querySelector('.btn-eliminar-alumno');
        if (btnEliminar) {
            btnEliminar.setAttribute('onclick', `eliminarAlumno(${nuevoNum})`);
        }
    });

    contadorAlumnos = cards.length;
}

// ==================== FUNCIONES PARA REGISTRO DE DOCENTE ====================

// Actualizar el indicador de pasos para docente
function actualizarIndicadorPasoDocente(numeroPaso) {
    const pasos = document.querySelectorAll('.pasos-docente .paso-item');

    pasos.forEach((paso, index) => {
        paso.classList.remove('active', 'completed');

        if (index + 1 < numeroPaso) {
            paso.classList.add('completed');
        } else if (index + 1 === numeroPaso) {
            paso.classList.add('active');
        }
    });
}

// Volver a la selección de tipo desde docente
function volverATipoDocente() {
    mostrarPaso('paso-tipo');
    document.querySelector('.pasos-docente').style.display = 'none';
    // Quitar selección de tipo
    document.querySelectorAll('.tipo-usuario-card').forEach(c => c.classList.remove('selected'));
    tipoUsuarioSeleccionado = null;
}

// Ir al paso 2 de docente (contraseña)
async function irAPasoDocente2() {
    // Validar paso 1 docente
    const nombre = document.getElementById('nombreDocente').value.trim();
    const apellido = document.getElementById('apellidoDocente').value.trim();
    const rut = document.getElementById('rutDocente').value.trim();
    const telefono = document.getElementById('telefonoDocente').value.trim();
    const correo = document.getElementById('correoDocente').value.trim();
    const confirmarCorreo = document.getElementById('confirmarCorreoDocente').value.trim();
    const establecimiento = document.getElementById('establecimientoDocente').value;

    if (!nombre || !apellido || !rut || !telefono || !correo || !confirmarCorreo || !establecimiento) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Validar formato de correo
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        alert('Por favor ingrese un correo electrónico válido');
        return;
    }

    // Validar que los correos coincidan
    if (correo !== confirmarCorreo) {
        alert('Los correos electrónicos no coinciden');
        return;
    }

    // Validar pre-registro del docente (RUT y correo)
    const validacion = await validarPreregistroDocente(rut, correo, establecimiento);

    if (!validacion.success) {
        // Registrar intento fallido
        const datosDocente = {
            rut: rut,
            nombres: nombre,
            apellidos: apellido,
            telefono: telefono,
            correo: correo
        };
        await registrarIntentoFallidoDocente(datosDocente, establecimiento);
        mostrarModalError(validacion.message);
        return;
    }

    mostrarPaso('paso-docente-2');
    actualizarIndicadorPasoDocente(2);
}

// Función para validar pre-registro de docente (RUT y correo)
async function validarPreregistroDocente(rutDocente, correoDocente, establecimientoId) {
    try {
        const response = await fetch('api/validar_preregistro_docente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                rut_docente: rutDocente,
                correo_docente: correoDocente,
                establecimiento_id: establecimientoId
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al validar pre-registro docente:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Volver al paso 1 de docente
function volverAPasoDocente1() {
    mostrarPaso('paso-docente-1');
    actualizarIndicadorPasoDocente(1);
}

// Finalizar registro de docente
async function finalizarRegistroDocente() {
    // Validar paso 2 docente
    const password = document.getElementById('passwordDocente').value;
    const confirmarPassword = document.getElementById('confirmarPasswordDocente').value;

    if (!password || !confirmarPassword) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Validar que sea exactamente 8 dígitos numéricos
    const passwordRegex = /^\d{8}$/;
    if (!passwordRegex.test(password)) {
        alert('La contraseña debe tener exactamente 8 dígitos numéricos');
        return;
    }

    if (password !== confirmarPassword) {
        alert('Las contraseñas no coinciden');
        return;
    }

    // Recopilar datos del docente
    const datosDocente = {
        docente_nombres: document.getElementById('nombreDocente').value.trim(),
        docente_apellidos: document.getElementById('apellidoDocente').value.trim(),
        docente_rut: document.getElementById('rutDocente').value.trim(),
        docente_telefono: document.getElementById('telefonoDocente').value.trim(),
        docente_correo: document.getElementById('correoDocente').value.trim(),
        establecimiento_id: document.getElementById('establecimientoDocente').value,
        password: password
    };

    // Enviar registro al servidor
    const resultado = await registrarDocente(datosDocente);

    if (resultado.success) {
        // Mostrar paso final (éxito)
        mostrarPaso('paso-final');
        // Ocultar indicador de pasos docente
        document.querySelector('.pasos-docente').style.display = 'none';
    } else {
        mostrarModalError(resultado.message);
    }
}

// Función para registrar docente en la base de datos
async function registrarDocente(datos) {
    try {
        const response = await fetch('api/registrar_docente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al registrar docente:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Función de autocompletado deshabilitada - datos se cargarán desde la base de datos
function completarPasoDocente1() {
    // Función deshabilitada - los datos se cargarán desde la base de datos
}

// ==================== FUNCIONES PARA REGISTRO DE ADMINISTRADOR ====================

// Actualizar el indicador de pasos para administrador
function actualizarIndicadorPasoAdmin(numeroPaso) {
    const pasos = document.querySelectorAll('.pasos-administrador .paso-item');

    pasos.forEach((paso, index) => {
        paso.classList.remove('active', 'completed');

        if (index + 1 < numeroPaso) {
            paso.classList.add('completed');
        } else if (index + 1 === numeroPaso) {
            paso.classList.add('active');
        }
    });
}

// Volver a la selección de tipo desde administrador
function volverATipoAdmin() {
    mostrarPaso('paso-tipo');
    document.querySelector('.pasos-administrador').style.display = 'none';
    // Quitar selección de tipo
    document.querySelectorAll('.tipo-usuario-card').forEach(c => c.classList.remove('selected'));
    tipoUsuarioSeleccionado = null;
}

// Ir al paso 2 de administrador (código y contraseña)
async function irAPasoAdmin2() {
    // Validar paso 1 administrador
    const nombre = document.getElementById('nombreAdmin').value.trim();
    const apellido = document.getElementById('apellidoAdmin').value.trim();
    const rut = document.getElementById('rutAdmin').value.trim();
    const telefono = document.getElementById('telefonoAdmin').value.trim();
    const correo = document.getElementById('correoAdmin').value.trim();
    const confirmarCorreo = document.getElementById('confirmarCorreoAdmin').value.trim();
    const establecimiento = document.getElementById('establecimientoAdmin').value;

    if (!nombre || !apellido || !rut || !telefono || !correo || !confirmarCorreo || !establecimiento) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Validar formato de correo
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        alert('Por favor ingrese un correo electrónico válido');
        return;
    }

    // Validar que los correos coincidan
    if (correo !== confirmarCorreo) {
        alert('Los correos electrónicos no coinciden');
        return;
    }

    // Validar pre-registro del administrador
    const validacion = await validarPreregistroAdmin(rut, establecimiento);

    if (!validacion.success) {
        // Registrar intento fallido
        const datosAdmin = {
            rut: rut,
            nombres: nombre,
            apellidos: apellido,
            telefono: telefono,
            correo: correo,
            codigoValidacion: ''
        };
        await registrarIntentoFallidoAdmin(datosAdmin, establecimiento);
        mostrarModalError(validacion.message);
        return;
    }

    mostrarPaso('paso-admin-2');
    actualizarIndicadorPasoAdmin(2);
}

// Función para validar pre-registro de administrador
async function validarPreregistroAdmin(rutAdmin, establecimientoId) {
    try {
        const response = await fetch('api/validar_preregistro_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                rut_admin: rutAdmin,
                establecimiento_id: establecimientoId
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al validar pre-registro admin:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Volver al paso 1 de administrador
function volverAPasoAdmin1() {
    mostrarPaso('paso-admin-1');
    actualizarIndicadorPasoAdmin(1);
}

// Finalizar registro de administrador
async function finalizarRegistroAdmin() {
    // Validar paso 2 administrador
    const codigoValidacion = document.getElementById('codigoValidacionAdmin').value.trim();
    const password = document.getElementById('passwordAdmin').value;
    const confirmarPassword = document.getElementById('confirmarPasswordAdmin').value;

    if (!codigoValidacion || !password || !confirmarPassword) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Validar que sea exactamente 8 dígitos numéricos
    const passwordRegex = /^\d{8}$/;
    if (!passwordRegex.test(password)) {
        alert('La contraseña debe tener exactamente 8 dígitos numéricos');
        return;
    }

    if (password !== confirmarPassword) {
        alert('Las contraseñas no coinciden');
        return;
    }

    // Obtener datos del administrador
    const establecimientoId = document.getElementById('establecimientoAdmin').value;
    const datosAdminIntento = {
        rut: document.getElementById('rutAdmin').value.trim(),
        nombres: document.getElementById('nombreAdmin').value.trim(),
        apellidos: document.getElementById('apellidoAdmin').value.trim(),
        telefono: document.getElementById('telefonoAdmin').value.trim(),
        correo: document.getElementById('correoAdmin').value.trim(),
        codigoValidacion: codigoValidacion
    };

    // Validar código de administrador
    const validacion = await validarCodigoAdmin(codigoValidacion, establecimientoId);

    if (!validacion.success) {
        // Registrar intento fallido
        await registrarIntentoFallidoAdmin(datosAdminIntento, establecimientoId);
        mostrarModalError(validacion.message);
        return;
    }

    // Recopilar datos para el registro
    const datosAdmin = {
        admin_nombres: document.getElementById('nombreAdmin').value.trim(),
        admin_apellidos: document.getElementById('apellidoAdmin').value.trim(),
        admin_rut: document.getElementById('rutAdmin').value.trim(),
        admin_telefono: document.getElementById('telefonoAdmin').value.trim(),
        admin_correo: document.getElementById('correoAdmin').value.trim(),
        codigo_validacion: codigoValidacion,
        establecimiento_id: establecimientoId,
        password: password
    };

    // Enviar registro al servidor
    const resultado = await registrarAdministrador(datosAdmin);

    if (resultado.success) {
        // Mostrar paso final (éxito)
        mostrarPaso('paso-final');
        // Ocultar indicador de pasos administrador
        document.querySelector('.pasos-administrador').style.display = 'none';
    } else {
        mostrarModalError(resultado.message);
    }
}

// Función para registrar administrador en la base de datos
async function registrarAdministrador(datos) {
    try {
        const response = await fetch('api/registrar_administrador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al registrar administrador:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Función para validar código de administrador
async function validarCodigoAdmin(codigo, establecimientoId) {
    try {
        const response = await fetch('api/validar_codigo_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                codigo: codigo,
                establecimiento_id: establecimientoId
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error al validar código:', error);
        return {
            success: false,
            message: 'Error de conexión. Por favor intente nuevamente.'
        };
    }
}

// Función de autocompletado deshabilitada - datos se cargarán desde la base de datos
function completarPasoAdmin1() {
    // Función deshabilitada - los datos se cargarán desde la base de datos
}

// ==================== MODAL DE INFORMACIÓN ====================

// Textos de información para cada tipo de modal
const textosInfo = {
    codigoValidacion: 'Este código deberá ser entregado por Portal Estudiantil previa solicitud del administrador del establecimiento. Ante cualquier duda nos puede contactar por nuestros canales de comunicación y le atenderemos a la brevedad.'
};

// Mostrar modal de información
function mostrarModalInfo(tipo) {
    const modal = document.getElementById('modalInfo');
    const texto = document.getElementById('modalInfoTexto');

    if (textosInfo[tipo]) {
        texto.textContent = textosInfo[tipo];
        modal.classList.add('active');
    }
}

// Cerrar modal de información
function cerrarModalInfo(event) {
    // Si se pasa un evento, verificar que se hizo clic en el overlay
    if (event && event.target !== event.currentTarget) {
        return;
    }

    const modal = document.getElementById('modalInfo');
    modal.classList.remove('active');
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalInfo();
    }
});

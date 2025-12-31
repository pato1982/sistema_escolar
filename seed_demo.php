<?php
// seed_demo.php
// Script para poblar la base de datos con datos de demostración
// Ejecutar via CLI o navegador
header('Content-Type: text/plain');

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

echo "Conexión exitosa. Iniciando poblado de datos demo...\n";

// Desactivar FK checks para truncar
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tables = [
    'tb_notas',
    'tb_asistencia',
    'tb_comunicado_leido',
    'tb_comunicado_curso',
    'tb_comunicados',
    'tb_apoderado_alumno',
    'tb_alumnos',
    'tb_asignaciones',
    'tb_docente_asignatura',
    'tb_cursos',
    'tb_asignaturas',
    'tb_apoderados',
    'tb_docentes',
    'tb_administradores',
    'tb_usuarios',
    'tb_establecimientos'
];

foreach ($tables as $table) {
    // Verificar si la tabla existe antes de truncar
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        if ($conn->query("TRUNCATE TABLE $table")) {
            echo "Tabla $table limpiada.\n";
        } else {
            echo "Error limpiando $table: " . $conn->error . "\n";
        }
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Crear Establecimiento
$sql = "INSERT INTO tb_establecimientos (nombre, direccion, ciudad, region, email, activo) VALUES 
('Colegio Demo Excelencia', 'Av. Siempreviva 742', 'Santiago', 'Metropolitana', 'contacto@demo.cl', 1)";
if (!$conn->query($sql))
    die("Error creando establecimiento: " . $conn->error);
$est_id = $conn->insert_id;
echo "Establecimiento creado (ID: $est_id)\n";

// Definir año académico actual para usar en todo el script
$anio = date('Y');

// Hash común para todos: 123456
$pass_hash = password_hash('123456', PASSWORD_DEFAULT);

// 2. Crear Usuarios y Perfiles

// ADMIN
$sql = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id, activo) VALUES 
('admin@demo.cl', '$pass_hash', 'administrador', $est_id, 1)";
$conn->query($sql);
$user_admin_id = $conn->insert_id;
$sql = "INSERT INTO tb_administradores (usuario_id, nombres, apellidos, rut, establecimiento_id, activo) VALUES 
($user_admin_id, 'Admin', 'Principal', '11.111.111-1', $est_id, 1)";
$conn->query($sql);
echo "Usuario Admin creado: admin@demo.cl / 123456\n";

// DOCENTE
$sql = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id, activo) VALUES 
('docente@demo.cl', '$pass_hash', 'docente', $est_id, 1)";
$conn->query($sql);
$user_docente_id = $conn->insert_id;
$sql = "INSERT INTO tb_docentes (usuario_id, nombres, apellidos, rut, email, establecimiento_id, activo) VALUES 
($user_docente_id, 'Profesor', 'Jirafales', '22.222.222-2', 'docente@demo.cl', $est_id, 1)";
$conn->query($sql);
$docente_id = $conn->insert_id; // ID de la tabla docente
echo "Usuario Docente creado: docente@demo.cl / 123456\n";

// APODERADO
$sql = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id, activo) VALUES 
('apoderado@demo.cl', '$pass_hash', 'apoderado', $est_id, 1)";
$conn->query($sql);
$user_apoderado_id = $conn->insert_id;
$sql = "INSERT INTO tb_apoderados (usuario_id, nombres, apellidos, rut, direccion, establecimiento_id, activo) VALUES 
($user_apoderado_id, 'Papá', 'Modelo', '33.333.333-3', 'Calle Falsa 123', $est_id, 1)";
$conn->query($sql);
$apoderado_id = $conn->insert_id;
echo "Usuario Apoderado creado: apoderado@demo.cl / 123456\n";

// 3. Crear Datos Académicos

// CURSO
$sql = "INSERT INTO tb_cursos (nombre, codigo, nivel, establecimiento_id) VALUES 
('1° Medio A', '1MA', 'Media', $est_id)";
$conn->query($sql);
$curso_id = $conn->insert_id;

// Definir asignaturas y docentes extra
$asignaturas_data = [
    [
        'nombre' => 'Matemáticas',
        'codigo' => 'MAT1',
        'docente_ex' => true, // Usar el docente demo ya creado
        'docente_id' => $docente_id
    ],
    [
        'nombre' => 'Lenguaje y Comunicación',
        'codigo' => 'LEN1',
        'docente_ex' => false,
        'docente_nombre' => 'Gabriela',
        'docente_apellido' => 'Mistral',
        'docente_rut' => '33.333.333-K',
        'docente_email' => 'lenguaje@demo.cl'
    ],
    [
        'nombre' => 'Historia y Geografía',
        'codigo' => 'HIS1',
        'docente_ex' => false,
        'docente_nombre' => 'Arturo',
        'docente_apellido' => 'Prat',
        'docente_rut' => '44.444.444-4',
        'docente_email' => 'historia@demo.cl'
    ],
    [
        'nombre' => 'Ciencias Naturales',
        'codigo' => 'CIE1',
        'docente_ex' => false,
        'docente_nombre' => 'Albert',
        'docente_apellido' => 'Einstein',
        'docente_rut' => '55.555.555-5',
        'docente_email' => 'ciencias@demo.cl'
    ]
];

$asignaturas_creadas = []; // Para guardar IDs y usarlos en notas

foreach ($asignaturas_data as $asig) {
    // 1. Crear Asignatura
    $sql = "INSERT INTO tb_asignaturas (nombre, codigo, establecimiento_id) VALUES 
    ('{$asig['nombre']}', '{$asig['codigo']}', $est_id)";
    $conn->query($sql);
    $asig_id = $conn->insert_id;

    // 2. Resolver Docente
    $doc_id_final = 0;
    if ($asig['docente_ex']) {
        $doc_id_final = $asig['docente_id'];
    } else {
        // Crear Usuario Docente
        $email = $asig['docente_email'];
        $sql = "INSERT INTO tb_usuarios (email, password_hash, tipo_usuario, establecimiento_id, activo) VALUES 
        ('$email', '$pass_hash', 'docente', $est_id, 1)";
        $conn->query($sql);
        $u_id = $conn->insert_id;

        // Crear Registro Docente
        $sql = "INSERT INTO tb_docentes (usuario_id, nombres, apellidos, rut, email, establecimiento_id, activo) VALUES 
        ($u_id, '{$asig['docente_nombre']}', '{$asig['docente_apellido']}', '{$asig['docente_rut']}', '$email', $est_id, 1)";
        $conn->query($sql);
        $doc_id_final = $conn->insert_id;
        echo "Docente creado: $email\n";
    }

    // 3. Asignar Docente a Asignatura
    $sql = "INSERT INTO tb_docente_asignatura (docente_id, asignatura_id) VALUES ($doc_id_final, $asig_id)";
    $conn->query($sql);

    // 4. Asignar al Curso (Asignación)
    $sql = "INSERT INTO tb_asignaciones (docente_id, curso_id, asignatura_id, anio_academico, establecimiento_id) VALUES 
    ($doc_id_final, $curso_id, $asig_id, $anio, $est_id)";
    $conn->query($sql);

    // Guardar para loop de notas
    $asignaturas_creadas[] = ['id' => $asig_id, 'docente_id' => $doc_id_final];
}


// 5. Crear Lista de Alumnos y Notas
$lista_alumnos = [
    ['Juan', 'Pérez', '22.000.001-1', '2010-03-10'],
    ['María', 'González', '22.000.002-K', '2010-07-22'],
    ['Carlos', 'Rodríguez', '22.000.003-8', '2010-01-05'],
    ['Ana', 'López', '22.000.004-6', '2010-11-30'],
    ['Sofía', 'Martínez', '22.000.005-4', '2010-09-12'],
    ['Lucas', 'Fernández', '22.000.006-2', '2010-04-18'],
    ['Valentina', 'Soto', '22.000.007-0', '2010-05-25']
];

echo "Creando alumnos y asignando notas...\n";

foreach ($lista_alumnos as $idx => $datos_alumno) {
    // Insertar Alumno
    $sql = "INSERT INTO tb_alumnos (nombres, apellidos, rut, fecha_nacimiento, curso_id, establecimiento_id) VALUES 
    ('{$datos_alumno[0]}', '{$datos_alumno[1]}', '{$datos_alumno[2]}', '{$datos_alumno[3]}', $curso_id, $est_id)";

    if ($conn->query($sql)) {
        $nuevo_alumno_id = $conn->insert_id;

        // Si es el primer alumno (Juan Pérez), lo asignamos al Apoderado Demo
        if ($idx === 0) {
            $sql = "INSERT INTO tb_apoderado_alumno (apoderado_id, alumno_id, parentesco, es_titular) VALUES 
            ($apoderado_id, $nuevo_alumno_id, 'Padre', 1)";
            $conn->query($sql);
        }

        // --- BUCLE DE NOTAS POR ASIGNATURA ---
        foreach ($asignaturas_creadas as $asig_data) {
            $current_asig_id = $asig_data['id'];
            $current_doc_id = $asig_data['docente_id'];

            // Asignar Notas Variadas para 3 Trimestres
            for ($trimestre = 1; $trimestre <= 3; $trimestre++) {
                // Generar entre 2 y 4 notas por trimestre
                $num_notas = rand(2, 4);

                for ($n = 1; $n <= $num_notas; $n++) {
                    // Generar nota aleatoria entre 3.0 y 7.0
                    $nota = mt_rand(30, 70) / 10;
                    $tipo_ev = ($n % 2 == 0) ? "Trabajo" : "Prueba";

                    // Generar fecha según trimestre
                    $mes_inicio = ($trimestre - 1) * 3 + 3; // T1=3 (Mar), T2=6 (Jun), T3=9 (Sep)
                    $mes_rand = rand($mes_inicio, $mes_inicio + 2);
                    if ($mes_rand > 12)
                        $mes_rand = 12; // Seguridad
                    $dia_rand = rand(1, 28);
                    $fecha_eval = sprintf("%s-%02d-%02d", $anio, $mes_rand, $dia_rand);

                    $sql_nota = "INSERT INTO tb_notas (alumno_id, asignatura_id, curso_id, docente_id, nota, tipo_evaluacion, trimestre, anio_academico, establecimiento_id, fecha_evaluacion) VALUES 
                    ($nuevo_alumno_id, $current_asig_id, $curso_id, $current_doc_id, $nota, '$tipo_ev $n', $trimestre, $anio, $est_id, '$fecha_eval')";
                    $conn->query($sql_nota);
                }
            }
        }
    }
}

// 6. Crear Comunicados de Ejemplo
echo "Creando comunicados de prueba...\n";

// Comunicado 1: Global (Para todos) - Simulado hace 5 días
$titulo = "Bienvenida al Año Escolar " . $anio;
$mensaje = "Estimados apoderados, les damos la más cordial bienvenida a este nuevo ciclo académico. Esperamos contar con su apoyo y compromiso en el proceso educativo de sus pupilos.";
$fecha = date('Y-m-d H:i:s', strtotime('-5 days'));
$sql = "INSERT INTO tb_comunicados (titulo, mensaje, tipo, remitente_id, establecimiento_id, para_todos_cursos, fecha_envio) VALUES 
('$titulo', '$mensaje', 'informativo', $user_admin_id, $est_id, 1, '$fecha')";
$conn->query($sql);

// Comunicado 2: Específico para el curso (1° Medio A) - Simulado hoy
$titulo = "Reunión de Apoderados 1° Medio A";
$mensaje = "Se cita a la primera reunión de apoderados del curso para este viernes a las 19:00 hrs. Tabla: Elección de directiva y presentación de profesores.";
$fecha = date('Y-m-d H:i:s');
$sql = "INSERT INTO tb_comunicados (titulo, mensaje, tipo, remitente_id, establecimiento_id, para_todos_cursos, fecha_envio) VALUES 
('$titulo', '$mensaje', 'reunion', $user_admin_id, $est_id, 0, '$fecha')";

if ($conn->query($sql)) {
    $com_id = $conn->insert_id;
    // Vincular al curso específico
    $sql = "INSERT INTO tb_comunicado_curso (comunicado_id, curso_id) VALUES ($com_id, $curso_id)";
    $conn->query($sql);
}

echo "Datos académicos creados exitosamente.\n";
echo "--- PROCESO TERMINADO ---";
?>
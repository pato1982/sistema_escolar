<?php
// adjuntar_notas_matematicas.php
// Script para agregar 2 notas adicionales por trimestre a todos los alumnos en Matemáticas

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

echo "Conexión exitosa. Iniciando carga de notas adicionales para Matemáticas...\n";

// 1. Obtener ID de la asignatura Matemáticas (segun seed_demo es la primera)
$res_asig = $conn->query("SELECT id FROM tb_asignaturas WHERE nombre = 'Matemáticas' LIMIT 1");
if ($res_asig->num_rows === 0) {
    die("Error: No se encontró la asignatura 'Matemáticas'\n");
}
$asig_id = $res_asig->fetch_assoc()['id'];

// 2. Obtener todos los alumnos
$res_alumnos = $conn->query("SELECT id, curso_id, establecimiento_id FROM tb_alumnos");
if ($res_alumnos->num_rows === 0) {
    die("Error: No se encontraron alumnos para poblar\n");
}

$anio = date('Y');
$count = 0;

while ($alumno = $res_alumnos->fetch_assoc()) {
    $alumno_id = $alumno['id'];
    $curso_id = $alumno['curso_id'];
    $est_id = $alumno['establecimiento_id'];

    // 3. Obtener el docente asignado a esta asignatura y curso
    $res_doc = $conn->query("SELECT docente_id FROM tb_asignaciones WHERE curso_id = $curso_id AND asignatura_id = $asig_id LIMIT 1");
    if ($res_doc->num_rows === 0)
        continue;
    $docente_id = $res_doc->fetch_assoc()['docente_id'];

    // 4. Agregar 2 notas por cada uno de los 3 trimestres
    for ($trimestre = 1; $trimestre <= 3; $trimestre++) {
        for ($i = 1; $i <= 2; $i++) {
            $nota = mt_rand(35, 70) / 10; // Notas entre 3.5 y 7.0

            // Definir mes según trimestre (T1: 03-05, T2: 06-08, T3: 09-11)
            $mes_base = ($trimestre - 1) * 3 + 3;
            $mes = rand($mes_base, $mes_base + 2);
            $dia = rand(1, 28);
            $fecha_eval = sprintf("%s-%02d-%02d", $anio, $mes, $dia);

            $tipo_eval = "Control Extra $i";

            $sql = "INSERT INTO tb_notas (alumno_id, asignatura_id, curso_id, docente_id, nota, tipo_evaluacion, trimestre, anio_academico, establecimiento_id, fecha_evaluacion) 
                    VALUES ($alumno_id, $asig_id, $curso_id, $docente_id, $nota, '$tipo_eval', $trimestre, $anio, $est_id, '$fecha_eval')";

            if ($conn->query($sql)) {
                $count++;
            } else {
                echo "Error insertando nota: " . $conn->error . "\n";
            }
        }
    }
}

echo "Proceso terminado. Se agregaron $count nuevas notas de Matemáticas.\n";
$conn->close();
?>
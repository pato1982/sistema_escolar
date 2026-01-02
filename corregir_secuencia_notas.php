<?php
// corregir_secuencia_notas.php
// Script para re-numerar las evaluaciones (N1, N2, etc) correctamente en la base de datos

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

echo "Conexión exitosa. Corrigiendo secuencia de evaluaciones...\n";

// Obtener todas las notas agrupadas por alumno, asignatura y trimestre
$sql = "SELECT id, alumno_id, asignatura_id, trimestre FROM tb_notas ORDER BY alumno_id, asignatura_id, trimestre, id";
$result = $conn->query($sql);

$current_alumno = 0;
$current_asig = 0;
$current_trimestre = 0;
$num_eval = 0;
$updates = 0;

while ($row = $result->fetch_assoc()) {
    if ($current_alumno != $row['alumno_id'] || $current_asig != $row['asignatura_id'] || $current_trimestre != $row['trimestre']) {
        $current_alumno = $row['alumno_id'];
        $current_asig = $row['asignatura_id'];
        $current_trimestre = $row['trimestre'];
        $num_eval = 1;
    } else {
        $num_eval++;
    }

    $id = $row['id'];
    $conn->query("UPDATE tb_notas SET numero_evaluacion = $num_eval WHERE id = $id");
    $updates++;
}

echo "Proceso terminado. Se corrigieron $updates registros.\n";
$conn->close();
?>
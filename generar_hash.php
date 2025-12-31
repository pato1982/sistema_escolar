<?php
// Script temporal para generar hash de contraseña
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generado para '123456':\n";
echo $hash . "\n\n";

// Conectar a la base de datos y actualizar
$host = "190.114.252.5";
$port = 3306;
$user = "root";
$password_db = "vpsroot123";
$database = "portal_estudiantil";

$conn = new mysqli($host, $user, $password_db, $database, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Actualizar todos los usuarios del establecimiento 1
$sql = "UPDATE tb_usuarios SET password_hash = ? WHERE establecimiento_id = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "Contraseñas actualizadas correctamente.\n";
    echo "Usuarios afectados: " . $stmt->affected_rows . "\n\n";

    // Mostrar los usuarios
    $result = $conn->query("SELECT id, email, tipo_usuario FROM tb_usuarios WHERE establecimiento_id = 1");
    echo "Usuarios disponibles:\n";
    echo "----------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "Email: " . $row['email'] . " | Tipo: " . $row['tipo_usuario'] . "\n";
    }
    echo "----------------------------------------\n";
    echo "\nTodos pueden ingresar con contraseña: 123456\n";
} else {
    echo "Error al actualizar: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>

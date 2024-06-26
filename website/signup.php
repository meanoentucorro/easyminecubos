<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../inc/dbinfo.inc";

// Crear conexión
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Comprobar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Inicializa las variables
$nombre = $pass = $confirmar_pass = "";
$error = "";

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validación de campos
    $nombre         = $_POST["username"];
    $email          = $_POST["email"];
    $pass           = $_POST["password"];
    $confirmar_pass = $_POST["confirm_password"];

    // Validar si el usuario ya existe
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE user=?");
    if (!$stmt) {
        die("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "El nombre de usuario ya está en uso.";
    } elseif ($pass !== $confirmar_pass) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Insertar el nuevo usuario en la base de datos
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT); // Hash de la contraseña
        $fecha_registro = date('Y-m-d H:i:s'); // Fecha y hora actual
        $sql_insert = "INSERT INTO usuario (user, pass, email, fecha_registro) VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql_insert);
        if (!$stmt) {
            die("Error en prepare: " . $conn->error);
        }
        $stmt->bind_param("ssss", $nombre, $hashed_password, $email, $fecha_registro);

        if ($stmt->execute()) {
            // Registro exitoso.
            header("Location: login.php");
            exit();
        } else {
            $error = "Error al registrar el usuario: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>EasyMinecubos - Signup</title>
    <link rel='stylesheet' href='styles.css'>
</head>
<body>
  <div class='container'>
    <img src='assets/easy-minecubos.png' alt='Título de la Página'>
      <form class='signup-form' action='signup.php' method='POST'>
        <h2>Crear una cuenta</h2>
        <input type='text' name='username' placeholder='Nombre de usuario' required>
        <input type='email' name='email' placeholder='Correo electrónico' required>
        <input type='password' name='password' placeholder='Contraseña' required>
        <input type='password' name='confirm_password' placeholder='Confirmar contraseña' required><br>
        <span style="color: red;"><?php echo htmlspecialchars($error); ?></span><br> <!-- Muestra el mensaje de error -->
        <button type='submit' class='minecraft-button'>Registrarse</button>
      </form>
      <a href='index.html' class='back-to-home'>Volver a la página de inicio</a>
  </div>
</body>
</html>

<?php
// Cierra la conexión
$conn->close();
?>

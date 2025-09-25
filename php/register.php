<?php
session_start();
include("config.php");

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Verificar si el email ya está registrado
    $query = "SELECT user_id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $mensaje = "<p class='error'>⚠️ El correo ya está registrado.</p>";
    } else {
        // Datos por defecto
        $rol = "cliente";
        $puntos = 0;
        $fecha_registro = date("Y-m-d H:i:s");

        // Insertar nuevo usuario
        $insert = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol, puntos, fecha_registro) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssis", $nombre, $email, $password, $rol, $puntos, $fecha_registro);

        if ($insert->execute()) {
            $_SESSION['usuario_id'] = $insert->insert_id;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['rol'] = "cliente";

            header("Location: ./cliente/home_cliente.php");
            exit();
        } else {
            $mensaje = "<p class='error'>⚠️ Error al registrarse. Intenta de nuevo.</p>";
        }

        $insert->close();
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <title>Padel Alquiler | Registro</title>
    <style>
        * {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #054a56ff, #1bab9dff);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-container img {
            width: 160px;
        }

        .login-box {
            width: 380px;
            background: white;
            padding: 35px 30px;
            border-radius: 16px;
            box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 1.7rem;
            color: #054a56;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 42px 15px 15px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            background-color: #f9f9f9;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: #1bab9dff;
            outline: none;
            background-color: #fff;
        }

        .input-group svg {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            fill: #999;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: #1bab9dff;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #14897f;
        }

        .extra-links {
            margin-top: 18px;
            font-size: 14px;
        }

        .extra-links a {
            color: #1bab9dff;
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo Padel">
    </div>

    <div class="login-box">
        <h1>Registro de usuario</h1>

        <?= $mensaje ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4zm0-2a4 4 0 1 0-4-4 4 4 0 0 0 4 4z" />
                </svg>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Correo electrónico" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 13.065 0 6V4l12 7 12-7v2l-12 7.065z" />
                </svg>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Contraseña" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M17 9V7a5 5 0 0 0-10 0v2H5v14h14V9h-2zm-6-2a3 3 0 0 1 6 0v2H11V7z" />
                </svg>
            </div>

            <button type="submit" class="btn">Registrarme</button>
        </form>

        <div class="extra-links">
            <a href="login.php">¿Ya tienes una cuenta? Inicia sesión</a>
        </div>
    </div>
</body>

</html>
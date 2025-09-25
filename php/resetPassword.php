<?php
session_start();
include("config.php");

if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    header("Location: forgot.php");
    exit();
}

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = trim($_POST['password']);
    $confirmar = trim($_POST['confirmar']);

    if (strlen($password) < 6) {
        $mensaje = "<p class='error'>⚠️ La contraseña debe tener al menos 6 caracteres.</p>";
    } elseif ($password !== $confirmar) {
        $mensaje = "<p class='error'>⚠️ Las contraseñas no coinciden.</p>";
    } else {
        $user_id = $_SESSION['reset_user'];
        $reset_id = $_SESSION['reset_id'];

        // ⚠️ Guardar contraseña en texto plano (SOLO PARA TESTING)
        $update = $conn->prepare("UPDATE usuarios SET contrasenia = ? WHERE user_id = ?");
        $update->bind_param("si", $password, $user_id);
        $update->execute();
        $update->close();

        // Marcar código como usado
        $mark = $conn->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?");
        $mark->bind_param("i", $reset_id);
        $mark->execute();
        $mark->close();

        // Limpiar variables de sesión
        session_unset();
        session_destroy();

        header("Location: login.php?reset=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <style>
        /* Mismos estilos de forgot.php y verifyCode.php */
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
            width: 350px;
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

        .error {
            color: #b80000;
            margin-bottom: 12px;
            font-size: 14px;
            background: #ffe6e6;
            padding: 8px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo">
    </div>
    <div class="login-box">
        <h1>Restablecer Contraseña</h1>
        <?= $mensaje ?>
        <form method="POST">
            <div class="input-group">
                <input type="password" name="password" placeholder="Nueva contraseña" required>
            </div>
            <div class="input-group">
                <input type="password" name="confirmar" placeholder="Confirmar contraseña" required>
            </div>
            <button type="submit" class="btn">Guardar contraseña</button>
        </form>
        <div class="extra-links">
            <a href="login.php">Volver al login</a>
        </div>
    </div>
</body>
</html>

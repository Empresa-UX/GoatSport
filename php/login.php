<?php
session_start();
include("config.php");

if (isset($_SESSION['usuario_id'])) {
    header("Location: home.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT user_id, email, contrasenia, rol FROM usuarios WHERE email = ?";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $user_email, $user_password, $rol);
            mysqli_stmt_fetch($stmt);

            if ($password === $user_password) {
                $_SESSION['usuario_id'] = $user_id;
                $_SESSION['usuario_email'] = $user_email;
                $_SESSION['rol'] = $rol;

                if (isset($_POST['checkbox'])) {
                    setcookie("usuario_id", $user_id, time() + (86400 * 30), "/");
                    setcookie("usuario_email", $user_email, time() + (86400 * 30), "/");
                }

                if ($rol === 'admin') {
                    header("Location: ./admin/home_admin.php");
                } elseif ($rol === 'proveedor') {
                    header("Location: ./proveedor/home_proveedor.php");
                } else {
                    header("Location: ./cliente/home_cliente.php");
                }
                exit();
            } else {
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padel Alquiler | Login</title>
    <style>
        * {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to bottom, #054a56ff, #1bab9dff);
        }

        .login-box {
            width: 350px;
            text-align: center;
            color: white;
        }

        .login-box img.logo {
            width: 90px;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: #023e8a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 15px 0;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #0077b6;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #fff;
            margin: 20px 0;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
        }

        .divider:not(:empty)::before {
            margin-right: .75em;
        }

        .divider:not(:empty)::after {
            margin-left: .75em;
        }

        .google-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #fff;
            color: #444;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .google-btn img {
            width: 18px;
            height: 18px;
        }

        .extra-links {
            margin-top: 20px;
            font-size: 13px;
            color: #fff;
        }

        .extra-links a {
            color: #fff;
            font-weight: bold;
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        .error {
            color: #ffcccc;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <img src="../img/logo_padel.png" alt="Logo Padel" class="logo">

        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" id="email" placeholder="Correo electrónico" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
            </div>

            <button type="submit" class="btn">Ingresar</button>

            <div class="divider">o</div>

            <button type="button" class="google-btn">
                <img src="../img/google-icon-authentication.png" alt="Google"> Continuar con Google
            </button>

            <div class="extra-links">
                <p>¿No tenés cuenta? <a href="register.php">Registrarse</a></p>
            </div>
        </form>
    </div>
</body>

</html>
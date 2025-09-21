<?php
session_start();
include("config.php");

// Si ya está logueado, lo mandamos a su home según el rol
if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: /php/admin/home_admin.php");
            break;
        case 'cliente':
            header("Location: /php/cliente/home_cliente.php");
            break;
        default:
            header("Location: /php/proveedor/home_cliente.php");
            break;
    }
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
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
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
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #054a56ff, #1bab9dff);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 10px;
            /* 👈 Más espacio para que suba un poco */
        }

        .logo-container img {
            width: 160px;
        }

        .login-box {
            width: 350px;
            /* 👈 Más ancho */
            height: 400px;
            background: white;
            padding: 35px 30px;
            border-radius: 16px;
            /* 👈 Bordes más suaves */
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

    <!-- Logo más arriba -->
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo Padel">
    </div>

    <div class="login-box">
        <h1>Iniciar Sesión</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
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

            <button type="submit" class="btn">Ingresar</button>
        </form>

        <div class="extra-links">
            <a href="#">¿Olvidaste tu contraseña?</a>
        </div>
    </div>

</body>

</html>
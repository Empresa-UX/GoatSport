<?php
session_start();
include("config.php");

if (!isset($_SESSION['reset_user'])) {
    header("Location: forgot.php");
    exit();
}

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST['codigo']);
    $user_id = $_SESSION['reset_user'];

    $conn->query("DELETE FROM password_resets WHERE expira < NOW()");

    $query = "SELECT id, expira, usado FROM password_resets 
              WHERE user_id = ? AND codigo = ? 
              ORDER BY creado DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $codigo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($reset_id, $expira, $usado);
        $stmt->fetch();

        if ((int)$usado === 1) {
            $mensaje = "<p class='error'>⚠️ Este código ya fue usado.</p>";
        } elseif (strtotime($expira) < time()) {
            $mensaje = "<p class='error'>⚠️ El código expiró.</p>";
        } else {
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_id'] = $reset_id;
            header("Location: resetPassword.php");
            exit();
        }
    } else {
        $mensaje = "<p class='error'>⚠️ Código inválido.</p>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código</title>
    <link rel="icon" type="image/png" href="/img/logotipo.png">
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
            text-align: center;
            letter-spacing: 2px;
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
        <h1>Verificar Código</h1>
        <?= $mensaje ?>
        <form method="POST">
            <div class="input-group">
                <input type="text" name="codigo" placeholder="Código recibido" required>
            </div>
            <button type="submit" class="btn">Verificar</button>
        </form>
        <div class="extra-links">
            <a href="forgot.php">Reenviar código</a>
        </div>
    </div>
</body>
</html>

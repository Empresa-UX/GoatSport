<?php
session_start();
include("config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $query = "SELECT user_id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();

        // Generar código y guardar en DB
        $codigo = rand(100000, 999999);
        $expira = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        $insert = $conn->prepare(
            "INSERT INTO password_resets (user_id, codigo, expira) VALUES (?, ?, ?)"
        );
        $insert->bind_param("iss", $user_id, $codigo, $expira);
        $insert->execute();
        $insert->close();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'goatsportsoporte2025@gmail.com';
            $mail->Password = 'renl iuxw gwtf qmut';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('goatsportsoporte2025@gmail.com', 'Goat Sport Soporte');
            $mail->addAddress(address: $email);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña';
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; background-color: #e0f2f1; padding: 40px;'>
                <div style='max-width: 600px; margin: auto; background: #ffffff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); padding: 30px; text-align: center;'>
                    <!-- Logo -->
                    <img src='https://i.postimg.cc/tChXrCQX/goatsport.jpg' alt='GoatSport' style='width: 180px; margin-bottom: 20px;'>

                    <!-- Título -->
                    <h1 style='color: #054a56; font-size: 28px; margin-bottom: 20px;'>Recuperación de Contraseña</h1>

                    <!-- Mensaje -->
                    <p style='color: #333; font-size: 16px; margin-bottom: 30px;'>
                        Has solicitado recuperar tu contraseña en <strong>GoatSport</strong>. Utiliza el siguiente código para restablecerla.
                    </p>

                    <!-- Código -->
                    <p style='font-size: 32px; font-weight: bold; color: #1bab9dff; margin: 20px 0; letter-spacing: 2px;'>$codigo</p>

                    <!-- Advertencias -->
                    <p style='font-size: 14px; color: #666; line-height: 1.5;'>
                        ⚠ Este código expirará en 10 minutos.<br>
                        ⚠ GoatSport no se hace responsable de accesos no autorizados.<br>
                        ⚠ Si no solicitaste este cambio, ignora este correo.
                    </p>

                    <!-- Botón opcional -->
                    <a href='#' style='display: inline-block; margin-top: 25px; padding: 12px 25px; background-color: #1bab9dff; color: #fff; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir a GoatSport</a>

                    <!-- Pie de página -->
                    <p style='margin-top: 30px; font-size: 12px; color: #999;'>Este es un correo automático, por favor no respondas.</p>
                </div>
            </div>
            ";
            $mail->send();

            $_SESSION['reset_user'] = $user_id;
            header("Location: verifyCode.php");
            exit();

        } catch (Exception $e) {
            $mensaje = "<p class='error'>No se pudo enviar el correo. Error: {$mail->ErrorInfo}</p>";
        }

    } else {
        $mensaje = "<p class='error'>⚠️ El correo no está registrado.</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <title>Padel Alquiler | Recuperar Contraseña</title>
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
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo Padel">
    </div>

    <div class="login-box">
        <h1>Recuperar Contraseña</h1>

        <?= $mensaje ?>

        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Introduce tu correo" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 13.065 0 6V4l12 7 12-7v2l-12 7.065z" />
                </svg>
            </div>
            <button type="submit" class="btn">Enviar código</button>
        </form>

        <div class="extra-links">
            <a href="login.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>

</html>
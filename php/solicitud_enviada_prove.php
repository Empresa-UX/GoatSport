<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Solicitud enviada</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/jpeg" href="/img/logotipo_negro.jpeg">

    <style>
        /* ======== ESTILO GENERAL ======== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #06606d, #23b9aa);
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 25px;
        }

        /* ======== LOGO SUPERIOR ======== */
        .logo-large {
            width: 160px;
            margin-bottom: 20px;
        }

        /* ======== CAJA DE MENSAJE ======== */
        .box {
            background: #ffffff;
            padding: 40px;
            width: 500px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0px 12px 28px rgba(0, 0, 0, 0.18);
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .box h2 {
            color: #054a56;
            font-size: 1.9rem;
            margin-bottom: 15px;
        }

        .box p {
            color: #075a60;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .voler_log {
            margin-top: 18px;
            font-size: 14px;
            text-align: center;
        }

        .volver_log a {
            color: #1bab9dff;
            text-decoration: none;
        }

        .volver_log a:hover {
            text-decoration: underline;
        }
    </style>

</head>

<body>

    <!-- LOGO SUPERIOR -->
    <img src="/img/logotipo.png" class="logo-large" alt="Logo">

    <div class="box">
        <h2>Solicitud enviada</h2>
        <p>Tu solicitud ha sido recibida correctamente.</p>
        <p>Un administrador revisará la información y te enviaremos un correo con la respuesta.</p>
        <p></p>
        <div class="volver_log">
            <a href="login.php">Volver a ingreso</a>
        </div>
    </div>

</body>

</html>
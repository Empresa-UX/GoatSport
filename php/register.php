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

        // INICIAR TRANSACCIÓN para asegurar que todo se guarde o nada
        $conn->begin_transaction();
        
        try {
            // 1. Insertar nuevo usuario en tabla usuarios
            $insert = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol, fecha_registro) 
                                      VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $nombre, $email, $password, $rol, $fecha_registro);
            
            if (!$insert->execute()) {
                throw new Exception("Error al insertar en usuarios: " . $insert->error);
            }
            
            // Obtener el ID del usuario recién creado
            $nuevo_user_id = $insert->insert_id;
            $insert->close();
            
            // 2. Insertar registro en cliente_detalle (solo con cliente_id, otros campos NULL)
            $insert_cliente = $conn->prepare("INSERT INTO cliente_detalle (cliente_id) VALUES (?)");
            $insert_cliente->bind_param("i", $nuevo_user_id);
            
            if (!$insert_cliente->execute()) {
                throw new Exception("Error al insertar en cliente_detalle: " . $insert_cliente->error);
            }
            $insert_cliente->close();
            
            // 3. Insertar registro inicial en ranking (todos en 0)
            // CORREGIDO: Usar 'derrotas' en lugar de 'derrotas_'
            $insert_ranking = $conn->prepare("INSERT INTO ranking (usuario_id, puntos, partidos, victorias, derrotas) 
                                             VALUES (?, 0, 0, 0, 0)");
            $insert_ranking->bind_param("i", $nuevo_user_id);
            
            if (!$insert_ranking->execute()) {
                throw new Exception("Error al insertar en ranking: " . $insert_ranking->error);
            }
            $insert_ranking->close();
            
            // 4. CONFIRMAR TODAS LAS INSERCIONES
            $conn->commit();
            
            // Configurar sesión
            $_SESSION['usuario_id'] = $nuevo_user_id;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['rol'] = "cliente";

            header("Location: ./cliente/home_cliente.php");
            exit();
            
        } catch (Exception $e) {
            // 5. Si algo falla, REVERTIR todo
            $conn->rollback();
            $mensaje = "<p class='error'>⚠️ Error en el registro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
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
        
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
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
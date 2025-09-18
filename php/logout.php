<?php
session_start();

session_unset();

session_destroy();

if (isset($_COOKIE['usuario_id'])) {
    setcookie('usuario_id', '', time() - 3600, '/');
}

if (isset($_COOKIE['usuario_email'])) {
    setcookie('usuario_email', '', time() - 3600, '/'); 
}

header("Location: login.php");
exit();
?>

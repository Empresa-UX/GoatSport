<?php
$host = 'localhost';
$dbname = 'goatsport_db';
$username = 'root';
$password = '';
$puerto = '3307';

$conn = new mysqli($host, $username, $password, $dbname, $puerto);

if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}
?>
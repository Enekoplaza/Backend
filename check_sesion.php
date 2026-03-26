<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173"); // Ajusta al puerto de tu Vue
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'nombre' => $_SESSION['nombre'],
        'rol' => $_SESSION['rol']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}

?>
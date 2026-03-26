<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "usuarios.php";


$data = json_decode(file_get_contents('php://input'), true);

$usuario = $data['usuario'] ?? null;
$dni = $data['dni'] ?? null;
$email = $data['email'] ?? null;
$password = $data['contraseña'] ?? null;
$direccion = $data['direccion'] ?? null;

$errores = [];


if (!$usuario) $errores[] = 'El nombre de usuario es obligatorio';
if (!$dni) $errores[] = 'El DNI es obligatorio';
if (!$email) $errores[] = 'El email es obligatorio';
if (!$password) $errores[] = 'La contraseña es obligatoria';
if (!$direccion) $errores[] = 'La dirección es obligatoria';

if ($errores) {
    echo json_encode(['correcto' => false, 'errores' => $errores]);
    exit;
}

$registroCorrecto = registroUsuario($username, $dni, $email, $password, $direccion);

if ($registroCorrecto) {
    echo json_encode(['correcto' => true, 'mensaje' => 'Usuario registrado correctamente']);
} else {
    echo json_encode(['correcto' => false, 'errores' => ['Error al registrar usuario. Posiblemente el usuario o email ya existe']]);
}
exit;
?>

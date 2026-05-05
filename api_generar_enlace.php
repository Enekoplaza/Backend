<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Solo admins']);
    exit;
}

// 🔑 CLAVE SECRETA (No la cambies, es la que firma los enlaces)
$secret_key = "LAKOBRA_SECRET_2024"; 

$expira = time() + (2 * 60 * 60); // 2 horas desde ahora
$payload = "puerta_access|" . $expira;

// Creamos un sello de seguridad (HMAC)
$hash = hash_hmac('sha256', $payload, $secret_key);

// El token final es el mensaje + el sello, todo en Base64 para que quepa en la URL
$token_final = base64_encode($payload . "|" . $hash);

echo json_encode(['success' => true, 'token' => $token_final]);
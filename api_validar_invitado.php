<?php
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

$token_recibido = $_GET['token'] ?? '';
$secret_key = "LAKOBRA_SECRET_2024";

if (empty($token_recibido)) {
    echo json_encode(['success' => false]);
    exit;
}

// Descodificamos el código
$decoded = base64_decode($token_recibido);
$partes = explode("|", $decoded);

if (count($partes) === 3) {
    $identificador = $partes[0]; // "puerta_access"
    $expira = (int)$partes[1];   // timestamp
    $hash_recibido = $partes[2]; // el sello

    // 🛡️ VERIFICACIÓN: Volvemos a calcular el sello para ver si coincide
    $recalculate_hash = hash_hmac('sha256', $identificador . "|" . $expira, $secret_key);

    if (hash_equals($recalculate_hash, $hash_recibido)) {
        // El sello es auténtico, ahora miramos la fecha
        if (time() < $expira) {
            $_SESSION['acceso_puerta_temporal'] = true; // Sello de acceso para el invitado
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Token inválido o caducado']);
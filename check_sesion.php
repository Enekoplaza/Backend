<?php
// --- check_sesion.php ---
try {
    session_start();

    // Permitir CORS para desarrollo local
    $allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Access-Control-Allow-Credentials: true");
    }

    header("Content-Type: application/json");

    // AÑADIMOS LAS VARIABLES QUE FALTAN: dni, email, direccion
    if (isset($_SESSION['user_id'], $_SESSION['nombre'], $_SESSION['rol'])) {
        echo json_encode([
            'logged_in' => true,
            'id'        => $_SESSION['user_id'],
            'nombre'    => $_SESSION['nombre'],
            'rol'       => $_SESSION['rol'],
            'dni'       => $_SESSION['dni'] ?? '---',       // Enviamos el DNI
            'email'     => $_SESSION['email'] ?? '---',     // Enviamos el Email
            'direccion' => $_SESSION['direccion'] ?? '---', // Enviamos la Dirección
            'solicitud_txandalari' => $_SESSION['solicitud_txandalari'] ?? 0
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }

} catch (Exception $e) {
    echo json_encode([
        'logged_in' => false,
        'error'     => $e->getMessage()
    ]);
}
?>
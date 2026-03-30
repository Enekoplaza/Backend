<?php
// --- check_sesion.php ---
// Inicia la sesión y maneja CORS
try {
    session_start();

    // Permitir CORS para desarrollo local
    $allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Access-Control-Allow-Credentials: true");
    }

    header("Content-Type: application/json");

    // Verificar que todas las variables necesarias existan
    if (isset($_SESSION['user_id'], $_SESSION['nombre'], $_SESSION['rol'])) {
        echo json_encode([
            'logged_in' => true,
            'nombre'    => $_SESSION['nombre'],
            'rol'       => $_SESSION['rol']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }

} catch (Exception $e) {
    // En caso de error inesperado, devolver JSON válido
    echo json_encode([
        'logged_in' => false,
        'error'     => $e->getMessage()
    ]);
}
?>
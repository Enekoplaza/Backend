<?php
// --- CONFIGURACIÓN CORS (Añade esto al principio del todo) ---
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Manejo de peticiones pre-flight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// -----------------------------------------------------------

require_once 'conexion.php';
header('Content-Type: application/json');
$conexion = conexionBBDD();

// =========================
// GET: obtener solicitudes
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $artistas = [];
    $txandalaris = [];

    // ARTISTAS
    $res1 = mysqli_query($conexion, "SELECT * FROM solicitudes_artistas");
    if ($res1) {
        while ($row = mysqli_fetch_assoc($res1)) { $artistas[] = $row; }
    }

    // TXANDALARI (Socios con solicitud activa)
    $res2 = mysqli_query($conexion, "SELECT * FROM usuarios WHERE solicitud_txandalari = 1");
    if ($res2) {
        while ($row = mysqli_fetch_assoc($res2)) { $txandalaris[] = $row; }
    }

    echo json_encode([
        "success" => true,
        "artistas" => $artistas,
        "txandalaris" => $txandalaris
    ]);
    cerrarConexion($conexion);
    exit;
}

// =========================
// POST: ACEPTAR
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['usuario_id'])) {
        echo json_encode(["success" => false, "message" => "ID no recibido"]);
        exit;
    }

    $id = intval($data['usuario_id']);
    $tipo = $data['tipo'] ?? 'txandalari';

    if ($tipo === 'artista') {
        $stmt = $conexion->prepare("DELETE FROM solicitudes_artistas WHERE id = ?");
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET rol = 'txandalari', solicitud_txandalari = 0 WHERE id = ?");
    }

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode([
        "success" => $ok,
        "message" => $ok ? "Actualizado correctamente" : "Error en la DB"
    ]);

    $stmt->close();
    cerrarConexion($conexion);
    exit;
}

// =========================
// DELETE: RECHAZAR
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['usuario_id']);
    $tipo = $data['tipo'] ?? 'txandalari';

    if ($tipo === 'artista') {
        $stmt = $conexion->prepare("DELETE FROM solicitudes_artistas WHERE id = ?");
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET solicitud_txandalari = 0 WHERE id = ?");
    }

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["success" => $ok]);
    $stmt->close();
    cerrarConexion($conexion);
    exit;
}
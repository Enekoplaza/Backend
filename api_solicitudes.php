<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

require_once 'conexion.php';
header('Content-Type: application/json');
$conexion = conexionBBDD();

// =========================
// GET: OBTENER SOLICITUDES
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $artistas = [];
    $txandalaris = [];

    // ARTISTAS (Ordenados por estado pendiente primero)
    $res1 = mysqli_query($conexion, "SELECT * FROM solicitudes_artistas ORDER BY FIELD(estado, 'pendiente', 'aceptada'), fecha_solicitud DESC");
    if ($res1) {
        while ($row = mysqli_fetch_assoc($res1)) { $artistas[] = $row; }
    }

    // TXANDALARI (Socios con solicitud activa)
    $res2 = mysqli_query($conexion, "SELECT id, nombre, email, dni FROM usuarios WHERE solicitud_txandalari = 1");
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
        // CORRECCIÓN: Si aceptamos artista, cambiamos estado, NO lo borramos.
        $stmt = $conexion->prepare("UPDATE solicitudes_artistas SET estado = 'aceptada' WHERE id = ?");
    } else {
        // Txandalari: Le damos el rol y quitamos el aviso de solicitud pendiente
        $stmt = $conexion->prepare("UPDATE usuarios SET rol = 'txandalari', solicitud_txandalari = 0 WHERE id = ?");
    }

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["success" => $ok, "message" => $ok ? "Aceptado correctamente" : "Error en la DB"]);
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
        // Si rechazamos artista, lo borramos de la BD
        $stmt = $conexion->prepare("DELETE FROM solicitudes_artistas WHERE id = ?");
    } else {
        // Si rechazamos Txandalari, le quitamos la petición pero sigue siendo socio
        $stmt = $conexion->prepare("UPDATE usuarios SET solicitud_txandalari = 0 WHERE id = ?");
    }

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["success" => $ok, "message" => "Rechazado correctamente"]);
    $stmt->close();
    cerrarConexion($conexion);
    exit;
}
?>
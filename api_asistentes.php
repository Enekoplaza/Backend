<?php
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
$mysqli = conexionBBDD();

// Obtenemos el ID del evento de la URL
$id_evento = intval($_GET['id_evento'] ?? 0);

if ($id_evento > 0) {
    // Unimos (JOIN) la tabla de asistencias con la de usuarios para sacar su nombre y DNI
    $sql = "SELECT u.nombre, u.dni, a.fecha_hora_entrada 
            FROM asistencias a 
            JOIN usuarios u ON a.id_usuario = u.id 
            WHERE a.id_evento = ? 
            ORDER BY a.fecha_hora_entrada ASC";
            
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $asistentes = [];
    while($row = $result->fetch_assoc()) {
        $asistentes[] = $row;
    }
    
    echo json_encode(['success' => true, 'asistentes' => $asistentes]);
} else {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del evento']);
}

cerrarConexion($mysqli);
?>
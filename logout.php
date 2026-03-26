<?php
session_start();
require_once("funciones.php"); 

cerrarSesion(); 
// Devolver JSON para Vue
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>

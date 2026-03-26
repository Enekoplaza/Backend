<?php
require_once "conexion.php";

function registroUsuario($nombre, $dni, $email, $password, $direccion, $rol) {
    $mysqli = conexionBBDD();
    
    // Generar un token único para el QR (Requerido por tu SQL)
    $qr_token = bin2hex(random_bytes(16));
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (nombre, dni, email, password, qr_token, direccion, rol) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) return ["success" => false, "error" => $mysqli->error];

    $stmt->bind_param("sssssss", $nombre, $dni, $email, $password_hash, $qr_token, $direccion, $rol);
    
    $resultado = $stmt->execute();
    $stmt->close();
    cerrarConexion($mysqli);

    return $resultado;
}

function login($email, $password) {
    $mysqli = conexionBBDD();
    // Buscamos por email, que es lo más común en logins modernos
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($password, $usuario['password'])) {
            cerrarConexion($mysqli);
            return $usuario;
        }
    }
    cerrarConexion($mysqli);
    return false;
}
?>


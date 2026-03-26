<?php


//registro de usuario 

require_once "conexion.php";

function registroUsuario($username, $dni, $email, $password, $direccion)
{

    $mysqli = conexionBBDD();
    $mysqli->set_charset(charset: "utf8mb4");

    $sql = "INSERT INTO users (username, dni, email, password_hash, direccion)
     VALUES (?,?,?,?,?)";


    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Error en preparación de la consulta: " . $mysqli->error);
    }


    $password_hash = password_hash($password, PASSWORD_BCRYPT);


    $stmt->bind_param("sssss", $username, $dni, $email, $password_hash, $direccion);


    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }


    $stmt->close();
    cerrarConexion($mysqli);

    return "Usuario registrado correctamente: $username ($email)";
}
require_once "conexion.php";




function login($username, $password)
{
    $mysqli = conexionBBDD();
    $mysqli->set_charset("utf8mb4");


    $sql = "SELECT * FROM usuarios WHERE nombre = ?";

    $stmt = $mysqli->prepare($sql);


    $stmt->bind_param("s", $username);


    $stmt->execute();


    $resultado = $stmt->get_result();


    $usuario = null;
    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // 🔑 Verificación de contraseña
        if (password_verify($password, $usuario['password_hash'])) {
            // Login correcto
            $stmt->close();
            cerrarConexion($mysqli);
            return json_encode($usuario, JSON_UNESCAPED_UNICODE);
        } else {
            $stmt->close();
            cerrarConexion($mysqli);
            return false; // contraseña incorrecta
        }
    }
    
    $stmt->close();
    cerrarConexion($mysqli);
    return false;
}


require_once "conexion.php";


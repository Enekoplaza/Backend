<?php
function conexionBBDD() {
    $host = "localhost";
    $user = "lakobra1";
    $pass = "12345";
    $db   = "lakobra1"; 

    $conexion = mysqli_connect($host, $user, $pass, $db);
    if (!$conexion) {
        die(json_encode(["error" => "Error de conexión: " . mysqli_connect_error()]));
    }
    $conexion->set_charset("utf8mb4");
    return $conexion;
}

function cerrarConexion($conexion) {
    mysqli_close($conexion);
}
?>

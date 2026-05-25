<?php
function conexionBBDD() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "lakobra"; 

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




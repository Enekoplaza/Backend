<?php


function conexionBBDD() {

$nombreServidor = "localhost";
$nombreUser = "root";
$contraseña = "";
$nombreBD = "laKobra";


$conexion = mysqli_connect($nombreServidor, $nombreUser, $contraseña, $nombreBD )
or die("Ha ocurrido un error a la hora de conectar con la bbdd");


return $conexion;

}

function cerrarConexion($conexion) {

 mysqli_close($conexion);

}


?>

<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");


require_once "usuarios.php";


$username   = $_GET['username']   ?? null;
$password  = $_GET['password']  ?? null;



$usersJson = login($username, $password);
$users = json_decode($usersJson, true); 
//var_dump($users);
if (count($users) > 0) {

    $_SESSION['user_id']   = $users['id'];
    $_SESSION['username']  = $users['username'];
    $_SESSION['rol']      = $users['rol'];   

    $_SESSION['logged_in'] = true;



    echo json_encode([
        'success' => true,
        'user_id' => $users['id'],
        'username' => $users['username'],
        'rol' => $users['rol']  
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario o contraseña incorrectos'
    ]);
}
?>

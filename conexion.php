<?php

$conexion = new mysqli(
"localhost",
"root",
"",
"acarez_logistica"
);

if ($conexion->connect_error) {
die("Error de conexión");
}

?>

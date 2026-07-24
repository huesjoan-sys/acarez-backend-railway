<?php
header('Content-Type: application/json');

$respuesta = [
    'mensaje' => '✅ API de prueba funcionando',
    'datos_recibidos' => $_POST,
    'direccion_recibida' => isset($_POST['direccion_actual']) ? $_POST['direccion_actual'] : 'No se recibió dirección'
];

echo json_encode($respuesta);
?>

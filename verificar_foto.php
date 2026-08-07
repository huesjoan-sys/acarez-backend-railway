<?php
$archivo = $_GET['archivo'] ?? '';
if (empty($archivo)) {
    die("❌ Especifica ?archivo=...");
}

$ruta = __DIR__ . '/' . $archivo;
if (file_exists($ruta)) {
    echo "✅ El archivo existe en: " . $ruta . "<br>";
    echo "<img src='/$archivo' width='200'>";
} else {
    echo "❌ El archivo NO existe en: " . $ruta;
}
?>

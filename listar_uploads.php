<?php
echo "<h2>Contenido de uploads/</h2>";
$dir = __DIR__ . '/uploads';
if (is_dir($dir)) {
    echo "<pre>";
    print_r(scandir($dir));
    echo "</pre>";
    
    // Listar subcarpetas
    foreach (scandir($dir) as $sub) {
        if ($sub != '.' && $sub != '..' && is_dir($dir . '/' . $sub)) {
            echo "<h3>$sub</h3>";
            echo "<pre>";
            print_r(scandir($dir . '/' . $sub));
            echo "</pre>";
        }
    }
} else {
    echo "❌ La carpeta uploads/ no existe.";
}
?>

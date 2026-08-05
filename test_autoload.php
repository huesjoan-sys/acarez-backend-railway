<?php
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✅ Autoloader encontrado en: " . $autoloadPath;
} else {
    echo "❌ Autoloader NO encontrado en: " . $autoloadPath;
}
?>

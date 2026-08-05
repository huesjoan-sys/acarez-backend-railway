<?php
// prueba_dompdf.php
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// Verificar si la clase existe
if (class_exists('Dompdf\Dompdf')) {
    echo "✅ Clase Dompdf encontrada.<br>";
    $dompdf = new Dompdf();
    $dompdf->loadHtml('<h1>Prueba</h1>');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("prueba.pdf", array("Attachment" => 0));
    exit;
} else {
    echo "❌ Clase Dompdf NO encontrada.<br>";
    echo "Intentando cargar manualmente...<br>";
    $manualPath = __DIR__ . '/vendor/dompdf/dompdf/src/Dompdf.php';
    if (file_exists($manualPath)) {
        require_once $manualPath;
        if (class_exists('Dompdf\Dompdf')) {
            echo "✅ Carga manual exitosa.<br>";
            $dompdf = new Dompdf();
            $dompdf->loadHtml('<h1>Prueba manual</h1>');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("prueba_manual.pdf", array("Attachment" => 0));
            exit;
        } else {
            echo "❌ Sigue sin encontrarse la clase.";
        }
    } else {
        echo "❌ Archivo no encontrado en: " . $manualPath;
    }
}
?>

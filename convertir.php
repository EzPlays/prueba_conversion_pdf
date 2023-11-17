<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use Mpdf\Mpdf;

function convertirDocxAPdf($inputPath, $outputPath) {
    // Cargar el documento Word
    $phpWord = IOFactory::load($inputPath);

    // Guardar el documento Word como HTML temporal
    $htmlTempPath = 'temp.html';
    $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');
    $xmlWriter->save($htmlTempPath);

    // Inicializar un objeto mPDF
    $mpdf = new Mpdf();

    // Leer el contenido HTML
    $htmlContent = file_get_contents($htmlTempPath);

    // Crear directorio para imágenes temporales
    $tempImagesDir = __DIR__ . '/temp_images';
    if (!is_dir($tempImagesDir)) {
        mkdir($tempImagesDir);
    }

   // Encontrar todas las imágenes y reemplazar la ruta en el contenido HTML
    preg_match_all('/<img[^>]+>/i', $htmlContent, $matches);
    foreach ($matches[0] as $match) {
        preg_match('/src="([^"]+)"/', $match, $src);
        if (isset($src[1])) {
            // Reemplazar la ruta de la imagen en el contenido HTML con la nueva ruta local
            $htmlContent = str_replace($src[1], 'cid:' . $src[1], $htmlContent);
        }
    }

    // Adjuntar imágenes en formato base64 al PDF
    preg_match_all('/<img[^>]+src="data:([^"]+)"/i', $htmlContent, $matches);
    foreach ($matches[1] as $imageData) {
        list($type, $data) = explode(';', $imageData);
        list(, $data)      = explode(',', $data);

        $imageData = base64_decode($data);

        // Generar un nombre de archivo único para la imagen
        $imageName = 'image_' . uniqid() . '.png';
        $imagePath = $tempImagesDir . '/' . $imageName;

        // Guardar la imagen en el directorio temporal
        file_put_contents($imagePath, $imageData);

        // Reemplazar la ruta de la imagen en el contenido HTML con la nueva ruta local y agregar un estilo CSS
        $htmlContent = str_replace(
            'data:' . $type,
            '<img src="' . $imagePath . '" style="max-width: 100%; height: auto; page-break-inside: avoid; position: absolute;">',
            $htmlContent
        );

        // Adjuntar la imagen al PDF
        $mpdf->imageVars['cid:' . $imagePath] = $imageData;
    }

    // Cargar el contenido HTML actualizado en mPDF
    $mpdf->WriteHTML($htmlContent);


    // Guardar el archivo PDF
    $mpdf->Output($outputPath, 'F');

    // Eliminar el archivo HTML temporal
    unlink($htmlTempPath);

    // Eliminar las imágenes temporales
    array_map('unlink', glob("$tempImagesDir/*"));
    rmdir($tempImagesDir);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $archivoEntrada = $_FILES["archivoWord"]["tmp_name"];
    $archivoSalida = "archivo_convertido.pdf";

    convertirDocxAPdf($archivoEntrada, $archivoSalida);

    // Descargar el archivo convertido
    header("Content-type: application/pdf");
    header("Content-Disposition: attachment; filename=$archivoSalida");
    readfile($archivoSalida);

    // Eliminar el archivo PDF
    unlink($archivoSalida);
} else {
    echo "Método no permitido.";
}

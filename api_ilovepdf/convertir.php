<?php

require_once('../vendor/autoload.php');

use Ilovepdf\OfficepdfTask;

$projectPublicId = 'project_public_26546a35630a60c2bc921ef89e862e89_DVEST7fbfb146f485c8c6983b6ae26a5e9f6f';
$projectSecretKey = 'secret_key_f983b3d6e0ebe31ccfe84758a571f3c5_iqm4ibb1dcfdcf280079e6e47524937b31c3d';

$filePath = $_FILES['file']['tmp_name'];

if (empty($filePath)) {
    echo json_encode(['success' => false, 'error' => 'No se ha seleccionado ningún archivo.']);
    die();
}

// Obtener la extensión del archivo original
$fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

// Generar un nombre de archivo temporal con la extensión adecuada
$tempFileName = tempnam(sys_get_temp_dir(), 'ilovepdf_') . '.' . $fileExtension;

// Mover el archivo temporalmente a la ubicación generada
move_uploaded_file($filePath, $tempFileName);

$myTask = new OfficepdfTask($projectPublicId, $projectSecretKey);

try {
    // Agregar el archivo a la tarea
    $file = $myTask->addFile($tempFileName);

    // Ejecutar la tarea
    $myTask->execute();

    // Descargar el PDF resultante
    $outputPath = $myTask->download(null, __DIR__);
    echo json_encode(['success' => true, 'output' => $outputPath]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    // Eliminar el archivo temporal después de usarlo
    unlink($tempFileName);
}


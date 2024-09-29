<?php
require 'vendor/autoload.php';

ini_set('memory_limit', '-1');
set_time_limit(120);

// Habilitar CORS para as requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
    header('Access-Control-Max-Age: 86400'); // Cache a resposta por um dia
    exit(0); // Finaliza a requisição OPTIONS
}

use Google\Client;

function authorize() {
    $credentials_path = dirname(__FILE__) . "/credentials.json";
    $client = new Google\Client();
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentials_path);
    $client->useApplicationDefaultCredentials();
    
    $client->setScopes([
        "https://www.googleapis.com/auth/drive.file",
        "https://www.googleapis.com/auth/drive"
    ]);
    
    return $client;
}

$client = authorize();
$service = new Google_Service_Drive($client);

// Upload de arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With, Content-Type");
    header('Content-Type: application/json');
    header('Access-Control-Max-Age: 86400'); // Cache a resposta por um dia

    // $result_json = array('log' => $_FILES['file'] );


    // print_r($_FILES['file']);

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $folderId = $_POST['folder'];
    $customName = $_POST['customName'] ? $_POST['customName'] : $_FILES['file']['name'];

    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $customName,
        'parents' => [$folderId]
    ]);

    $content = file_get_contents($fileTmpPath);

    $uploadedFile = $service->files->create($fileMetadata, [
        'data' => $content,
        'mimeType' => mime_content_type($fileTmpPath),
        'uploadType' => 'multipart',
        'fields' => 'id, name',
    ]);

    $result_json = array('fileId' => $uploadedFile->id, 'fileName'=> $uploadedFile->name);

    echo json_encode($result_json);

} else {
    echo 'Nenhum arquivo foi enviado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo 'Rodando na porta 8000';
}
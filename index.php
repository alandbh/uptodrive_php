<?php
ini_set('max_execution_time', '700');
ini_set('max_input_time', '700');
ini_set('post_max_size', '970M');
ini_set('memory_limit', '-1');
// set_time_limit(600);
require 'vendor/autoload.php';


// Habilitar CORS para as requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // header('Access-Control-Allow-Origin: http://localhost:3003');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
    header('Access-Control-Allow-Credentials: true'); // Se necessário
    // exit(0);
    return 0;
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
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
    header('Access-Control-Allow-Credentials: true'); // Se necessário

    // $result_json = array('log' => $_FILES['file'] );


    // print_r($_FILES['file']);

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $folderId = $_POST['folder'];
    $customName = $_POST['customName'] ? $_POST['customName'] . '.' . $_POST['extension'] : $_FILES['file']['name'];

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
    phpinfo();
}
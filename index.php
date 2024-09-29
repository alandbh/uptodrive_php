<?php
require 'vendor/autoload.php';

ini_set('memory_limit', '-1');
set_time_limit(120);



use Google\Client;


function authorize() {
    $credentials_path = dirname(__FILE__) . "/credentials.json";
    $client = new Google\Client();
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentials_path);
    $client->useApplicationDefaultCredentials();
// $client->useApplicationDefaultCredentials();


    // $client = new Client();
    
    // Defina o email e a chave privada diretamente ou carregue de variáveis de ambiente
    // $client->setAuthConfig([
    //     'client_email' => getenv('CLIENT_EMAIL'),
    //     'private_key'  => getenv('PRIVATE_KEY')
    // ]);
    
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
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $folderId = $_POST['folder'];
    $customName = $_POST['customName'] ? $_POST['customName'] : $_FILES['file']['name'];

    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $customName,
        'parents' => [$folderId]
    ]);

    print_r($_FILES['file']);

    // $content = file_get_contents($fileTmpPath);

    // $uploadedFile = $service->files->create($fileMetadata, [
    //     'data' => $content,
    //     'mimeType' => mime_content_type($fileTmpPath),
    //     'uploadType' => 'multipart',
    //     'fields' => 'id, name',
    // ]);

    // $result_json = array('fileId' => $uploadedFile->id, 'fileName'=> $uploadedFile->name);


    // // headers to tell that result is JSON
    // header('Content-type: application/json');

    // // send the result now
    // echo json_encode($result_json);

    // echo 'Arquivo enviado: ' . $uploadedFile->id;
} else {
    echo 'Nenhum arquivo foi enviado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo 'Rodando na porta 8000';
    // echo $credentials_path;
}
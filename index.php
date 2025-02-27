<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('max_execution_time', '700');
ini_set('max_input_time', '700');
ini_set('post_max_size', '970M');
ini_set('memory_limit', '-1');
// set_time_limit(600);
require 'vendor/autoload.php';


// Habilitar CORS para as requisições OPTIONS (preflight)
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     // header('Access-Control-Allow-Origin: http://localhost:3003');
//     header('Access-Control-Allow-Origin: *');
//     header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
//     header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
//     header('Access-Control-Allow-Credentials: true'); // Se necessário
//     // exit(0);
//     return 0;
// }

use Google\Client;
use Google\Service\Drive;


function authorize() {
    $credentials_path = dirname(__FILE__) . "/credentials2.json";
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
        'fields' => 'id, name, webViewLink',  // Added webViewLink to get the URL
    ]);

    $result_json = array(
        'fileId' => $uploadedFile->id, 
        'fileName'=> $uploadedFile->name,
        'fileUrl' => $uploadedFile->webViewLink  // Include the URL in response
    );

    echo json_encode($result_json);

}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

}

// Tratamento da requisição

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $whatToList = $_GET['list'];
    $folderId = $_GET['folder'];

    // echo json_encode($whatToList);

    if ($whatToList === 'files') {
        $files = listFilesInFolder($folderId);
        echo json_encode($files);
        exit;
        
    } elseif($whatToList === 'folders') {

        try {
            $authClient = authorize();
            $response = listFoldersAndSubfolders($authClient, $folderId);
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['message' => 'error on listing folders', 'error' => $e->getMessage()]);
        }
        exit;

    } else {
        echo json_encode(['message' => 'invalid endpoint']);
    }
}

function renameFile($fileId, $newName) {
    // $client = authorize();
    // $service = new Drive($client);

    try {
        $fileMetadata = new DriveFile([
            'name' => $newName
        ]);

        $updatedFile = $service->files->update($fileId, $fileMetadata, [
            'fields' => 'id, name'
        ]);

        return [
            'fileId' => $updatedFile->id,
            'fileName' => $updatedFile->name
        ];
    } catch (Exception $e) {
        return [
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
}
function listFilesInFolder($folderId) {
    $client = authorize();
    $service = new Drive($client);

    try {
        $response = $service->files->listFiles([
            'q' => "'" . $folderId . "' in parents",
            'pageSize' => 1000,
            'fields' => 'nextPageToken, files(id, name)',
        ]);

        $files = $response->getFiles();
        if (count($files) > 0) {
            return $files;
        } else {
            return [];
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
        return [];
    }
}

function listFoldersAndSubfolders($authClient, $parentFolderId) {
    $drive = new Drive($authClient);
    $foldersAndSubfolders = [];

    // Listando as pastas do primeiro nível
    $firstLevelFolders = $drive->files->listFiles([
        'q' => "'$parentFolderId' in parents and mimeType = 'application/vnd.google-apps.folder'",
        'pageSize' => 1000,
        'fields' => 'nextPageToken, files(id, name)'
    ]);

    foreach ($firstLevelFolders->getFiles() as $folder) {
        // Listando subpastas de cada pasta do primeiro nível
        $subfoldersResponse = $drive->files->listFiles([
            'q' => "'{$folder->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder'",
            'pageSize' => 1000,
            'fields' => 'nextPageToken, files(id, name)'
        ]);

        $subfolders = [];
        foreach ($subfoldersResponse->getFiles() as $subfolder) {
            $subfolders[] = [
                'id' => $subfolder->getId(),
                'name' => $subfolder->getName(),
                'type' => 'journey'
            ];
        }

        $foldersAndSubfolders[] = [
            'name' => $folder->getName(),
            'id' => $folder->getId(),
            'type' => 'player',
            'subfolders' => $subfolders
        ];
    }

    return $foldersAndSubfolders;
}
?>
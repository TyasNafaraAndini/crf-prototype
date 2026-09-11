```
<?php
/**
 * Script Upload ke Wasabi Storage
 * Menggunakan AWS SDK untuk PHP (Wasabi compatible dengan S3)
 */

// Install AWS SDK terlebih dahulu via Composer:
// composer require aws/aws-sdk-php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Konfigurasi Wasabi
$config = [
    'version' => 'latest',
    'region'  => 'ap-southeast-1',
    'endpoint' => 'https://s3.ap-southeast-1.wasabisys.com',
    'credentials' => [
        'key'    => 'BS22E6CQMDFDLXBBJCLM',
        'secret' => 'AEOBzAs7e7SzAdizvTjbNP2Nj7TXFejRYfvfzAHp',
    ],
    'use_path_style_endpoint' => true,
];

$bucket = 'cdn.ptppu.co.id';
$uploadPath = 'tracker/uploads/';

// Inisialisasi S3 Client
$s3Client = new S3Client($config);

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    $file = $_FILES['file'];
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('Error upload file: ' . $file['error']);
    }
    
    // Generate nama file unik (opsional)
    $fileName = time() . '_' . basename($file['name']);
    
    // Key untuk S3 (path lengkap di bucket)
    $key = $uploadPath . $fileName;
    
    try {
        // Upload file ke Wasabi
        $result = $s3Client->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'SourceFile' => $file['tmp_name'],
            'ACL'    => 'public-read', // Atur sesuai kebutuhan
            'ContentType' => $file['type'],
        ]);
        
        // URL file yang diupload
        $fileUrl = $result['ObjectURL'];
        
        echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px;'>";
        echo "<h3>✓ Upload Berhasil!</h3>";
        echo "<p><strong>File:</strong> {$fileName}</p>";
        echo "<p><strong>URL:</strong> <a href='{$fileUrl}' target='_blank'>{$fileUrl}</a></p>";
        echo "</div>";
        
    } catch (AwsException $e) {
        echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;'>";
        echo "<h3>✗ Upload Gagal!</h3>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload ke Wasabi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .upload-form {
            background: #f5f5f5;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #333;
        }
        input[type="file"] {
            margin: 20px 0;
            padding: 10px;
            width: 100%;
            border: 2px dashed #ccc;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="upload-form">
        <h2>Upload File ke Wasabi</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Upload File</button>
        </form>
    </div>
</body>
</html>
```
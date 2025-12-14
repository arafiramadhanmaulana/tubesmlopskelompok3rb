<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    header("HTTP/1.0 403 Forbidden");
    echo json_encode(["success" => false, "error" => "Akses Ditolak."]);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/functions_upload.php';

if (!function_exists('generateSafeFileName')) {
    function generateSafeFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $cleanName = preg_replace("/[^a-zA-Z0-9]/", "_", $filename);
        return time() . "_" . $cleanName . "." . $extension;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["pdf_file"]) && $_FILES["pdf_file"]["error"] === UPLOAD_ERR_OK) {
    header('Content-Type: application/json');

    $subject_code = $_POST["subject_code"] ?? '';
    $topic = $_POST["topic"] ?? '';
    $description = $_POST["description"] ?? '';
    
    if (empty($subject_code) || empty($topic) || empty($description)) {
        echo json_encode(["success" => false, "error" => "Semua kolom wajib diisi."]);
        exit();
    }

    $originalFilename = basename($_FILES["pdf_file"]["name"]);
    $newFileName = generateSafeFileName($originalFilename);
    
    $targetDir = __DIR__ . "/uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $targetFile = $targetDir . $newFileName;
    $filePathForDb = "uploads/" . $newFileName;

    if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $targetFile)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO documents (
                file_name, subject_code, topic, description, file_path, created_at, updated_at
            ) VALUES (
                :file_name, :subject_code, :topic, :description, :file_path, NOW(), NOW()
            )");

            $stmt->execute([
                ":file_name" => $originalFilename,
                ":subject_code" => $subject_code,
                ":topic" => $topic,
                ":description" => $description,
                ":file_path" => $filePathForDb
            ]);

            echo json_encode(["success" => true, "message" => "Dokumen berhasil diunggah!"]);

        } catch (PDOException $e) {
            if (file_exists($targetFile)) unlink($targetFile);
            echo json_encode(["success" => false, "error" => "Database Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Gagal memindahkan file."]);
    }
    exit();
} else {
    echo json_encode(["success" => false, "error" => "Tidak ada file yang diunggah atau terjadi error."]);
    exit();
}
?>

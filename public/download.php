<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.0 403 Forbidden");
    die("Akses Ditolak.");
}

// PERBAIKAN PATH: Tambahkan /../
require_once __DIR__ . '/../config/config.php';

if (isset($_GET['file'])) {
    $file = str_replace(['../', './', '\\'], '', $_GET['file']);
    if (strpos($file, 'uploads/') === 0) {
        $file = substr($file, 8); // Hapus 'uploads/'
    }

    // Path fisik di public/uploads/
    $path = __DIR__ . '/uploads/' . $file;

    if (file_exists($path) && is_file($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        
        ob_clean();
        flush();
        readfile($path);
        exit;
    } else {
        echo "File tidak ditemukan: " . htmlspecialchars($path);
    }
}
?>
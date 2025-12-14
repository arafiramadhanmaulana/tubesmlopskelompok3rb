<?php
// delete_document.php

// Bersihkan output buffer agar tidak ada spasi/error PHP yang merusak JSON
ob_clean(); 
header('Content-Type: application/json');

session_start();

// 1. Cek Login & Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit();
}

try {
    // --- PERBAIKAN DI SINI ---
    // Gunakan '/../' untuk naik satu level dari folder 'public'
    if (file_exists(__DIR__ . '/../config/config.php')) {
        require_once __DIR__ . '/../config/config.php';
    } else {
        // Fallback jika struktur folder berbeda (misal file ini ada di root)
        require_once __DIR__ . '/config/config.php';
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode request harus POST.');
    }

    $doc_id = $_POST['doc_id'] ?? '';

    if (empty($doc_id)) {
        throw new Exception('ID Dokumen tidak valid.');
    }

    // 2. Cari File di Database
    $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        throw new Exception('Data dokumen tidak ditemukan di database.');
    }

    // 3. Hapus File Fisik
    // Path fisik juga mungkin perlu penyesuaian tergantung struktur folder upload
    // Asumsi: folder uploads ada di dalam folder public
    $filePath = __DIR__ . '/' . $doc['file_path']; 
    
    // Jika folder uploads ada di LUAR public, gunakan: 
    // $filePath = __DIR__ . '/../' . $doc['file_path'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // 4. Hapus Database
    $delStmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    if ($delStmt->execute([$doc_id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Gagal menghapus data dari database.');
    }

} catch (Exception $e) {
    // Tangkap error path atau database dan kirim sebagai JSON
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
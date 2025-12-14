<?php
ob_clean();
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit();
}

try {
    if (file_exists(__DIR__ . '/../config/config.php')) {
        require_once __DIR__ . '/../config/config.php';
    } else {
        require_once __DIR__ . '/config/config.php';
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode request harus POST.');
    }

    $doc_id = $_POST['doc_id'] ?? '';

    if (empty($doc_id)) {
        throw new Exception('ID Dokumen tidak valid.');
    }

    $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        throw new Exception('Data dokumen tidak ditemukan di database.');
    }

    $filePath = __DIR__ . '/' . $doc['file_path'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $delStmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    if ($delStmt->execute([$doc_id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Gagal menghapus data dari database.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

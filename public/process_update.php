<?php
// FILE: process_update.php
header('Content-Type: application/json'); 

session_start();

// Matikan display error agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer output untuk menangkap error tak terduga
ob_start();

try {
    // 1. Cek Login & Role
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Akses ditolak. Silakan login ulang.');
    }

    // 2. Koneksi Database (Sesuaikan path config Anda)
    $configPath = __DIR__ . '/../config/config.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("File konfigurasi database tidak ditemukan di: $configPath");
    }
    require_once $configPath;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode request tidak valid.');
    }

    // 3. Ambil Data Input
    $doc_id = $_POST['doc_id'] ?? '';
    $subject_code = $_POST['subject_code'] ?? '';
    $topic = trim($_POST['topic'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($doc_id) || empty($subject_code) || empty($topic) || empty($description)) {
        throw new Exception("Semua kolom bertanda * wajib diisi.");
    }

    // 4. Cek Data Lama
    $stmt = $pdo->prepare("SELECT id, file_path FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $old_doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old_doc) {
        throw new Exception("Dokumen tidak ditemukan di database.");
    }

    $final_file_path = $old_doc['file_path'];
    $file_updated = false;
    $new_file_name_display = null;

    // 5. Proses Upload File (Jika ada file baru)
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['pdf_file'];
        
        // Validasi Tipe MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            throw new Exception("File harus berformat PDF.");
        }

        // Validasi Ukuran (10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("Ukuran file terlalu besar (Maks 10MB).");
        }

        // Siapkan Folder Upload
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate Nama File Baru
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('modul_', true) . '.' . $extension;
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Gagal menyimpan file ke server.");
        }

        // Hapus File Fisik Lama
        $old_physical_path = __DIR__ . '/../' . $old_doc['file_path'];
        if (file_exists($old_physical_path) && is_file($old_physical_path)) {
            unlink($old_physical_path);
        }

        $final_file_path = 'uploads/' . $newFileName;
        $new_file_name_display = $file['name']; // Nama asli file user
        $file_updated = true;
    }

    // 6. Update Database
    // CATATAN: Bagian 'uploaded_at = NOW()' sudah dihapus
    if ($file_updated) {
        $sql = "UPDATE documents SET 
                    subject_code = ?, 
                    topic = ?, 
                    description = ?, 
                    file_path = ?, 
                    file_name = ?
                WHERE id = ?";
        $params = [$subject_code, $topic, $description, $final_file_path, $new_file_name_display, $doc_id];
    } else {
        $sql = "UPDATE documents SET 
                    subject_code = ?, 
                    topic = ?, 
                    description = ?
                WHERE id = ?";
        $params = [$subject_code, $topic, $description, $doc_id];
    }

    $stmtUpdate = $pdo->prepare($sql);
    $exec = $stmtUpdate->execute($params);

    if ($exec) {
        ob_clean(); // Bersihkan buffer sebelum output JSON
        echo json_encode(['success' => true, 'message' => 'Modul berhasil diperbarui!']);
    } else {
        throw new Exception("Gagal update database.");
    }

} catch (Exception $e) {
    ob_clean(); // Bersihkan buffer jika ada error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
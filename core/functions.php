<?php
/**
 * functions.php
 * Berisi fungsi-fungsi bantuan umum (General Helpers) untuk Repository Modul MLOps.
 * Menggantikan logika 'getOrCreateLayerId' yang lama karena struktur database sudah berubah.
 */

/**
 * 1. Ambil Nama Lengkap Mata Kuliah
 * Mengubah kode (misal 'DM') menjadi nama lengkap ('Data Mining').
 * Berguna jika kita hanya punya kodenya tapi ingin menampilkan namanya.
 */
function getSubjectName($pdo, $code) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM subjects WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetchColumn();
        
        // Kembalikan nama jika ada, atau kembalikan kodenya saja jika tidak ketemu
        return $result ? $result : $code;
    } catch (PDOException $e) {
        return $code;
    }
}

/**
 * 2. Format Tanggal Indonesia
 * Mengubah format database (YYYY-MM-DD HH:MM:SS) menjadi format yang enak dibaca.
 * Contoh: 27-11-2025 14:30
 */
function formatDateIndo($dateString) {
    if (empty($dateString)) return '-';
    $date = new DateTime($dateString);
    return $date->format('d-m-Y H:i');
}

/**
 * 3. Potong Teks (Truncate)
 * Memotong deskripsi panjang agar tidak merusak tampilan kartu di dashboard.
 */
function truncateText($text, $limit = 100) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    // Potong teks dan tambahkan ellipsis (...)
    return substr($text, 0, $limit) . '...';
}
?>
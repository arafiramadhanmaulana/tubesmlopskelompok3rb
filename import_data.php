<?php
// import_data.php - VERSI FIX MAPPING & TANGGAL
require_once 'config/config.php';

$csvFile = __DIR__ . '/data/indexing_final_db.csv'; 

if (!file_exists($csvFile)) {
    die("<h3 style='color:red'>Error: File CSV tidak ditemukan di: $csvFile</h3>");
}

$handle = fopen($csvFile, "r");
$header = fgetcsv($handle); // Skip header

echo "<div style='font-family: monospace; padding: 20px;'>";
echo "<h2>🚀 Import Data: Perbaikan Mapping & Tanggal</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
echo "<tr style='background:#eee'><th>No</th><th>File Name (Benar)</th><th>Matkul</th><th>Created At</th><th>Status</th></tr>";

// 1. Prepare Subject
$stmt_ins_sub = $pdo->prepare("INSERT IGNORE INTO subjects (code, name) VALUES (?, ?)");

// 2. Prepare Document
// URUTAN QUERY: file_name, subject_code, topic, description, file_path, uploaded_by, created_at
$stmt_ins_doc = $pdo->prepare("INSERT INTO documents (file_name, subject_code, topic, description, file_path, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

$count = 0;
$rowNum = 0;
$adminId = 1;

while (($row = fgetcsv($handle, 2000, ",")) !== FALSE) {
    $rowNum++;
    if (count($row) < 5) continue;

    // --- MAPPING VARIABEL (HATI-HATI DI SINI) ---
    // CSV Index:
    // [0] = Code (PD)
    // [1] = Name (Pergudangan Data)
    // [2] = Topic
    // [3] = Desc
    // [4] = Filename (MODUL...)
    // [5] = Path Drive (Abaikan, kita pakai path lokal)
    // [6] = Created At
    
    $subjectCode = trim($row[0]); // PD
    $subjectName = trim($row[1]); // Pergudangan Data
    $topic       = trim($row[2]); 
    $desc        = trim($row[3]); 
    $fileName    = trim($row[4]); // MODUL PRAKTIKUM...
    
    // Ambil tanggal dari CSV (Kolom ke-7 / Index 6)
    $createdAt   = !empty($row[6]) ? trim($row[6]) : date('Y-m-d H:i:s');

    // Path Lokal
    $localPath = 'uploads/' . basename($fileName);

    // A. Insert Subject
    try {
        $stmt_ins_sub->execute([$subjectCode, $subjectName]);
    } catch (Exception $e) {}

    // B. Insert Document
    $status = "";
    try {
        // EKSEKUSI DENGAN URUTAN YANG BENAR
        $stmt_ins_doc->execute([
            $fileName,      // Masuk ke col: file_name
            $subjectCode,   // Masuk ke col: subject_code
            $topic,         // Masuk ke col: topic
            $desc,          // Masuk ke col: description
            $localPath,     // Masuk ke col: file_path
            $adminId,       // Masuk ke col: uploaded_by
            $createdAt      // Masuk ke col: created_at
        ]);
        
        $status = "<span style='color:green'>✅ OK</span>";
        $count++;
    } catch (Exception $e) {
        $status = "<span style='color:red'>Gagal: " . $e->getMessage() . "</span>";
    }

    echo "<tr>";
    echo "<td>$rowNum</td>";
    echo "<td>$fileName</td>";     // Cek apakah ini nama file PDF?
    echo "<td>$subjectCode</td>";  // Cek apakah ini kode pendek (PD)?
    echo "<td>$createdAt</td>";    // Cek apakah tanggal muncul?
    echo "<td>$status</td>";
    echo "</tr>";
}

fclose($handle);
echo "</table>";
echo "<br><h3>Selesai! $count data berhasil diperbaiki.</h3>";
echo "<a href='public/index.php'>&rarr; Buka Dashboard</a>";
echo "</div>";
?>
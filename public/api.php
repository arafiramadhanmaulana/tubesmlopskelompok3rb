<?php
require_once __DIR__ . '/../config/config.php';

if (isset($_GET['action']) && $_GET['action'] == 'get_documents') {
    header('Content-Type: application/json');
    $sql = "SELECT d.*, s.name AS subject_name FROM documents d 
            LEFT JOIN subjects s ON d.subject_code = s.code 
            ORDER BY d.created_at DESC";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
<?php
session_start();

// 1. Cek Akses Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../config/config.php';

if (!isset($_GET['id'])) {
    die("ID dokumen tidak valid.");
}

$doc_id = $_GET['id'];

try {
    // Ambil data dokumen lama
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("Dokumen tidak ditemukan.");
    }

    // Ambil data mata kuliah
    $stmt_subj = $pdo->query("SELECT * FROM subjects ORDER BY name ASC");
    $courses = $stmt_subj->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Modul - Sains Data Repo</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        /* --- VARIABLES (SAMA DENGAN UPLOAD) --- */
        :root {
            --tc-navy: #001a41;
            --tc-orange: #fca12b;
            --tc-red: #ff0025;
            --tc-white: #ffffff;
            --tc-gray: #f8f9fa;
            --text-main: #2d3748;
            --text-muted: #718096;
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            height: 100vh;
            width: 100%;
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            color: var(--text-main);
        }

        /* --- HEADER --- */
        header {
            background-color: var(--tc-navy);
            color: var(--tc-white);
            height: 60px;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .header-brand h2 { font-weight: 700; font-size: 1.3rem; margin: 0; }
        .header-brand span { color: var(--tc-orange); }

        .btn-back {
            text-decoration: none; color: var(--tc-white);
            background: rgba(255,255,255,0.1); padding: 6px 12px;
            border-radius: 8px; font-size: 0.85rem; transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); }

        /* --- CONTAINER --- */
        .container {
            flex: 1; width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;
            overflow: hidden; display: flex; justify-content: center; align-items: center;
        }

        /* --- CARD --- */
        .card {
            background: var(--tc-white); border-radius: 16px; padding: 25px;
            box-shadow: var(--shadow-soft); width: 100%; height: 100%; max-height: 650px;
            display: flex; flex-direction: column;
        }

        .card-header {
            border-bottom: 1px dashed #eee; padding-bottom: 10px; margin-bottom: 15px; flex-shrink: 0;
        }
        .card-header h3 { color: var(--tc-navy); margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .card-header p { color: var(--text-muted); font-size: 0.85rem; margin-top: 3px; margin-bottom: 0; }

        /* --- FORM LAYOUT --- */
        .upload-form {
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
            flex: 1; overflow: hidden; 
        }

        .left-col {
            display: flex; flex-direction: column; gap: 12px;
            padding-right: 5px; overflow: hidden; justify-content: flex-start;
        }

        .right-col {
            display: flex; flex-direction: column; height: 100%; 
        }

        .upload-wrapper-group {
            flex: 1; display: flex; flex-direction: column;
            min-height: 0; margin-bottom: 10px;
        }

        /* --- INPUTS --- */
        .form-group { margin-bottom: 0; }
        .form-group label {
            display: block; margin-bottom: 4px; font-weight: 600; font-size: 0.85rem; color: var(--text-main);
        }
        .required { color: var(--tc-red); }

        .form-control {
            width: 100%; padding: 10px 12px; background: var(--tc-gray);
            border: 1px solid transparent; border-radius: 8px;
            font-size: 0.9rem; color: var(--text-main); font-family: 'Poppins', sans-serif;
            transition: 0.3s;
        }
        .form-control:focus {
            background: #fff; border-color: var(--tc-navy); outline: none;
            box-shadow: 0 0 0 3px rgba(0, 26, 65, 0.05);
        }
        textarea.form-control { resize: none; height: 141px; }

        /* CHOICES JS */
        .choices__inner {
            background-color: var(--tc-gray); border: 1px solid transparent; border-radius: 8px;
            padding: 5px 12px; min-height: 44px; display: flex; align-items: center;
            font-family: 'Poppins', sans-serif;
        }
        .is-focused .choices__inner, .is-open .choices__inner {
            background-color: #fff; border-color: var(--tc-navy); box-shadow: 0 0 0 3px rgba(0, 26, 65, 0.05);
        }
        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: var(--tc-navy) !important; color: #fff !important;
        }

        /* --- UPLOAD AREA --- */
        .file-upload-area {
            flex: 1; border: 2px dashed #cbd5e0; border-radius: 12px; background-color: #f8fafc;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; transition: 0.3s; cursor: pointer; position: relative;
        }
        .file-upload-area:hover { border-color: var(--tc-navy); background-color: #e3f2fd; }
        .file-upload-area.file-selected { border-color: #48bb78; background-color: #f0fff4; }

        .current-file-badge {
            background: #e0e7ff; color: var(--tc-navy); padding: 5px 12px;
            border-radius: 50px; font-size: 0.75rem; font-weight: 600; margin-bottom: 15px;
            display: inline-flex; align-items: center; gap: 5px;
        }

        .upload-icon { font-size: 2.5rem; color: var(--tc-navy); margin-bottom: 10px; }
        .upload-text { font-size: 0.85rem; color: var(--text-muted); padding: 0 20px; }
        .browse-link { color: var(--tc-navy); font-weight: 600; text-decoration: underline; }
        .file-name-display { margin-top: 10px; font-weight: 600; font-size: 0.85rem; color: var(--text-main); }

        /* Progress Bar */
        .progress-bar-container { width: 80%; height: 6px; background-color: #e2e8f0; border-radius: 3px; margin-top: 10px; overflow: hidden; display: none; }
        .progress-bar { height: 100%; width: 0%; background-color: var(--tc-navy); transition: width 0.3s ease; }
        .progress-text { font-size: 0.75rem; margin-top: 5px; color: var(--text-muted); display: none; }

        /* Buttons */
        .action-buttons { display: flex; gap: 10px; flex-shrink: 0; margin-top: 0; }
        .btn-submit {
            flex: 2; padding: 12px; background: linear-gradient(135deg, var(--tc-navy), #0a2a5c);
            color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-reset {
            flex: 1; padding: 12px; background: #fff; color: var(--text-main);
            border: 1px solid #ddd; border-radius: 10px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s;
        }
        .btn-reset:hover { background: #f1f1f1; }

        .status-message { margin-bottom: 10px; padding: 10px; border-radius: 8px; font-size: 0.85rem; text-align: center; display: none; flex-shrink: 0; }
        .status-message.success { background: #e8f5e9; color: #2e7d32; }
        .status-message.error { background: #ffebee; color: #c62828; }

        #pdf_file { display: none; }

        @media (max-width: 900px) {
            body { height: auto; overflow: auto; }
            .container { height: auto; display: block; }
            .card { height: auto; max-height: none; }
            .upload-form { grid-template-columns: 1fr; gap: 20px; display: block; }
            .left-col { margin-bottom: 20px; overflow: visible; }
            .upload-wrapper-group { height: 200px; }
        }
    </style>
</head>

<body>

    <header>
        <div class="header-brand">
            <h2>Sains Data <span>Repo</span></h2>
        </div>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
    </header>

    <div class="container">
        <section class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Edit Informasi Modul</h3>
                <p>Perbarui detail modul praktikum atau ganti file dokumen jika diperlukan.</p>
            </div>

            <form class="upload-form" id="updateForm" enctype="multipart/form-data">
                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                <input type="hidden" name="old_file_path" value="<?= $doc['file_path'] ?>">

                <div class="left-col">
                    <div class="form-group">
                        <label for="subject_code">Mata Kuliah Praktikum <span class="required">*</span></label>
                        <select id="subject_code" name="subject_code" class="form-control" required>
                            <option value="">Pilih Mata Kuliah...</option>
                            <?php foreach($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['code']) ?>" <?= ($c['code'] == $doc['subject_code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="topic">Judul Modul / Topik Pertemuan <span class="required">*</span></label>
                        <input type="text" id="topic" name="topic" class="form-control" value="<?= htmlspecialchars($doc['topic']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi & Tujuan Pembelajaran <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($doc['description']) ?></textarea>
                    </div>
                </div>

                <div class="right-col">
                    <div class="upload-wrapper-group">
                        <label style="margin-bottom: 5px; display: block; font-weight: 600; font-size: 0.85rem;">Ganti File PDF (Opsional)</label>
                        
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="current-file-badge">
                                <i class="fas fa-file-alt"></i> File Saat Ini: <?= htmlspecialchars($doc['file_name']) ?>
                            </div>

                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <p class="upload-text">Seret & Lepas file PDF <b>baru</b> di sini<br>atau <span class="browse-link">klik untuk mengganti</span></p>
                            
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
                            
                            <div class="file-name-display" id="file-name-display">Tidak ada file baru dipilih</div>
                            
                            <div class="progress-bar-container" id="progressContainer">
                                <div class="progress-bar" id="uploadProgressBar"></div>
                            </div>
                            <span class="progress-text" id="uploadProgressText">0%</span>
                        </div>
                    </div>

                    <div id="update-status" class="status-message"></div>

                    <div class="action-buttons">
                        <a href="index.php" class="btn-reset" style="text-decoration:none;">
                            <i class="fas fa-times"></i> Batal
                        </a>
                        <button type="submit" class="btn-submit" id="updateButton">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>

            </form>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <script src="js/script_update.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const element = document.querySelector('#subject_code');
            const choices = new Choices(element, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                position: 'bottom',
                placeholder: true,
                placeholderValue: 'Cari Mata Kuliah...'
            });
        });
    </script>
</body>
</html>
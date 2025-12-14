<?php
session_start();
// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/../config/config.php';

// Data Mata Kuliah
$subjects = [
    "DM"  => "Data Mining", 
    "PS"  => "Pemodelan Stokastik", 
    "PD"  => "Pergudangan Data", 
    "KP"  => "Komputasi Paralel", 
    "ADS" => "Analisis Data Statistik", 
    "TBD" => "Teknologi Basis Data",  
    "AP"  => "Algoritma Pemrograman", 
    "DL"  => "Deep Learning",
    "Analisis Multivariat"  => "Analisis Multivariat",
    "Komputasi Statistik"  => "Komputasi Statistik",
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Repository Praktikum Sains Data</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Container Filter agar tidak mentok */
        .filter-section {
            margin-bottom: 30px;
        }

        .filter-label {
            font-weight: 600;
            color: #001a41;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Container tombol-tombol (Chips) */
        .filter-chips-container {
            display: flex;
            flex-wrap: wrap; /* Agar turun ke bawah jika penuh */
            gap: 10px;       /* Jarak antar tombol (kanan-kiri-atas-bawah) */
            align-items: center;
        }

        /* Gaya Tombol Filter */
        .chip {
            border: 1px solid #e2e8f0;
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .chip:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
            transform: translateY(-1px);
        }

        /* Gaya saat tombol aktif */
        .chip.active {
            background: #001a41; /* Warna Navy */
            color: white;
            border-color: #001a41;
            box-shadow: 0 4px 6px rgba(0, 26, 65, 0.2);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-database"></i></div>
                Sains Data <span>Repo</span>
            </div>
            <div class="nav-actions">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="upload.php" class="btn-pill primary"><i class="fas fa-cloud-upload-alt"></i> Upload Modul</a>
                    <a href="admin.php" class="btn-pill secondary"><i class="fas fa-cog"></i> Kelola Akun</a>
                <?php endif; ?>
                <div class="divider"></div>
                <div class="user-profile">
                    Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </div>
                <a href="logout.php" class="btn-logout" title="Keluar"><i class="fas fa-power-off"></i></a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-shape shape-1"></div>
        <div class="hero-shape shape-2"></div>
        <div class="hero-shape shape-3"></div>

        <div class="hero-container">
            <h1>Repository Modul Praktikum <br> Program Studi Sains Data</h1>
            <p>Platform manajemen file terintegrasi untuk mengakses modul praktikum secara mudah dan terstruktur.</p>
            
            <div class="search-bar-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari judul modul, topik, atau kode matkul..." autocomplete="off">
                <div class="shortcut-hint">⌘ K</div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="filter-section reveal">
            <div class="filter-label"><i class="fas fa-filter"></i> Filter Mata Kuliah:</div>
            
            <div class="filter-chips-container" id="filterChips">
                <button class="chip active" data-value="all">Tampilkan Semua</button>
                
                <?php foreach($subjects as $code => $name): ?>
                    <button class="chip" data-value="<?= $code ?>"><?= $name ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="controls-row reveal">
            <div class="result-count">
                <span class="total-documents">Memuat data...</span>
            </div>
            <div class="sort-wrapper">
                <label><i class="fas fa-sort-amount-down"></i> Urutkan:</label>
                <select id="sortSelect" onchange="handleSortChange(this.value)">
                    <option value="recent">Terbaru Diupload</option>
                    <option value="oldest">Terlama Diupload</option>
                    <option value="az">Judul (A-Z)</option>
                </select>
            </div>
        </div>

        <div id="documentList" class="document-grid">
            </div>

        <div class="pagination-wrapper reveal">
            <div class="pagination-controls"></div>
        </div>
    </main>

    <div id="detailModal" class="modal">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content">
            <button class="close-modal-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            
            <div class="modal-info">
                <span class="modal-badge" id="m-badge">MATA KULIAH</span>
                <h2 class="modal-title" id="m-title">Judul Modul</h2>
                
                <div class="modal-meta">
                    <div>
                        <i class="far fa-calendar-alt"></i> 
                        Tanggal Upload: <span id="m-date" style="font-weight: 700; color: var(--primary);">Date</span>
                    </div>
                    <div>
                        <i class="far fa-user"></i> 
                        Diupload Oleh: <span id="m-uploader" style="font-weight: 700; color: var(--primary);">Loading...</span>
                    </div>
                </div>
                
                <div style="font-weight: 700; color: #001a41; margin-bottom: 10px;">Deskripsi & Tujuan:</div>
                <div class="modal-desc" id="m-desc">
                    ...
                </div>

                <div class="modal-actions">
                    <a href="#" id="m-download" target="_blank" class="btn-download-modal">
                        <i class="fas fa-cloud-download-alt"></i> Unduh Modul PDF
                    </a>
                </div>
            </div>

            <div class="modal-preview">
                <iframe id="pdf-frame" src=""></iframe>
            </div>
        </div>
    </div>

    <script>const userRole = '<?php echo $_SESSION['role']; ?>';</script>
    <script src="js/script.js"></script>
</body>
</html>
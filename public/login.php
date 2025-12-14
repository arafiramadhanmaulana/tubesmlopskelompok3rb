<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Jika sudah login -> redirect ke dashboard langsung
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userInput = trim($_POST['username'] ?? '');
    $passInput = $_POST['password'] ?? '';

    if ($userInput === '' || $passInput === '') {
        $error = "Username dan Password wajib diisi.";
    } else {
        // 1. Ambil data user dari database berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$userInput]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // 2. Cek Password (Mendukung Hash & Plain Text)
            $isValid = false;
            
            // Cek apakah password di database hash atau text biasa
            if (password_verify($passInput, $data['password'])) {
                $isValid = true; // Cocok dengan hash
            } elseif ($passInput === $data['password']) {
                $isValid = true; // Cocok dengan text biasa (untuk testing)
            }

            if ($isValid) {
                // --- BAGIAN KUNCI AGAR "HALO" MUNCUL ---
                $_SESSION['user_id']  = $data['id'];
                $_SESSION['role']     = $data['role'];
                $_SESSION['username'] = $data['username']; // <--- Ini yang akan dibaca index.php
                // ---------------------------------------

                header('Location: index.php');
                exit();
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sains Data Repository</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- GAYA TAMPILAN (CSS) --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --tc-red: #ff0025;
            --tc-navy: #001a41;
            --tc-navy-light: #002b6b;
            --tc-orange: #fca12b;
            --tc-white: #ffffff;
            --tc-gray: #f8f9fa;
            --text-main: #2d3748;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --input-focus: rgba(0, 26, 65, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--tc-white); height: 100vh; width: 100%; overflow: hidden; }
        .split-screen { display: flex; height: 100%; width: 100%; }

        .left-pane {
            flex: 1.2;
            background-color: var(--tc-navy);
            color: var(--tc-white);
            display: flex; flex-direction: column; justify-content: center;
            padding: 4rem; position: relative; overflow: hidden;
        }
        .left-pane::before {
            content: ""; position: absolute; inset: 0;
            background-image: radial-gradient(var(--tc-navy-light) 1.5px, transparent 1.5px);
            background-size: 30px 30px; opacity: 0.3;
        }

        .brand-content { position: relative; z-index: 2; max-width: 500px; }
        .brand-logo {
            font-size: 2.8rem; font-weight: 700; margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 30%, var(--tc-orange) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }
        .brand-tagline {
            font-size: 1rem; opacity: 0.9; font-weight: 300;
            border-left: 4px solid var(--tc-red); padding-left: 1.5rem;
            margin-top: 1.5rem; line-height: 1.6;
        }

        .right-pane { flex: 1; display: flex; align-items: center; justify-content: center; background: var(--tc-white); padding: 2rem; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; animation: fadeIn .8s ease-out; }

        .form-header { margin-bottom: 2.5rem; }
        .form-header h2 { font-size: 1.8rem; font-weight: 600; color: var(--text-main); }
        .form-header p { font-size: .95rem; color: var(--text-muted); }

        .form-group { margin-bottom: 1.5rem; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); transition: .3s; }

        .form-control {
            width: 100%; padding: 14px 14px 14px 45px;
            background: var(--tc-gray); border: 2px solid transparent; border-radius: 12px;
            font-size: .95rem; color: var(--text-main); transition: .3s;
        }
        .form-control:focus { background: #fff; border-color: var(--tc-navy); box-shadow: 0 4px 15px var(--input-focus); outline: none; }
        .form-control:focus + i { color: var(--tc-navy); }

        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--tc-navy), #0a2a5c);
            border: none; border-radius: 12px; color: #fff; font-weight: 600; font-size: 1rem;
            margin-top: 1rem; cursor: pointer; transition: .3s; box-shadow: 0 4px 6px rgba(0,26,65,.2);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,26,65,.3); background: linear-gradient(135deg, var(--tc-navy), var(--tc-red) 150%); }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1.5rem; font-size: .9rem; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fff5f5; border: 1px solid #fed7d7; color: var(--tc-red); }

        .footer-text { margin-top: 2rem; text-align: center; font-size: .85rem; color: var(--text-muted); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0);} }

        @media (max-width: 900px) {
            .split-screen { flex-direction: column; min-height: 100vh; }
            .left-pane { flex: 0 0 200px; padding: 2rem; text-align: center; align-items: center; }
            .brand-tagline { display: none; }
        }
    </style>
</head>

<body>
<div class="split-screen">
    <div class="left-pane">
        <div class="brand-content">
            <h1 class="brand-logo">Sains Data<br>Repository</h1>
            <p class="brand-tagline">
                Sistem Manajemen File Modul Praktikum Terintegrasi.<br>
                Kelola, simpan, dan akses materi praktikum Sains Data dengan mudah, aman, dan terstruktur.
            </p>
        </div>
    </div>

    <div class="right-pane">
        <div class="login-card">
            <div class="form-header">
                <h2>Selamat Datang</h2>
                <p>Silakan masuk ke akun Lab Assistant atau Administrator.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-control" placeholder="NIP / NIM / Username" autocomplete="off" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Masuk Portal <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: .8em;"></i>
                </button>
            </form>

            <div class="footer-text">
                &copy; <?= date('Y') ?> Laboratorium Sains Data
            </div>
        </div>
    </div>
</div>
</body>
</html>
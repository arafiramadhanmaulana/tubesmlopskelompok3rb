<?php
session_start();

// 1. Cek Akses Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 2. Koneksi Database
require_once __DIR__ . '/../config/config.php';

$message = '';
$message_type = '';

// --- LOGIKA PHP (BACKEND) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($username) || empty($password)) {
        $message = 'Username dan password wajib diisi.';
        $message_type = 'error';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            $message = "Pengguna '$username' berhasil ditambahkan.";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $message = "Gagal: Username '$username' sudah terdaftar.";
            } else {
                $message = "Terjadi kesalahan sistem.";
            }
            $message_type = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id == $_SESSION['user_id']) {
        $message = 'Anda tidak dapat menghapus akun Anda sendiri.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = 'Akun berhasil dihapus dari sistem.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Gagal menghapus akun.";
            $message_type = 'error';
        }
    }
}

try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal memuat data.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Sistem Manajemen Modul</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- IMPORT FONTS --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        /* --- VARIABLES --- */
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

        .header-nav { display: flex; flex-direction: row; align-items: center; gap: 14px; }

        .btn-nav {
            text-decoration: none; color: var(--tc-white);
            background: rgba(255,255,255,0.1); padding: 6px 12px;
            border-radius: 8px; font-size: 0.85rem; transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-nav:hover { background: rgba(255,255,255,0.2); }

        .btn-logout { background: rgba(255, 0, 37, 0.2); color: #ffcccc; }
        .btn-logout:hover { background: var(--tc-red); color: white; }


        /* --- MAIN CONTAINER --- */
        .container {
            flex: 1; 
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 15px 2rem; 
            
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
            overflow: hidden; 
        }

        /* --- CARD STYLE --- */
        .card {
            background: var(--tc-white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-soft);
            height: 100%; 
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--tc-navy);
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
            flex-shrink: 0;
        }

        /* Scrollable Area */
        .card-content-scroll {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
            padding-left: 2px;
            padding-bottom: 10px;
        }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 12px; width: 100%; }
        
        .form-group label {
            display: block; margin-bottom: 4px;
            font-weight: 500; font-size: 0.85rem; color: var(--text-muted);
        }

        .input-wrapper { position: relative; width: 100%; }
        
        .input-wrapper i.fa-user, 
        .input-wrapper i.fa-lock, 
        .input-wrapper i.fa-shield-alt {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #ccc; font-size: 0.9rem; pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 10px 40px 10px 38px;
            background: var(--tc-gray);
            border: 1px solid transparent; 
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-main);
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            background: #fff; border-color: var(--tc-navy); outline: none;
            box-shadow: 0 0 0 3px rgba(0, 26, 65, 0.05);
        }
        
        .form-control:focus + i { color: var(--tc-navy); }

        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #ccc; z-index: 10;
        }
        .toggle-password:hover { color: var(--tc-navy); }

        .btn-submit {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--tc-navy), #0a2a5c);
            border: none; border-radius: 10px;
            color: #fff; font-weight: 600; font-size: 0.95rem;
            cursor: pointer; margin-top: 10px;
            display: flex; justify-content: center; align-items: center; gap: 8px;
            transition: .3s;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        /* --- TABLE STYLE --- */
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }

        th {
            text-align: left; padding: 12px;
            color: var(--text-muted); font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            background: #fff;
            position: sticky; top: 0;
            z-index: 10;
        }

        td { padding: 12px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin { background-color: #e3f2fd; color: #1565c0; }
        .badge-user { background-color: #fff3e0; color: #ef6c00; }

        .btn-delete {
            background: #ffebee; color: var(--tc-red); border: none;
            padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: 0.2s;
        }
        .btn-delete:hover { background: var(--tc-red); color: white; }

        .alert {
            padding: 10px; border-radius: 8px; margin-bottom: 15px;
            font-size: 0.85rem; display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; }

        @media (max-width: 900px) {
            body { height: auto; overflow: auto; }
            .container { display: flex; flex-direction: column; height: auto; overflow: visible; }
            .card { height: auto; }
            .card-content-scroll { overflow: visible; }
        }
    </style>
</head>

<body>

    <header>
        <div class="header-brand">
            <h2>Sains Data <span>Admin</span></h2>
        </div>
        <nav class="header-nav">
            <a href="index.php" class="btn-nav"><i class="fas fa-home"></i> <span style="font-size: 0.8em">Portal Utama</span></a>
            <a href="logout.php" class="btn-nav btn-logout"><i class="fas fa-sign-out-alt"></i> <span style="font-size: 0.8em">Keluar</span></a>
        </nav>
    </header>

    <div class="container">
        
        <section class="card">
            <h3><i class="fas fa-user-plus"></i> Registrasi Pengguna</h3>
            
            <div class="card-content-scroll">
                <?php if ($message): ?>
                    <div class="alert <?php echo ($message_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo ($message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" class="form-control" placeholder="NIP / NIM / username" required autocomplete="off">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password Akun" required>
                            <i class="fas fa-lock"></i>
                            
                            <span class="toggle-password" onclick="togglePass()">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Role / Peran</label>
                        <div class="input-wrapper">
                            <select name="role" class="form-control" style="cursor: pointer;">
                                <option value="user">User Biasa</option>
                                <option value="admin">Administrator</option>
                            </select>
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Simpan Akun <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </section>

        <section class="card">
            <h3><i class="fas fa-users"></i> Manajemen Akun Pengguna</h3>
            
            <div class="card-content-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat Pada</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($u['username']); ?></div>
                                        <?php if($u['id'] == $_SESSION['user_id']): ?>
                                            <span style="font-size: 0.7rem; color: #2e7d32;">(Anda)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($u['role']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus akun ini?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                <button type="button" class="btn-delete" disabled style="opacity: 0.5; cursor: not-allowed;" title="Tidak dapat menghapus akun sendiri">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-delete" title="Hapus User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 30px;">Belum ada data pengguna.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <script>
        function togglePass() {
            const pass = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pass.type === 'password') {
                pass.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pass.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
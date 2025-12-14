<?php
// public/reset_user.php
require_once __DIR__ . '/../config/config.php';

echo "<h2>⚙️ Reset User Admin</h2>";

// 1. Hapus user admin lama (jika ada) untuk menghindari duplikat
try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = 'admin'");
    $stmt->execute();
    echo "✅ User 'admin' lama dihapus (bersih-bersih).<br>";
} catch (Exception $e) {
    echo "⚠️ Gagal hapus (mungkin belum ada): " . $e->getMessage() . "<br>";
}

// 2. Buat Password Hash (Standar Keamanan)
$username = 'admin';
$password_plain = '123'; 
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
$role = 'admin';

// 3. Masukkan User Baru
try {
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $password_hash, $role]);
    
    echo "<hr>";
    echo "<h3>🎉 SUKSES! User Berhasil Dibuat.</h3>";
    echo "Silakan Login dengan data ini:<br>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>123</b><br>";
    echo "<br>";
    echo "<a href='login.php' style='padding:10px; background:blue; color:white; text-decoration:none;'>👉 Ke Halaman Login</a>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>❌ Gagal: " . $e->getMessage() . "</h3>";
}
?>
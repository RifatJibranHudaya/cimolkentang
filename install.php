<?php
// ============================================================
// INSTALL.PHP - Jalankan sekali untuk setup database
// Akses: http://localhost/food-app/install.php
// ============================================================

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'food_sales_db';

$errors  = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = $_POST['host']   ?? 'localhost';
    $user   = $_POST['user']   ?? 'root';
    $pass   = $_POST['pass']   ?? '';
    $dbname = $_POST['dbname'] ?? 'food_sales_db';

    $conn = @new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        $errors[] = "Koneksi gagal: " . $conn->connect_error;
    } else {
        $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($dbname);
        $conn->set_charset('utf8mb4');

        $tables = [
            "branches" => "CREATE TABLE IF NOT EXISTS `branches` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nama_cabang` VARCHAR(100) NOT NULL,
                `alamat` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "users" => "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) UNIQUE NOT NULL,
                `email` VARCHAR(100) UNIQUE NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `level` ENUM('owner','admin','admin_cadangan') DEFAULT 'admin_cadangan',
                `branch_id` INT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "orders" => "CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT,
                `branch_id` INT DEFAULT NULL,
                `kategori` ENUM('offline','shopeefood','gofood') NOT NULL,
                `total` DECIMAL(12,0) NOT NULL DEFAULT 0,
                `keterangan` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "order_items" => "CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `produk` VARCHAR(100) NOT NULL,
                `harga` DECIMAL(12,0) NOT NULL DEFAULT 0,
                FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB",

            "stock_records" => "CREATE TABLE IF NOT EXISTS `stock_records` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT,
                `branch_id` INT DEFAULT NULL,
                `tanggal` DATE NOT NULL,
                `tipe` ENUM('pembukaan','penutupan') NOT NULL,
                `produk` VARCHAR(100) NOT NULL,
                `jumlah` INT NOT NULL DEFAULT 0,
                `satuan` VARCHAR(20) DEFAULT 'pcs',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "production" => "CREATE TABLE IF NOT EXISTS `production` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT,
                `branch_id` INT DEFAULT NULL,
                `nama_item` VARCHAR(150) NOT NULL,
                `harga` DECIMAL(12,0) NOT NULL DEFAULT 0,
                `supplier` VARCHAR(150),
                `tempat` VARCHAR(150),
                `tanggal` DATE NOT NULL,
                `keterangan` TEXT,
                `edited_by` INT DEFAULT NULL,
                `edited_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`edited_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "operational" => "CREATE TABLE IF NOT EXISTS `operational` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT,
                `nama_alat` VARCHAR(150) NOT NULL,
                `harga` DECIMAL(12,0) NOT NULL DEFAULT 0,
                `tempat_beli` VARCHAR(150),
                `merk` VARCHAR(100),
                `periode_ganti` INT DEFAULT 0 COMMENT 'in months, 0=tidak tentu',
                `tanggal_beli` DATE,
                `keterangan` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB",
        ];

        foreach ($tables as $name => $sql) {
            if (!$conn->query($sql)) {
                $errors[] = "Error tabel $name: " . $conn->error;
            }
        }

        if (empty($errors)) {
            // Update users schema for existing db
            $conn->query("UPDATE users SET level='admin_cadangan' WHERE level='pembeli'");
            $conn->query("ALTER TABLE users MODIFY level ENUM('owner','admin','admin_cadangan') DEFAULT 'admin_cadangan'");
            
            $res = $conn->query("SHOW COLUMNS FROM users LIKE 'branch_id'");
            if ($res && $res->num_rows === 0) {
                $conn->query("ALTER TABLE users ADD COLUMN branch_id INT DEFAULT NULL");
                $conn->query("ALTER TABLE users ADD CONSTRAINT fk_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
            }

            // Also add branch_id to orders, stock_records, and production if they don't have it
            $branchTables = ['orders', 'stock_records', 'production'];
            foreach ($branchTables as $t) {
                $resB = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'branch_id'");
                if ($resB && $resB->num_rows === 0) {
                    $conn->query("ALTER TABLE `$t` ADD COLUMN branch_id INT DEFAULT NULL");
                    $conn->query("ALTER TABLE `$t` ADD CONSTRAINT fk_br_$t FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL");
                }
            }

            $resProd = $conn->query("SHOW COLUMNS FROM production LIKE 'edited_by'");
            if ($resProd && $resProd->num_rows === 0) {
                $conn->query("ALTER TABLE production ADD COLUMN edited_by INT DEFAULT NULL");
                $conn->query("ALTER TABLE production ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL");
                $conn->query("ALTER TABLE production ADD CONSTRAINT fk_prod_editor FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL");
            }

            // Insert default branch
            $conn->query("INSERT IGNORE INTO `branches` (id, nama_cabang, alamat) VALUES (1, 'Cabang Utama', 'Pusat')");

            // Create default owner account
            $ownerPwd   = password_hash('owner123', PASSWORD_DEFAULT);
            $ownerUser  = 'owner';
            $ownerEmail = 'owner@dapurku.com';
            $ownerPhone = '081234567890';
            $stmt = $conn->prepare("INSERT IGNORE INTO users (username,email,phone,password,level,branch_id) VALUES (?,?,?,?,'owner',1)");
            $stmt->bind_param('ssss', $ownerUser, $ownerEmail, $ownerPhone, $ownerPwd);
            $stmt->execute();

            // Save db.php
            $cfg = "<?php\n// db.php – dibuat otomatis oleh install.php\ndefine('DB_HOST', '$host');\ndefine('DB_USER', '$user');\ndefine('DB_PASS', '$pass');\ndefine('DB_NAME', '$dbname');\n\n\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\nif (\$conn->connect_error) {\n    header('Location: install.php');\n    exit;\n}\n\$conn->set_charset('utf8mb4');\n";
            file_put_contents(__DIR__ . '/db.php', $cfg);

            $success[] = "✅ Database <strong>$dbname</strong> berhasil dibuat!";
            $success[] = "✅ Semua tabel berhasil dibuat!";
            $success[] = "✅ Akun Owner: <strong>owner</strong> / <strong>owner123</strong>";
            $success[] = "✅ Cabang default 'Cabang Utama' ditambahkan!";
            $success[] = "🎉 Instalasi selesai! <a href='index.php'>Klik di sini untuk masuk</a>";
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instalasi – DapurKu POS</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:linear-gradient(135deg,#1a0a00 0%,#2d1500 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;color:#FFF8F0;padding:20px}
.card{background:rgba(42,20,0,.95);border:1px solid rgba(255,107,0,.3);border-radius:20px;padding:40px;width:100%;max-width:480px;backdrop-filter:blur(10px);box-shadow:0 20px 60px rgba(0,0,0,.5)}
h1{font-family:'Playfair Display',serif;color:#FF6B00;font-size:2rem;margin-bottom:8px;text-align:center}
p.sub{text-align:center;color:#FF9A3C;margin-bottom:30px;font-size:.9rem}
label{display:block;font-size:.85rem;color:#FFB347;margin-bottom:6px;font-weight:500}
input{width:100%;background:#1C0D00;border:1px solid rgba(255,107,0,.2);border-radius:10px;padding:12px 14px;color:#FFF8F0;font-size:.95rem;margin-bottom:16px;outline:none;transition:all .2s;font-family:inherit}
input:focus{border-color:#FF6B00;box-shadow:0 0 0 3px rgba(255,107,0,.15)}
button{width:100%;background:linear-gradient(135deg,#FF6B00,#FF8C00);border:none;border-radius:10px;padding:14px;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s;letter-spacing:.3px}
button:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(255,107,0,.4)}
.error{background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.4);border-radius:10px;padding:14px;margin-bottom:16px;font-size:.9rem;color:#FCA5A5}
.success{background:rgba(22,163,74,.15);border:1px solid rgba(22,163,74,.4);border-radius:10px;padding:14px;margin-bottom:16px;font-size:.9rem;color:#86EFAC;line-height:1.8}
.success a{color:#FF9A3C;font-weight:600}
.logo-icon{font-size:3rem;display:block;text-align:center;margin-bottom:8px}
</style>
</head>
<body>
<div class="card">
  <span class="logo-icon">🍢</span>
  <h1>DapurKu POS</h1>
  <p class="sub">Instalasi Database – Jalankan Sekali</p>
  <?php foreach($errors as $e): ?>
    <div class="error">❌ <?= $e ?></div>
  <?php endforeach; ?>
  <?php if(!empty($success)): ?>
    <div class="success"><?= implode('<br>', $success) ?></div>
  <?php else: ?>
  <form method="POST">
    <label>Host Database</label>
    <input type="text" name="host" value="localhost" required>
    <label>Username MySQL</label>
    <input type="text" name="user" value="root" required>
    <label>Password MySQL</label>
    <input type="password" name="pass">
    <label>Nama Database</label>
    <input type="text" name="dbname" value="food_sales_db" required>
    <button type="submit">🚀 Mulai Instalasi</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

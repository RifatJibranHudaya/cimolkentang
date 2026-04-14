# 🍢 DapurKu POS – Sistem Penjualan Makanan
Aplikasi kasir dan manajemen toko makanan berbasis PHP + MySQL.

---

## 📁 Struktur File
```
food-app/
├── install.php      ← Jalankan sekali untuk setup database
├── db.php           ← Konfigurasi koneksi database (dibuat oleh install.php)
├── functions.php    ← Fungsi bersama & komponen HTML
├── index.php        ← Router utama + semua halaman
├── export.php       ← Handler export CSV
├── style.css        ← Stylesheet global
└── README.md        ← Panduan ini
```

---

## 🚀 Cara Instalasi

### 1. Persyaratan Server
- PHP >= 7.4 (atau PHP 8.x)
- MySQL >= 5.7 atau MariaDB >= 10.3
- Web server: Apache / Nginx / XAMPP / Laragon

### 2. Upload File
Upload semua file ke folder di web server Anda.
Contoh: `htdocs/food-app/` (untuk XAMPP)

### 3. Jalankan Installer
Buka browser dan akses:
```
http://localhost/food-app/install.php
```
Isi formulir konfigurasi database lalu klik **"Mulai Instalasi"**.

### 4. Login
Setelah instalasi berhasil, akses:
```
http://localhost/food-app/index.php
```
Login dengan akun Owner default:
- **Username:** `owner`
- **Password:** `owner123`
> ⚠️ Segera ganti password setelah login pertama!

---

## 👥 Level Pengguna

| Level    | Akses |
|----------|-------|
| 👑 Owner  | Semua fitur + kelola pengguna + ubah level |
| 🛡️ Admin  | Kasir, Stok, Produksi, Operasional |
| 🛒 Pembeli | Dashboard saja |

---

## 🔧 Fitur-Fitur

### 🧾 Kasir
- Input pesanan dengan produk: Cimol, Kentang, Otak-otak, Tahu, Sosis, Bakso
- Harga input manual per produk
- Tanggal & waktu otomatis
- Kategori: Offline, ShopeeFood, GoFood
- Keterangan opsional
- Kalkulasi total otomatis
- Riwayat log pesanan
- Filter: Hari / Minggu / Bulan
- **Export CSV**
- Ringkasan pendapatan Harian / Mingguan / Bulanan

### 📦 Stok
- Input stok pembukaan & penutupan harian
- Kalkulasi pemakaian otomatis
- Riwayat stok dengan filter
- **Export CSV**

### 🛒 Produksi
- Catatan belanja bahan baku
- Data supplier & tempat pembelian
- Filter per periode & per tempat
- **Export CSV**

### 🔧 Operasional
- Daftar alat masak (nama, merk, harga, tempat beli)
- Periode ganti alat dengan peringatan otomatis (⚠️)
- Filter per nama alat
- **Export CSV**

### 👥 Manajemen Pengguna (Owner only)
- Lihat semua pengguna
- Ubah level pengguna
- Hapus pengguna

---

## 🍪 Cookie
Saat login dengan "Ingat Saya":
- Cookie `fs_user` → menyimpan username (30 hari)
- Cookie `fs_token` → menyimpan token autentikasi (30 hari)

---

## 📱 Responsif
Aplikasi mendukung perangkat mobile dengan:
- Sidebar collapsible dengan hamburger menu
- Layout grid yang menyesuaikan layar
- Input dan tombol yang mobile-friendly

---

## 🛠️ Troubleshooting

**Koneksi database gagal:**
- Pastikan MySQL berjalan
- Cek username/password MySQL di `db.php`

**Halaman blank:**
- Aktifkan error reporting PHP
- Cek error log web server

**File export tidak ter-download:**
- Pastikan tidak ada output sebelum header HTTP
- Cek izin file pada server

---

## 📞 Konfigurasi Manual `db.php`
Jika installer tidak bisa menulis file, buat `db.php` manual:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password_anda');
define('DB_NAME', 'food_sales_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header("Location: install.php");
    exit;
}
$conn->set_charset('utf8mb4');
```

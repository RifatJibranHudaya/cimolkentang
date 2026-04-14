<?php
// modules/auth/register.php – Halaman Register
require_once __DIR__ . '/../../functions.php';

if (isLoggedIn()) {
    header('Location: index.php?page=dashboard');
    exit;
}

$errs = [];
$old  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    $old = compact('username','email','phone');

    // Validasi
    if (strlen($username) < 3)                            $errs[] = "Username minimal 3 karakter.";
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))     $errs[] = "Username hanya boleh huruf, angka, dan underscore.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errs[] = "Format email tidak valid.";
    if (!preg_match('/^[0-9]{8,15}$/', $phone))           $errs[] = "Nomor HP harus 8–15 digit angka.";
    if (strlen($password) < 6)                            $errs[] = "Password minimal 6 karakter.";
    if ($password !== $confirm)                           $errs[] = "Konfirmasi password tidak cocok.";

    if (empty($errs)) {
        // Cek duplikat
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errs[] = "Username atau email sudah terdaftar.";
        }
    }

    if (empty($errs)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // User pertama otomatis jadi owner
        $count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
        $level = ($count == 0) ? 'owner' : 'admin_cadangan';

        // Branch default untuk owner = 1
        $branch = ($level === 'owner') ? 1 : null;

        $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, level, branch_id) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('sssssi', $username, $email, $phone, $hash, $level, $branch);
        $stmt->execute();

        $msg = ($level === 'owner')
            ? 'Akun Owner berhasil dibuat! Silakan login.'
            : 'Akun berhasil dibuat! Silakan login. (Level: Admin Cadangan – menunggu promosi dari Owner)';
        flashSet('success', $msg);
        header('Location: index.php?page=login');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Daftar akun DapurKu POS">
<title>Daftar – DapurKu POS</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="modules/auth/auth.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:480px">

    <div class="auth-logo">
      <span class="logo-emoji">🍢</span>
      <h1>Buat Akun</h1>
      <p>Daftar ke DapurKu POS</p>
    </div>

    <div class="auth-info">
      ℹ️ Akun baru akan mendapat level <strong>Admin Cadangan</strong>. Owner dapat mempromosikan level Anda.
    </div>

    <?php foreach ($errs as $e): ?>
      <div class="auth-flash error">⚠️ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="index.php?page=register" autocomplete="on">

      <div class="auth-form-group">
        <label class="auth-form-label" for="username">👤 Nama Pengguna</label>
        <input
          type="text"
          id="username"
          name="username"
          class="auth-input"
          placeholder="Min. 3 karakter (huruf, angka, _)"
          value="<?= htmlspecialchars($old['username'] ?? '') ?>"
          autocomplete="username"
          required
        >
      </div>

      <div class="auth-form-group">
        <label class="auth-form-label" for="email">📧 Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="auth-input"
          placeholder="contoh@email.com"
          value="<?= htmlspecialchars($old['email'] ?? '') ?>"
          autocomplete="email"
          required
        >
      </div>

      <div class="auth-form-group">
        <label class="auth-form-label" for="phone">📱 Nomor HP</label>
        <input
          type="tel"
          id="phone"
          name="phone"
          class="auth-input"
          placeholder="08xxxxxxxxxx (8–15 digit)"
          value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
          autocomplete="tel"
          required
        >
      </div>

      <div class="auth-form-group">
        <label class="auth-form-label" for="reg_password">🔒 Password</label>
        <div class="auth-password-wrap">
          <input
            type="password"
            id="reg_password"
            name="password"
            class="auth-input"
            placeholder="Min. 6 karakter"
            autocomplete="new-password"
            required
          >
          <button type="button" class="auth-eye-btn" onclick="togglePassword('reg_password', this)" title="Tampilkan password">👁️</button>
        </div>
      </div>

      <div class="auth-form-group">
        <label class="auth-form-label" for="confirm">🔒 Konfirmasi Password</label>
        <div class="auth-password-wrap">
          <input
            type="password"
            id="confirm"
            name="confirm"
            class="auth-input"
            placeholder="Ulangi password"
            autocomplete="new-password"
            required
          >
          <button type="button" class="auth-eye-btn" onclick="togglePassword('confirm', this)" title="Tampilkan password">👁️</button>
        </div>
      </div>

      <button type="submit" class="auth-btn" style="margin-top:4px">
        Daftar Sekarang →
      </button>
    </form>

    <div class="auth-switch">
      Sudah punya akun? <a href="index.php?page=login">Login di sini</a>
    </div>

  </div>
</div>

<script>
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
    btn.title = 'Sembunyikan password';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
    btn.title = 'Tampilkan password';
  }
}
</script>
</body>
</html>

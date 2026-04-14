<?php
// modules/auth/login.php – Halaman Login
require_once __DIR__ . '/../../functions.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: index.php?page=dashboard');
    exit;
}

// ── Handle POST ──────────────────────────────────────────────
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $err = 'Username/email dan password wajib diisi.';
    } else {
        // Cari berdasarkan username ATAU email
        $stmt = $conn->prepare("SELECT id, username, email, password, level, branch_id FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['level']     = $user['level'];
            $_SESSION['branch_id'] = $user['branch_id'];

            if ($remember) {
                $token = hash('sha256', $user['password']);
                setcookie('fs_user',  $user['username'], time() + 86400 * 30, '/', '', false, true);
                setcookie('fs_token', $token,            time() + 86400 * 30, '/', '', false, true);
            }
            flashSet('success', 'Selamat datang kembali, ' . $user['username'] . '! 👋');
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $err = 'Username/email atau password salah. Silakan coba lagi.';
        }
    }
}

$flash = flashGet();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Login ke DapurKu POS – Sistem Penjualan Makanan">
<title>Login – DapurKu POS</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="modules/auth/auth.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <span class="logo-emoji">🍢</span>
      <h1>DapurKu POS</h1>
      <p>Sistem Penjualan Makanan Digital</p>
    </div>

    <?php if ($flash): ?>
      <div class="auth-flash success"><?= $flash ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="auth-flash error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login" autocomplete="on">

      <div class="auth-form-group">
        <label class="auth-form-label" for="identifier">👤 Username atau Email</label>
        <input
          type="text"
          id="identifier"
          name="identifier"
          class="auth-input"
          placeholder="Masukkan username atau email"
          value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
          autocomplete="username"
          required
        >
      </div>

      <div class="auth-form-group">
        <label class="auth-form-label" for="password">🔒 Password</label>
        <div class="auth-password-wrap">
          <input
            type="password"
            id="password"
            name="password"
            class="auth-input"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          >
          <button type="button" class="auth-eye-btn" onclick="togglePassword('password', this)" title="Tampilkan password">👁️</button>
        </div>
      </div>

      <label class="auth-remember">
        <input type="checkbox" name="remember" id="remember">
        <span>Ingat saya (simpan cookie 30 hari)</span>
      </label>

      <button type="submit" class="auth-btn" id="loginBtn">
        Masuk ke Dashboard →
      </button>
    </form>

    <div class="auth-switch">
      Belum punya akun? <a href="index.php?page=register">Daftar di sini</a>
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

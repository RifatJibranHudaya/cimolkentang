<?php
// modules/users/users.php – Halaman Manajemen Pengguna
require_once __DIR__ . '/../../functions.php';
requireLogin();
global $conn;
$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/users_handler.php';
    exit;
}

// Branches list (untuk assign)
$branches = $conn->query("SELECT * FROM branches ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Ambil semua user (owner lihat semua, lainnya lihat sesama cabang)
if (isOwner()) {
    $res = $conn->query("SELECT u.*, b.nama_cabang FROM users u LEFT JOIN branches b ON u.branch_id=b.id ORDER BY FIELD(u.level,'owner','admin','admin_cadangan'), u.username");
} else {
    $myBranch = (int)($u['branch_id'] ?? 0);
    $res = $conn->query("SELECT u.*, b.nama_cabang FROM users u LEFT JOIN branches b ON u.branch_id=b.id WHERE u.branch_id=$myBranch ORDER BY FIELD(u.level,'owner','admin','admin_cadangan'), u.username");
}
$users = $res->fetch_all(MYSQLI_ASSOC);

// Count per level
$lvlCounts = ['owner' => 0, 'admin' => 0, 'admin_cadangan' => 0];
foreach ($users as $usr) {
    if (isset($lvlCounts[$usr['level']])) $lvlCounts[$usr['level']]++;
}

renderHeader('Manajemen Pengguna', 'users');
echo flashGet();
?>
<link rel="stylesheet" href="modules/users/users.css">

<!-- Stats -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <span class="user-count-badge" style="background:rgba(255,107,0,.12);color:var(--primary);border:1px solid rgba(255,107,0,.25)">
    👑 <?= $lvlCounts['owner'] ?> Owner
  </span>
  <span class="user-count-badge" style="background:rgba(59,130,246,.12);color:#93C5FD;border:1px solid rgba(59,130,246,.25)">
    🛡️ <?= $lvlCounts['admin'] ?> Admin
  </span>
  <span class="user-count-badge" style="background:rgba(139,92,246,.12);color:#C4B5FD;border:1px solid rgba(139,92,246,.25)">
    🔵 <?= $lvlCounts['admin_cadangan'] ?> Admin Cadangan
  </span>
  <span class="user-count-badge" style="background:var(--card2);color:var(--text2);border:1px solid var(--border)">
    👥 <?= count($users) ?> Total
  </span>
</div>

<?php if (isOwner()): ?>
<div class="auth-info mb-3" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.18);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#93C5FD">
  💡 <strong>Panduan Owner:</strong> Ubah level pengguna dengan dropdown di kolom Level. Assign cabang untuk Admin/Admin Cadangan agar mereka hanya bisa akses data cabangnya.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">👥 Daftar Pengguna <?= !isOwner() ? '– Cabang Saya' : '' ?></div>

  <?php if (empty($users)): ?>
    <div class="empty-state"><div class="icon">👥</div><p>Belum ada pengguna terdaftar</p></div>
  <?php else: ?>
  <div>
    <?php foreach ($users as $usr):
      $isMe      = ($usr['id'] == $u['id']);
      $lvl       = $usr['level'];
      $avCls     = match($lvl) { 'owner' => 'avatar-owner', 'admin' => 'avatar-admin', default => 'avatar-cadangan' };
      $initial   = strtoupper(mb_substr($usr['username'], 0, 1));
    ?>
    <div class="user-card">
      <!-- Avatar -->
      <div class="user-card-avatar <?= $avCls ?>"><?= $initial ?></div>

      <!-- Info -->
      <div class="user-card-info">
        <div class="user-card-name">
          <?= htmlspecialchars($usr['username']) ?>
          <?php if ($isMe): ?><span class="you-tag">Anda</span><?php endif; ?>
          <span class="badge badge-<?= $lvl ?>"><?= levelLabel($lvl) ?></span>
        </div>
        <div class="user-card-sub">
          📧 <?= htmlspecialchars($usr['email']) ?>
          &nbsp;·&nbsp; 📱 <?= htmlspecialchars($usr['phone']) ?>
          &nbsp;·&nbsp; 🏠 <?= htmlspecialchars($usr['nama_cabang'] ?? 'Belum ada cabang') ?>
          &nbsp;·&nbsp; 📅 <?= date('d/m/Y', strtotime($usr['created_at'])) ?>
        </div>
      </div>

      <!-- Actions (owner only for non-self) -->
      <?php if (isOwner() && !$isMe): ?>
      <div class="user-card-actions">
        <!-- Ubah Level -->
        <form method="POST" action="index.php?page=users">
          <input type="hidden" name="action" value="change_level">
          <input type="hidden" name="id" value="<?= $usr['id'] ?>">
          <select name="level" class="level-select" onchange="this.form.submit()" title="Ubah level">
            <option value="owner"          <?= $lvl==='owner'?'selected':'' ?>>👑 Owner</option>
            <option value="admin"          <?= $lvl==='admin'?'selected':'' ?>>🛡️ Admin</option>
            <option value="admin_cadangan" <?= $lvl==='admin_cadangan'?'selected':'' ?>>🔵 Admin Cadangan</option>
          </select>
        </form>

        <!-- Assign Cabang -->
        <form method="POST" action="index.php?page=users">
          <input type="hidden" name="action" value="assign_branch">
          <input type="hidden" name="id" value="<?= $usr['id'] ?>">
          <select name="branch_id" class="branch-select" onchange="this.form.submit()" title="Assign cabang">
            <option value="0">🏠 Pilih Cabang</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ($usr['branch_id']==$b['id'])?'selected':'' ?>>
                <?= htmlspecialchars($b['nama_cabang']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <!-- Hapus -->
        <form method="POST" action="index.php?page=users" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($usr['username'])) ?>?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $usr['id'] ?>">
          <button class="btn btn-xs btn-danger" title="Hapus">🗑️</button>
        </form>
      </div>
      <?php elseif ($isMe): ?>
        <span class="text-sm text-muted">(Akun aktif)</span>
      <?php else: ?>
        <span class="text-sm text-muted">–</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>

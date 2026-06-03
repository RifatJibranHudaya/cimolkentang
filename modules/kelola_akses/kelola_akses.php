<?php
// modules/kelola_akses/kelola_akses.php – Halaman Kelola Akses (Superadmin Only)
require_once __DIR__ . '/../../functions.php';
requireLogin();

if (!isSuperadmin()) {
    flashSet('error', 'Hanya Superadmin yang dapat mengakses halaman ini.');
    header('Location: index.php?page=dashboard');
    exit;
}

global $conn;
$u = currentUser();

// Get all users except current superadmin
$res = $conn->query("SELECT u.*, b.nama_cabang FROM users u LEFT JOIN branches b ON u.branch_id=b.id WHERE u.id != {$u['id']} ORDER BY FIELD(u.level,'superadmin','owner','admin','admin_cadangan'), u.username");
$users = $res->fetch_all(MYSQLI_ASSOC);

// Get all permissions
$allPerms = [];
$permRes = $conn->query("SELECT * FROM user_permissions");
while ($p = $permRes->fetch_assoc()) {
    $allPerms[$p['user_id']][$p['feature']] = $p;
}

$features = ['produk', 'kasir', 'stok', 'produksi', 'operasional'];
$featureIcons = [
    'produk' => '🍔',
    'kasir' => '🧾',
    'stok' => '📦',
    'produksi' => '🛒',
    'operasional' => '🔧',
];

// Count per level
$lvlCounts = ['superadmin' => 0, 'owner' => 0, 'admin' => 0, 'admin_cadangan' => 0];
foreach ($users as $usr) {
    if (isset($lvlCounts[$usr['level']])) $lvlCounts[$usr['level']]++;
}

renderHeader('Kelola Akses', 'akses');
?>
<link rel="stylesheet" href="modules/kelola_akses/akses.css">

<!-- Header -->
<div class="akses-header">
  <div>
    <h2>🔑 Kelola Hak Akses Pengguna</h2>
    <p style="font-size:.84rem;color:var(--text3);margin:4px 0 0">Atur hak akses CRUD setiap pengguna terhadap fitur-fitur sistem</p>
  </div>
  <div class="akses-stats">
    <span class="akses-stat">👥 <?= count($users) ?> Pengguna</span>
    <span class="akses-stat" style="color:#22C55E;border-color:rgba(34,197,94,.3)">🔓 <?= count($allPerms) ?> Punya Akses</span>
  </div>
</div>

<!-- Info Banner -->
<div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.18);border-radius:10px;padding:14px 18px;font-size:.84rem;color:#93C5FD;margin-bottom:20px">
  💡 <strong>Panduan:</strong> Aktifkan toggle di baris pengguna untuk memberikan akses cepat. Klik tombol <strong>Detail</strong> untuk mengatur akses per-fitur secara rinci (Kasir, Produk, Stok, dll).
</div>

<!-- Table -->
<div class="akses-table-wrap">
  <table class="akses-table" id="aksesTable">
    <thead>
      <tr>
        <th style="width:5%">#</th>
        <th>Pengguna</th>
        <th>Role</th>
        <th class="center">Create</th>
        <th class="center">Read</th>
        <th class="center">Update</th>
        <th class="center">Delete</th>
        <th class="center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1; foreach ($users as $usr):
        $lvl = $usr['level'];
        $avCls = match($lvl) { 'superadmin' => 'av-superadmin', 'owner' => 'av-owner', 'admin' => 'av-admin', default => 'av-cadangan' };
        $roleCls = match($lvl) { 'superadmin' => 'role-superadmin', 'owner' => 'role-owner', 'admin' => 'role-admin', default => 'role-cadangan' };
        $initial = strtoupper(mb_substr($usr['username'], 0, 1));
        $uid = $usr['id'];
        
        // Calculate bulk toggle states (ON if ALL features have this perm)
        $userPerms = $allPerms[$uid] ?? [];
        $bulkCreate = true; $bulkRead = true; $bulkUpdate = true; $bulkDelete = true;
        foreach ($features as $f) {
            $fp = $userPerms[$f] ?? null;
            if (!$fp || !$fp['can_create']) $bulkCreate = false;
            if (!$fp || !$fp['can_read'])   $bulkRead = false;
            if (!$fp || !$fp['can_update']) $bulkUpdate = false;
            if (!$fp || !$fp['can_delete']) $bulkDelete = false;
        }
        
        $isSA = ($lvl === 'superadmin');
      ?>
      <!-- Main Row -->
      <tr id="row-<?= $uid ?>">
        <td style="color:var(--text3);font-size:.82rem"><?= $no++ ?></td>
        <td>
          <div class="akses-user">
            <div class="akses-avatar <?= $avCls ?>"><?= $initial ?></div>
            <div class="akses-user-info">
              <div class="akses-user-name"><?= htmlspecialchars($usr['username']) ?></div>
              <div class="akses-user-email"><?= htmlspecialchars($usr['email'] ?? '-') ?></div>
            </div>
          </div>
        </td>
        <td><span class="role-badge <?= $roleCls ?>"><?= levelLabel($lvl) ?></span></td>
        
        <?php if ($isSA): ?>
          <td colspan="4" class="sa-notice">⭐ Superadmin memiliki semua akses otomatis</td>
        <?php else: ?>
          <td class="toggle-cell">
            <label class="toggle-switch">
              <input type="checkbox" <?= $bulkCreate ? 'checked' : '' ?> onchange="toggleBulk(<?= $uid ?>, 'can_create', this.checked)">
              <span class="toggle-slider"></span>
            </label>
          </td>
          <td class="toggle-cell">
            <label class="toggle-switch">
              <input type="checkbox" <?= $bulkRead ? 'checked' : '' ?> onchange="toggleBulk(<?= $uid ?>, 'can_read', this.checked)">
              <span class="toggle-slider"></span>
            </label>
          </td>
          <td class="toggle-cell">
            <label class="toggle-switch">
              <input type="checkbox" <?= $bulkUpdate ? 'checked' : '' ?> onchange="toggleBulk(<?= $uid ?>, 'can_update', this.checked)">
              <span class="toggle-slider"></span>
            </label>
          </td>
          <td class="toggle-cell">
            <label class="toggle-switch">
              <input type="checkbox" <?= $bulkDelete ? 'checked' : '' ?> onchange="toggleBulk(<?= $uid ?>, 'can_delete', this.checked)">
              <span class="toggle-slider"></span>
            </label>
          </td>
        <?php endif; ?>
        
        <td class="toggle-cell">
          <?php if (!$isSA): ?>
          <button type="button" class="btn-detail" onclick="toggleDetail(<?= $uid ?>)">
            📋 Detail
          </button>
          <?php else: ?>
          <span style="font-size:.75rem;color:var(--text3)">—</span>
          <?php endif; ?>
        </td>
      </tr>
      
      <!-- Detail Row (Hidden by default) -->
      <?php if (!$isSA): ?>
      <tr class="detail-row" id="detail-<?= $uid ?>">
        <td colspan="8">
          <div class="detail-content">
            <h4>📋 Detail Akses: <strong><?= htmlspecialchars($usr['username']) ?></strong></h4>
            <div class="detail-grid" id="detailGrid-<?= $uid ?>">
              <?php foreach ($features as $feat):
                $fp = $userPerms[$feat] ?? [];
                $cC = !empty($fp['can_create']);
                $cR = !empty($fp['can_read']);
                $cU = !empty($fp['can_update']);
                $cD = !empty($fp['can_delete']);
                $icon = $featureIcons[$feat] ?? '📁';
              ?>
              <div class="detail-feature-card">
                <div class="detail-feature-name"><?= $icon ?> <?= ucfirst($feat) ?></div>
                <div class="detail-feature-toggles">
                  <span class="detail-perm-chip <?= $cR ? 'active' : '' ?>" 
                        data-uid="<?= $uid ?>" data-feat="<?= $feat ?>" data-perm="can_read"
                        onclick="togglePerm(this)">
                    👁️ Read
                  </span>
                  <span class="detail-perm-chip <?= $cC ? 'active' : '' ?>" 
                        data-uid="<?= $uid ?>" data-feat="<?= $feat ?>" data-perm="can_create"
                        onclick="togglePerm(this)">
                    ➕ Create
                  </span>
                  <span class="detail-perm-chip <?= $cU ? 'active' : '' ?>" 
                        data-uid="<?= $uid ?>" data-feat="<?= $feat ?>" data-perm="can_update"
                        onclick="togglePerm(this)">
                    ✏️ Update
                  </span>
                  <span class="detail-perm-chip <?= $cD ? 'active' : '' ?>" 
                        data-uid="<?= $uid ?>" data-feat="<?= $feat ?>" data-perm="can_delete"
                        onclick="togglePerm(this)">
                    🗑️ Delete
                  </span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($users)): ?>
<div style="text-align:center;padding:40px;color:var(--text3)">
  <div style="font-size:3rem;margin-bottom:10px">👥</div>
  <p>Tidak ada pengguna lain untuk dikelola</p>
</div>
<?php endif; ?>

<script>
// Toggle detail row
function toggleDetail(uid) {
  const row = document.getElementById('detail-' + uid);
  if (!row) return;
  row.classList.toggle('open');
  
  // Scroll into view smoothly
  if (row.classList.contains('open')) {
    setTimeout(() => row.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
  }
}

// Toggle single permission chip
function togglePerm(el) {
  const uid  = el.dataset.uid;
  const feat = el.dataset.feat;
  const perm = el.dataset.perm;
  const isActive = el.classList.contains('active');
  const newVal = isActive ? 0 : 1;

  // Optimistic UI
  el.classList.toggle('active');

  const fd = new FormData();
  fd.append('action', 'toggle_perm');
  fd.append('user_id', uid);
  fd.append('feature', feat);
  fd.append('perm', perm);
  fd.append('value', newVal);

  fetch('modules/kelola_akses/akses_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        el.classList.toggle('active'); // Revert
        alert(res.msg);
      }
    })
    .catch(() => {
      el.classList.toggle('active'); // Revert
    });
}

// Toggle bulk (all features for a user)
function toggleBulk(uid, perm, checked) {
  const val = checked ? 1 : 0;

  const fd = new FormData();
  fd.append('action', 'toggle_bulk');
  fd.append('user_id', uid);
  fd.append('perm', perm);
  fd.append('value', val);

  fetch('modules/kelola_akses/akses_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        // Update detail chips if open
        const grid = document.getElementById('detailGrid-' + uid);
        if (grid) {
          grid.querySelectorAll(`.detail-perm-chip[data-perm="${perm}"]`).forEach(chip => {
            if (val) chip.classList.add('active');
            else     chip.classList.remove('active');
          });
        }
      } else {
        alert(res.msg);
      }
    });
}
</script>

<?php renderFooter(); ?>

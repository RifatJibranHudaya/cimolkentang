<?php
// modules/dashboard/dashboard.php – Halaman Dashboard
require_once __DIR__ . '/../../functions.php';
requireLogin();
global $conn;

$u     = currentUser();
$today = date('Y-m-d');
$week  = date('Y-m-d', strtotime('-6 days'));
$month = date('Y-m-01');

// Filter by branch kalau bukan owner
$branchWhere = '';
if (!isOwner() && $u['branch_id']) {
    $bid = (int)$u['branch_id'];
    $branchWhere = " AND branch_id=$bid";
}

$todayIncome = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)='$today'$branchWhere")->fetch_assoc()['t'];
$weekIncome  = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)>='$week'$branchWhere")->fetch_assoc()['t'];
$monthIncome = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)>='$month'$branchWhere")->fetch_assoc()['t'];
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)='$today'$branchWhere")->fetch_assoc()['c'];
$prodToday   = $conn->query("SELECT COALESCE(SUM(harga),0) as t FROM production WHERE DATE(created_at)='$today'")->fetch_assoc()['t'];
$opTotal     = $conn->query("SELECT COUNT(*) as c FROM operational")->fetch_assoc()['c'];
$userCount   = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];

// Nama hari Indonesia
$days = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$months_id = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$dayName   = $days[date('l')] ?? date('l');
$monthName = $months_id[date('F')] ?? date('F');
$dateStr   = $dayName . ', ' . date('d') . ' ' . $monthName . ' ' . date('Y');

renderHeader('Dashboard', 'dashboard');
?>
<link rel="stylesheet" href="modules/dashboard/dashboard.css">

<!-- Welcome Banner -->
<div class="welcome-banner">
  <div class="welcome-h">Selamat datang, <?= htmlspecialchars($u['username']) ?>! 👋</div>
  <div class="welcome-sub"><?= $dateStr ?> &nbsp;•&nbsp; <?= levelLabel($u['level']) ?></div>
</div>

<!-- Quick Actions -->
<div class="quick-actions mb-3">
  <a href="index.php?page=kasir" class="quick-btn"><span class="q-icon">🧾</span> Input Kasir</a>
  <a href="index.php?page=stok" class="quick-btn"><span class="q-icon">📦</span> Input Stok</a>
  <a href="index.php?page=produksi" class="quick-btn"><span class="q-icon">🛒</span> Catat Belanja</a>
  <?php if (isAdmin()): ?>
  <a href="index.php?page=operasional" class="quick-btn"><span class="q-icon">🔧</span> Operasional</a>
  <?php endif; ?>
</div>

<!-- Income Cards -->
<div class="income-grid mb-3">
  <div class="income-card daily">
    <div class="income-val"><?= rupiah($todayIncome) ?></div>
    <div class="income-lbl">💰 Pendapatan Hari Ini</div>
  </div>
  <div class="income-card weekly">
    <div class="income-val"><?= rupiah($weekIncome) ?></div>
    <div class="income-lbl">📅 Pendapatan Minggu Ini</div>
  </div>
  <div class="income-card monthly">
    <div class="income-val"><?= rupiah($monthIncome) ?></div>
    <div class="income-lbl">📊 Pendapatan Bulan Ini</div>
  </div>
</div>

<!-- Stat Cards -->
<div class="card-grid mb-3">
  <div class="stat-card">
    <div class="stat-val"><?= $totalOrders ?></div>
    <div class="stat-label">🧾 Order Hari Ini</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= rupiah($prodToday) ?></div>
    <div class="stat-label">🛒 Belanja Hari Ini</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $opTotal ?></div>
    <div class="stat-label">🔧 Total Alat</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $userCount ?></div>
    <div class="stat-label">👥 Total Pengguna</div>
  </div>
</div>

<!-- Dashboard Grid: Recent Orders + Summary -->
<div class="dashboard-grid">

  <!-- Recent Orders -->
  <div class="card">
    <div class="card-title">🧾 Order Terbaru Hari Ini</div>
    <?php
    $sql = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE DATE(o.created_at)='$today'$branchWhere ORDER BY o.created_at DESC LIMIT 10";
    $res = $conn->query($sql);
    if ($res->num_rows === 0):
    ?>
      <div class="empty-state"><div class="icon">📋</div><p>Belum ada order hari ini</p></div>
    <?php else: ?>
    <div class="table-wrap recent-table">
      <table>
        <thead><tr><th>Waktu</th><th>Kasir</th><th>Kategori</th><th>Total</th></tr></thead>
        <tbody>
        <?php while ($r = $res->fetch_assoc()):
            $ktCls = 'badge-' . $r['kategori'];
        ?>
          <tr>
            <td><?= date('H:i', strtotime($r['created_at'])) ?></td>
            <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
            <td><span class="badge <?= $ktCls ?>"><?= $r['kategori'] ?></span></td>
            <td class="text-primary" style="font-weight:600"><?= rupiah($r['total']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Card -->
  <div>
    <div class="card mb-3">
      <div class="card-title">📊 Ringkasan Bulan Ini</div>
      <?php
      $catSums = $conn->query("SELECT kategori, COUNT(*) as cnt, SUM(total) as total FROM orders WHERE DATE(created_at)>='$month'$branchWhere GROUP BY kategori");
      while ($cs = $catSums->fetch_assoc()):
      ?>
        <div class="summary-row">
          <span class="summary-label">
            <?= $cs['kategori'] === 'offline' ? '🏪' : ($cs['kategori'] === 'shopeefood' ? '🛍️' : '🛵') ?>
            <?= ucfirst($cs['kategori']) ?> (<?= $cs['cnt'] ?> order)
          </span>
          <span class="summary-val"><?= rupiah($cs['total']) ?></span>
        </div>
      <?php endwhile; ?>
      <div class="summary-row" style="border-top:1px solid rgba(255,107,0,.15);padding-top:12px;margin-top:4px">
        <span class="summary-label" style="color:#FF9A3C;font-weight:600">💰 Total Bulan Ini</span>
        <span class="summary-val" style="color:#FF6B00;font-family:'Playfair Display',serif;font-size:1.1rem"><?= rupiah($monthIncome) ?></span>
      </div>
    </div>

    <?php if (isOwner()): ?>
    <div class="card">
      <div class="card-title">👥 Level Pengguna</div>
      <?php
      $lvlStats = $conn->query("SELECT level, COUNT(*) as cnt FROM users GROUP BY level");
      while ($ls = $lvlStats->fetch_assoc()):
      ?>
        <div class="summary-row">
          <span class="summary-label"><?= levelLabel($ls['level']) ?></span>
          <span class="badge badge-<?= $ls['level'] ?>"><?= $ls['cnt'] ?> orang</span>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFooter(); ?>

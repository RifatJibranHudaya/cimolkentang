<?php
// modules/stok/stok.php – Halaman Manajemen Stok
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;
$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/stok_handler.php';
    exit;
}

$products = [
    ['emoji'=>'🫙','name'=>'Cimol'],
    ['emoji'=>'🥔','name'=>'Kentang'],
    ['emoji'=>'🐟','name'=>'Otak-otak'],
    ['emoji'=>'🟡','name'=>'Tahu'],
    ['emoji'=>'🌭','name'=>'Sosis'],
    ['emoji'=>'🍡','name'=>'Bakso'],
];

$today = date('Y-m-d');
$week  = date('Y-m-d', strtotime('monday this week'));
$month = date('Y-m-01');

$branchWhere = '';
if (!isOwner() && $u['branch_id']) {
    $bid = (int)$u['branch_id'];
    $branchWhere = " AND branch_id=$bid";
}

// Stok hari ini (pembukaan & penutupan)
$pembukaan = [];
$penutupan = [];
$res = $conn->query("SELECT produk, jumlah, tipe FROM stock_records WHERE tanggal='$today'$branchWhere");
while ($r = $res->fetch_assoc()) {
    if ($r['tipe'] === 'pembukaan') $pembukaan[$r['produk']] = $r['jumlah'];
    else                             $penutupan[$r['produk']] = $r['jumlah'];
}

// Filter riwayat
$filter = $_GET['filter'] ?? 'today';
$filterWhere = match($filter) {
    'week'  => "tanggal >= '$week'",
    'month' => "tanggal >= '$month'",
    default => "tanggal = '$today'",
};

// Auto-totals per produk per periode
$prodWeekTotals  = [];
$prodMonthTotals = [];
foreach ($products as $p) {
    $pn = $p['name'];
    // Total pakai seminggu (pembukaan - penutupan per hari, sum semua hari)
    $wRes = $conn->query("
        SELECT
          SUM(CASE WHEN tipe='pembukaan' THEN jumlah ELSE 0 END) -
          SUM(CASE WHEN tipe='penutupan' THEN jumlah ELSE 0 END) as pakai
        FROM stock_records
        WHERE produk='$pn' AND tanggal>='$week'$branchWhere
    ");
    $prodWeekTotals[$pn] = (int)($wRes->fetch_assoc()['pakai'] ?? 0);

    $mRes = $conn->query("
        SELECT
          SUM(CASE WHEN tipe='pembukaan' THEN jumlah ELSE 0 END) -
          SUM(CASE WHEN tipe='penutupan' THEN jumlah ELSE 0 END) as pakai
        FROM stock_records
        WHERE produk='$pn' AND tanggal>='$month'$branchWhere
    ");
    $prodMonthTotals[$pn] = (int)($mRes->fetch_assoc()['pakai'] ?? 0);
}

renderHeader('Manajemen Stok', 'stok');
echo flashGet();
?>
<link rel="stylesheet" href="modules/stok/stok.css">

<!-- Toggle Buttons -->
<div class="stok-toggle-btns">
  <button class="btn btn-primary" onclick="toggleForm('formPembukaan')">📦 Input Stok Pembukaan</button>
  <button class="btn btn-accent"  onclick="toggleForm('formPenutupan')">🏁 Input Stok Penutupan</button>
</div>

<!-- Form Stok Pembukaan -->
<div id="formPembukaan" class="card mb-3" style="display:none">
  <div class="card-title">📦 Stok Pembukaan – Stok Dibawa Hari Ini</div>
  <form method="POST">
    <input type="hidden" name="action" value="save_pembukaan">
    <div class="form-group">
      <label class="form-label">📅 Tanggal</label>
      <input type="date" name="tanggal" class="form-control" value="<?= $today ?>" style="max-width:200px">
    </div>
    <div class="stok-grid mb-2">
      <?php foreach ($products as $p):
        $val = $pembukaan[$p['name']] ?? ''; ?>
        <div class="stok-item">
          <div class="stok-emoji"><?= $p['emoji'] ?></div>
          <div class="stok-name"><?= $p['name'] ?></div>
          <input type="number" name="produk[<?= $p['name'] ?>]" class="stok-input" value="<?= $val ?>" placeholder="0" min="0">
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary">💾 Simpan Stok Pembukaan</button>
  </form>
</div>

<!-- Form Stok Penutupan -->
<div id="formPenutupan" class="card mb-3" style="display:none">
  <div class="card-title">🏁 Stok Penutupan – Sisa Stok Akhir Hari</div>
  <form method="POST">
    <input type="hidden" name="action" value="save_penutupan">
    <div class="form-group">
      <label class="form-label">📅 Tanggal</label>
      <input type="date" name="tanggal" class="form-control" value="<?= $today ?>" style="max-width:200px">
    </div>
    <div class="stok-grid mb-2">
      <?php foreach ($products as $p):
        $val = $penutupan[$p['name']] ?? ''; ?>
        <div class="stok-item">
          <div class="stok-emoji"><?= $p['emoji'] ?></div>
          <div class="stok-name"><?= $p['name'] ?></div>
          <input type="number" name="produk[<?= $p['name'] ?>]" class="stok-input" value="<?= $val ?>" placeholder="0" min="0">
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-accent">💾 Simpan Stok Penutupan</button>
  </form>
</div>

<!-- Ringkasan Stok Hari Ini -->
<div class="card mb-3">
  <div class="card-title">📊 Ringkasan Stok Hari Ini – <?= date('d/m/Y') ?></div>
  <div class="stok-summary-grid">
    <?php foreach ($products as $p):
      $buka  = $pembukaan[$p['name']] ?? null;
      $tutup = $penutupan[$p['name']] ?? null;
      $pakai = (is_numeric($buka) && is_numeric($tutup)) ? ($buka - $tutup) : null;
      $sisaClass = '';
      if (is_numeric($pakai)) {
          $sisaClass = $pakai < 0 ? 'minus' : ($pakai == 0 ? 'zero' : '');
      }
    ?>
      <div class="stok-summary-card">
        <div class="stok-summary-emoji"><?= $p['emoji'] ?></div>
        <div class="stok-summary-name"><?= $p['name'] ?></div>
        <div class="stok-summary-row">
          <span>Buka</span>
          <span class="stok-summary-val"><?= $buka !== null ? $buka : '–' ?></span>
        </div>
        <div class="stok-summary-row">
          <span>Tutup</span>
          <span class="stok-summary-val"><?= $tutup !== null ? $tutup : '–' ?></span>
        </div>
        <div class="stok-pakai <?= $sisaClass ?>">
          Pakai: <?= $pakai !== null ? $pakai : '–' ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Auto-Total Seminggu & Sebulan -->
<div class="card mb-3">
  <div class="card-title">📈 Auto Total Pemakaian Stok</div>
  <div class="d-flex gap-3 flex-wrap mb-2">
    <span class="text-sm text-muted">Periode:</span>
    <span class="badge badge-admin">📅 Minggu Ini (<?= date('d/m', strtotime($week)) ?> – <?= date('d/m') ?>)</span>
    <span class="badge badge-admin_cadangan">📊 Bulan Ini (<?= date('F Y') ?>)</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Produk</th>
          <th>📅 Minggu Ini (pcs)</th>
          <th>📊 Bulan Ini (pcs)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><strong><?= $p['emoji'] ?> <?= $p['name'] ?></strong></td>
          <td style="color:var(--accent);font-weight:600"><?= $prodWeekTotals[$p['name']] ?></td>
          <td style="color:#C4B5FD;font-weight:600"><?= $prodMonthTotals[$p['name']] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Riwayat Stok -->
<div class="card">
  <div class="section-header">
    <div class="card-title" style="margin:0">📋 Riwayat Stok</div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="?page=stok&filter=today" class="btn btn-xs <?= $filter==='today'?'btn-primary':'btn-secondary' ?>">Hari</a>
      <a href="?page=stok&filter=week"  class="btn btn-xs <?= $filter==='week' ?'btn-primary':'btn-secondary' ?>">Minggu</a>
      <a href="?page=stok&filter=month" class="btn btn-xs <?= $filter==='month'?'btn-primary':'btn-secondary' ?>">Bulan</a>
      <a href="export.php?type=stok&filter=<?= $filter ?>" class="btn btn-xs btn-accent">⬇️ CSV</a>
    </div>
  </div>
  <?php
  $res = $conn->query("SELECT sr.*, u.username FROM stock_records sr LEFT JOIN users u ON sr.user_id=u.id WHERE $filterWhere ORDER BY sr.tanggal DESC, sr.tipe ASC, sr.produk ASC");
  if ($res->num_rows === 0):
  ?>
    <div class="empty-state"><div class="icon">📦</div><p>Belum ada riwayat stok</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tanggal</th><th>Tipe</th><th>Produk</th><th>Jumlah</th><th>Dicatat Oleh</th></tr>
      </thead>
      <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['tanggal'] ?></td>
          <td><span class="badge badge-<?= $r['tipe'] ?>"><?= ucfirst($r['tipe']) ?></span></td>
          <td><?= htmlspecialchars($r['produk']) ?></td>
          <td style="font-weight:600"><?= $r['jumlah'] ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>

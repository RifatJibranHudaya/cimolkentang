<?php
// modules/produksi/produksi.php – Halaman Produksi & Belanja
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;
$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/produksi_handler.php';
    exit;
}

$today = date('Y-m-d');
$week  = date('Y-m-d', strtotime('monday this week'));
$month = date('Y-m-01');

// Filter periode
$filter = $_GET['filter'] ?? 'month';
$filterWhere = match($filter) {
    'today' => "p.tanggal = '$today'",
    'week'  => "p.tanggal >= '$week'",
    default => "p.tanggal >= '$month'",
};

// Filter tempat
$filterTempat = trim($_GET['tempat'] ?? '');
if ($filterTempat) {
    $ft = $conn->real_escape_string($filterTempat);
    $filterWhere .= " AND p.tempat LIKE '%$ft%'";
}

// Total pengeluaran
$total = $conn->query("SELECT COALESCE(SUM(harga),0) as t FROM production p WHERE $filterWhere")->fetch_assoc()['t'];

// List tempat unik untuk filter
$tempatList = $conn->query("SELECT DISTINCT tempat FROM production WHERE tempat!='' ORDER BY tempat")->fetch_all(MYSQLI_ASSOC);

// Data edit (jika ada ?edit=id)
$editData = null;
if (isset($_GET['edit']) && (isAdmin() || isOwner())) {
    $eid = (int)$_GET['edit'];
    $editData = $conn->query("SELECT * FROM production WHERE id=$eid")->fetch_assoc();
}

renderHeader('Produksi & Belanja', 'produksi');
echo flashGet();
?>
<link rel="stylesheet" href="modules/produksi/produksi.css">

<!-- Action Buttons -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <button class="btn btn-primary" onclick="toggleForm('formProd')">➕ Tambah Catatan Belanja</button>
</div>

<!-- Form Tambah -->
<div id="formProd" class="card mb-3" style="display:<?= $editData ? 'none' : 'none' ?>">
  <div class="card-title">🛒 Catatan Belanja / Supplier</div>
  <form method="POST" action="index.php?page=produksi">
    <input type="hidden" name="action" value="save">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📝 Nama Item / Bahan</label>
        <input type="text" name="nama_item" class="form-control" placeholder="cth: Tepung 1kg, Minyak goreng" required>
      </div>
      <div class="form-group">
        <label class="form-label">💰 Harga (Rp)</label>
        <input type="number" name="harga" class="form-control" placeholder="0" min="0" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">🏭 Nama Supplier</label>
        <input type="text" name="supplier" class="form-control" placeholder="Nama supplier / toko">
      </div>
      <div class="form-group">
        <label class="form-label">📍 Tempat Pembelian</label>
        <input type="text" name="tempat" class="form-control" placeholder="Pasar, Toko, dll">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📅 Tanggal Pembelian</label>
        <input type="date" name="tanggal" class="form-control" value="<?= $today ?>">
      </div>
      <div class="form-group">
        <label class="form-label">📝 Keterangan (Opsional)</label>
        <input type="text" name="keterangan" class="form-control" placeholder="Catatan tambahan">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">💾 Simpan</button>
  </form>
</div>

<!-- Form Edit (muncul kalau ada ?edit=id) -->
<?php if ($editData): ?>
<div class="card mb-3" style="border-color:rgba(234,179,8,.3)">
  <div class="card-title">✏️ Edit Data Produksi</div>
  <div class="edit-badge mb-2">✏️ Mengedit item: <strong><?= htmlspecialchars($editData['nama_item']) ?></strong></div>
  <form method="POST" action="index.php?page=produksi">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📝 Nama Item / Bahan</label>
        <input type="text" name="nama_item" class="form-control" value="<?= htmlspecialchars($editData['nama_item']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">💰 Harga (Rp)</label>
        <input type="number" name="harga" class="form-control" value="<?= $editData['harga'] ?>" min="0" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">🏭 Nama Supplier</label>
        <input type="text" name="supplier" class="form-control" value="<?= htmlspecialchars($editData['supplier']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">📍 Tempat Pembelian</label>
        <input type="text" name="tempat" class="form-control" value="<?= htmlspecialchars($editData['tempat']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📅 Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="<?= $editData['tanggal'] ?>">
      </div>
      <div class="form-group">
        <label class="form-label">📝 Keterangan</label>
        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($editData['keterangan']) ?>">
      </div>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-warning">💾 Simpan Perubahan</button>
      <a href="index.php?page=produksi" class="btn btn-secondary">✕ Batal</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Total Pengeluaran -->
<div class="total-summary-box">
  <div>
    <div class="total-summary-label">Total Pengeluaran Belanja</div>
    <div style="font-size:.78rem;color:var(--text3);margin-top:2px">
      Periode: <strong style="color:var(--text2)"><?= $filter==='today'?'Hari Ini':($filter==='week'?'Minggu Ini':'Bulan Ini') ?></strong>
      <?php if ($filterTempat): ?> · Tempat: <strong style="color:var(--text2)"><?= htmlspecialchars($filterTempat) ?></strong><?php endif; ?>
    </div>
  </div>
  <div class="total-summary-val"><?= rupiah($total) ?></div>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-3">
  <label>Periode:</label>
  <select onchange="updateFilter('filter', this.value)">
    <option value="today" <?= $filter==='today'?'selected':'' ?>>Hari Ini</option>
    <option value="week"  <?= $filter==='week' ?'selected':'' ?>>Minggu Ini</option>
    <option value="month" <?= $filter==='month'?'selected':'' ?>>Bulan Ini</option>
  </select>
  <label>Tempat:</label>
  <select onchange="updateFilter('tempat', this.value)">
    <option value="">Semua Tempat</option>
    <?php foreach ($tempatList as $t): ?>
      <option value="<?= htmlspecialchars($t['tempat']) ?>" <?= $filterTempat===$t['tempat']?'selected':'' ?>>
        <?= htmlspecialchars($t['tempat']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <a href="export.php?type=produksi&filter=<?= $filter ?>&tempat=<?= urlencode($filterTempat) ?>" class="btn btn-xs btn-accent">⬇️ CSV</a>
</div>

<!-- Tabel Riwayat -->
<div class="card">
  <div class="card-title">📋 Riwayat Pembelian</div>
  <?php
  $res = $conn->query("SELECT p.*, u.username, eu.username as editor FROM production p LEFT JOIN users u ON p.user_id=u.id LEFT JOIN users eu ON p.edited_by=eu.id WHERE $filterWhere ORDER BY p.tanggal DESC, p.created_at DESC");
  if ($res->num_rows === 0):
  ?>
    <div class="empty-state"><div class="icon">🛒</div><p>Belum ada riwayat pembelian</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>Item</th><th>Harga</th><th>Supplier</th><th>Tempat</th>
          <th>Keterangan</th><th>Oleh</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['tanggal'] ?></td>
          <td>
            <strong><?= htmlspecialchars($r['nama_item']) ?></strong>
            <?php if ($r['editor']): ?>
              <div class="edit-badge" style="margin-top:4px">✏️ <?= htmlspecialchars($r['editor']) ?> · <?= date('d/m H:i', strtotime($r['edited_at'])) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-primary" style="font-weight:600"><?= rupiah($r['harga']) ?></td>
          <td><?= htmlspecialchars($r['supplier']) ?></td>
          <td><?= htmlspecialchars($r['tempat']) ?></td>
          <td class="text-sm text-muted"><?= htmlspecialchars($r['keterangan']) ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
          <td>
            <div class="action-btns">
              <a href="index.php?page=produksi_detail&id=<?= $r['id'] ?>" class="btn btn-xs btn-info">🔍</a>
              <a href="index.php?page=produksi&edit=<?= $r['id'] ?>&filter=<?= $filter ?>" class="btn btn-xs btn-warning">✏️</a>
              <?php if (isOwner() || $r['user_id'] == $u['id']): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus data ini?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-xs btn-danger">🗑️</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
function updateFilter(key, val) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, val);
  window.location.href = url.toString();
}
function toggleForm(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isHidden = el.style.display === 'none' || el.style.display === '';
  el.style.display = isHidden ? 'block' : 'none';
  if (isHidden) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php renderFooter(); ?>

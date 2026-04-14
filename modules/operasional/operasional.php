<?php
// modules/operasional/operasional.php – Halaman Catatan Operasional
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin']);
global $conn;
$u = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/operasional_handler.php';
    exit;
}

$filterAlat = trim($_GET['alat'] ?? '');
$filterWhere = $filterAlat
    ? "WHERE o.nama_alat LIKE '%" . $conn->real_escape_string($filterAlat) . "%'"
    : '';

$alatList   = $conn->query("SELECT DISTINCT nama_alat FROM operational ORDER BY nama_alat")->fetch_all(MYSQLI_ASSOC);
$totalNilai = $conn->query("SELECT COALESCE(SUM(harga),0) as t FROM operational o $filterWhere")->fetch_assoc()['t'];
$totalAlat  = $conn->query("SELECT COUNT(*) as c FROM operational o $filterWhere")->fetch_assoc()['c'];
$dueSoonCnt = 0;

// Data edit
$editData = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editData = $conn->query("SELECT * FROM operational WHERE id=$eid")->fetch_assoc();
}

renderHeader('Catatan Operasional', 'operasional');
echo flashGet();
?>
<link rel="stylesheet" href="modules/operasional/operasional.css">

<!-- Stats -->
<div class="ops-stats mb-3">
  <div class="ops-stat-box">
    <div class="ops-stat-val"><?= $totalAlat ?></div>
    <div class="ops-stat-lbl">🔧 Total Alat Terdaftar</div>
  </div>
  <div class="ops-stat-box">
    <div class="ops-stat-val"><?= rupiah($totalNilai) ?></div>
    <div class="ops-stat-lbl">💰 Total Nilai Aset</div>
  </div>
</div>

<!-- Action Buttons -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <button class="btn btn-primary" onclick="toggleForm('formOps')">➕ Tambah Alat</button>
</div>

<!-- Form Tambah Alat -->
<div id="formOps" class="card mb-3" style="display:none">
  <div class="card-title">🔧 Data Alat Masak / Peralatan</div>
  <form method="POST" action="index.php?page=operasional">
    <input type="hidden" name="action" value="save">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">🔧 Nama Alat</label>
        <input type="text" name="nama_alat" class="form-control" placeholder="cth: Wajan, Kompor, Spatula" required>
      </div>
      <div class="form-group">
        <label class="form-label">🏷️ Merk</label>
        <input type="text" name="merk" class="form-control" placeholder="Nama merk alat">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">💰 Harga (Rp)</label>
        <input type="number" name="harga" class="form-control" placeholder="0" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">📍 Tempat Beli</label>
        <input type="text" name="tempat_beli" class="form-control" placeholder="Toko, Marketplace, Pasar">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📅 Tanggal Beli</label>
        <input type="date" name="tanggal_beli" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">🔄 Periode Ganti (Bulan) <span class="text-muted" style="font-weight:400">(Opsional)</span></label>
        <input type="number" name="periode_ganti" class="form-control" placeholder="0 = tidak tentu" min="0">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">📝 Keterangan <span class="text-muted" style="font-weight:400">(Opsional)</span></label>
      <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan tentang alat ini..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary">💾 Simpan Alat</button>
  </form>
</div>

<!-- Form Edit Alat -->
<?php if ($editData): ?>
<div class="card mb-3" style="border-color:rgba(234,179,8,.3)">
  <div class="card-title">✏️ Edit Data Alat</div>
  <form method="POST" action="index.php?page=operasional">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">🔧 Nama Alat</label>
        <input type="text" name="nama_alat" class="form-control" value="<?= htmlspecialchars($editData['nama_alat']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">🏷️ Merk</label>
        <input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($editData['merk']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">💰 Harga (Rp)</label>
        <input type="number" name="harga" class="form-control" value="<?= $editData['harga'] ?>" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">📍 Tempat Beli</label>
        <input type="text" name="tempat_beli" class="form-control" value="<?= htmlspecialchars($editData['tempat_beli']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">📅 Tanggal Beli</label>
        <input type="date" name="tanggal_beli" class="form-control" value="<?= $editData['tanggal_beli'] ?>">
      </div>
      <div class="form-group">
        <label class="form-label">🔄 Periode Ganti (Bulan)</label>
        <input type="number" name="periode_ganti" class="form-control" value="<?= $editData['periode_ganti'] ?>" min="0">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">📝 Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($editData['keterangan']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-warning">💾 Simpan Perubahan</button>
      <a href="index.php?page=operasional" class="btn btn-secondary">✕ Batal</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="filter-bar mb-3">
  <label>Filter Alat:</label>
  <select onchange="window.location.href='?page=operasional&alat='+this.value">
    <option value="">Semua Alat</option>
    <?php foreach ($alatList as $a): ?>
      <option value="<?= htmlspecialchars($a['nama_alat']) ?>" <?= $filterAlat===$a['nama_alat']?'selected':'' ?>>
        <?= htmlspecialchars($a['nama_alat']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <a href="export.php?type=operasional&alat=<?= urlencode($filterAlat) ?>" class="btn btn-xs btn-accent">⬇️ CSV</a>
</div>

<!-- Daftar Alat -->
<div class="card">
  <div class="card-title">📋 Daftar Alat & Peralatan</div>
  <?php
  $res = $conn->query("SELECT o.*, u.username FROM operational o LEFT JOIN users u ON o.user_id=u.id $filterWhere ORDER BY o.nama_alat, o.tanggal_beli DESC");
  if ($res->num_rows === 0):
  ?>
    <div class="empty-state"><div class="icon">🔧</div><p>Belum ada data alat</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama Alat</th><th>Merk</th><th>Harga</th><th>Tempat Beli</th>
          <th>Tgl Beli</th><th>Ganti/Bln</th><th>Keterangan</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php
      while ($r = $res->fetch_assoc()):
          $periodeGanti = $r['periode_ganti'] > 0 ? $r['periode_ganti'].' bln' : '–';
          $isDue = false;
          if ($r['periode_ganti'] > 0 && $r['tanggal_beli']) {
              $nextReplace = strtotime("+{$r['periode_ganti']} months", strtotime($r['tanggal_beli']));
              $isDue = ($nextReplace <= time());
              if ($isDue) $dueSoonCnt++;
          }
      ?>
        <tr <?= $isDue ? 'style="background:rgba(234,179,8,.05)"' : '' ?>>
          <td>
            <strong><?= htmlspecialchars($r['nama_alat']) ?></strong>
            <?php if ($isDue): ?>
              <div class="alat-badge-due" style="margin-top:4px">⚠️ Perlu diganti</div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['merk']) ?: '–' ?></td>
          <td class="text-primary" style="font-weight:600"><?= rupiah($r['harga']) ?></td>
          <td><?= htmlspecialchars($r['tempat_beli']) ?: '–' ?></td>
          <td><?= $r['tanggal_beli'] ?: '–' ?></td>
          <td><?= $periodeGanti ?></td>
          <td class="text-sm text-muted"><?= htmlspecialchars($r['keterangan']) ?: '–' ?></td>
          <td>
            <div class="action-btns">
              <a href="?page=operasional&edit=<?= $r['id'] ?>" class="btn btn-xs btn-warning">✏️</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus alat ini?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-xs btn-danger">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <p class="text-sm text-muted mt-2">⚠️ = Alat sudah melewati periode ganti yang ditentukan</p>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>

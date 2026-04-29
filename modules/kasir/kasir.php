<?php
// modules/kasir/kasir.php – Halaman Kasir (POS)
require_once __DIR__ . '/../../functions.php';
requireLevel(['owner','admin','admin_cadangan']);
global $conn;
$u = currentUser();

// Dispatch POST ke handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/kasir_handler.php';
    exit;
}

// ── Income Stats ──────────────────────────────────────────────
$today = date('Y-m-d');
$week  = date('Y-m-d', strtotime('monday this week'));
$month = date('Y-m-01');

$branchWhere = '';
if (!isOwner() && $u['branch_id']) {
    $bid = (int)$u['branch_id'];
    $branchWhere = " AND branch_id=$bid";
}

$todayI = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)='$today'$branchWhere")->fetch_assoc()['t'];
$weekI  = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)>='$week'$branchWhere")->fetch_assoc()['t'];
$monthI = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at)>='$month'$branchWhere")->fetch_assoc()['t'];

// ── History Filter ────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'today';
$filterWhere = match($filter) {
    'week'  => "DATE(o.created_at) >= '$week'",
    'month' => "DATE(o.created_at) >= '$month'",
    default => "DATE(o.created_at) = '$today'",
};
if ($branchWhere) $filterWhere .= str_replace(' AND ', " AND o.", $branchWhere);

$filterTotal = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM orders o WHERE $filterWhere")->fetch_assoc()['t'];

// ── Products ─────────────────────────────────────────────────
$res = $conn->query("SELECT nama as name, emoji, harga_default FROM products WHERE is_active=1 ORDER BY urutan, nama");
$products = $res->fetch_all(MYSQLI_ASSOC);

renderHeader('Kasir', 'kasir');
echo flashGet();
?>
<link rel="stylesheet" href="modules/kasir/kasir.css">

<!-- Income Summary -->
<div class="income-grid mb-3">
  <div class="income-card daily">
    <div class="income-val"><?= rupiah($todayI) ?></div>
    <div class="income-lbl">💰 Hari Ini</div>
  </div>
  <div class="income-card weekly">
    <div class="income-val"><?= rupiah($weekI) ?></div>
    <div class="income-lbl">📅 Minggu Ini</div>
  </div>
  <div class="income-card monthly">
    <div class="income-val"><?= rupiah($monthI) ?></div>
    <div class="income-lbl">📊 Bulan Ini</div>
  </div>
</div>

<div class="pos-grid">

  <!-- ── KIRI: Form Order ──────────────────────────────── -->
  <div>
    <div class="card">
      <div class="card-title">🧾 Tambah Pesanan</div>
      <form method="POST" action="index.php?page=kasir" id="orderForm">
        <input type="hidden" name="action" value="save_order">

        <p class="text-sm text-muted mb-2">Pilih Produk:</p>
        <div class="product-grid mb-2" id="productGrid">
          <?php foreach ($products as $p): ?>
          <button type="button" class="product-btn" id="pbtn-<?= $p['name'] ?>" onclick="addToCart('<?= $p['name'] ?>','<?= $p['emoji'] ?>', <?= $p['harga_default'] ?? 0 ?>)">
            <span class="product-emoji"><?= $p['emoji'] ?></span>
            <span class="product-name"><?= $p['name'] ?></span>
          </button>
          <?php endforeach; ?>
        </div>

        <hr class="divider">

        <p class="text-sm text-muted mb-1">🛒 Keranjang Pesanan:</p>
        <div class="cart-area" id="cartArea">
          <div id="cartItems"></div>
          <div class="cart-total">
            <span class="total-label">= TOTAL</span>
            <span class="total-val" id="totalDisplay">Rp 0</span>
          </div>
        </div>
        <div id="hiddenInputs"></div>

        <div class="form-row mt-2">
          <div class="form-group">
            <label class="form-label">📅 Tanggal &amp; Waktu</label>
            <input type="text" class="form-control" id="datetimeDisplay" readonly style="color:var(--accent);cursor:default">
          </div>
          <div class="form-group">
            <label class="form-label">🏷️ Kategori Pemesanan</label>
            <select name="kategori" class="form-select" id="kategoriSelect">
              <option value="offline">🏪 Offline</option>
              <option value="shopeefood">🛍️ ShopeeFood</option>
              <option value="gofood">🛵 GoFood</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">📝 Keterangan (Opsional)</label>
          <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan pesanan..."></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" onclick="clearCart()">🗑️ Kosongkan</button>
          <button type="submit" class="btn btn-primary ms-auto" id="saveBtn" disabled>💾 Simpan Pesanan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── KANAN: Riwayat ────────────────────────────────── -->
  <div>
    <div class="card">
      <div class="section-header">
        <div class="card-title" style="margin:0">📋 Riwayat Order</div>
        <div class="order-filter-tabs">
          <a href="?page=kasir&filter=today" class="btn btn-xs <?= $filter==='today'?'btn-primary':'btn-secondary' ?>">Hari</a>
          <a href="?page=kasir&filter=week"  class="btn btn-xs <?= $filter==='week' ?'btn-primary':'btn-secondary' ?>">Minggu</a>
          <a href="?page=kasir&filter=month" class="btn btn-xs <?= $filter==='month'?'btn-primary':'btn-secondary' ?>">Bulan</a>
          <a href="export.php?type=orders&filter=<?= $filter ?>" class="btn btn-xs btn-accent">⬇️ CSV</a>
        </div>
      </div>

      <!-- Total Periode -->
      <div class="total-period-box">
        <span class="total-period-label">
          Total <?= $filter==='today'?'Hari Ini':($filter==='week'?'Minggu Ini':'Bulan Ini') ?>
        </span>
        <span class="total-period-val"><?= rupiah($filterTotal) ?></span>
      </div>

      <?php
      $res = $conn->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE $filterWhere ORDER BY o.created_at DESC LIMIT 100");
      if ($res->num_rows === 0):
      ?>
        <div class="log-empty"><div class="icon">📋</div><p>Belum ada order di periode ini</p></div>
      <?php else: ?>
        <div class="log-list" style="max-height:560px;overflow-y:auto">
        <?php while ($r = $res->fetch_assoc()):
          $items = $conn->query("SELECT * FROM order_items WHERE order_id={$r['id']}");
          $prodList = [];
          while ($it = $items->fetch_assoc()) $prodList[] = $it['produk'].' ('.rupiah($it['harga']).')';
          $katCls = 'badge-'.$r['kategori'];
          $ket = $r['keterangan'] ? "<div class='order-detail'>📝 ".htmlspecialchars($r['keterangan'])."</div>" : '';
        ?>
          <div class="log-item">
            <div class="log-header">
              <span class="log-time"><?= date('d/m H:i', strtotime($r['created_at'])) ?></span>
              <span class="badge <?= $katCls ?>"><?= $r['kategori'] ?></span>
              <span class="text-sm text-muted">👤 <?= htmlspecialchars($r['username'] ?? '-') ?></span>
              <?php if (isAdmin()): ?>
              <form method="POST" style="margin-left:auto" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete_order">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-xs btn-danger">🗑️</button>
              </form>
              <?php endif; ?>
            </div>
            <div class="log-products"><?= implode(' • ', $prodList) ?></div>
            <?= $ket ?>
            <div class="log-total"><?= rupiah($r['total']) ?></div>
          </div>
        <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
let cart = [];

function addToCart(name, emoji, defaultHarga = 0) {
  if (cart.find(i => i.name === name)) {
    const btn = document.getElementById('pbtn-' + name);
    btn.style.animation = 'none';
    btn.offsetHeight; // reflow
    btn.style.borderColor = 'var(--primary)';
    setTimeout(() => btn.style.borderColor = '', 600);
    return;
  }
  cart.push({ name, emoji, harga: defaultHarga });
  document.getElementById('pbtn-' + name).classList.add('active');
  renderCart();
}

function removeFromCart(i) {
  const name = cart[i].name;
  const btn = document.getElementById('pbtn-' + name);
  if (btn) btn.classList.remove('active');
  cart.splice(i, 1);
  renderCart();
}

function renderCart() {
  const area   = document.getElementById('cartItems');
  const hidden = document.getElementById('hiddenInputs');
  const saveBtn = document.getElementById('saveBtn');

  if (cart.length === 0) {
    area.innerHTML = '<p class="text-sm text-muted" style="text-align:center;padding:20px 16px">Pilih produk di atas</p>';
    document.getElementById('totalDisplay').textContent = 'Rp 0';
    saveBtn.disabled = true;
    hidden.innerHTML = '';
    return;
  }

  let html = '', hiddenHtml = '', total = 0;
  cart.forEach((item, i) => {
    html += `<div class="cart-item" style="animation:slideDown .2s ease">
      <span class="cart-name">${item.emoji} ${item.name}</span>
      <div class="cart-price">
        <input type="number" placeholder="Harga" min="0" step="500"
          value="${item.harga || ''}"
          oninput="updateHarga(${i},this.value)"
          style="width:110px" id="cartInput_${i}">
      </div>
      <button type="button" class="cart-del" onclick="removeFromCart(${i})">✕</button>
    </div>`;
    hiddenHtml += `<input type="hidden" name="produk[]" value="${item.name}">
                   <input type="hidden" name="harga[]" id="harga_${i}" value="${item.harga||0}">`;
    total += item.harga || 0;
  });

  area.innerHTML = html;
  hidden.innerHTML = hiddenHtml;
  document.getElementById('totalDisplay').textContent = formatRp(total);
  saveBtn.disabled = (total === 0);
}

function updateHarga(i, val) {
  cart[i].harga = parseInt(val) || 0;
  const total = cart.reduce((s, c) => s + (c.harga || 0), 0);
  document.getElementById('totalDisplay').textContent = formatRp(total);
  const h = document.getElementById('harga_' + i);
  if (h) h.value = cart[i].harga;
  document.getElementById('saveBtn').disabled = (total === 0);
}

function clearCart() {
  cart.forEach(item => {
    const btn = document.getElementById('pbtn-' + item.name);
    if (btn) btn.classList.remove('active');
  });
  cart = [];
  renderCart();
}

function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

function confirmDelete() {
  return confirm('Yakin ingin menghapus order ini?');
}

// Live DateTime
function updateTime() {
  const now = new Date();
  const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
  const d = now.toLocaleDateString('id-ID', opts);
  const t = now.toLocaleTimeString('id-ID');
  const el = document.getElementById('datetimeDisplay');
  if (el) el.value = d + ' ' + t;
}
setInterval(updateTime, 1000);
updateTime();
renderCart();
</script>

<?php renderFooter(); ?>

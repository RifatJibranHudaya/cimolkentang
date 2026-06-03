<?php
// home.php – Landing Page Publik dengan Fitur CRUD
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

global $conn;

// Fetch home content
$hero = $conn->query("SELECT * FROM home_content WHERE section='hero' AND is_active=1 LIMIT 1")->fetch_assoc();
$features = $conn->query("SELECT * FROM home_content WHERE section='feature' AND is_active=1 ORDER BY order_index ASC")->fetch_all(MYSQLI_ASSOC);
$footer_content = $conn->query("SELECT * FROM home_content WHERE section='footer' AND is_active=1 LIMIT 1")->fetch_assoc();

// Fetch products
$res = $conn->query("SELECT * FROM products WHERE is_active=1 ORDER BY urutan, nama");
$products = $res->fetch_all(MYSQLI_ASSOC);

// Fetch branches
$branches = $conn->query("SELECT * FROM branches ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$loggedIn = isLoggedIn();
$user = currentUser();
$canEdit = isLoggedIn() && in_array($user['level'], ['superadmin', 'owner', 'admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($hero['title'] ?? 'DapurKu - Menu Kami') ?></title>
<script>document.documentElement.setAttribute('data-theme', localStorage.getItem('fs_theme') || 'dark');</script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="modules/home/home.css">
<style>
body {
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.menu-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px 80px;
    width: 100%;
}

.menu-title {
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    margin-bottom: 40px;
    color: var(--text);
    font-weight: 700;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 30px;
}

.menu-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all .3s ease;
    position: relative;
    overflow: hidden;
}

.menu-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255,107,0,.4);
    box-shadow: var(--shadow);
}

.menu-card::before {
    content: '';
    position: absolute;
    top: -50px; left: -50px;
    width: 100px; height: 100px;
    background: rgba(255,107,0,.1);
    filter: blur(40px);
    border-radius: 50%;
}

.menu-card:nth-child(even)::before {
    background: rgba(59,130,246,.1);
}

.menu-emoji {
    font-size: 4rem;
    margin-bottom: 15px;
    display: block;
}

.menu-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text);
}

.menu-desc {
    font-size: .9rem;
    color: var(--text3);
    line-height: 1.5;
    margin-bottom: 20px;
}

.menu-price {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: var(--primary);
    font-weight: 700;
}

@media (max-width: 768px) {
    .menu-grid { gap: 20px; grid-template-columns: 1fr; }
    .menu-card { padding: 25px 20px; }
    .menu-emoji { font-size: 3.5rem; }
    .menu-title { font-size: 1.5rem; }
}
</style>
</head>
<body>

<!-- Professional Header -->
<header class="pro-header">
    <div class="pro-header-inner">
        <a href="index.php" class="pro-logo">
            <span class="pro-logo-icon">🍢</span>
            <span class="pro-logo-text">DapurKu</span>
        </a>
        
        <nav class="pro-nav">
            <a href="#menu">Menu</a>
            <a href="#features">Keunggulan</a>
            <a href="#contact">Hubungi Kami</a>
        </nav>

        <div class="pro-header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" id="themeIcon" aria-label="Toggle Theme">☀️</button>
            <?php if ($loggedIn): ?>
                <a href="index.php?page=dashboard" class="btn-primary">Dashboard →</a>
            <?php else: ?>
                <a href="index.php?page=login" class="btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Edit Panel (Visible to Owner & Admin) -->
<?php if ($canEdit): ?>
<div class="edit-panel">
    <span class="edit-panel-text">✏️ Anda adalah <?= ucfirst(str_replace('_', ' ', $user['level'])) ?>. Kelola konten home page</span>
    <div>
        <a href="index.php?page=home_manager">Kelola Konten</a>
        <?php if (in_array($user['level'], ['superadmin', 'owner'])): ?>
        <a href="index.php?page=produk" style="background: var(--secondary); margin-left: 10px;">Kelola Produk</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Professional Hero Section -->
<section class="pro-hero" id="hero">
    <div class="pro-hero-content">
        <div class="pro-hero-icon"><?= htmlspecialchars($hero['icon'] ?? '🍢') ?></div>
        <h1>
            <span class="pro-hero-gradient"><?= htmlspecialchars($hero['title'] ?? 'DapurKu') ?></span>
        </h1>
        <p><?= htmlspecialchars($hero['subtitle'] ?? 'Nikmati pengalaman kuliner terbaik') ?></p>
        <div class="pro-hero-buttons">
            <a href="#menu" class="btn-primary">Lihat Menu</a>
            <a href="#contact" class="btn-secondary">Hubungi Kami</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<?php if (!empty($features)): ?>
<section class="pro-features" id="features">
    <h2 class="pro-features-title">Keunggulan Kami</h2>
    <div class="pro-features-grid">
        <?php foreach ($features as $feature): ?>
        <div class="pro-feature-card">
            <div class="pro-feature-icon"><?= htmlspecialchars($feature['icon']) ?></div>
            <h3 class="pro-feature-title"><?= htmlspecialchars($feature['title']) ?></h3>
            <p class="pro-feature-desc"><?= htmlspecialchars($feature['subtitle']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Menu Section -->
<section class="menu-section" id="menu">
    <h2 class="menu-title">🍽️ Menu Favorit Kami</h2>
    <div class="menu-grid">
        <?php foreach ($products as $p): ?>
        <div class="menu-card">
            <span class="menu-emoji"><?= $p['emoji'] ?></span>
            <div class="menu-name"><?= htmlspecialchars($p['nama']) ?></div>
            <div class="menu-desc"><?= htmlspecialchars($p['deskripsi'] ?: 'Sajian nikmat menggugah selera') ?></div>
            <div class="menu-price">
                <?= $p['harga_default'] > 0 ? rupiah($p['harga_default']) : 'Mulai dari Rp 5.000' ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Lokasi Cabang Section -->
<?php if (!empty($branches)): ?>
<section class="menu-section" id="lokasi-cabang" style="background:var(--bg); border-top:1px solid var(--border)">
    <h2 class="menu-title">📍 Lokasi Cabang Kami</h2>
    <div class="menu-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        <?php foreach ($branches as $b): ?>
        <div class="menu-card" style="display:flex; flex-direction:column; padding:0; overflow:hidden;">
            <?php if (!empty($b['map_url'])): ?>
            <div style="width:100%; height:200px; background:var(--card2);">
                <?php
                // Clean the map_url if it contains iframe wrapper
                $map_html = $b['map_url'];
                if (strpos($map_html, '<iframe') !== false) {
                    // Inject width and height to fit container
                    $map_html = preg_replace('/width="[^"]+"/', 'width="100%"', $map_html);
                    $map_html = preg_replace('/height="[^"]+"/', 'height="100%"', $map_html);
                    echo $map_html;
                } else {
                    echo '<iframe src="'.htmlspecialchars($map_html).'" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
                }
                ?>
            </div>
            <?php else: ?>
            <div style="width:100%; height:150px; background:var(--card2); display:flex; align-items:center; justify-content:center; color:var(--text3); font-size:2rem;">
                🏠
            </div>
            <?php endif; ?>
            <div style="padding: 20px;">
                <div class="menu-name" style="font-size:1.2rem; margin-bottom:8px;"><?= htmlspecialchars($b['nama_cabang']) ?></div>
                <div class="menu-desc" style="margin:0; font-size:0.95rem;">
                    <?= htmlspecialchars($b['alamat'] ?: 'Alamat belum tersedia') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Professional Footer -->
<footer class="pro-footer" id="contact">
    <div class="pro-footer-content">
        <div class="pro-footer-grid">
            <div class="pro-footer-section">
                <h4>📍 Lokasi</h4>
                <p><?= htmlspecialchars($footer_content['title'] ?? 'DapurKu Restaurant') ?></p>
                <p style="font-size: 0.9rem; color: var(--text3);"><?= htmlspecialchars($footer_content['subtitle'] ?? 'Hubungi untuk alamat lengkap') ?></p>
            </div>
            
            <div class="pro-footer-section">
                <h4>🕐 Jam Operasional</h4>
                <p>Senin - Minggu</p>
                <p>10:00 - 22:00 WIB</p>
                <p style="font-size: 0.85rem; color: var(--text3); margin-top: 10px;">*Buka setiap hari<br/>*Delivery & Dine-in tersedia</p>
            </div>
            
            <div class="pro-footer-section">
                <h4>📞 Hubungi Kami</h4>
                <a href="tel:021xxxx">📱 021-XXXX-XXXX</a>
                <a href="https://wa.me/62" target="_blank">💬 WhatsApp</a>
                <a href="mailto:info@dapurku.com">✉️ Email</a>
                
                <h4 style="margin-top: 20px;">🌐 Media Sosial</h4>
                <div class="pro-social">
                    <a href="https://wa.me/62" target="_blank" title="WhatsApp">💬</a>
                    <a href="https://instagram.com" target="_blank" title="Instagram">📸</a>
                    <a href="https://facebook.com" target="_blank" title="Facebook">f</a>
                    <a href="https://tiktok.com" target="_blank" title="TikTok">🎵</a>
                </div>
            </div>
        </div>
        
        <div class="pro-footer-bottom">
            <p>&copy; <?= date('Y') ?> DapurKu - Jajanan Bikin Nagih. Semua hak dilindungi. | <a href="#hero">Kembali ke Atas ⬆️</a></p>
        </div>
    </div>
</footer>

<script src="assets/js/main.js"></script>
<script>
// Smooth scrolling untuk anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Update theme icon
function updateThemeIcon() {
    const theme = localStorage.getItem('fs_theme') || 'dark';
    document.getElementById('themeIcon').textContent = theme === 'dark' ? '🌙' : '☀️';
}

// Initial update
updateThemeIcon();

// Listen for theme changes
window.addEventListener('storage', updateThemeIcon);
</script>

</body>
</html>

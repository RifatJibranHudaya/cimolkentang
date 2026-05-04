<?php
// home.php – Landing Page Publik
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

global $conn;

// Fetch products
$res = $conn->query("SELECT * FROM products WHERE is_active=1 ORDER BY urutan, nama");
$products = $res->fetch_all(MYSQLI_ASSOC);

$loggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DapurKu - Menu Kami</title>
<script>document.documentElement.setAttribute('data-theme', localStorage.getItem('fs_theme') || 'dark');</script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<style>
/* Override default main.css for public landing page layout using CSS variables */
body {
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.landing-header {
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg2);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 100;
}
.landing-logo {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    color: var(--primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}
.landing-logo span { font-size: 2.2rem; }

.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.landing-hero {
    text-align: center;
    padding: 80px 20px;
    position: relative;
}
.landing-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    color: var(--primary);
    margin-bottom: 16px;
    text-shadow: 0 4px 20px rgba(255,107,0,.3);
}
.landing-hero p {
    font-size: 1.1rem;
    color: var(--text2);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}
.menu-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 80px;
    width: 100%;
}
.menu-title {
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    margin-bottom: 40px;
    color: var(--text);
}
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 30px;
}
.menu-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
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
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.menu-card:nth-child(even) .menu-emoji {
    animation-delay: 1.5s;
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
.footer {
    text-align: center;
    padding: 30px;
    background: var(--bg2);
    border-top: 1px solid var(--border);
    color: var(--text3);
    margin-top: auto;
}

/* Responsif Mobile */
@media (max-width: 768px) {
    .landing-header { padding: 15px 20px; }
    .landing-hero { padding: 40px 15px; }
    .landing-hero h1 { font-size: 2.2rem; }
    .landing-hero p { font-size: 1rem; }
    .menu-grid { gap: 20px; grid-template-columns: 1fr; }
    .menu-card { padding: 25px 20px; }
    .menu-emoji { font-size: 3.5rem; }
}
@media (max-width: 480px) {
    .landing-header { flex-wrap: wrap; justify-content: center; gap: 15px; }
    .landing-logo { font-size: 1.5rem; }
    .landing-logo span { font-size: 1.8rem; }
    .header-actions { width: 100%; justify-content: space-between; }
    .header-actions .btn { padding: 8px 12px; font-size: 0.85rem; flex: 1; text-align: center; }
}
</style>
</head>
<body>

<header class="landing-header">
    <a href="index.php" class="landing-logo">
        <span>🍢</span> DapurKu
    </a>
    <div class="header-actions">
        <button class="theme-toggle" onclick="toggleTheme()" id="themeIcon" aria-label="Toggle Theme" style="background:transparent;border:none;font-size:1.4rem;cursor:pointer;">☀️</button>
        <?php if ($loggedIn): ?>
            <a href="index.php?page=dashboard" class="btn btn-primary" style="text-decoration:none;">Ke Dashboard →</a>
        <?php else: ?>
            <a href="index.php?page=login" class="btn btn-primary" style="text-decoration:none;">Login Staf</a>
        <?php endif; ?>
    </div>
</header>

<section class="landing-hero">
    <h1>Cimol Kentang & Jajanan Bikin Nagih!</h1>
    <p>Temukan sensasi rasa jajanan favoritmu yang diolah dengan bahan pilihan dan bumbu spesial. Siap menemani setiap momen santaimu!</p>
</section>

<section class="menu-section">
    <h2 class="menu-title">Menu Favorit Kami</h2>
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

<footer class="footer">
    <div style="display: flex; justify-content: center; align-items: center; gap: 20px; margin-bottom: 20px;">
        <a href="#" style="color: var(--text3); text-decoration: none; transition: transform 0.3s ease, color 0.3s ease; display: flex;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#25D366';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="WhatsApp">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
        <a href="#" style="color: var(--text3); text-decoration: none; transition: transform 0.3s ease, color 0.3s ease; display: flex;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#E1306C';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Instagram">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
            </svg>
        </a>
        <a href="#" style="color: var(--text3); text-decoration: none; transition: transform 0.3s ease, color 0.3s ease; display: flex;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#1877F2';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Facebook">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
        </a>
        <a href="#" style="color: var(--text3); text-decoration: none; transition: transform 0.3s ease, color 0.3s ease; display: flex;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#D44638';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Email">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                <path d="M0 5.75v12.5C0 19.355.895 20.25 2 20.25h20c1.105 0 2-.895 2-2V5.75c0-1.105-.895-2-2-2H2c-1.105 0-2 .895-2 2zm20 12.5H4V8.566l8 5 8-5v9.684zM4 6.25h16L12 10 4 6.25z"/>
            </svg>
        </a>
    </div>
    <p>&copy; <?= date('Y') ?> DapurKu - Jajanan Bikin Nagih. All rights reserved.</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>

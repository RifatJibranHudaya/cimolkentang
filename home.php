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
    <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px;">
        <a href="#" style="color: var(--text3); font-size: 1.8rem; text-decoration: none; transition: transform 0.3s ease, color 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#25D366';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="WhatsApp">📱</a>
        <a href="#" style="color: var(--text3); font-size: 1.8rem; text-decoration: none; transition: transform 0.3s ease, color 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#E1306C';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Instagram">📸</a>
        <a href="#" style="color: var(--text3); font-size: 1.8rem; text-decoration: none; transition: transform 0.3s ease, color 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#1877F2';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Facebook">📘</a>
        <a href="#" style="color: var(--text3); font-size: 1.8rem; text-decoration: none; transition: transform 0.3s ease, color 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'; this.style.color='#D44638';" onmouseout="this.style.transform='scale(1)'; this.style.color='var(--text3)';" title="Email">✉️</a>
    </div>
    <p>&copy; <?= date('Y') ?> DapurKu - Jajanan Bikin Nagih. All rights reserved.</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>

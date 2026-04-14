// assets/js/main.js – Global JavaScript DapurKu POS

// ── Sidebar ──────────────────────────────────────────────────
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
  document.body.style.overflow = '';
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 900) closeSidebar();
});

// ── Toggle Form Visibility ───────────────────────────────────
function toggleForm(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isHidden = el.style.display === 'none' || el.style.display === '';
  el.style.display = isHidden ? 'block' : 'none';
  // Smooth scroll to form if opening
  if (isHidden) {
    setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
  }
}

// ── URL Filter Update ────────────────────────────────────────
function updateFilter(key, val) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, val);
  window.location.href = url.toString();
}

// ── Password Show/Hide Toggle ────────────────────────────────
function togglePassword(inputId, btnEl) {
  const input = document.getElementById(inputId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    btnEl.textContent = '🙈';
    btnEl.title = 'Sembunyikan password';
  } else {
    input.type = 'password';
    btnEl.textContent = '👁️';
    btnEl.title = 'Tampilkan password';
  }
}

// ── Format Rupiah ────────────────────────────────────────────
function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

// ── Confirm Delete ───────────────────────────────────────────
function confirmDelete(msg) {
  return confirm(msg || 'Yakin ingin menghapus data ini?');
}

// ── Auto-dismiss flash messages & Theme Init ───────────────────
document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('fs_theme') || 'dark';
  updateThemeIcon(savedTheme);

  const flashes = document.querySelectorAll('.flash');
  flashes.forEach(f => {
    setTimeout(() => {
      f.style.transition = 'opacity .5s ease, transform .5s ease';
      f.style.opacity = '0';
      f.style.transform = 'translateY(-6px)';
      setTimeout(() => f.remove(), 500);
    }, 4000);
  });
});

// ── Theme Toggle ─────────────────────────────────────────────
function toggleTheme() {
  const body = document.documentElement;
  const currentTheme = body.getAttribute('data-theme') || 'dark';
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
  body.setAttribute('data-theme', newTheme);
  localStorage.setItem('fs_theme', newTheme);
  updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
  const icon = document.getElementById('themeIcon');
  if (icon) {
    icon.textContent = theme === 'light' ? '🌙' : '☀️';
  }
}

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

// ── UID Interceptor (Multi-Account Tab Isolation) ─────────────
// Membaca uid dari URL saat ini dan menyisipkan ke semua link/form
// agar setiap tab tetap menggunakan akunnya sendiri
(function injectUid() {
  var uid = new URLSearchParams(window.location.search).get('uid');
  if (!uid) return;

  function addUidToUrl(url) {
    if (!url || /^(https?:|mailto:|tel:|#|javascript:|\/\/)/i.test(url)) return url;
    if (url.indexOf('uid=') !== -1) return url;
    return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'uid=' + uid;
  }

  function patchLinks(root) {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('a[href]').forEach(function(a) {
      var orig = a.getAttribute('href');
      var patched = addUidToUrl(orig);
      if (patched !== orig) a.setAttribute('href', patched);
    });
  }

  function patchForms(root) {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('form').forEach(function(form) {
      if (!form.querySelector('input[name="uid"]')) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'uid';
        inp.value = uid;
        form.prepend(inp);
      }
    });
  }

  // Patch saat DOM siap
  function runPatch() {
    patchLinks(document);
    patchForms(document);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runPatch);
  } else {
    runPatch();
  }

  // Observer untuk elemen yang dibuat secara dinamis (modal, AJAX, dll)
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      m.addedNodes.forEach(function(node) {
        if (node.nodeType !== 1) return;
        patchLinks(node);
        patchForms(node);
      });
    });
  });

  function startObserver() {
    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.body) {
    startObserver();
  } else {
    document.addEventListener('DOMContentLoaded', startObserver);
  }
})();

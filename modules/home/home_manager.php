<?php
// modules/home/home_manager.php – Manage home page content
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../db.php';

// Only owner and admin can access
requireLevel(['owner', 'admin']);

global $conn;
$user = currentUser();

// Fetch all home content
$res = $conn->query("SELECT * FROM home_content ORDER BY section, order_index ASC");
$contents = $res->fetch_all(MYSQLI_ASSOC);

// Group by section
$grouped = [];
foreach ($contents as $item) {
    $grouped[$item['section']][] = $item;
}

renderHeader('Kelola Home Page', 'home_manager');
?>
<link rel="stylesheet" href="modules/home/home.css">
<style>
.home-manager-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.section-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--primary);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.btn-add-content {
    background: var(--primary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all .3s ease;
}

.btn-add-content:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
}

.content-item {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all .3s ease;
}

.content-item:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow);
}

.content-info {
    flex: 1;
}

.content-title {
    font-weight: 600;
    color: var(--text);
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.content-subtitle {
    font-size: 0.9rem;
    color: var(--text3);
    margin-bottom: 5px;
}

.content-meta {
    font-size: 0.85rem;
    color: var(--text3);
}

.content-actions {
    display: flex;
    gap: 10px;
}

.btn-small {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all .2s ease;
}

.btn-edit {
    background: var(--primary);
    color: white;
}

.btn-edit:hover {
    background: #e59500;
}

.btn-delete {
    background: #ff4444;
    color: white;
}

.btn-delete:hover {
    background: #cc0000;
}

.btn-toggle {
    background: var(--bg2);
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-toggle.active {
    background: #4caf50;
    color: white;
    border-color: #4caf50;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--card);
    border-radius: 12px;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--primary);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn-submit {
    flex: 1;
    padding: 12px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all .3s ease;
}

.btn-submit:hover {
    background: #e59500;
}

.btn-cancel {
    flex: 1;
    padding: 12px;
    background: var(--bg2);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel:hover {
    background: var(--bg);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text3);
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .content-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .content-actions {
        width: 100%;
        margin-top: 15px;
    }

    .btn-small {
        flex: 1;
    }

    .modal-content {
        width: 95%;
        padding: 20px;
    }
}
</style>

<main class="home-manager-container">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--text);">📝 Kelola Konten Home Page</h1>

    <?php
    $sections = ['hero' => 'Hero Section', 'feature' => 'Fitur/Keunggulan', 'footer' => 'Footer'];
    
    foreach ($sections as $key => $label): 
        $items = $grouped[$key] ?? [];
    ?>
    <div class="section-panel">
        <div class="section-header">
            <h2 class="section-title"><?= htmlspecialchars($label) ?></h2>
            <button class="btn-add-content" onclick="openAddModal('<?= $key ?>')">+ Tambah Konten</button>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Belum ada konten di bagian ini</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <div class="content-item" data-id="<?= $item['id'] ?>">
                <div class="content-info">
                    <div class="content-title">
                        <span style="font-size: 1.3rem; margin-right: 8px;"><?= htmlspecialchars($item['icon']) ?></span>
                        <?= htmlspecialchars($item['title']) ?>
                    </div>
                    <div class="content-subtitle"><?= htmlspecialchars($item['subtitle'] ?? '') ?></div>
                    <div class="content-meta">
                        📅 <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                        <?php if ($item['updated_at']): ?>
                            | ✏️ <?= date('d/m/Y H:i', strtotime($item['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="content-actions">
                    <button class="btn-small btn-toggle <?= $item['is_active'] ? 'active' : '' ?>" 
                            onclick="toggleActive(<?= $item['id'] ?>, <?= $item['is_active'] ?>)">
                        <?= $item['is_active'] ? '✓ Aktif' : '✗ Nonaktif' ?>
                    </button>
                    <button class="btn-small btn-edit" onclick="openEditModal(<?= $item['id'] ?>)">✏️ Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteContent(<?= $item['id'] ?>)">🗑️ Hapus</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</main>

<!-- Modal Add/Edit -->
<div id="contentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="modalTitle">Tambah Konten Baru</div>
        <form id="contentForm" onsubmit="handleFormSubmit(event)">
            <input type="hidden" id="actionType" value="create">
            <input type="hidden" id="contentId" value="">
            
            <div class="form-group">
                <label>Bagian</label>
                <select id="section" required>
                    <option value="hero">Hero Section</option>
                    <option value="feature">Fitur/Keunggulan</option>
                    <option value="footer">Footer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Judul / Nama</label>
                <input type="text" id="title" placeholder="Masukkan judul..." required>
            </div>

            <div class="form-group">
                <label>Subtitle / Deskripsi Singkat</label>
                <input type="text" id="subtitle" placeholder="Masukkan subtitle..." required>
            </div>

            <div class="form-group">
                <label>Konten Lengkap</label>
                <textarea id="content" placeholder="Masukkan konten lengkap..."></textarea>
            </div>

            <div class="form-group">
                <label>Icon/Emoji (cth: 🍢, ⭐, 💰)</label>
                <input type="text" id="icon" value="⭐" maxlength="10" placeholder="Masukkan emoji...">
            </div>

            <div class="form-group">
                <label>Urutan</label>
                <input type="number" id="order_index" value="0" min="0">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">💾 Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal(section) {
    document.getElementById('actionType').value = 'create';
    document.getElementById('modalTitle').textContent = 'Tambah Konten Baru';
    document.getElementById('contentForm').reset();
    document.getElementById('section').value = section;
    document.getElementById('contentId').value = '';
    document.getElementById('contentModal').classList.add('active');
}

function openEditModal(id) {
    document.getElementById('actionType').value = 'update';
    document.getElementById('modalTitle').textContent = 'Edit Konten';
    document.getElementById('contentId').value = id;
    
    // Fetch content data
    fetch('modules/home/home_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_by_id&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById('section').value = d.section;
            document.getElementById('title').value = d.title;
            document.getElementById('subtitle').value = d.subtitle || '';
            document.getElementById('content').value = d.content || '';
            document.getElementById('icon').value = d.icon || '⭐';
            document.getElementById('order_index').value = d.order_index || 0;
            document.getElementById('contentModal').classList.add('active');
        } else {
            alert('Gagal memuat konten: ' + data.msg);
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

function closeModal() {
    document.getElementById('contentModal').classList.remove('active');
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    const actionType = document.getElementById('actionType').value;
    const formData = new FormData(document.getElementById('contentForm'));
    
    formData.append('action', actionType);
    formData.append('section', document.getElementById('section').value);
    formData.append('title', document.getElementById('title').value);
    formData.append('subtitle', document.getElementById('subtitle').value);
    formData.append('content', document.getElementById('content').value);
    formData.append('icon', document.getElementById('icon').value);
    formData.append('order_index', document.getElementById('order_index').value);
    
    if (actionType === 'update') {
        formData.append('id', document.getElementById('contentId').value);
    }

    fetch('modules/home/home_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.msg);
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + data.msg);
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

function deleteContent(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus konten ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('modules/home/home_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.msg);
            location.reload();
        } else {
            alert('Error: ' + data.msg);
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

function toggleActive(id, currentStatus) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('is_active', currentStatus ? '0' : '1');

    fetch('modules/home/home_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.msg);
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

// Close modal when clicking outside
document.getElementById('contentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php renderFooter(); ?>

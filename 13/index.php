<?php
// index.php - Basit Günlük Uygulaması Ana Arayüzü

require_once __DIR__ . '/db.php';

// Notları veritabanından çek (Tarih sırasına göre en yeni en üstte)
try {
    $stmt = $db->query("SELECT * FROM notes ORDER BY note_date DESC, id DESC");
    $notes = $stmt->fetchAll();
} catch (PDOException $e) {
    $notes = [];
    $error_msg = "Notlar yüklenirken bir hata oluştu: " . $e->getMessage();
}

// Ruh hali emojileri ve Türkçe isimleri için yardımcı fonksiyonlar
function getMoodEmoji($mood) {
    switch ($mood) {
        case 'happy': return '😊';
        case 'calm': return '😌';
        case 'sad': return '😢';
        case 'tired': return '😴';
        case 'energetic': return '⚡';
        default: return '📝';
    }
}

function getMoodName($mood) {
    switch ($mood) {
        case 'happy': return 'Mutlu';
        case 'calm': return 'Huzurlu';
        case 'sad': return 'Üzgün';
        case 'tired': return 'Yorgun';
        case 'energetic': return 'Enerjik';
        default: return 'Normal';
    }
}

// Tarihi Türkçe formatına çeviren yardımcı fonksiyon
function formatDateTurkish($dateStr) {
    $months = [
        '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
        '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
        '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
    ];
    
    $parts = explode('-', $dateStr);
    if (count($parts) === 3) {
        return intval($parts[2]) . ' ' . $months[$parts[1]] . ' ' . $parts[0];
    }
    return $dateStr;
}

// Bugünün tarihi varsayılan olarak formda görüntülenecek (YYYY-MM-DD)
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Basit Günlük - Anılarınızı, düşüncelerinizi ve günlük notlarınızı saklayabileceğiniz şık ve modern bir kişisel günlük uygulaması.">
    <title>Basit Günlük - Kişisel Anı Defteri</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-container">
        
        <!-- Üst Başlık Alanı -->
        <header>
            <div class="brand-section">
                <h1><span>📖</span> Kişisel Günlük</h1>
                <p>Zihninizi boşaltın, anılarınızı geleceğe taşıyın.</p>
            </div>
            <div class="header-controls">
                <button id="themeToggleBtn" class="theme-toggle" aria-label="Temayı Değiştir" title="Koyu/Açık Tema">
                    🌓
                </button>
            </div>
        </header>

        <!-- Sol Panel: Günlük Ekleme / Düzenleme Formu -->
        <aside class="form-panel">
            <div class="glass-card">
                <h2 id="formTitle">✍️ Yeni Bir Gün Yaz</h2>
                
                <form id="journalForm">
                    <!-- Gizli inputlar (İşlem türü ve Düzenleme için Not ID) -->
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="noteId" value="">
                    
                    <div class="form-group">
                        <label for="note_date">Tarih</label>
                        <input type="date" id="note_date" name="note_date" class="form-control" value="<?php echo $today; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="title">Günün Başlığı</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Bugüne bir isim verin..." required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Bugün Nasıl Hissediyorsunuz?</label>
                        <input type="hidden" id="mood" name="mood" value="calm">
                        <div class="mood-selector">
                            <button type="button" class="mood-btn" data-mood="happy" title="Mutlu">
                                <span class="mood-emoji">😊</span>
                                <span>Mutlu</span>
                            </button>
                            <button type="button" class="mood-btn active" data-mood="calm" title="Huzurlu">
                                <span class="mood-emoji">😌</span>
                                <span>Huzurlu</span>
                            </button>
                            <button type="button" class="mood-btn" data-mood="sad" title="Üzgün">
                                <span class="mood-emoji">😢</span>
                                <span>Üzgün</span>
                            </button>
                            <button type="button" class="mood-btn" data-mood="tired" title="Yorgun">
                                <span class="mood-emoji">😴</span>
                                <span>Yorgun</span>
                            </button>
                            <button type="button" class="mood-btn" data-mood="energetic" title="Enerjik">
                                <span class="mood-emoji">⚡</span>
                                <span>Enerjik</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="content">Günlük Notunuz</label>
                        <textarea id="content" name="content" class="form-control" placeholder="Bugün neler oldu? Neler hissettiniz?..." required></textarea>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button type="submit" id="submitBtn" class="btn btn-primary">Kaydet 💾</button>
                        <button type="button" id="cancelEditBtn" class="btn btn-secondary" style="display: none;">İptal Et</button>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Sağ Panel: Günlük Akışı ve Arama -->
        <main class="feed-panel">
            
            <!-- Arama ve Filtreleme Seçenekleri -->
            <div class="glass-card filter-bar">
                <div class="search-box">
                    <input type="text" id="searchInput" class="form-control" placeholder="Notlarda ara..." autocomplete="off">
                </div>
                
                <div class="filter-options">
                    <select id="moodFilter" class="filter-select">
                        <option value="">Tüm Ruh Halleri</option>
                        <option value="happy">😊 Mutlu</option>
                        <option value="calm">😌 Huzurlu</option>
                        <option value="sad">😢 Üzgün</option>
                        <option value="tired">😴 Yorgun</option>
                        <option value="energetic">⚡ Enerjik</option>
                    </select>
                    
                    <select id="sortOrder" class="filter-select">
                        <option value="desc">Yeniden Eskiye</option>
                        <option value="asc">Eskiden Yeniye</option>
                    </select>
                </div>
            </div>

            <!-- İstatistik Özeti -->
            <div class="stats-bar">
                <div class="stat-pill">
                    Total Not: <strong id="totalNotesCount"><?php echo count($notes); ?></strong>
                </div>
                <div class="stat-pill" id="happyStats" style="display: none;">
                    Mutlu Günler: <strong id="happyCount">0</strong>
                </div>
            </div>

            <!-- Not Listesi -->
            <section class="notes-list" id="notesList">
                <?php if (empty($notes)): ?>
                    <div class="glass-card empty-state" id="emptyState">
                        <span class="empty-icon">✍️</span>
                        <h3>Günlüğünüz henüz boş</h3>
                        <p>Bugün hakkında bir şeyler yazarak ilk anınızı kaydedin.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <article class="glass-card note-card" 
                                 data-id="<?php echo $note['id']; ?>" 
                                 data-mood="<?php echo htmlspecialchars($note['mood']); ?>"
                                 data-date="<?php echo htmlspecialchars($note['note_date']); ?>"
                                 data-timestamp="<?php echo strtotime($note['note_date'] . ' ' . $note['created_at']); ?>">
                            
                            <div class="note-header">
                                <div class="note-meta">
                                    <span class="note-date" data-raw-date="<?php echo htmlspecialchars($note['note_date']); ?>">
                                        📅 <?php echo formatDateTurkish($note['note_date']); ?>
                                    </span>
                                    <span class="mood-tag" data-mood="<?php echo htmlspecialchars($note['mood']); ?>">
                                        <?php echo getMoodEmoji($note['mood']); ?> <?php echo getMoodName($note['mood']); ?>
                                    </span>
                                </div>
                            </div>

                            <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                            
                            <div class="note-content"><?php echo htmlspecialchars($note['content']); ?></div>
                            
                            <div class="note-footer">
                                <button class="btn-read-more" onclick="openNoteModal(<?php echo $note['id']; ?>)">Devamını Oku →</button>
                                <div class="note-actions">
                                    <button class="action-btn" onclick="editNote(<?php echo $note['id']; ?>)" title="Düzenle">
                                        ✏️ Düzenle
                                    </button>
                                    <button class="action-btn btn-delete-item" onclick="deleteNote(<?php echo $note['id']; ?>)" title="Sil">
                                        🗑️ Sil
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    
                    <div class="glass-card empty-state" id="emptyState" style="display: none;">
                        <span class="empty-icon">🔍</span>
                        <h3>Eşleşen not bulunamadı</h3>
                        <p>Arama teriminizi veya ruh hali filtrenizi değiştirmeyi deneyin.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Detay Görünümü Modalı -->
    <div class="modal-overlay" id="noteModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Not Yükleniyor...</h3>
                <button class="modal-close" onclick="closeNoteModal()">&times;</button>
            </div>
            <div class="modal-meta" style="padding: 0.75rem 2rem; border-bottom: 1px solid var(--border); display: flex; gap: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                <span id="modalDate">📅 --</span>
                <span id="modalMood" class="mood-tag" data-mood="calm">😌 Huzurlu</span>
            </div>
            <div class="modal-body" id="modalBody">
                ...
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeNoteModal()">Kapat</button>
            </div>
        </div>
    </div>

    <!-- Bildirim Balonları Konteyneri -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Tema Yönetimi (Koyu / Açık Tema)
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const currentTheme = localStorage.getItem('theme') || 'light';

        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeToggleBtn.textContent = '☀️';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            themeToggleBtn.textContent = '🌙';
        }

        themeToggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeToggleBtn.textContent = '🌙';
                showToast('Açık tema aktif edildi.', 'success');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeToggleBtn.textContent = '☀️';
                showToast('Koyu tema aktif edildi.', 'success');
            }
        });

        // Ruh Hali Seçimi Form Etkileşimi
        const moodButtons = document.querySelectorAll('.mood-btn');
        const moodInput = document.getElementById('mood');

        moodButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                moodButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                moodInput.value = btn.getAttribute('data-mood');
            });
        });

        // Toast Bildirim Fonksiyonu
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${type === 'success' ? '✓' : '✗'}</span> ${message}`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s cubic-bezier(0.165, 0.84, 0.44, 1) reverse forwards';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Form Gönderme (Ekleme / Güncelleme)
        const journalForm = document.getElementById('journalForm');
        const submitBtn = document.getElementById('submitBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const formTitle = document.getElementById('formTitle');

        journalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(journalForm);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            submitBtn.disabled = true;
            submitBtn.textContent = 'Kaydediliyor...';

            fetch('actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    
                    // AJAX sonrası sayfayı yenileyelim ki yeni not eklenmiş ve güncel olarak sıralanmış listelensin.
                    // Bu sayede PHP tarafındaki formatlama ve sıralama mantığı tam tutarlı çalışır.
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    showToast(res.message || 'Bir hata oluştu.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Kaydet 💾';
                }
            })
            .catch(err => {
                showToast('Ağ bağlantısında bir hata oluştu.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kaydet 💾';
            });
        });

        // Düzenleme Modu Tetikleme
        function editNote(id) {
            fetch(`actions.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const note = res.note;
                    
                    // Formu doldur
                    document.getElementById('noteId').value = note.id;
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('note_date').value = note.note_date;
                    document.getElementById('title').value = note.title;
                    document.getElementById('content').value = note.content;
                    document.getElementById('mood').value = note.mood;
                    
                    // Mood butonlarını güncelle
                    moodButtons.forEach(btn => {
                        if (btn.getAttribute('data-mood') === note.mood) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });

                    // Form Arayüz Değişiklikleri
                    formTitle.textContent = '✏️ Notu Düzenle';
                    submitBtn.textContent = 'Güncelle 💾';
                    cancelEditBtn.style.display = 'inline-flex';
                    
                    // Forma yumuşak odaklanma (özellikle mobilde)
                    document.querySelector('.form-panel').scrollIntoView({ behavior: 'smooth' });
                } else {
                    showToast(res.message || 'Not bilgileri alınamadı.', 'error');
                }
            })
            .catch(() => {
                showToast('Bağlantı hatası oluştu.', 'error');
            });
        }

        // Düzenlemeyi İptal Et
        cancelEditBtn.addEventListener('click', () => {
            resetForm();
        });

        function resetForm() {
            journalForm.reset();
            document.getElementById('noteId').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('note_date').value = '<?php echo $today; ?>';
            
            // Mood sıfırla
            moodInput.value = 'calm';
            moodButtons.forEach(btn => {
                if (btn.getAttribute('data-mood') === 'calm') {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            formTitle.textContent = '✍️ Yeni Bir Gün Yaz';
            submitBtn.textContent = 'Kaydet 💾';
            submitBtn.disabled = false;
            cancelEditBtn.style.display = 'none';
        }

        // Not Silme İşlemi
        function deleteNote(id) {
            if (confirm('Bu günlük notunu kalıcı olarak silmek istediğinize emin misiniz?')) {
                fetch('actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'delete', id: id })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        
                        // Kartı DOM'dan sil
                        const card = document.querySelector(`.note-card[data-id="${id}"]`);
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9) translateY(20px)';
                            card.style.transition = 'all 0.4s ease';
                            
                            setTimeout(() => {
                                card.remove();
                                updateStats();
                                
                                // Eğer hiç not kalmadıysa boş durum görselini göster
                                const remainingCards = document.querySelectorAll('.note-card');
                                if (remainingCards.length === 0) {
                                    window.location.reload(); // Boş durum şablonunu yükle
                                }
                            }, 400);
                        }
                    } else {
                        showToast(res.message || 'Silme işlemi başarısız oldu.', 'error');
                    }
                })
                .catch(() => {
                    showToast('Silme işlemi sırasında bağlantı hatası oluştu.', 'error');
                });
            }
        }

        // Modal Yönetimi
        const noteModal = document.getElementById('noteModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalDate = document.getElementById('modalDate');
        const modalMood = document.getElementById('modalMood');
        const modalBody = document.getElementById('modalBody');

        function openNoteModal(id) {
            modalTitle.textContent = 'Yükleniyor...';
            modalBody.textContent = 'Lütfen bekleyin...';
            
            noteModal.classList.add('active');

            fetch(`actions.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const note = res.note;
                    modalTitle.textContent = note.title;
                    modalBody.textContent = note.content;
                    
                    // Tarih ve Mood Güncelleme
                    const rawDate = note.note_date;
                    modalDate.textContent = `📅 ${formatDateStr(rawDate)}`;
                    
                    const emojis = { happy: '😊', calm: '😌', sad: '😢', tired: '😴', energetic: '⚡' };
                    const names = { happy: 'Mutlu', calm: 'Huzurlu', sad: 'Üzgün', tired: 'Yorgun', energetic: 'Enerjik' };
                    
                    modalMood.setAttribute('data-mood', note.mood);
                    modalMood.innerHTML = `${emojis[note.mood] || '📝'} ${names[note.mood] || 'Normal'}`;
                } else {
                    modalTitle.textContent = 'Hata';
                    modalBody.textContent = 'Not içeriği yüklenemedi.';
                }
            })
            .catch(() => {
                modalTitle.textContent = 'Hata';
                modalBody.textContent = 'Sunucuyla bağlantı kurulamadı.';
            });
        }

        function closeNoteModal() {
            noteModal.classList.remove('active');
        }

        // Modal dışına tıklayınca kapatma
        noteModal.addEventListener('click', (e) => {
            if (e.target === noteModal) {
                closeNoteModal();
            }
        });

        // Tarih formatlama yardımcı JS
        function formatDateStr(dateStr) {
            const months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
            }
            return dateStr;
        }

        // Client-side Arama, Filtreleme ve Sıralama
        const searchInput = document.getElementById('searchInput');
        const moodFilter = document.getElementById('moodFilter');
        const sortOrder = document.getElementById('sortOrder');
        const notesContainer = document.getElementById('notesList');
        const emptyState = document.getElementById('emptyState');

        function filterNotes() {
            const searchVal = searchInput.value.toLowerCase();
            const moodVal = moodFilter.value;
            const cards = Array.from(document.querySelectorAll('.note-card'));
            
            let visibleCount = 0;

            cards.forEach(card => {
                const title = card.querySelector('.note-title').textContent.toLowerCase();
                const content = card.querySelector('.note-content').textContent.toLowerCase();
                const mood = card.getAttribute('data-mood');

                const matchesSearch = title.includes(searchVal) || content.includes(searchVal);
                const matchesMood = moodVal === "" || mood === moodVal;

                if (matchesSearch && matchesMood) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Boş durum kontrolü
            if (visibleCount === 0 && cards.length > 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }

            sortNotes();
        }

        function sortNotes() {
            const order = sortOrder.value;
            const cards = Array.from(document.querySelectorAll('.note-card'));
            
            cards.sort((a, b) => {
                // Tarih ve timestamp'e göre sıralama
                const timeA = parseInt(a.getAttribute('data-timestamp')) || 0;
                const timeB = parseInt(b.getAttribute('data-timestamp')) || 0;
                
                if (order === 'desc') {
                    return timeB - timeA;
                } else {
                    return timeA - timeB;
                }
            });

            // Elemanları yeniden ekle
            cards.forEach(card => {
                notesContainer.appendChild(card);
            });
        }

        searchInput.addEventListener('input', filterNotes);
        moodFilter.addEventListener('change', filterNotes);
        sortOrder.addEventListener('change', sortNotes);

        // İstatistikleri dinamik güncelleme
        function updateStats() {
            const cards = document.querySelectorAll('.note-card');
            document.getElementById('totalNotesCount').textContent = cards.length;
            
            // Mutlu günlerin istatistiği
            let happyCount = 0;
            cards.forEach(card => {
                if (card.getAttribute('data-mood') === 'happy') {
                    happyCount++;
                }
            });
            
            const happyStatsPill = document.getElementById('happyStats');
            if (happyCount > 0) {
                happyStatsPill.style.display = 'inline-flex';
                document.getElementById('happyCount').textContent = happyCount;
            } else {
                happyStatsPill.style.display = 'none';
            }
        }

        // Sayfa yüklendiğinde istatistikleri ilk kez hesapla
        document.addEventListener('DOMContentLoaded', () => {
            updateStats();
        });
    </script>
</body>
</html>

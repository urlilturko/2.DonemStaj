<?php
// actions.php - Günlük İşlemleri Denetleyicisi (Ekleme, Güncelleme, Silme)

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// İstek metodunu ve eylemi kontrol et
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON girdisini çöz
    $raw_input = file_get_contents('php://input');
    $json_data = json_decode($raw_input, true);
    
    // Eğer JSON verisi varsa $_POST yerine onu kullan
    if ($json_data) {
        $post_data = $json_data;
    } else {
        $post_data = $_POST;
    }
    
    $action = $post_data['action'] ?? $action;
}

try {
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            $title = trim($post_data['title'] ?? '');
            $content = trim($post_data['content'] ?? '');
            $mood = trim($post_data['mood'] ?? 'calm');
            $note_date = trim($post_data['note_date'] ?? date('Y-m-d'));
            
            if (empty($title)) {
                throw new Exception('Lütfen günlüğünüze bir başlık yazın.');
            }
            if (empty($content)) {
                throw new Exception('Lütfen günlük notunuzu boş bırakmayın.');
            }
            
            // Tarih geçerlilik kontrolü
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $note_date)) {
                $note_date = date('Y-m-d');
            }
            
            // Veritabanına ekle
            $stmt = $db->prepare("INSERT INTO notes (title, content, mood, note_date) VALUES (:title, :content, :mood, :note_date)");
            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':mood' => $mood,
                ':note_date' => $note_date
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Günlük notunuz başarıyla eklendi.',
                'id' => $db->lastInsertId()
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            $id = intval($post_data['id'] ?? 0);
            $title = trim($post_data['title'] ?? '');
            $content = trim($post_data['content'] ?? '');
            $mood = trim($post_data['mood'] ?? 'calm');
            $note_date = trim($post_data['note_date'] ?? date('Y-m-d'));
            
            if ($id <= 0) {
                throw new Exception('Geçersiz not ID.');
            }
            if (empty($title)) {
                throw new Exception('Lütfen günlüğünüze bir başlık yazın.');
            }
            if (empty($content)) {
                throw new Exception('Lütfen günlük notunuzu boş bırakmayın.');
            }
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $note_date)) {
                $note_date = date('Y-m-d');
            }
            
            // Güncelleme sorgusu
            $stmt = $db->prepare("UPDATE notes SET title = :title, content = :content, mood = :mood, note_date = :note_date WHERE id = :id");
            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':mood' => $mood,
                ':note_date' => $note_date,
                ':id' => $id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Günlük notunuz başarıyla güncellendi.'
            ]);
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu.');
            }
            
            $id = intval($post_data['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Geçersiz not ID.');
            }
            
            $stmt = $db->prepare("DELETE FROM notes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Günlük notu başarıyla silindi.'
            ]);
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Geçersiz not ID.');
            }
            
            $stmt = $db->prepare("SELECT * FROM notes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $note = $stmt->fetch();
            
            if (!$note) {
                throw new Exception('Not bulunamadı.');
            }
            
            echo json_encode([
                'success' => true,
                'note' => $note
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem (action).');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

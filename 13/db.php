<?php
// db.php - SQLite Veritabanı Bağlantısı ve Kurulumu

$db_file = __DIR__ . '/gunluk.db';

try {
    // PDO ile SQLite veritabanına bağlan (Dosya yoksa otomatik oluşturulur)
    $db = new PDO("sqlite:" . $db_file);
    
    // Hata modunu istisna (exception) fırlatacak şekilde ayarla
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Günlük notları tablosunu oluştur
    $query = "
        CREATE TABLE IF NOT EXISTS notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            mood TEXT NOT NULL, -- 'happy', 'calm', 'sad', 'tired', 'energetic'
            note_date TEXT NOT NULL, -- YYYY-MM-DD
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_notes_date ON notes(note_date);
    ";
    
    $db->exec($query);
    
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

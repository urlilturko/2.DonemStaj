========================================================================
             BASİT GÜNLÜK UYGULAMASI - KULLANIM KILAVUZU
========================================================================

Bu dosya, geliştirdiğimiz "Basit Günlük" (Kişisel Anı Defteri) PHP uygulaması
dosyalarını nasıl çalıştıracağınızı ve test edeceğinizi açıklamaktadır.

------------------------------------------------------------------------
1. UYGULAMA DOSYALARI
------------------------------------------------------------------------

Uygulama tamamen modüler ve temiz bir yapıda şu dosyalardan oluşmaktadır:
- index.php    : Ana arayüz, arama, filtreleme, tema ve modal pencereleri.
- db.php       : SQLite veritabanı bağlantısı ve tablo şeması kurulumu.
- actions.php  : Ekleme, düzenleme, silme ve detay çekme mantığı.
- style.css    : Cozy ve premium tasarımlı koyu/açık tema destekli CSS kodları.
- gunluk.db    : Uygulama ilk kez çalıştığında otomatik olarak oluşacak veritabanı.

------------------------------------------------------------------------
2. UYGULAMAYI ÇALIŞTIRMA VE TEST ETME
------------------------------------------------------------------------

Bu uygulama PHP ve SQLite3 gerektirmektedir. Bilgisayarınızda PHP yüklü ise
aşağıdaki yöntemlerle hemen çalıştırabilirsiniz:

YÖNTEM A: Yerleşik PHP Sunucusu ile Çalıştırma (En Hızlı Yöntem)
-----------------------------------------------------------------
1. Proje klasöründe (`Desktop/13`) bir terminal (PowerShell veya CMD) açın.
2. Aşağıdaki komutu çalıştırarak PHP'nin kendi dahili sunucusunu başlatın:
   php -S localhost:8000
3. Tarayıcınızı açın ve şu adrese gidin:
   http://localhost:8000

YÖNTEM B: XAMPP, Laragon veya WampServer ile Çalıştırma
-------------------------------------------------------
1. XAMPP/Laragon kontrol panelinden Apache sunucusunu başlatın.
2. Bu projedeki 4 dosyayı (`index.php`, `db.php`, `actions.php`, `style.css`)
   web sunucunuzun kök dizinine kopyalayın:
   - XAMPP için  : C:\xampp\htdocs\gunluk\
   - Laragon için: C:\laragon\www\gunluk\
3. Tarayıcınızdan şu adrese erişin:
   http://localhost/gunluk/

------------------------------------------------------------------------
3. ÖNEMLİ TEKNİK NOTLAR
------------------------------------------------------------------------
- Veritabanı Kurulumu: Uygulama ilk kez yüklendiğinde, `db.php` dosyası
  klasör içinde `gunluk.db` adında boş bir SQLite veritabanı dosyası oluşturur
  ve içine gerekli tabloları otomatik yazar. Ekstra SQL kurulumu gerekmez.
- PHP SQLite3 Aktifliği: PHP yapılandırmanızda (php.ini) `extension=pdo_sqlite`
  ve `extension=sqlite3` satırlarının başındaki noktalı virgülün (;) kaldırılmış
  (aktif edilmiş) olduğundan emin olun. XAMPP/Laragon'da bu varsayılan olarak aktiftir.

------------------------------------------------------------------------
4. ÖNE ÇIKAN ÖZELLİKLER
------------------------------------------------------------------------
- Koyu/Açık Tema Desteği: Sağ üstteki buton ile modlar arası geçiş yapabilirsiniz.
  Tercihiniz tarayıcı hafızasında saklanır.
- Anlık Filtreleme ve Arama: Yazdığınız notları başlık ve içeriğe göre anlık olarak
  arayabilir, ruh haline göre süzebilir, eskiden yeniye / yeniden eskiye sıralayabilirsiniz.
- AJAX ile Hızlı Yönetim: Not ekleme, düzenleme ve silme işlemleri sayfa yenilenmeden
  arka planda AJAX ile gerçekleştirilir, sonrasında akıcı animasyonlarla güncellenir.
- Ruh Hali Seçimi: Günlüğünüzü yazarken o günkü ruh halinizi emojiler eşliğinde seçebilirsiniz.

========================================================================
Keyifli yazmalar dileriz!
========================================================================

========================================================================
             ETKİNLİK TAKVİMİ VE KAYIT PORTALI - KULLANIM KILAVUZU
========================================================================

Bu dosya, geliştirdiğimiz "Etkinlik Takvimi ve Kayıt Portalı" uygulamasını 
nasıl çalıştıracağınızı ve test edeceğinizi açıklamaktadır.

------------------------------------------------------------------------
1. UYGULAMAYI ÇALIŞTIRMA YÖNTEMLERİ
------------------------------------------------------------------------

YÖNTEM A: Doğrudan Dosya Açma (En Kolay Yöntem)
----------------------------------------------
1. index.html dosyasının bulunduğu klasöre gidin.
2. index.html dosyasına çift tıklayarak varsayılan tarayıcınızda 
   (Chrome, Edge, Firefox, Safari vb.) doğrudan açın.
* Not: Bu yöntemle uygulama tamamen çalışır durumda olacaktır.


YÖNTEM B: Yerel HTTP Sunucusu ile Çalıştırma (Önerilen / Sunucu Simülasyonu)
-------------------------------------------------------------------------
Tarayıcı özelliklerinin (Local Storage, animasyonlar vb.) tam ve profesyonel 
bir sunucu ortamında çalışmasını simüle etmek için bu yöntemi kullanabilirsiniz:

1. Python ile Çalıştırma:
   - Dosyaların olduğu klasörde bir terminal veya komut satırı (CMD / PowerShell) açın.
   - Şu komutu yazıp Enter'a basın:
     python -m http.server 8000
   - Tarayıcınızı açın ve şu adrese gidin:
     http://localhost:8000

2. Node.js / NPX ile Çalıştırma:
   - Terminalde şu komutu çalıştırın:
     npx http-server -p 8000
   - Tarayıcınızda şu adresi açın:
     http://localhost:8000

------------------------------------------------------------------------
2. UYGULAMA ÖZELLİKLERİ VE KULLANIMI
------------------------------------------------------------------------

- Kategori Filtreleme: Sayfanın üst kısmındaki "Seminer", "Toplantı", "Parti" 
  gibi butonlara tıklayarak etkinlikleri türlerine göre anında süzebilirsiniz.
  
- Kayıt Formu Modalı: Herhangi bir etkinlik kartındaki "Katılacağım" butonuna 
  tıkladığınızda modern bir kayıt formu açılır. Formda Ad Soyad ve E-posta 
  alanları zorunludur.
  
- Akıllı Akış ve UX Detayları:
  * Bir kez kayıt formu doldurduğunuzda, sonraki etkinlik kayıtlarında adınız 
    ve e-postanız otomatik olarak doldurulur (tekrar yazmak zorunda kalmazsınız).
  * Kayıt sonrasında buton "Katıldınız ✓" durumuna geçer, rengi gri olur ve 
    tekrar tıklanması engellenir.
  * Kart üzerindeki "Kişi Katılıyor" sayacı dinamik olarak 1 artar.
  * Ekranın sağ üstünde şık bir Toast bildirim animasyonu ve hemen ardından 
    kaydınızın alındığına dair bir tarayıcı uyarısı (alert) belirir.
  * Kayıt durumları tarayıcınızın "Local Storage" (Yerel Depolama) alanında 
    saklanır. Sayfayı yenileseniz bile kayıtlı olduğunuz etkinlikler korunur.

========================================================================
Klasör İçeriği:
- index.html   : Uygulamanın tüm HTML, CSS ve JavaScript kodlarını barındıran dosya.
- README.txt   : Bu kullanım kılavuzu dosyası.
========================================================================

# Otopark Otomasyon Sistemi

Üniversite Veritabanı Yönetimi dersi için geliştirilmiş **Otopark Otomasyon Sistemi**. Tam çalışır, gerçek veritabanı bağlantılı bir web uygulamasıdır.

## 📋 Özellikler

- ✅ **10 Tablo** ile tam ilişkisel veritabanı modeli
- ✅ **4 Trigger** ile otomatik business logic kontrolü
- ✅ **4 View** ile dashboard sorguları
- ✅ **6 PHP API Endpoint** (procedural, framework yok)
- ✅ Modern, responsive web arayüzü
- ✅ Araç giriş/çıkış yönetimi
- ✅ Otomatik ücret hesaplama
- ✅ Gerçek zamanlı doluluk durumu
- ✅ Ödeme kayıtları

## 🗂️ Klasör Yapısı

```
otopark-sistem/
├── index.html          # Ana sayfa (frontend)
├── style.css           # Tasarım dosyası
├── app.js              # Frontend JavaScript (API entegrasyonu)
├── schema.sql          # Veritabanı şeması (tablolar + triggers + views)
├── api/
│   ├── db.php          # MySQL PDO bağlantısı
│   ├── entry.php       # Araç giriş API
│   ├── exit.php        # Araç çıkış API
│   ├── payments.php    # Ödeme kayıt API
│   ├── spaces.php      # Park yerleri listesi API
│   ├── tariffs.php     # Tarifeler listesi API
│   └── dashboard.php   # Dashboard verileri API
└── README.md           # Bu dosya
```

## 🚀 Kurulum Adımları (Ubuntu)

### 1. Gereksinimler

```bash
# Apache, PHP, MySQL yükle
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server -y

# Servisleri başlat
sudo systemctl start apache2
sudo systemctl start mysql
```

### 2. Veritabanı Kurulumu

```bash
# MySQL'e giriş yap
sudo mysql -u root -p

# Veritabanını oluştur (şema.sql'i kullanarak)
# MySQL prompt'ta:
source /path/to/schema.sql;
# VEYA
# MySQL dışından:
sudo mysql -u root -p < schema.sql
```

**Not:** Eğer `schema.sql` yolunu belirtmekte sorun yaşıyorsanız:

```bash
cd /home/abdullahemirkirecci/Downloads/VS
sudo mysql -u root -p < schema.sql
```

### 3. Proje Dosyalarını Apache'ye Taşı

```bash
# Proje klasörünü Apache'nin web dizinine kopyala
sudo cp -r /home/abdullahemirkirecci/Downloads/VS /var/www/html/otopark

# Dosya izinlerini ayarla
sudo chown -R www-data:www-data /var/www/html/otopark
sudo chmod -R 755 /var/www/html/otopark
```

### 4. MySQL Kullanıcısı ve Şifre Ayarları

Eğer MySQL root kullanıcısının şifresi varsa, `api/db.php` dosyasını düzenleyin:

```bash
sudo nano /var/www/html/otopark/api/db.php
```

Şu satırları güncelleyin:

```php
define('DB_USER', 'root');
define('DB_PASS', 'MYSQL_SIFRENIZ'); // Boşsa '' olarak bırakın
```

### 5. Uygulamayı Çalıştırın

Tarayıcınızda şu adresi açın:

```
http://localhost/otopark/
```

## 🧪 Test Etme

### 1. Dashboard Kontrol

- Ana sayfada doluluk oranı, gelir, içerideki araçlar görünmeli
- Sidebar'da anlık durum (Boş/Dolu/Bakım) görünmeli

### 2. Araç Girişi

1. Sol menüden **"Araç Girişi"** sekmesine tıklayın
2. Plaka girin (örn: `34ABC123`)
3. Tarife seçin (örn: "Standart Saatlik")
4. Boş park yeri seçin
5. **"Giriş Oluştur"** butonuna tıklayın
6. ✅ Başarılı mesajı almalısınız
7. Dashboard'a dönüp içerideki araçlar listesinde görmelisiniz

### 3. Araç Çıkışı

1. Sol menüden **"Araç Çıkışı / Ücret"** sekmesine tıklayın
2. İçerideki araçlardan birinin yanındaki **"Seç"** butonuna tıklayın
3. Ücret otomatik hesaplanacak (Trigger sayesinde!)
4. Ödeme yöntemi seçin (Nakit/Kredi Kartı/Online)
5. **"Ödemeyi Tamamla"** butonuna tıklayın
6. ✅ Ödeme kaydedilmeli, park yeri otomatik "Boş" olmalı

### 4. Park Yerleri

1. **"Park Yerleri"** sekmesine tıklayın
2. Tüm park yerlerini Boş/Dolu/Bakım durumlarıyla görmelisiniz

## 📊 Veritabanı Yapısı

### Tablolar

1. **employee** - Çalışanlar
2. **parking_lot** - Otopark lokasyonları
3. **floor** - Katlar
4. **parking_space** - Park yerleri
5. **customer** - Müşteriler
6. **vehicle** - Araçlar
7. **tariff** - Tarifeler
8. **subscription** - Abonelikler
9. **entry_exit** - Giriş-çıkış kayıtları
10. **payment** - Ödemeler

### Trigger'lar

1. **trg_before_entry** - Girişte park yeri ve araç kontrolü
2. **trg_after_entry** - Girişten sonra park yeri "Dolu" yap
3. **trg_before_exit** - Çıkışta süre ve ücret hesapla
4. **trg_after_exit** - Çıkıştan sonra park yeri "Boş" yap

### View'lar

1. **vw_occupancy_now** - Anlık doluluk
2. **vw_revenue_today** - Bugünkü gelir
3. **vw_inside_cars** - İçerideki araçlar
4. **vw_active_subscriptions** - Aktif abonelikler

## 🔧 Sorun Giderme

### Problem: "Veritabanı bağlantı hatası"

**Çözüm:**
- MySQL servisinin çalıştığından emin olun: `sudo systemctl status mysql`
- `api/db.php` dosyasındaki kullanıcı adı ve şifreyi kontrol edin

### Problem: "404 Not Found" (API çağrılarında)

**Çözüm:**
- Apache'nin `mod_rewrite` modülü aktif olmalı
- Dosya izinlerini kontrol edin: `ls -la /var/www/html/otopark/api/`

### Problem: "Parse error" (PHP hatası)

**Çözüm:**
- PHP versiyonunu kontrol edin: `php -v` (PHP 7.4+ (PDO destekli))
- Syntax hatası varsa ilgili dosyayı kontrol edin

### Problem: Trigger çalışmıyor

**Çözüm:**
```sql
-- MySQL'de trigger'ları kontrol edin
SHOW TRIGGERS;

-- Gerekirse schema.sql'i tekrar çalıştırın
DROP DATABASE IF EXISTS otopark_db;
source /path/to/schema.sql;
```

## 📝 API Dokümantasyonu

### `GET /api/dashboard.php`

Dashboard için gerekli tüm verileri döner.

**Response:**
```json
{
  "success": true,
  "data": {
    "occupancy": {
      "total_spaces": 25,
      "occupied": 2,
      "available": 23,
      "maintenance": 0,
      "occupancy_rate": 8
    },
    "revenue": {
      "today_revenue": 0.00,
      "payment_count": 0
    },
    "inside_cars": [...],
    "subscriptions": {
      "active_count": 2
    },
    "transactions": {
      "total_today": 2,
      "entries": 2,
      "exits": 0
    }
  }
}
```

### `POST /api/entry.php`

Araç girişi kaydı oluşturur.

**Request Body:**
```json
{
  "plate": "34ABC123",
  "space_id": 1,
  "tid": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Araç girişi başarılı",
  "data": {
    "record_id": 5,
    "plate": "34ABC123",
    "space_id": 1
  }
}
```

### `POST /api/exit.php`

Araç çıkışı yapar ve ücret hesaplar.

**Request Body:**
```json
{
  "record_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Araç çıkışı başarılı",
  "data": {
    "record_id": 5,
    "plate": "34ABC123",
    "duration_min": 125,
    "fee": 104.17
  }
}
```

### `POST /api/payments.php`

Ödeme kaydı ekler.

**Request Body:**
```json
{
  "record_id": 5,
  "method": "Nakit"
}
```

### `GET /api/spaces.php?status=Bos`

Park yerlerini listeler (opsiyonel status filtresi).

### `GET /api/tariffs.php`

Aktif tarifeleri listeler.

## 🎓 Hoca İçin Notlar

- ✅ Tüm tablolar foreign key ilişkileriyle bağlanmıştır
- ✅ Trigger'lar business logic'i otomatik kontrol eder
- ✅ View'lar karmaşık JOIN sorgulerını basitleştirir
- ✅ API'ler PDO ile SQL injection'a karşı korumalıdır
- ✅ Frontend gerçek zamanlı veri gösterir
- ✅ Procedural PHP kullanılmıştır (framework yok)
- ✅ Kod yorum satırlarıyla açıklanmıştır

## 📞 İletişim

Bu proje **Abdullah Emir Kireçci** tarafından geliştirilmiştir.

---

**Not:** Bu sistem eğitim amaçlıdır. Production ortamında güvenlik önlemleri (authentication, HTTPS, input sanitization, vs.) eklenmelidir.

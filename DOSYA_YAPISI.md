# 📁 Otopark Otomasyon Sistemi - Dosya Yapısı

## Genel Bakış

```
/home/abdullahemirkirecci/Downloads/VS/
│
├── 📄 index.html           # Ana sayfa (Frontend arayüzü)
├── 🎨 style.css            # CSS tasarım dosyası
├── ⚡ app.js              # JavaScript (API entegrasyonu)
│
├── 🗄️ schema.sql          # Veritabanı şeması
│
├── 📂 api/                 # Backend API klasörü
│   ├── db.php              # MySQL PDO bağlantısı
│   ├── entry.php           # Araç giriş endpoint'i
│   ├── exit.php            # Araç çıkış endpoint'i
│   ├── payments.php        # Ödeme kayıt endpoint'i
│   ├── spaces.php          # Park yerleri listesi endpoint'i
│   ├── tariffs.php         # Tarifeler listesi endpoint'i
│   └── dashboard.php       # Dashboard verileri endpoint'i
│
└── 📖 README.md            # Kurulum ve kullanım dökümanı
```

## Dosya Açıklamaları

### Frontend Dosyaları

#### `index.html` (32 KB)
- **Amaç:** Ana web arayüzü
- **İçerik:**
  - Dashboard ekranı
  - Araç giriş formu
  - Araç çıkış formu
  - Park yerleri görünümü
  - Müşteri ve araç yönetimi ekranları
  - Raporlar bölümü
- **Bağımlılıklar:** `style.css`, `app.js`

#### `style.css` (14.7 KB)
- **Amaç:** Modern, responsive tasarım
- **Özellikler:**
  - Dark mode tema
  - Gradient efektler
  - Responsive grid sistem
  - Animasyonlar ve hover efektleri
  - Glassmorphism UI elemanları

#### `app.js` (~15 KB)
- **Amaç:** Frontend-Backend entegrasyonu
- **Fonksiyonlar:**
  - `loadDashboard()` - Dashboard verilerini yükler
  - `setupEntryForm()` - Giriş formunu hazırlar
  - `submitEntry()` - Araç girişi yapar
  - `loadExitRecords()` - Çıkış kayıtlarını listeler
  - `processExit()` - Çıkış işlemini yapar
  - `submitPayment()` - Ödeme kaydeder
  - `loadSpaces()` - Park yerlerini listeler
- **API Çağrıları:** `fetch()` ile JSON over HTTP

### Veritabanı Dosyası

#### `schema.sql` (~12 KB)
- **Amaç:** Tam veritabanı yapısını oluşturur
- **İçerik:**
  - **10 Tablo** tanımı (employee, parking_lot, floor, parking_space, customer, vehicle, tariff, subscription, entry_exit, payment)
  - **4 Trigger** (giriş/çıkış kontrolleri, ücret hesaplama)
  - **4 View** (dashboard sorguları)
  - **Demo veriler** (test için örnek kayıtlar)

### Backend API Dosyaları

#### `api/db.php`
- **Amaç:** PDO MySQL bağlantısı
- **İçerik:**
  - Database credentials
  - PDO instance oluşturma
  - `sendJSON()` helper fonksiyonu
  - CORS headers

#### `api/entry.php`
- **Method:** POST
- **Parametreler:** `plate`, `space_id`, `tid`
- **İşlev:**
  - Plaka kontrolü
  - Yoksa otomatik customer+vehicle oluşturma
  - entry_exit kaydı ekleme
  - Trigger'lar park yeri ve araç kontrolü yapar

#### `api/exit.php`
- **Method:** POST
- **Parametreler:** `record_id`
- **İşlev:**
  - exit_time = NOW() olarak günceller
  - Trigger otomatik süre ve ücret hesaplar
  - Park yeri otomatik "Boş" olur

#### `api/payments.php`
- **Method:** POST
- **Parametreler:** `record_id`, `method`
- **İşlev:**
  - payment tablosuna kayıt ekler
  - Ödeme yöntemi: Nakit/Kredi Kartı/Online

#### `api/spaces.php`
- **Method:** GET
- **Parametreler:** `?status=Bos` (opsiyonel)
- **İşlev:**
  - Tüm park yerlerini listeler
  - Status'a göre filtreleme

#### `api/tariffs.php`
- **Method:** GET
- **İşlev:**
  - Aktif tarifeleri listeler
  - HOURLY ve SUBSCRIPTION tiplerinde

#### `api/dashboard.php`
- **Method:** GET
- **İşlev:**
  - 4 view'dan veri çeker:
    - `vw_occupancy_now` - Doluluk durumu
    - `vw_revenue_today` - Bugünkü gelir
    - `vw_inside_cars` - İçerideki araçlar
    - `vw_active_subscriptions` - Aktif abonelikler
  - Giriş/çıkış istatistikleri

## Veri Akışı

```
[Frontend: index.html]
        ↓
   [app.js]
        ↓
   fetch() → [Backend: api/*.php]
                ↓
          [db.php: PDO]
                ↓
          [MySQL: otopark_db]
                ↓
      [Triggers & Views]
                ↓
          [JSON Response]
                ↓
          [Frontend Display]
```

## Kurulum Sırası

1. ✅ MySQL'i kur ve çalıştır
2. ✅ `schema.sql` ile veritabanını oluştur
3. ✅ Apache + PHP'yi kur
4. ✅ Dosyaları `/var/www/html/otopark/` dizinine kopyala
5. ✅ `api/db.php` içinde MySQL credentials'ı ayarla
6. ✅ Tarayıcıda `http://localhost/otopark/` aç
7. ✅ Test et: Araç girişi → Çıkış → Ödeme

## Teknoloji Stack

| Katman | Teknoloji |
|--------|-----------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 7.4+ (Procedural) |
| Database | MySQL 8.0 |
| Web Server | Apache 2.4 |
| API | RESTful JSON |
| Connection | PDO (PHP Data Objects) |

## Güvenlik Notları

- ✅ PDO prepared statements (SQL injection koruması)
- ✅ Input validasyonu (PHP tarafında)
- ⚠️ **Eksikler (production için):**
  - Authentication/Authorization yok
  - HTTPS yok
  - CSRF token yok
  - Rate limiting yok
  - Şifreleme yok

**Not:** Bu sistem eğitim amaçlıdır. Production ortamında ek güvenlik önlemleri alınmalıdır.

---

**Son Güncelleme:** 29 Aralık 2025  
**Proje:** Üniversite Veritabanı Yönetimi Dersi

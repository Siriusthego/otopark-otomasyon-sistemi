# 📁 Dosya Yapısı ve Açıklaması

## 🗂️ Ana Dizin

```
VS/
├── api/                    # Backend API Endpoints (28 dosya)
├── index.html              # Ana sayfa (SPA)
├── app.js                  # Frontend JavaScript logic
├── style.css               # Styling
├── schema.sql              # Database schema
├── init_db.sql             # Initial data
├── add_is_active.sql       # Soft delete patch
├── setup_database.sh       # Database kurulum scripti
├── README.md               # Proje dokümantasyonu
├── DOSYA_YAPISI.md         # ← Bu dosya
└── KULLANIM_REHBERI.md     # Kullanım kılavuzu
```

---

## 📋 Dosya Detayları

### 🎨 Frontend

#### `index.html` (33KB)
**Amaç:** Single Page Application (SPA) ana dosyası  
**İçerik:**
- 8 ana sekme (Dashboard, Giriş, Çıkış, Park Yerleri, Müşteriler, Tarifeler, Abonelikler, Raporlar)
- Modal formlar (park yeri ekleme, araç ekleme)
- Dinamik tablolar ve grid'ler
- Müşteri detay paneli (sidebar)

**Önemli ID'ler:**
- `#global-search-input` - Global arama kutusu
- `#spaces-lot-filter`, `#spaces-floor-filter` - Park yeri filtreleri
- `#new-space-modal`, `#add-vehicle-modal` - Modal formlar

#### `app.js` (65KB, ~2000 satır)
**Amaç:** Tüm frontend logic  
**Yapı:**
```javascript
// Global state
spacesFilterState = {...}

// Core functions
apiCall()              // API wrapper
showNotification()     // Bildirimler

// Page-specific functions
loadDashboard()        // Dashboard
loadSpaces()           // Park yerleri + filtreleme
loadCustomers()        // Müşteriler
loadExitRecords()      // Çıkış kayıtları

// NEW: Global Search
performGlobalSearch()  // Ana arama fonksiyonu
searchPlate()          // Plaka araması
searchParkSpace()      // Park yeri araması
searchCustomer()       // Müşteri araması

// NEW: Vehicle Management
displayGuestVehicleDetail()  // Misafir araç detayı
highlightCustomerByName()    // Müşteri highlight

// Modal functions
openAddVehicleModal()
saveVehicle()
```

**Öne Çıkanlar:**
- Eager loading (DOMContentLoaded'da preload)
- Event delegation
- Async/await pattern
- State management

#### `style.css` (16KB, ~700 satır)
**Amaç:** Modern, responsive styling  
**Yapı:**
```css
:root { --primary, --success, etc. }  /* Design tokens */
.card, .table, .badge                  /* Components */
.modal                                 /* Modal stilleri */
.space-grid                            /* Park yeri grid */
.highlight-row                         /* Arama highlight */
```

**Özellikler:**
- CSS Variables
- Flexbox + Grid layout
- Responsive design
- Smooth transitions

---

### 🔧 Backend API

#### **Core APIs**

**`db.php`** (413 bytes)
- PDO database bağlantısı
- `sendJSON()` helper
- Error handling

**`dashboard.php`** (2.8 KB)
- Anlık doluluk
- Günlük gelir
- Aktif abonelikler
- İçerideki araçlar

---

#### **Araç Giriş/Çıkış APIs**

**`entry.php`** (2.8 KB) ⭐ Güncellenmiş
- Araç girişi
- **Abonelik kontrolü** ✨
- Park yeri müsaitlik
- Otomatik araç kaydı (misafir)

**`exit.php`** (2.3 KB) ⭐ Güncellenmiş
- Araç çıkışı
- **Abonelik indirimi (fee = 0)** ✨
- Ücret hesaplama (trigger)
- Park yeri durumu güncelleme

---

#### **Park Yeri APIs**

**`parking_lots.php`** (1 KB) ✨ Yeni
```http
GET /api/parking_lots.php

Response:
{
  "success": true,
  "data": [
    {"code": "OTOP01", "name": "Kadıköy Otopark", ...}
  ]
}
```

**`floors.php`** (1.2 KB) ✨ Yeni
```http
GET /api/floors.php?lot_code=OTOP01

Response:
{
  "success": true,
  "data": [
    {"floor_id": 1, "name": "Zemin", ...}
  ]
}
```

**`spaces.php`** (3.1 KB) ⭐ Güncellenmiş
```http
GET /api/spaces.php?status=Bos&lot_code=OTOP01&floor_id=1

Supports:
- status: 'Bos', 'Dolu', 'Bakim'
- lot_code: 'OTOP01'
- floor_id: 1
```

**`space_create.php`** (2.4 KB) ✨ Yeni
```http
POST /api/space_create.php
{
  "floor_id": 1,
  "space_code": "Z-05",
  "type": "Normal"
}
```

---

#### **Araç Yönetimi APIs**

**`vehicle_create.php`** (2.5 KB) ✨ Yeni
```http
POST /api/vehicle_create.php
{
  "cid": 5,
  "plate": "34ABC123",
  "make": "Toyota",
  "model": "Corolla",
  "color": "Beyaz"
}
```

**`vehicle_info.php`** (2.1 KB) ✨ Yeni
```http
GET /api/vehicle_info.php?plate=34MRV007

Response:
{
  "vehicle": {...},
  "history": [...]  // Son 5 işlem
}
```

**`vehicles.php`** (1.5 KB)
- Araç listesi
- Müşteri bazlı filtreleme

---

#### **Müşteri APIs**

**`customers.php`** (1.8 KB)
- Müşteri listesi
- Aktif müşteriler (is_active = 1)

**`customer_detail.php`** (3.5 KB)
- Müşteri detayı
- Araçlar
- İşlem geçmişi

**`customer_create.php`** (2.2 KB)
- Yeni müşteri
- Validation

**`customer_update.php`** (2.4 KB)
- Müşteri güncelleme

**`customer_delete.php`** (3.8 KB)
- Soft delete (is_active = 0)
- İş kuralları:
  - Aktif abonelik → Silinemez
  - İçeride araç → Silinemez

---

#### **Tarife APIs**

**`tariffs.php`** (1.9 KB)
- Tarife CRUD
- Aktif tarifeler

**`tariff_create.php`**, **`tariff_update.php`**
- Tarife yönetimi

---

#### **Abonelik APIs**

**`subscriptions.php`** (2.1 KB)
- Abonelik listesi
- Durum filtreleme

**`subscription_detail.php`** (1.8 KB)
- Abonelik detayı

**`subscription_create.php`** (2.8 KB)
- Yeni abonelik

**`subscription_update.php`** (2.5 KB)
- Abonelik güncelleme

---

#### **Raporlama APIs**

**`report_occupancy.php`** (1.5 KB)
- Anlık doluluk raporu

**`report_revenue_monthly.php`** (2.2 KB)
- Aylık gelir raporu

**`report_usage.php`** (1.8 KB)
- Kullanım özeti

**`report_export_all_csv.php`** (4.1 KB)
- Tüm raporlar CSV

**`report_export_all_pdf.php`** (5.3 KB)
- Tüm raporlar PDF (mPDF)

---

### 🗄️ Database

**`schema.sql`** (11.9 KB)
- 10 Tablo tanımı
- 7 View
- 4 Trigger
- Foreign key constraints
- Indexes

**Tablolar:**
1. `parking_lot` - Otoparklar
2. `floor` - Katlar
3. `parking_space` - Park yerleri
4. `employee` - Çalışanlar
5. `customer` - Müşteriler (+ is_active)
6. `vehicle` - Araçlar
7. `tariff` - Tarifeler
8. `subscription` - Abonelikler
9. `entry_exit` - Giriş/çıkış kayıtları
10. `payment` - Ödemeler

**View'lar:**
- `vw_occupancy_now` - Anlık doluluk
- `vw_revenue_today` - Günlük gelir
- `vw_active_subscriptions` - Aktif abonelikler
- `vw_customer_summary` - Müşteri özeti
- `vw_recent_entries` - Son girişler
- `vw_inside_cars` - İçerideki araçlar
- `vw_subscription_status_update` - Abonelik durumu

**Trigger'lar:**
1. `before_entry_check_availability` - Park yeri müsaitlik
2. `after_entry_update_space` - Giriş sonrası güncelleme
3. `after_exit_update_space` - Çıkış sonrası güncelleme
4. `calculate_fee_on_exit` - Ücret hesaplama

**`init_db.sql`** (217 bytes)
- Initial data
- Misafir müşteri (cid=999)
- Sample otopark

**`add_is_active.sql`** (229 bytes)
- Soft delete patch
- `is_active` column ekleme

---

## 📊 Dosya İstatistikleri

### API Endpoints (28 dosya)

| Kategori | Dosya Sayısı | Toplam Boyut |
|----------|--------------|--------------|
| Core | 2 | ~3 KB |
| Giriş/Çıkış | 2 | ~5 KB |
| Park Yerleri | 4 | ~8 KB |
| Araç Yönetimi | 3 | ~6 KB |
| Müşteri | 5 | ~13 KB |
| Tarife | 3 | ~6 KB |
| Abonelik | 4 | ~9 KB |
| Raporlama | 5 | ~14 KB |
| **TOPLAM** | **28** | **~64 KB** |

### Frontend

| Dosya | Satır | Boyut |
|-------|-------|-------|
| index.html | ~900 | 33 KB |
| app.js | ~2000 | 65 KB |
| style.css | ~700 | 16 KB |
| **TOPLAM** | **~3600** | **114 KB** |

### Database

| Dosya | İçerik |
|-------|--------|
| schema.sql | 10 tablo + 7 view + 4 trigger |
| init_db.sql | Initial data |
| add_is_active.sql | Soft delete patch |

---

## 🆕 v2.0.0 Yeni Dosyalar

✨ **Yeni Backend APIs:**
- `api/parking_lots.php`
- `api/floors.php`
- `api/space_create.php`
- `api/vehicle_create.php`
- `api/vehicle_info.php`

⭐ **Güncellenen APIs:**
- `api/entry.php` (abonelik)
- `api/exit.php` (abonelik + fee)
- `api/spaces.php` (filtreleme)

---

## 🗑️ Temizlenen Dosyalar

Aşağıdaki gereksiz dosyalar silindi:

- ❌ `app.js.backup` (eski yedek)
- ❌ `app.js.bak` (eski yedek)
- ❌ `app.js.broken` (bozuk versiyon)
- ❌ `loadCustomerDetails_new.js` (test dosyası)
- ❌ `test.html` (test dosyası)
- ❌ `test_db.php` (debug dosyası)
- ❌ `fix_customer_details.sh` (eski script)

---

## 📝 Dokümantasyon Dosyaları

- `README.md` - Ana dokümantasyon
- `DOSYA_YAPISI.md` - Bu dosya
- `KULLANIM_REHBERI.md` - Kullanım kılavuzu
- `KURULUM_KOMUTLARI.md` - Kurulum adımları

---

**Son Güncelleme:** 02 Ocak 2026  
**Toplam Dosya:** 35  
**Toplam Kod:** ~3600+ satır  
**API Endpoint:** 28

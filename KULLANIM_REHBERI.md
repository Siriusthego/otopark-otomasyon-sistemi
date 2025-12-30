# Otopark Yönetim Sistemi - Kullanım Rehberi

## Sistemi Başlatma

### 1. Sunucuyu Başlat
```bash
cd ~/Downloads/VS
php -S localhost:8000
```

### 2. Tarayıcıda Aç
http://localhost:8000

---

## Dashboard Kullanımı

Dashboard açıldığında şunları göreceksiniz:
- **Doluluk Oranı**: Toplam park yerleri ve dolu/boş durum
- **Günlük Gelir**: Bugün toplanan ödemeler
- **İşlem Sayısı**: Bugünkü giriş/çıkış sayıları
- **Aktif Abonelikler**: Geçerli abonelik sayısı
- **İçerideki Araçlar**: Şu anda otoparkta olan araçların listesi

---

## Araç Girişi Yapma

### Adımlar:
1. Sol menüden **"Araç Girişi"** sekmesine tıklayın
2. **Plaka** girin (örnek: `34ABC123`)
3. **Tarife** seçin (dropdown'dan)
4. **Park Yeri** seçin (sadece boş yerler gösterilir)
5. **"Giriş Oluştur"** butonuna tıklayın

### Beklenen Sonuç:
- ✅ "Araç girişi başarılı!" mesajı görünür
- Form temizlenir
- Dashboard otomatik güncellenir
- Doluluk sayısı artar

### Olası Hatalar:
- ❌ "Park yeri müsait değil" → Başka bir park yeri seçin
- ❌ "Bu araç zaten otoparkta" → Araç daha önce girmiş, çıkış yapın

---

## Araç Çıkışı ve Ödeme

### Adımlar:
1. Sol menüden **"Araç Çıkışı / Ücret"** sekmesine tıklayın
2. İçerideki araçlar tablosunda çıkış yapacak aracı bulun
3. **"Seç"** butonuna tıklayın
4. Ücret özeti gösterilir:
   - Kalış süresi (saat, dakika)
   - Uygulanan tarife  
   - Toplam tutar (₺)
5. **Ödeme Yöntemi** seçin (Nakit / Kredi Kartı / Online)
6. **"Ödemeyi Tamamla"** butonuna tıklayın

### Beklenen Sonuç:
- ✅ "Ödeme başarıyla kaydedildi!" mesajı
- Park yeri "Boş" durumuna döner (trigger)
- Dashboard günlük gelir artar
- İçerideki araçlar listesi güncellenir

---

## Park Yerleri Görüntüleme

1. Sol menüden **"Park Yerleri"** sekmesine tıklayın
2. Tüm park yerlerini görsel olarak görebilirsiniz:
   - 🟢 Yeşil = Boş
   - 🔴 Kırmızı = Dolu
   - ⚫ Gri = Bakımda

### Filtreleme:
- **Kat seçici** ile belirli bir kat gösterilebilir
- **Durum filtreleri** ile sadece boş/dolu/bakım olan yerler gösterilebilir

---

## Test Senaryosu

### Tam Akış Testi:

**1. Araç Girişi:**
```
Plaka: 06TEST123
Tarife: Standart Saatlik (₺50/saat)
Park Yeri: Z-05 (Zemin)
```

**2. Dashboard Kontrolü:**
- Doluluk +1 artmalı
- "İçerideki Araçlar" tablosunda 06TEST123 görünmeli

**3. Araç Çıkışı:**
- "Araç Çıkışı" sekmesinden 06TEST123'ü seç
- Ücret hesaplanmalı (örn: 2 saat = ₺100)

**4. Ödeme:**
- Ödeme yöntemi: Kredi Kartı
- Ödemeyi tamamla

**5. Doğrulama:**
- Dashboard geliri +₺100 artmalı
- İçerideki araçlar listesinden 06TEST123 kaybolmalı
- Park Yerleri'nde Z-05 "Boş" olmalı

---

## Sorun Giderme

### JavaScript Çalışmıyor mu?

**Tarayıcı Console Kontrolü:**
1. F12 tuşuna basın
2. "Console" sekmesine gidin
3. Şu mesajları görmeli siniz:
   ```
   🚗 Otopark Yönetim Sistemi yüklendi
   ✅ Sistem hazır!
   ```

4. Eğer kırmızı hatalar varsa:
   - Sayfayı yenileyin (Ctrl+F5)
   - Browser cache'i temizleyin
   - Farklı tarayıcıda deneyin

### Dashboard Yüklenmiyor mu?

**Network Kontrolü:**
1. F12 → Network sekmesi
2. Sayfayı yenileyin
3. `/api/dashboard.php` isteğini bulun
4. Response'u kontrol edin:
   - Status 200 olmalı
   - JSON formatında veri dönmeli

### API Test Komutu:
```bash
curl http://localhost:8000/api/dashboard.php | python3 -m json.tool
```

---

## Veritabanı Bilgileri

```
Kullanıcı: otopark_user
Şifre: otopark_2025
Database: otopark_db
```

### MySQL'e Bağlanma:
```bash
mysql -u otopark_user -p'otopark_2025' otopark_db
```

### Hızlı Sorgular:
```sql
-- İçerideki araçlar
SELECT * FROM vw_inside_cars;

-- Bugünkü gelir
SELECT * FROM vw_revenue_today;

-- Doluluk durumu
SELECT * FROM vw_occupancy_now;
```

---

## Demo Verileri

Sistemde hazır demo verileri var:
- **Çalışanlar**: 3 kişi
- **Park Yerleri**: 25 adet (Zemin, 1.Kat, 2.Kat)
- **Tarifeler**: 4 farklı tarife
- **Müşteriler**: 4 kişi
- **Araçlar**: 5 kayıtlı araç
- **Abonelikler**: 2 aktif abonelik

---

## Önemli Notlar

1. **Trigger'lar Otomatik Çalışır:**
   - Giriş sırasında park yeri otomatik "Dolu" olur
   - Çıkış sırasında süre ve ücret otomatik hesaplanır
   - Ödeme sonrası park yeri otomatik "Boş" olur

2. **Güvenlik:**
   - Production ortamında `otopark_2025` şifresini değiştirin
   - `db.php` dosyasında `display_errors = 0` yapın

3. **Performans:**
   - View'lar anlık veri gösterir
   - Dashboard her açılışta yeniden yüklenir

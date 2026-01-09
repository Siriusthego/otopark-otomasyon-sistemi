-- =============================================
-- Otopark Otomasyon Sistemi - Database Schema
-- MySQL 8.0
-- =============================================

-- Database oluştur
CREATE DATABASE IF NOT EXISTS otopark_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE otopark_db;

-- =============================================
-- TABLOLAR
-- =============================================

-- Çalışanlar
CREATE TABLE employee (
    eid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL COMMENT 'Yönetici, Personel, vs.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Otopark Lokasyonları
CREATE TABLE parking_lot (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    manager_eid INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_eid) REFERENCES employee(eid) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Katlar
CREATE TABLE floor (
    floor_id INT AUTO_INCREMENT PRIMARY KEY,
    lot_code VARCHAR(20) NOT NULL,
    level_no INT NOT NULL COMMENT 'Zemin=0, 1.Kat=1, vs.',
    name VARCHAR(50) NOT NULL COMMENT 'Zemin, 1.Kat, vs.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lot_code) REFERENCES parking_lot(code) ON DELETE CASCADE,
    UNIQUE KEY unique_floor (lot_code, level_no)
) ENGINE=InnoDB;

-- Park Yerleri
CREATE TABLE parking_space (
    space_id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT NOT NULL,
    space_code VARCHAR(20) NOT NULL COMMENT 'Z-01, 1-08, vs.',
    space_type VARCHAR(50) DEFAULT 'Normal' COMMENT 'Normal, Engelli, Elektrikli, vs.',
    status ENUM('Bos', 'Dolu', 'Bakim') DEFAULT 'Bos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floor(floor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_space (floor_id, space_code)
) ENGINE=InnoDB;

-- Müşteriler
CREATE TABLE customer (
    cid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Araçlar
CREATE TABLE vehicle (
    vid INT AUTO_INCREMENT PRIMARY KEY,
    cid INT NOT NULL,
    plate VARCHAR(20) UNIQUE NOT NULL COMMENT 'Plaka unique olmalı',
    make VARCHAR(50) COMMENT 'Marka',
    model VARCHAR(50) COMMENT 'Model',
    color VARCHAR(30) COMMENT 'Renk',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cid) REFERENCES customer(cid) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tarifeler
CREATE TABLE tariff (
    tid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'HOURLY, SUBSCRIPTION, vs.',
    hourly_rate DECIMAL(10,2) NOT NULL COMMENT 'Saatlik ücret (TL)',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Abonelikler
CREATE TABLE subscription (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    cid INT NOT NULL,
    tid INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Aktif', 'Pasif', 'Sona Erdi', 'Yakında') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cid) REFERENCES customer(cid) ON DELETE CASCADE,
    FOREIGN KEY (tid) REFERENCES tariff(tid) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Giriş-Çıkış Kayıtları
CREATE TABLE entry_exit (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    vid INT NOT NULL,
    space_id INT NOT NULL,
    tid INT NOT NULL COMMENT 'Kullanılan tarife',
    entry_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exit_time DATETIME NULL,
    duration_min INT NULL COMMENT 'Süre dakika cinsinden (trigger ile hesaplanır)',
    fee DECIMAL(10,2) NULL COMMENT 'Ücret (trigger ile hesaplanır)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vid) REFERENCES vehicle(vid) ON DELETE RESTRICT,
    FOREIGN KEY (space_id) REFERENCES parking_space(space_id) ON DELETE RESTRICT,
    FOREIGN KEY (tid) REFERENCES tariff(tid) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Ödemeler
CREATE TABLE payment (
    pay_id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(30) NOT NULL COMMENT 'Nakit, Kredi Kartı, Online',
    pay_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES entry_exit(record_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =============================================
-- TRIGGER'LAR
-- =============================================

DELIMITER $$

-- TRIGGER 1: Girişte kontroller
-- Park yeri Boş değilse veya araç zaten içerideyse hata
CREATE TRIGGER trg_before_entry
BEFORE INSERT ON entry_exit
FOR EACH ROW
BEGIN
    DECLARE space_status VARCHAR(10);
    DECLARE inside_count INT;
    
    -- Park yeri durumunu kontrol et
    SELECT status INTO space_status 
    FROM parking_space 
    WHERE space_id = NEW.space_id;
    
    IF space_status != 'Bos' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Park yeri müsait değil (Dolu veya Bakımda)';
    END IF;
    
    -- Araç zaten içeride mi?
    SELECT COUNT(*) INTO inside_count
    FROM entry_exit
    WHERE vid = NEW.vid AND exit_time IS NULL;
    
    IF inside_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Bu araç zaten otoparkta bulunuyor';
    END IF;
END$$

-- TRIGGER 2: Girişten sonra park yeri Dolu yap
CREATE TRIGGER trg_after_entry
AFTER INSERT ON entry_exit
FOR EACH ROW
BEGIN
    UPDATE parking_space 
    SET status = 'Dolu' 
    WHERE space_id = NEW.space_id;
END$$

-- TRIGGER 3: Çıkışta süre ve ücret hesapla
CREATE TRIGGER trg_before_exit
BEFORE UPDATE ON entry_exit
FOR EACH ROW
BEGIN
    DECLARE rate DECIMAL(10,2);
    
    -- Sadece exit_time güncelleniyorsa işlem yap
    IF NEW.exit_time IS NOT NULL AND OLD.exit_time IS NULL THEN
        -- Süreyi dakika cinsinden hesapla
        SET NEW.duration_min = TIMESTAMPDIFF(MINUTE, OLD.entry_time, NEW.exit_time);
        
        -- Tarife oranını al
        SELECT hourly_rate INTO rate 
        FROM tariff 
        WHERE tid = OLD.tid;
        
        -- Ücreti hesapla (dakika / 60 * saatlik ücret)
        SET NEW.fee = ROUND((NEW.duration_min / 60.0) * rate, 2);
        
        -- Minimum ücret: 0.01 saat bile olsa en az 1 TL
        IF NEW.fee < rate * 0.1 THEN
            SET NEW.fee = rate * 0.1;
        END IF;
    END IF;
END$$

-- TRIGGER 4: Çıkıştan sonra park yeri Boş yap
CREATE TRIGGER trg_after_exit
AFTER UPDATE ON entry_exit
FOR EACH ROW
BEGIN
    -- Sadece exit_time güncelleniyorsa işlem yap
    IF NEW.exit_time IS NOT NULL AND OLD.exit_time IS NULL THEN
        UPDATE parking_space 
        SET status = 'Bos' 
        WHERE space_id = OLD.space_id;
    END IF;
END$$

DELIMITER ;

-- =============================================
-- VIEW'LAR (Dashboard için)
-- =============================================

-- VIEW 1: Anlık doluluk durumu
CREATE OR REPLACE VIEW vw_occupancy_now AS
SELECT 
    COUNT(*) AS total_spaces,
    SUM(CASE WHEN status = 'Dolu' THEN 1 ELSE 0 END) AS occupied,
    SUM(CASE WHEN status = 'Bos' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status = 'Bakim' THEN 1 ELSE 0 END) AS maintenance,
    ROUND(
        (SUM(CASE WHEN status = 'Dolu' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
        0
    ) AS occupancy_rate
FROM parking_space;

-- VIEW 2: Bugünkü gelir
CREATE OR REPLACE VIEW vw_revenue_today AS
SELECT 
    COALESCE(SUM(amount), 0) AS today_revenue,
    COUNT(*) AS payment_count
FROM payment
WHERE DATE(pay_time) = CURDATE();

-- VIEW 3: İçerideki araçlar
CREATE OR REPLACE VIEW vw_inside_cars AS
SELECT 
    ee.record_id,
    v.plate,
    c.name AS customer_name,
    ps.space_code,
    f.name AS floor_name,
    pl.name AS lot_name,
    t.name AS tariff_name,
    ee.entry_time,
    TIMESTAMPDIFF(MINUTE, ee.entry_time, NOW()) AS minutes_inside
FROM entry_exit ee
JOIN vehicle v ON ee.vid = v.vid
JOIN customer c ON v.cid = c.cid
JOIN parking_space ps ON ee.space_id = ps.space_id
JOIN floor f ON ps.floor_id = f.floor_id
JOIN parking_lot pl ON f.lot_code = pl.code
JOIN tariff t ON ee.tid = t.tid
WHERE ee.exit_time IS NULL
ORDER BY ee.entry_time DESC;

-- VIEW 4: Aktif abonelikler
CREATE OR REPLACE VIEW vw_active_subscriptions AS
SELECT 
    s.sub_id,
    c.name AS customer_name,
    c.email,
    t.name AS tariff_name,
    s.start_date,
    s.end_date,
    s.status,
    DATEDIFF(s.end_date, CURDATE()) AS days_remaining
FROM subscription s
JOIN customer c ON s.cid = c.cid
JOIN tariff t ON s.tid = t.tid
WHERE s.status = 'Aktif' 
  AND s.end_date >= CURDATE()
ORDER BY s.end_date ASC;

-- =============================================
-- DEMO VERİLER (Test için)
-- =============================================

-- Çalışanlar
INSERT INTO employee (name, role) VALUES 
('Merve Kul', 'Yönetici'),
('Abdullah Emir Kireçci', 'Personel'),
('Ahmet Yılmaz', 'Personel');

-- Otopark
INSERT INTO parking_lot (code, name, address, manager_eid) VALUES 
('OTOP01', 'Kadıköy Otopark', 'Kadıköy / İstanbul', 1);

-- Katlar
INSERT INTO floor (lot_code, level_no, name) VALUES 
('OTOP01', 0, 'Zemin'),
('OTOP01', 1, '1.Kat'),
('OTOP01', 2, '2.Kat');

-- Park Yerleri
INSERT INTO parking_space (floor_id, space_code, space_type, status) VALUES 
-- Zemin
(1, 'Z-01', 'Normal', 'Bos'),
(1, 'Z-02', 'Normal', 'Bos'),
(1, 'Z-03', 'Normal', 'Bos'),
(1, 'Z-04', 'Elektrikli', 'Bos'),
(1, 'Z-05', 'Engelli', 'Bos'),
(1, 'Z-06', 'Normal', 'Bos'),
(1, 'Z-07', 'Normal', 'Bos'),
(1, 'Z-08', 'Normal', 'Bos'),
(1, 'Z-09', 'Normal', 'Bos'),
(1, 'Z-10', 'Normal', 'Bos'),
-- 1. Kat
(2, '1-01', 'Normal', 'Bos'),
(2, '1-02', 'Normal', 'Bos'),
(2, '1-03', 'Normal', 'Bos'),
(2, '1-04', 'Normal', 'Bos'),
(2, '1-05', 'Normal', 'Bos'),
(2, '1-06', 'Normal', 'Bos'),
(2, '1-07', 'Normal', 'Bos'),
(2, '1-08', 'Normal', 'Bos'),
(2, '1-09', 'Normal', 'Bos'),
(2, '1-10', 'Normal', 'Bos'),
-- 2. Kat
(3, '2-01', 'Normal', 'Bos'),
(3, '2-02', 'Normal', 'Bos'),
(3, '2-03', 'Normal', 'Bos'),
(3, '2-04', 'Normal', 'Bos'),
(3, '2-05', 'Normal', 'Bos');

-- Tarifeler
INSERT INTO tariff (name, type, hourly_rate, description, is_active) VALUES 
('Standart Saatlik', 'HOURLY', 50.00, 'Gündüz saatleri için standart ücret', TRUE),
('Gece Tarifesi', 'HOURLY', 30.00, 'Akşam 22:00 sonrası indirimli', TRUE),
('Aylık Abonelik', 'SUBSCRIPTION', 1200.00, 'Sınırsız giriş/çıkış (aylık)', TRUE),
('Haftalık Abonelik', 'SUBSCRIPTION', 350.00, 'Haftalık abonelik', TRUE);

-- Müşteriler
INSERT INTO customer (name, phone, email) VALUES 
('Merve Kul', '555 000 0000', 'merve@example.com'),
('Abdullah Emir Kireçci', '555 111 2233', 'ae@example.com'),
('Ayşe Demir', '555 222 3344', 'ayse@example.com'),
('Mehmet Çelik', '555 333 4455', 'mehmet@example.com');

-- Araçlar
INSERT INTO vehicle (cid, plate, make, model, color) VALUES 
(1, '34ABC123', 'Renault', 'Clio', 'Beyaz'),
(1, '34MRV007', 'Toyota', 'Corolla', 'Siyah'),
(2, '16KUL404', 'Volkswagen', 'Passat', 'Gri'),
(3, '35IZM035', 'Ford', 'Focus', 'Mavi'),
(4, '06ANK777', 'Honda', 'Civic', 'Kırmızı');

-- Abonelikler
INSERT INTO subscription (cid, tid, start_date, end_date, status) VALUES 
(1, 3, '2025-12-01', '2025-12-31', 'Aktif'),
(2, 4, '2025-12-15', '2026-01-15', 'Aktif');

-- Demo: Birkaç araç girişi yap (test için)
-- Not: Gerçek kullanımda API üzerinden yapılacak
INSERT INTO entry_exit (vid, space_id, tid, entry_time) VALUES 
(1, 1, 1, NOW() - INTERVAL 2 HOUR),  -- 34ABC123, Z-01, 2 saat önce girdi
(3, 8, 1, NOW() - INTERVAL 1 HOUR);  -- 16KUL404, Z-08, 1 saat önce girdi

-- =============================================
-- TAMAMLANDI
-- =============================================

-- Müşteri soft delete için is_active alanı ekleme
ALTER TABLE customer ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER name;

-- Mevcut tüm müşterileri aktif yap
UPDATE customer SET is_active = 1 WHERE is_active IS NULL;

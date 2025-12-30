-- Veritabanını oluştur ve yetki ver
CREATE DATABASE IF NOT EXISTS otopark_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON otopark_db.* TO 'otopark_user'@'localhost';
FLUSH PRIVILEGES;

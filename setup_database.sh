#!/bin/bash
# MySQL Veritabanı Kurulum Script
# Bu scripti çalıştırmak için: bash setup_database.sh

echo "🗄️ MySQL Veritabanı Kurulumu Başlatılıyor..."

# Veritabanı oluştur
echo "📦 Veritabanı oluşturuluyor..."
sudo mysql -u root -e "DROP DATABASE IF EXISTS otopark_db;"
sudo mysql -u root < schema.sql

# Kontrol et
echo ""
echo "✅ Tablolar kontrol ediliyor..."
sudo mysql -u root -e "USE otopark_db; SHOW TABLES;"

echo ""
echo "✅ Trigger'lar kontrol ediliyor..."
sudo mysql -u root -e "USE otopark_db; SHOW TRIGGERS;"

echo ""
echo "✅ View'lar kontrol ediliyor..."
sudo mysql -u root -e "USE otopark_db; SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'otopark_db';"

echo ""
echo "✅ Demo veriler kontrol ediliyor..."
sudo mysql -u root -e "USE otopark_db; SELECT COUNT(*) as toplam_park_yeri FROM parking_space;"
sudo mysql -u root -e "USE otopark_db; SELECT COUNT(*) as toplam_tarife FROM tariff;"

echo ""
echo "🎉 Veritabanı kurulumu tamamlandı!"

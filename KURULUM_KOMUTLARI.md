# PHP Kurulum ve Test Komutları

## Hızlı Kurulum

Terminal'de şu komutları çalıştırın:

```bash
# 1. Sistem güncellemesi ve PHP kurulumu
sudo apt update
sudo apt install -y php-cli php-mysql libapache2-mod-php

# 2. PHP versiyonunu kontrol
php -v

# 3. PHP syntax testleri
cd /home/abdullahemirkirecci/Downloads/VS
php -l api/db.php
php -l api/entry.php
php -l api/exit.php
php -l api/payments.php
php -l api/spaces.php
php -l api/tariffs.php
php -l api/dashboard.php

# 4. MySQL veritabanı oluşturma
sudo mysql -u root -p < schema.sql

# 5. Veritabanı kontrolü
sudo mysql -u root -p -e "USE otopark_db; SHOW TABLES;"
sudo mysql -u root -p -e "USE otopark_db; SHOW TRIGGERS;"
```

## Kurulum Tamamlandıktan Sonra

Bana "devam et" yazın, PHP testlerini otomatik çalıştıracağım.

<?php
// Veritabanı bağlantı testi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DATABASE CONNECTION TEST ===\n\n";

// Bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'otopark_db');
define('DB_USER', 'otopark_user');
define('DB_PASS', 'otopark_2025');

echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    // PDO bağlantısı
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "✅ Bağlantı başarılı!\n\n";

    // Tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "📋 Tablolar (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n";

    // View testi
    echo "🔍 vw_occupancy_now testi:\n";
    $stmt = $pdo->query("SELECT * FROM vw_occupancy_now");
    $result = $stmt->fetch();
    print_r($result);

} catch (PDOException $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}

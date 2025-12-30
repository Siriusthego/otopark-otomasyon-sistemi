<?php
/**
 * Database Bağlantısı
 * MySQL PDO Connection
 */

// Hata raporlama (development için)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production'da 0 olmalı

// Veritabanı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'otopark_db');
define('DB_USER', 'otopark_user');
define('DB_PASS', 'otopark_2025'); // Güvenli şifre kullanın

// PDO bağlantısı oluştur
try {
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
} catch (PDOException $e) {
    // Bağlantı hatası
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * JSON Response Helper
 */
function sendJSON($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CORS Headers (gerekirse)
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

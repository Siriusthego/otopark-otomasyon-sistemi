<?php
/**
 * Tarife Listesi API
 * GET: Tüm tarifeleri listele
 */

require_once 'db.php';

// Sadece GET kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

try {
    // Aktif tarifeleri getir
    $stmt = $pdo->query("
        SELECT tid, name, type, hourly_rate, description, is_active
        FROM tariff
        WHERE is_active = TRUE
        ORDER BY 
            CASE type 
                WHEN 'HOURLY' THEN 1 
                WHEN 'SUBSCRIPTION' THEN 2 
                ELSE 3 
            END,
            hourly_rate ASC
    ");

    $tariffs = $stmt->fetchAll();

    // Başarılı
    sendJSON([
        'success' => true,
        'data' => $tariffs
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

<?php
/**
 * Parking Lots API
 * GET: Otopark listesi
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

try {
    $stmt = $pdo->query("
        SELECT code, name, address
        FROM parking_lot
        ORDER BY code
    ");

    $lots = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'data' => $lots
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

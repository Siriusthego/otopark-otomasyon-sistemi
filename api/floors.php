<?php
/**
 * Floors API
 * GET: ?lot_code=... ile seçili otoparkın katlarını döndür
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$lot_code = trim($_GET['lot_code'] ?? '');

try {
    if (!empty($lot_code)) {
        // Belirli bir otoparkın katları
        $stmt = $pdo->prepare("
            SELECT floor_id, name, lot_code, level_no
            FROM floor
            WHERE lot_code = ?
            ORDER BY level_no
        ");
        $stmt->execute([$lot_code]);
    } else {
        // Tüm katlar
        $stmt = $pdo->query("
            SELECT floor_id, name, lot_code, level_no
            FROM floor
            ORDER BY lot_code, level_no
        ");
    }

    $floors = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'data' => $floors
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

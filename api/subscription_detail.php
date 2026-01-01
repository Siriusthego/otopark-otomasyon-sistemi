<?php
/**
 * Abonelik Detay API
 * GET: ?sub_id=ID ile tek aboneliğin detayını döndürür
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$sub_id = intval($_GET['sub_id'] ?? 0);

if ($sub_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir abonelik ID gerekli'], 400);
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.sub_id,
            s.cid,
            s.tid,
            s.start_date,
            s.end_date,
            s.status,
            c.name as customer_name,
            t.name as tariff_name,
            t.hourly_rate
        FROM subscription s
        JOIN customer c ON s.cid = c.cid
        JOIN tariff t ON s.tid = t.tid
        WHERE s.sub_id = ?
    ");
    $stmt->execute([$sub_id]);
    $subscription = $stmt->fetch();

    if (!$subscription) {
        sendJSON(['success' => false, 'error' => 'Abonelik bulunamadı'], 404);
    }

    sendJSON([
        'success' => true,
        'data' => $subscription
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

<?php
/**
 * Müşteri Detay API
 * GET: ?cid=ID ile tek müşterinin tüm bilgileri
 * Response: customer info + vehicles + transactions
 */

require_once 'db.php';

// Sadece GET kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$cid = intval($_GET['cid'] ?? 0);

if ($cid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir müşteri ID gerekli'], 400);
}

try {
    // 1) Müşteri bilgisi
    $stmt = $pdo->prepare("
        SELECT 
            c.cid,
            c.name,
            c.phone,
            c.email,
            c.created_at,
            COALESCE(
                (SELECT CONCAT(t.name, ' (', s.end_date, ')') 
                 FROM subscription s 
                 JOIN tariff t ON s.tid = t.tid 
                 WHERE s.cid = c.cid AND s.status = 'Aktif' 
                 LIMIT 1), 
                'Yok'
            ) as active_subscription
        FROM customer c
        WHERE c.cid = ?
    ");
    $stmt->execute([$cid]);
    $customer = $stmt->fetch();

    if (!$customer) {
        sendJSON(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
    }

    // 2) Müşteri araçları
    $stmt = $pdo->prepare("
        SELECT vid, cid, plate, make, model, color, created_at
        FROM vehicle
        WHERE cid = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$cid]);
    $vehicles = $stmt->fetchAll();

    // 3) Son işlemler (entry_exit kayıtları)
    $stmt = $pdo->prepare("
        SELECT 
            ee.record_id,
            ee.entry_time,
            ee.exit_time,
            ee.duration_min,
            ee.fee,
            v.plate,
            ps.space_code,
            t.name as tariff_name
        FROM entry_exit ee
        JOIN vehicle v ON ee.vid = v.vid
        JOIN parking_space ps ON ee.space_id = ps.space_id
        JOIN tariff t ON ee.tid = t.tid
        WHERE v.cid = ?
        ORDER BY ee.entry_time DESC
        LIMIT 10
    ");
    $stmt->execute([$cid]);
    $transactions = $stmt->fetchAll();

    // Başarılı response
    sendJSON([
        'success' => true,
        'data' => [
            'customer' => $customer,
            'vehicles' => $vehicles,
            'transactions' => $transactions
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Detay bilgisi alınamadı: ' . $e->getMessage()], 500);
}

<?php
/**
 * Vehicle Info API
 * GET: plate - Araç ve müşteri bilgisi
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$plate = strtoupper(trim($_GET['plate'] ?? ''));

if (empty($plate)) {
    sendJSON(['success' => false, 'error' => 'Plaka gerekli'], 400);
}

try {
    // Araç ve müşteri bilgisi
    $stmt = $pdo->prepare("
        SELECT 
            v.vid, v.plate, v.make, v.model, v.color,
            c.cid, c.name as customer_name, c.phone, c.email
        FROM vehicle v
        LEFT JOIN customer c ON v.cid = c.cid
        WHERE v.plate = ?
    ");
    $stmt->execute([$plate]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        sendJSON(['success' => false, 'error' => 'Araç bulunamadı'], 404);
    }
    
    // Son işlemler
    $stmt = $pdo->prepare("
        SELECT 
            ee.record_id, ee.entry_time, ee.exit_time, ee.fee,
            ps.space_code, t.name as tariff_name
        FROM entry_exit ee
        JOIN parking_space ps ON ee.space_id = ps.space_id
        JOIN tariff t ON ee.tid = t.tid
        WHERE ee.vid = ?
        ORDER BY ee.entry_time DESC
        LIMIT 5
    ");
    $stmt->execute([$vehicle['vid']]);
    $history = $stmt->fetchAll();
    
    sendJSON([
        'success' => true,
        'data' => [
            'vehicle' => $vehicle,
            'history' => $history
        ]
    ]);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

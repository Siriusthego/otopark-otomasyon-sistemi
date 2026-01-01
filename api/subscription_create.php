<?php
/**
 * Abonelik Oluşturma API
 * POST: Yeni abonelik oluşturur
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$cid = intval($input['cid'] ?? 0);
$tid = intval($input['tid'] ?? 0);
$start_date = trim($input['start_date'] ?? '');
$end_date = trim($input['end_date'] ?? '');
$status = trim($input['status'] ?? 'Aktif');

// Validasyon
if ($cid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir müşteri seçiniz'], 400);
}

if ($tid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir tarife seçiniz'], 400);
}

if (empty($start_date) || empty($end_date)) {
    sendJSON(['success' => false, 'error' => 'Başlangıç ve bitiş tarihleri gerekli'], 400);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO subscription (cid, tid, start_date, end_date, status) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$cid, $tid, $start_date, $end_date, $status]);
    $sub_id = $pdo->lastInsertId();

    sendJSON([
        'success' => true,
        'message' => 'Abonelik başarıyla oluşturuldu',
        'data' => [
            'sub_id' => $sub_id,
            'cid' => $cid,
            'tid' => $tid,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status
        ]
    ], 201);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Abonelik oluşturulamadı: ' . $e->getMessage()], 500);
}

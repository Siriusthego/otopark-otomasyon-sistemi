<?php
/**
 * Abonelik Güncelleme API
 * POST: Mevcut aboneliği günceller
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$sub_id = intval($input['sub_id'] ?? 0);
$cid = intval($input['cid'] ?? 0);
$tid = intval($input['tid'] ?? 0);
$start_date = trim($input['start_date'] ?? '');
$end_date = trim($input['end_date'] ?? '');
$status = trim($input['status'] ?? '');

// Validasyon
if ($sub_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir abonelik ID gerekli'], 400);
}

if ($cid <= 0 || $tid <= 0) {
    sendJSON(['success' => false, 'error' => 'Müşteri ve tarife gerekli'], 400);
}

if (empty($start_date) || empty($end_date) || empty($status)) {
    sendJSON(['success' => false, 'error' => 'Tüm alanlar gerekli'], 400);
}

try {
    $stmt = $pdo->prepare("
        UPDATE subscription 
        SET cid = ?, tid = ?, start_date = ?, end_date = ?, status = ? 
        WHERE sub_id = ?
    ");
    $stmt->execute([$cid, $tid, $start_date, $end_date, $status, $sub_id]);

    if ($stmt->rowCount() === 0) {
        sendJSON(['success' => false, 'error' => 'Abonelik bulunamadı veya değişiklik yapılmadı'], 404);
    }

    sendJSON([
        'success' => true,
        'message' => 'Abonelik başarıyla güncellendi',
        'data' => [
            'sub_id' => $sub_id,
            'cid' => $cid,
            'tid' => $tid,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Abonelik güncellenemedi: ' . $e->getMessage()], 500);
}

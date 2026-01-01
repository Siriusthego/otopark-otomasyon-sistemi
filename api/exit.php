<?php
/**
 * Araç Çıkışı API (Abonelik Entegre)
 * POST: record_id
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$record_id = intval($input['record_id'] ?? 0);

if ($record_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir kayıt ID\'si gerekli'], 400);
}

try {
    // Kayıt bilgilerini al
    $stmt = $pdo->prepare("
        SELECT ee.*, v.plate, v.cid, ps.space_code, t.name as tariff_name
        FROM entry_exit ee
        JOIN vehicle v ON ee.vid = v.vid
        JOIN parking_space ps ON ee.space_id = ps.space_id
        JOIN tariff t ON ee.tid = t.tid
        WHERE ee.record_id = ?
    ");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();

    if (!$record) {
        sendJSON(['success' => false, 'error' => 'Kayıt bulunamadı'], 404);
    }

    if ($record['exit_time'] !== null) {
        sendJSON(['success' => false, 'error' => 'Bu araç zaten çıkış yapmış'], 400);
    }

    $cid = $record['cid'];

    // 🎯 ABONELİK KONTROLÜ
    $stmt = $pdo->prepare("
        SELECT sub_id, tid as subscription_type 
        FROM subscription 
        WHERE cid = ? 
        AND status = 'Aktif'
        AND start_date <= NOW()
        AND end_date >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$cid]);
    $subscription = $stmt->fetch();

    $has_subscription = (bool) $subscription;

    // Çıkış işlemi - trigger otomatik hesaplama yapacak
    $stmt = $pdo->prepare("UPDATE entry_exit SET exit_time = NOW() WHERE record_id = ?");
    $stmt->execute([$record_id]);

    // Güncellenmiş kaydı al
    $stmt = $pdo->prepare("SELECT duration_min, fee FROM entry_exit WHERE record_id = ?");
    $stmt->execute([$record_id]);
    $updated = $stmt->fetch();

    $original_fee = floatval($updated['fee']);
    $final_fee = $original_fee;

    // Abonelik varsa ücreti sıfırla
    if ($has_subscription) {
        $stmt = $pdo->prepare("UPDATE entry_exit SET fee = 0 WHERE record_id = ?");
        $stmt->execute([$record_id]);
        $final_fee = 0;
    }

    sendJSON([
        'success' => true,
        'message' => 'Araç çıkışı başarılı',
        'data' => [
            'record_id' => $record_id,
            'plate' => $record['plate'],
            'space_code' => $record['space_code'],
            'tariff_name' => $record['tariff_name'],
            'entry_time' => $record['entry_time'],
            'exit_time' => date('Y-m-d H:i:s'),
            'duration_min' => $updated['duration_min'],
            'original_fee' => $original_fee,
            'final_fee' => $final_fee,
            'has_subscription' => $has_subscription,
            'subscription_discount' => $has_subscription ? $original_fee : 0,
            'note' => $has_subscription ? 'Abonelik nedeniyle ücret alınmadı' : 'Normal tarife uygulandı'
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Çıkış işlemi başarısız: ' . $e->getMessage()], 500);
}

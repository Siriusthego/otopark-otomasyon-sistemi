<?php
/**
 * Araç Çıkışı API
 * POST: record_id
 */

require_once 'db.php';

// Sadece POST kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

// JSON body oku
$input = json_decode(file_get_contents('php://input'), true);

// Parametreleri al
$record_id = intval($input['record_id'] ?? 0);

// Validasyon
if ($record_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir kayıt ID\'si gerekli'], 400);
}

try {
    // Kayıt var mı ve çıkış yapılmış mı kontrol et
    $stmt = $pdo->prepare("
        SELECT ee.*, v.plate, ps.space_code, t.name as tariff_name
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

    // Çıkış işlemi yap
    // Trigger otomatik olarak duration_min ve fee hesaplayacak
    $stmt = $pdo->prepare("UPDATE entry_exit SET exit_time = NOW() WHERE record_id = ?");
    $stmt->execute([$record_id]);

    // Güncellenmiş kaydı al (fee ve duration_min trigger tarafından hesaplanmış)
    $stmt = $pdo->prepare("SELECT duration_min, fee FROM entry_exit WHERE record_id = ?");
    $stmt->execute([$record_id]);
    $updated = $stmt->fetch();

    // Başarılı
    sendJSON([
        'success' => true,
        'message' => 'Araç çıkışı başarılı',
        'data' => [
            'record_id' => $record_id,
            'plate' => $record['plate'],
            'space_code' => $record['space_code'],
            'tariff_name' => $record['tariff_name'],
            'entry_time' => $record['entry_time'],
            'exit_time' => date('Y-m-d H:i:s'), // NOW()
            'duration_min' => $updated['duration_min'],
            'fee' => floatval($updated['fee'])
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Çıkış işlemi başarısız: ' . $e->getMessage()], 500);
}

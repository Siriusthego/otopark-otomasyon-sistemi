<?php
/**
 * Ödeme Kaydı API
 * POST: record_id, method
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
$method = trim($input['method'] ?? 'Nakit');

// Validasyon
if ($record_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir kayıt ID\'si gerekli'], 400);
}

// Method validasyonu
$valid_methods = ['Nakit', 'Kredi Kartı', 'Online'];
if (!in_array($method, $valid_methods)) {
    $method = 'Nakit'; // Varsayılan
}

try {
    // Kayıt var mı ve çıkış yapılmış mı kontrol et
    $stmt = $pdo->prepare("SELECT fee, exit_time FROM entry_exit WHERE record_id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();

    if (!$record) {
        sendJSON(['success' => false, 'error' => 'Kayıt bulunamadı'], 404);
    }

    if ($record['exit_time'] === null) {
        sendJSON(['success' => false, 'error' => 'Araç henüz çıkış yapmamış'], 400);
    }

    // Zaten ödeme yapılmış mı kontrol et
    $stmt = $pdo->prepare("SELECT pay_id FROM payment WHERE record_id = ?");
    $stmt->execute([$record_id]);
    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'error' => 'Bu kayıt için zaten ödeme alınmış'], 400);
    }

    // Ödeme kaydı oluştur
    $amount = $record['fee'];
    $stmt = $pdo->prepare("INSERT INTO payment (record_id, amount, method, pay_time) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$record_id, $amount, $method]);
    $pay_id = $pdo->lastInsertId();

    // Başarılı
    sendJSON([
        'success' => true,
        'message' => 'Ödeme başarıyla kaydedildi',
        'data' => [
            'pay_id' => $pay_id,
            'record_id' => $record_id,
            'amount' => floatval($amount),
            'method' => $method
        ]
    ], 201);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Ödeme kaydı başarısız: ' . $e->getMessage()], 500);
}

<?php
/**
 * Vehicle Create API
 * POST: Müşteriye yeni araç ekle
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$cid = intval($input['cid'] ?? 0);
$plate = trim($input['plate'] ?? '');
$make = trim($input['make'] ?? '');
$model = trim($input['model'] ?? '');
$color = trim($input['color'] ?? '');

// Validation
if ($cid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir müşteri seçiniz'], 400);
}

if (empty($plate)) {
    sendJSON(['success' => false, 'error' => 'Plaka gerekli'], 400);
}

// Plaka formatını standartlaştır (boşlukları kaldır, büyük harf)
$plate = strtoupper(str_replace(' ', '', $plate));

try {
    // Müşteri var mı kontrol et
    $stmt = $pdo->prepare("SELECT cid FROM customer WHERE cid = ?");
    $stmt->execute([$cid]);

    if (!$stmt->fetch()) {
        sendJSON(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
    }

    // Duplicate plaka kontrolü
    $stmt = $pdo->prepare("SELECT vid FROM vehicle WHERE plate = ?");
    $stmt->execute([$plate]);

    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'error' => 'Bu plaka zaten kayıtlı'], 400);
    }

    // Araç ekle
    $stmt = $pdo->prepare("
        INSERT INTO vehicle (cid, plate, make, model, color)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$cid, $plate, $make, $model, $color]);

    $vid = $pdo->lastInsertId();

    sendJSON([
        'success' => true,
        'message' => 'Araç başarıyla eklendi',
        'data' => [
            'vid' => $vid,
            'cid' => $cid,
            'plate' => $plate,
            'make' => $make,
            'model' => $model,
            'color' => $color
        ]
    ], 201);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Araç eklenemedi: ' . $e->getMessage()], 500);
}

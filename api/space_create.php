<?php
/**
 * Space Create API
 * POST: Yeni park yeri oluştur
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$floor_id = intval($input['floor_id'] ?? 0);
$space_code = trim($input['space_code'] ?? '');
$space_type = trim($input['space_type'] ?? 'Normal');
$status = trim($input['status'] ?? 'Bos');

// Validation
if ($floor_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir kat seçiniz'], 400);
}

if (empty($space_code)) {
    sendJSON(['success' => false, 'error' => 'Park yeri kodu gerekli'], 400);
}

try {
    // Duplicate check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM parking_space
        WHERE floor_id = ? AND space_code = ?
    ");
    $stmt->execute([$floor_id, $space_code]);

    if ($stmt->fetch()['count'] > 0) {
        sendJSON(['success' => false, 'error' => 'Bu kod zaten kullanımda'], 400);
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO parking_space (floor_id, space_code, space_type, status)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$floor_id, $space_code, $space_type, $status]);

    $space_id = $pdo->lastInsertId();

    sendJSON([
        'success' => true,
        'message' => 'Park yeri başarıyla oluşturuldu',
        'data' => [
            'space_id' => $space_id,
            'floor_id' => $floor_id,
            'space_code' => $space_code,
            'space_type' => $space_type,
            'status' => $status
        ]
    ], 201);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Park yeri oluşturulamadı: ' . $e->getMessage()], 500);
}

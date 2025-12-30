<?php
/**
 * Araç Yönetimi API
 * GET: Araç listesi (opsiyonel cid parametresi ile filtreleme)
 * POST: Yeni araç ekle
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Araç listesini getir
    $cid = intval($_GET['cid'] ?? 0);

    try {
        if ($cid > 0) {
            // Belirli müşterinin araçları
            $stmt = $pdo->prepare("
                SELECT v.*, c.name as customer_name
                FROM vehicle v
                JOIN customer c ON v.cid = c.cid
                WHERE v.cid = ?
                ORDER BY v.created_at DESC
            ");
            $stmt->execute([$cid]);
        } else {
            // Tüm araçlar
            $stmt = $pdo->query("
                SELECT v.*, c.name as customer_name
                FROM vehicle v
                JOIN customer c ON v.cid = c.cid
                ORDER BY v.created_at DESC
            ");
        }

        $vehicles = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'data' => $vehicles
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Araç listesi alınamadı: ' . $e->getMessage()], 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Yeni araç ekle
    $input = json_decode(file_get_contents('php://input'), true);

    $cid = intval($input['cid'] ?? 0);
    $plate = strtoupper(str_replace(' ', '', trim($input['plate'] ?? '')));
    $make = trim($input['make'] ?? '');
    $model = trim($input['model'] ?? '');
    $color = trim($input['color'] ?? '');

    // Validasyon
    if ($cid <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir müşteri seçiniz'], 400);
    }

    if (empty($plate)) {
        sendJSON(['success' => false, 'error' => 'Plaka zorunludur'], 400);
    }

    try {
        // Plaka unique kontrolü
        $stmt = $pdo->prepare("SELECT vid FROM vehicle WHERE plate = ?");
        $stmt->execute([$plate]);
        if ($stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Bu plaka zaten kayıtlı'], 400);
        }

        // Araç ekle
        $stmt = $pdo->prepare("INSERT INTO vehicle (cid, plate, make, model, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$cid, $plate, $make ?: null, $model ?: null, $color ?: null]);
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

} else {
    sendJSON(['success' => false, 'error' => 'Desteklenmeyen method'], 405);
}

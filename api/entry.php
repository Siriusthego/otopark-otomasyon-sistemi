<?php
/**
 * Araç Girişi API
 * POST: plate, space_id, tid
 */

require_once 'db.php';

// Sadece POST kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

// JSON body oku
$input = json_decode(file_get_contents('php://input'), true);

// Parametreleri al
$plate = trim($input['plate'] ?? '');
$space_id = intval($input['space_id'] ?? 0);
$tid = intval($input['tid'] ?? 0);

// Validasyon
if (empty($plate)) {
    sendJSON(['success' => false, 'error' => 'Plaka boş olamaz'], 400);
}

if ($space_id <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir park yeri seçin'], 400);
}

if ($tid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir tarife seçin'], 400);
}

try {
    // Plakayı normalize et (büyük harf, boşluksuz)
    $plate = strtoupper(str_replace(' ', '', $plate));

    // Araç var mı kontrol et
    $stmt = $pdo->prepare("SELECT vid, cid FROM vehicle WHERE plate = ?");
    $stmt->execute([$plate]);
    $vehicle = $stmt->fetch();

    if ($vehicle) {
        // Araç zaten kayıtlı
        $vid = $vehicle['vid'];
    } else {
        // Yeni araç - Guest müşteri altında kaydet (cid=999)
        // Not: cid=999 "Misafir Müşteri" kaydı database'de olmalı
        $guest_cid = 999;

        // Araç oluştur
        $stmt = $pdo->prepare("INSERT INTO vehicle (cid, plate, make, model, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$guest_cid, $plate, null, null, null]);
        $vid = $pdo->lastInsertId();
    }

    // Entry kaydı oluştur
    // Trigger'lar otomatik kontrol edecek: park yeri boş mu, araç içeride mi
    $stmt = $pdo->prepare("INSERT INTO entry_exit (vid, space_id, tid, entry_time) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$vid, $space_id, $tid]);
    $record_id = $pdo->lastInsertId();

    // Başarılı
    sendJSON([
        'success' => true,
        'message' => 'Araç girişi başarılı',
        'data' => [
            'record_id' => $record_id,
            'plate' => $plate,
            'space_id' => $space_id
        ]
    ], 201);

} catch (PDOException $e) {
    // SQL hatası (trigger hataları da burada yakalanır)
    $error = $e->getMessage();

    // Trigger hatalarını kullanıcı dostu hale getir
    if (strpos($error, 'Park yeri müsait değil') !== false) {
        sendJSON(['success' => false, 'error' => 'Park yeri müsait değil (Dolu veya Bakımda)'], 400);
    } elseif (strpos($error, 'zaten otoparkta') !== false) {
        sendJSON(['success' => false, 'error' => 'Bu araç zaten otoparkta bulunuyor'], 400);
    } else {
        sendJSON(['success' => false, 'error' => 'Giriş işlemi başarısız: ' . $error], 500);
    }
}

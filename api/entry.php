<?php
/**
 * Araç Girişi API (Abonelik Entegre)
 * POST: plate, space_id, tid
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

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
    $plate = strtoupper(str_replace(' ', '', $plate));

    // Araç ve müşteri bilgisi
    $stmt = $pdo->prepare("SELECT vid, cid FROM vehicle WHERE plate = ?");
    $stmt->execute([$plate]);
    $vehicle = $stmt->fetch();

    if ($vehicle) {
        $vid = $vehicle['vid'];
        $cid = $vehicle['cid'];

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
        $subscription_id = $subscription ? $subscription['sub_id'] : null;
        $subscription_type = $subscription ? $subscription['subscription_type'] : null;

    } else {
        // Yeni araç - Guest müşteri
        $guest_cid = 999;
        $stmt = $pdo->prepare("INSERT INTO vehicle (cid, plate) VALUES (?, ?)");
        $stmt->execute([$guest_cid, $plate]);
        $vid = $pdo->lastInsertId();
        $cid = $guest_cid;
        $has_subscription = false;
        $subscription_id = null;
        $subscription_type = null;
    }

    // Park yeri müsait mi kontrol
    $stmt = $pdo->prepare("SELECT status FROM parking_space WHERE space_id = ?");
    $stmt->execute([$space_id]);
    $space = $stmt->fetch();

    if (!$space) {
        sendJSON(['success' => false, 'error' => 'Park yeri bulunamadı'], 404);
    }

    if ($space['status'] !== 'Bos') {
        sendJSON(['success' => false, 'error' => 'Park yeri dolu'], 400);
    }

    // Giriş kaydı oluştur
    $stmt = $pdo->prepare("
        INSERT INTO entry_exit (vid, tid, space_id, entry_time) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$vid, $tid, $space_id]);
    $record_id = $pdo->lastInsertId();

    // Park yerini dolu yap
    $stmt = $pdo->prepare("UPDATE parking_space SET status = 'Dolu' WHERE space_id = ?");
    $stmt->execute([$space_id]);

    sendJSON([
        'success' => true,
        'message' => 'Giriş işlemi başarılı',
        'data' => [
            'record_id' => $record_id,
            'plate' => $plate,
            'cid' => $cid,
            'has_subscription' => $has_subscription,
            'subscription_type' => $subscription_type,
            'subscription_id' => $subscription_id,
            'note' => $has_subscription ? 'Abonelik aktif - Ücret uygulanmayacak' : 'Normal tarife uygulanacak'
        ]
    ], 201);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Giriş işlemi başarısız: ' . $e->getMessage()], 500);
}

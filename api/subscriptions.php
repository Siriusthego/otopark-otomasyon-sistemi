<?php
/**
 * Abonelik Yönetimi API
 * GET: Abonelik listesi
 * POST: Yeni abonelik oluştur
 * PUT: Abonelik güncelle
 */

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Abonelik listesi
    try {
        // Önce süresi dolmuş abonelikleri otomatik pasifleştir
        $pdo->exec("
            UPDATE subscription 
            SET status = 'Pasif' 
            WHERE end_date < CURDATE() 
            AND status != 'Pasif'
        ");

        // Yakında sona erecekleri işaretle (7 gün içinde)
        $pdo->exec("
            UPDATE subscription 
            SET status = 'Yakında' 
            WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND status = 'Aktif'
        ");

        $stmt = $pdo->query("
            SELECT 
                s.sub_id,
                s.cid,
                s.tid,
                s.start_date,
                s.end_date,
                s.status,
                c.name as customer_name,
                t.name as tariff_name,
                t.hourly_rate
            FROM subscription s
            JOIN customer c ON s.cid = c.cid
            JOIN tariff t ON s.tid = t.tid
            ORDER BY 
                CASE s.status 
                    WHEN 'Aktif' THEN 1 
                    WHEN 'Yakında' THEN 2 
                    ELSE 3 
                END,
                s.end_date DESC
        ");

        $subscriptions = $stmt->fetchAll();

        sendJSON(['success' => true, 'data' => $subscriptions]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
    }

} elseif ($method === 'POST') {
    // Yeni abonelik oluştur
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
        $stmt = $pdo->prepare("INSERT INTO subscription (cid, tid, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
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

} elseif ($method === 'PUT') {
    // Abonelik güncelle
    $input = json_decode(file_get_contents('php://input'), true);

    $sub_id = intval($input['sub_id'] ?? 0);
    $start_date = trim($input['start_date'] ?? '');
    $end_date = trim($input['end_date'] ?? '');
    $status = trim($input['status'] ?? '');

    if ($sub_id <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir abonelik ID gerekli'], 400);
    }

    if (empty($start_date) || empty($end_date) || empty($status)) {
        sendJSON(['success' => false, 'error' => 'Tüm alanlar gerekli'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE subscription SET start_date = ?, end_date = ?, status = ? WHERE sub_id = ?");
        $stmt->execute([$start_date, $end_date, $status, $sub_id]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Abonelik bulunamadı'], 404);
        }

        sendJSON([
            'success' => true,
            'message' => 'Abonelik başarıyla güncellendi',
            'data' => [
                'sub_id' => $sub_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status
            ]
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Abonelik güncellenemedi: ' . $e->getMessage()], 500);
    }

} else {
    sendJSON(['success' => false, 'error' => 'Desteklenmeyen method'], 405);
}

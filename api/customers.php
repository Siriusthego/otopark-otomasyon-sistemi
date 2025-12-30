<?php
/**
 * Müşteri Yönetimi API
 * GET: Müşteri listesi
 * POST: Yeni müşteri ekle
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Müşteri listesini getir
    try {
        $stmt = $pdo->query("
            SELECT 
                c.*,
                COUNT(DISTINCT v.vid) as vehicle_count
            FROM customer c
            LEFT JOIN vehicle v ON c.cid = v.cid
            WHERE c.name NOT LIKE 'Otomatik-%'
            GROUP BY c.cid
            ORDER BY 
                CASE WHEN c.cid = 999 THEN 0 ELSE 1 END,
                c.created_at DESC
        ");
        $customers = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'data' => $customers
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Müşteri listesi alınamadı: ' . $e->getMessage()], 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Yeni müşteri ekle
    $input = json_decode(file_get_contents('php://input'), true);

    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');

    // Validasyon
    if (empty($name)) {
        sendJSON(['success' => false, 'error' => 'Müşteri adı zorunludur'], 400);
    }

    try {
        // Email unique kontrolü
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT cid FROM customer WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                sendJSON(['success' => false, 'error' => 'Bu email adresi zaten kayıtlı'], 400);
            }
        }

        // Müşteri ekle
        $stmt = $pdo->prepare("INSERT INTO customer (name, phone, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone ?: null, $email ?: null]);
        $cid = $pdo->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Müşteri başarıyla eklendi',
            'data' => [
                'cid' => $cid,
                'name' => $name,
                'phone' => $phone,
                'email' => $email
            ]
        ], 201);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Müşteri eklenemedi: ' . $e->getMessage()], 500);
    }

} else {
    sendJSON(['success' => false, 'error' => 'Desteklenmeyen method'], 405);
}

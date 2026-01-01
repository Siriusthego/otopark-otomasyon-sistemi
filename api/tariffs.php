<?php
/**
 * Tarife Yönetimi API
 * GET: Tarife listesi
 * POST: Yeni tarife ekle
 * PUT: Tarife güncelle
 * PATCH: Tarife aktif/pasif durumu değiştir
 */

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Tarife listesi
    try {
        $showAll = isset($_GET['all']) && $_GET['all'] == '1';

        $sql = "SELECT tid, name, type, hourly_rate, description, is_active FROM tariff";
        if (!$showAll) {
            $sql .= " WHERE is_active = TRUE";
        }
        $sql .= " ORDER BY 
            CASE type 
                WHEN 'HOURLY' THEN 1 
                WHEN 'SUBSCRIPTION' THEN 2 
                ELSE 3 
            END,
            hourly_rate ASC";

        $stmt = $pdo->query($sql);
        $tariffs = $stmt->fetchAll();

        sendJSON(['success' => true, 'data' => $tariffs]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
    }

} elseif ($method === 'POST') {
    // Yeni tarife ekle
    $input = json_decode(file_get_contents('php://input'), true);

    $name = trim($input['name'] ?? '');
    $type = trim($input['type'] ?? 'HOURLY');
    $hourly_rate = floatval($input['hourly_rate'] ?? 0);
    $description = trim($input['description'] ?? '');

    // Validasyon
    if (empty($name)) {
        sendJSON(['success' => false, 'error' => 'Tarife adı zorunludur'], 400);
    }

    if ($hourly_rate <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir ücret giriniz'], 400);
    }

    if (!in_array($type, ['HOURLY', 'SUBSCRIPTION'])) {
        sendJSON(['success' => false, 'error' => 'Geçersiz tarife tipi'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tariff (name, type, hourly_rate, description, is_active) VALUES (?, ?, ?, ?, TRUE)");
        $stmt->execute([$name, $type, $hourly_rate, $description ?: null]);
        $tid = $pdo->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Tarife başarıyla eklendi',
            'data' => [
                'tid' => $tid,
                'name' => $name,
                'type' => $type,
                'hourly_rate' => $hourly_rate,
                'description' => $description,
                'is_active' => true
            ]
        ], 201);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Tarife eklenemedi: ' . $e->getMessage()], 500);
    }

} elseif ($method === 'PUT') {
    // Tarife güncelle
    $input = json_decode(file_get_contents('php://input'), true);

    $tid = intval($input['tid'] ?? 0);
    $name = trim($input['name'] ?? '');
    $hourly_rate = floatval($input['hourly_rate'] ?? 0);
    $description = trim($input['description'] ?? '');

    if ($tid <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir tarife ID gerekli'], 400);
    }

    if (empty($name)) {
        sendJSON(['success' => false, 'error' => 'Tarife adı zorunludur'], 400);
    }

    if ($hourly_rate <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir ücret giriniz'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE tariff SET name = ?, hourly_rate = ?, description = ? WHERE tid = ?");
        $stmt->execute([$name, $hourly_rate, $description ?: null, $tid]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Tarife bulunamadı'], 404);
        }

        sendJSON([
            'success' => true,
            'message' => 'Tarife başarıyla güncellendi',
            'data' => [
                'tid' => $tid,
                'name' => $name,
                'hourly_rate' => $hourly_rate,
                'description' => $description
            ]
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Tarife güncellenemedi: ' . $e->getMessage()], 500);
    }

} elseif ($method === 'PATCH') {
    // Tarife aktif/pasif durumu değiştir
    $input = json_decode(file_get_contents('php://input'), true);

    $tid = intval($input['tid'] ?? 0);
    $is_active = isset($input['is_active']) ? (bool) $input['is_active'] : null;

    if ($tid <= 0) {
        sendJSON(['success' => false, 'error' => 'Geçerli bir tarife ID gerekli'], 400);
    }

    if ($is_active === null) {
        sendJSON(['success' => false, 'error' => 'is_active parametresi gerekli'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE tariff SET is_active = ? WHERE tid = ?");
        $stmt->execute([$is_active ? 1 : 0, $tid]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Tarife bulunamadı'], 404);
        }

        $statusText = $is_active ? 'aktifleştirildi' : 'pasifleştirildi';

        sendJSON([
            'success' => true,
            'message' => 'Tarife başarıyla ' . $statusText,
            'data' => [
                'tid' => $tid,
                'is_active' => $is_active
            ]
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'error' => 'Durum güncellenemedi: ' . $e->getMessage()], 500);
    }

} else {
    sendJSON(['success' => false, 'error' => 'Desteklenmeyen method'], 405);
}

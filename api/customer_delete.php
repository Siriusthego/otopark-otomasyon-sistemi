<?php
/**
 * Müşteri Silme API (Soft Delete)
 * POST: { "cid": 123 }
 * 
 * İş Kuralları:
 * - Aktif aboneliği varsa SİLİNEMEZ
 * - İçeride aracı varsa SİLİNEMEZ
 * - Geçmiş kaydı varsa SOFT DELETE (is_active=0)
 * - Hiç kaydı yoksa HARD DELETE
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Sadece POST desteklenir'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$cid = intval($input['cid'] ?? 0);

if ($cid <= 0) {
    sendJSON(['success' => false, 'error' => 'Geçerli bir müşteri ID gerekli'], 400);
}

// Özel müşteriler silinemez
if ($cid === 999) {
    sendJSON(['success' => false, 'error' => 'Misafir müşteri silinemez'], 400);
}

try {
    // 1) AKTİF ABONELİK KONTROLÜ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM subscription 
        WHERE cid = ? 
        AND (status = 'Aktif' OR end_date >= CURDATE())
    ");
    $stmt->execute([$cid]);
    $activeSubscriptions = $stmt->fetch()['count'];

    if ($activeSubscriptions > 0) {
        sendJSON([
            'success' => false,
            'error' => 'Müşteri silinemez: Aktif aboneliği var (' . $activeSubscriptions . ' adet)'
        ], 400);
    }

    // 2) İÇERİDE ARAÇ KONTROLÜ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM entry_exit ee
        JOIN vehicle v ON ee.plate = v.plate
        WHERE v.cid = ? AND ee.exit_time IS NULL
    ");
    $stmt->execute([$cid]);
    $insideVehicles = $stmt->fetch()['count'];

    if ($insideVehicles > 0) {
        sendJSON([
            'success' => false,
            'error' => 'Müşteri silinemez: İçeride aracı var (' . $insideVehicles . ' araç)'
        ], 400);
    }

    // 3) GEÇMİŞ İŞLEM KAYDI KONTROLÜ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM entry_exit ee
        JOIN vehicle v ON ee.plate = v.plate
        WHERE v.cid = ?
    ");
    $stmt->execute([$cid]);
    $historyCount = $stmt->fetch()['count'];

    // 4) ABONELİK GEÇMİŞİ KONTROLÜ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM subscription
        WHERE cid = ?
    ");
    $stmt->execute([$cid]);
    $subscriptionHistory = $stmt->fetch()['count'];

    // KARAR: Soft Delete mi Hard Delete mi?
    if ($historyCount > 0 || $subscriptionHistory > 0) {
        // GEÇMİŞ KAYIT VAR -> SOFT DELETE
        $stmt = $pdo->prepare("UPDATE customer SET is_active = 0 WHERE cid = ?");
        $stmt->execute([$cid]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
        }

        sendJSON([
            'success' => true,
            'message' => 'Müşteri pasifleştirildi (geçmiş kayıtlar korundu)',
            'type' => 'soft_delete'
        ]);
    } else {
        // GEÇMİŞ KAYIT YOK -> HARD DELETE
        // Önce araçlarını sil
        $stmt = $pdo->prepare("DELETE FROM vehicle WHERE cid = ?");
        $stmt->execute([$cid]);

        // Sonra müşteriyi sil
        $stmt = $pdo->prepare("DELETE FROM customer WHERE cid = ?");
        $stmt->execute([$cid]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
        }

        sendJSON([
            'success' => true,
            'message' => 'Müşteri tamamen silindi',
            'type' => 'hard_delete'
        ]);
    }

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Silme işlemi başarısız: ' . $e->getMessage()], 500);
}

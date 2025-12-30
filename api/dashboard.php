<?php
/**
 * Dashboard API
 * GET: Dashboard verileri (view'lar kullanılarak)
 */

require_once 'db.php';

// Sadece GET kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

try {
    // 1) Doluluk durumu (vw_occupancy_now)
    $stmt = $pdo->query("SELECT * FROM vw_occupancy_now");
    $occupancy = $stmt->fetch();

    // 2) Bugünkü gelir (vw_revenue_today)
    $stmt = $pdo->query("SELECT * FROM vw_revenue_today");
    $revenue = $stmt->fetch();

    // 3) İçerideki araçlar (vw_inside_cars)
    $stmt = $pdo->query("SELECT * FROM vw_inside_cars LIMIT 50");
    $inside_cars = $stmt->fetchAll();

    // 4) Aktif abonelikler sayısı (vw_active_subscriptions)
    $stmt = $pdo->query("SELECT COUNT(*) as active_subs FROM vw_active_subscriptions");
    $subs = $stmt->fetch();

    // 5) Bugünkü giriş/çıkış sayıları
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_today,
            SUM(CASE WHEN exit_time IS NULL THEN 1 ELSE 0 END) as entries_only,
            SUM(CASE WHEN exit_time IS NOT NULL THEN 1 ELSE 0 END) as exits_done
        FROM entry_exit
        WHERE DATE(entry_time) = CURDATE()
    ");
    $transactions = $stmt->fetch();

    // Başarılı response
    sendJSON([
        'success' => true,
        'data' => [
            'occupancy' => [
                'total_spaces' => intval($occupancy['total_spaces']),
                'occupied' => intval($occupancy['occupied']),
                'available' => intval($occupancy['available']),
                'maintenance' => intval($occupancy['maintenance']),
                'occupancy_rate' => intval($occupancy['occupancy_rate'])
            ],
            'revenue' => [
                'today_revenue' => floatval($revenue['today_revenue']),
                'payment_count' => intval($revenue['payment_count'])
            ],
            'inside_cars' => $inside_cars,
            'subscriptions' => [
                'active_count' => intval($subs['active_subs'])
            ],
            'transactions' => [
                'total_today' => intval($transactions['total_today']),
                'entries' => intval($transactions['entries_only']) + intval($transactions['exits_done']),
                'exits' => intval($transactions['exits_done'])
            ]
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Dashboard sorgu hatası: ' . $e->getMessage()], 500);
}

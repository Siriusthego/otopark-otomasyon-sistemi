<?php
/**
 * Aylık Gelir Raporu API
 * GET: ?year=YYYY&month=MM
 * Günlük gelir serisi döndürür
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

try {
    // Aylık toplam
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_total
        FROM payment
        WHERE YEAR(pay_time) = ? AND MONTH(pay_time) = ?
    ");
    $stmt->execute([$year, $month]);
    $totalData = $stmt->fetch();

    // Günlük seri
    $stmt = $pdo->prepare("
        SELECT 
            DATE(pay_time) as date,
            SUM(amount) as total_amount,
            COUNT(*) as payment_count
        FROM payment
        WHERE YEAR(pay_time) = ? AND MONTH(pay_time) = ?
        GROUP BY DATE(pay_time)
        ORDER BY date
    ");
    $stmt->execute([$year, $month]);
    $series = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'data' => [
            'year' => $year,
            'month' => $month,
            'monthly_total' => floatval($totalData['monthly_total']),
            'series' => $series
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Rapor oluşturulamadı: ' . $e->getMessage()], 500);
}

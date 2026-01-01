<?php
/**
 * Raporlar Özet API
 * GET: ?range=today|week|month
 * Tüm 3 raporun özetini döndürür
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$range = $_GET['range'] ?? 'month';

// Tarih aralığını belirle
$dateCondition = '';
switch ($range) {
    case 'today':
        $dateCondition = "DATE(pay_time) = CURDATE()";
        $entryCondition = "DATE(entry_time) = CURDATE()";
        break;
    case 'week':
        $dateCondition = "pay_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $entryCondition = "entry_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
    default:
        $dateCondition = "YEAR(pay_time) = YEAR(CURDATE()) AND MONTH(pay_time) = MONTH(CURDATE())";
        $entryCondition = "YEAR(entry_time) = YEAR(CURDATE()) AND MONTH(entry_time) = MONTH(CURDATE())";
        break;
}

try {
    // 1) Anlık Doluluk (occupancy)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Bos' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'Dolu' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'Bakim' THEN 1 ELSE 0 END) as maintenance
        FROM parking_space
    ");
    $occData = $stmt->fetch();

    $total = intval($occData['total']);
    $available = intval($occData['available']);
    $occupied = intval($occData['occupied']);
    $maintenance = intval($occData['maintenance']);

    $occupancy = [
        'total' => $total,
        'available' => $available,
        'occupied' => $occupied,
        'maintenance' => $maintenance,
        'available_rate' => $total > 0 ? round(($available / $total) * 100, 1) : 0,
        'occupied_rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
        'maintenance_rate' => $total > 0 ? round(($maintenance / $total) * 100, 1) : 0
    ];

    // 2) Aylık Gelir (monthly_revenue)
    $stmt = $pdo->query("
        SELECT 
            YEAR(CURDATE()) as year,
            MONTH(CURDATE()) as month,
            COALESCE(SUM(amount), 0) as monthly_total
        FROM payment
        WHERE $dateCondition
    ");
    $revData = $stmt->fetch();

    $monthly_revenue = [
        'year' => intval($revData['year']),
        'month' => intval($revData['month']),
        'monthly_total' => floatval($revData['monthly_total'])
    ];

    // 3) Kullanım Özeti (usage)
    $stmt = $pdo->query("
        SELECT 
            AVG(duration_min) as avg_duration_min,
            AVG(fee) as avg_fee,
            COUNT(*) as visit_count
        FROM entry_exit
        WHERE exit_time IS NOT NULL AND $entryCondition
    ");
    $usageData = $stmt->fetch();

    $usage = [
        'avg_duration_min' => $usageData['avg_duration_min'] ? round(floatval($usageData['avg_duration_min']), 0) : 0,
        'avg_fee' => $usageData['avg_fee'] ? round(floatval($usageData['avg_fee']), 2) : 0,
        'visit_count' => intval($usageData['visit_count'])
    ];

    sendJSON([
        'success' => true,
        'data' => [
            'occupancy' => $occupancy,
            'monthly_revenue' => $monthly_revenue,
            'usage' => $usage
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Rapor oluşturulamadı: ' . $e->getMessage()], 500);
}

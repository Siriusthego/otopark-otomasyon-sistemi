<?php
/**
 * Kullanım Özeti Raporu API
 * GET: ?range=today|week|month
 * Ortalama süre, ücret ve ziyaret sayısı
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

$range = $_GET['range'] ?? 'month';

// Tarih aralığını belirle
switch ($range) {
    case 'today':
        $condition = "DATE(entry_time) = CURDATE()";
        break;
    case 'week':
        $condition = "entry_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
    default:
        $condition = "YEAR(entry_time) = YEAR(CURDATE()) AND MONTH(entry_time) = MONTH(CURDATE())";
        break;
}

try {
    $stmt = $pdo->query("
        SELECT 
            AVG(duration_min) as avg_duration_min,
            AVG(fee) as avg_fee,
            COUNT(*) as visit_count
        FROM entry_exit
        WHERE exit_time IS NOT NULL AND $condition
    ");
    $data = $stmt->fetch();

    sendJSON([
        'success' => true,
        'data' => [
            'avg_duration_min' => $data['avg_duration_min'] ? round(floatval($data['avg_duration_min']), 0) : 0,
            'avg_fee' => $data['avg_fee'] ? round(floatval($data['avg_fee']), 2) : 0,
            'visit_count' => intval($data['visit_count'])
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Rapor oluşturulamadı: ' . $e->getMessage()], 500);
}

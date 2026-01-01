<?php
/**
 * Birleşik CSV Export - TÜM RAPORLAR
 * GET: ?range=today|week|month
 * Anlık Doluluk + Aylık Gelir + Kullanım Özeti
 */

require_once 'db.php';

$range = $_GET['range'] ?? 'month';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="parking_reports_ALL_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

try {
    // ===== 1) ANLIK DOLULUK =====
    fputcsv($output, ['--- Anlik Doluluk ---']);
    fputcsv($output, ['Metrik', 'Deger']);

    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Bos' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'Dolu' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'Bakim' THEN 1 ELSE 0 END) as maintenance
        FROM parking_space
    ");
    $occ = $stmt->fetch();

    $total = intval($occ['total']);
    fputcsv($output, ['Toplam Alan', $total]);
    fputcsv($output, ['Bos', $occ['available']]);
    fputcsv($output, ['Dolu', $occ['occupied']]);
    fputcsv($output, ['Bakim', $occ['maintenance']]);
    fputcsv($output, ['Doluluk Orani (%)', $total > 0 ? round(($occ['occupied'] / $total) * 100, 1) : 0]);

    fputcsv($output, []); // Boş satır

    // ===== 2) AYLIK GELIR =====
    fputcsv($output, ['--- Aylik Gelir ---']);
    fputcsv($output, ['Tarih', 'Toplam Gelir', 'Odeme Sayisi']);

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

    $monthTotal = 0;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['date'],
            number_format($row['total_amount'], 2),
            $row['payment_count']
        ]);
        $monthTotal += $row['total_amount'];
    }

    fputcsv($output, ['TOPLAM', number_format($monthTotal, 2), '']);

    fputcsv($output, []); // Boş satır

    // ===== 3) KULLANIM OZETI =====
    fputcsv($output, ['--- Kullanim Ozeti ---']);
    fputcsv($output, ['Metrik', 'Deger']);

    // Range condition
    $condition = '';
    switch ($range) {
        case 'today':
            $condition = "DATE(entry_time) = CURDATE()";
            break;
        case 'week':
            $condition = "entry_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        default:
            $condition = "YEAR(entry_time) = YEAR(CURDATE()) AND MONTH(entry_time) = MONTH(CURDATE())";
            break;
    }

    $stmt = $pdo->query("
        SELECT 
            AVG(duration_min) as avg_duration_min,
            AVG(fee) as avg_fee,
            COUNT(*) as visit_count
        FROM entry_exit
        WHERE exit_time IS NOT NULL AND $condition
    ");
    $usage = $stmt->fetch();

    fputcsv($output, ['Ortalama Sure (dk)', round($usage['avg_duration_min'] ?? 0, 0)]);
    fputcsv($output, ['Ortalama Ucret (TL)', number_format($usage['avg_fee'] ?? 0, 2)]);
    fputcsv($output, ['Toplam Ziyaret', $usage['visit_count'] ?? 0]);

    fclose($output);

} catch (PDOException $e) {
    fclose($output);
    die('CSV olusturulamadi: ' . $e->getMessage());
}

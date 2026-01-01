<?php
/**
 * CSV Export API
 * GET: ?type=occupancy|revenue|usage&range=...&year=...&month=...
 * CSV dosyası oluşturur ve indirir
 */

require_once 'db.php';

$type = $_GET['type'] ?? 'occupancy';
$range = $_GET['range'] ?? 'month';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

try {
    if ($type === 'occupancy') {
        // Doluluk raporu
        fputcsv($output, ['Durum', 'Adet', 'Yüzde']);

        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Bos' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'Dolu' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'Bakim' THEN 1 ELSE 0 END) as maintenance
            FROM parking_space
        ");
        $data = $stmt->fetch();

        $total = intval($data['total']);
        fputcsv($output, ['Boş', $data['available'], round(($data['available'] / $total) * 100, 1) . '%']);
        fputcsv($output, ['Dolu', $data['occupied'], round(($data['occupied'] / $total) * 100, 1) . '%']);
        fputcsv($output, ['Bakım', $data['maintenance'], round(($data['maintenance'] / $total) * 100, 1) . '%']);
        fputcsv($output, ['Toplam', $total, '100%']);

    } elseif ($type === 'revenue') {
        // Gelir raporu
        fputcsv($output, ['Tarih', 'Toplam Gelir', 'Ödeme Sayısı']);

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

        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['date'],
                number_format($row['total_amount'], 2),
                $row['payment_count']
            ]);
        }

    } elseif ($type === 'usage') {
        // Kullanım raporu
        fputcsv($output, ['Metrik', 'Değer']);

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
        $data = $stmt->fetch();

        fputcsv($output, ['Ortalama Süre (dk)', round($data['avg_duration_min'], 0)]);
        fputcsv($output, ['Ortalama Ücret (₺)', number_format($data['avg_fee'], 2)]);
        fputcsv($output, ['Toplam Ziyaret', $data['visit_count']]);
    }

    fclose($output);

} catch (PDOException $e) {
    fclose($output);
    die('CSV oluşturulamadı: ' . $e->getMessage());
}

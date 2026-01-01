<?php
/**
 * PDF Export API (Basit HTML-based)
 * GET: ?type=occupancy|revenue|usage&range=...&year=...&month=...
 * Print-friendly HTML sayfası döndürür
 */

require_once 'db.php';

$type = $_GET['type'] ?? 'occupancy';
$range = $_GET['range'] ?? 'month';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

$reportTitle = '';
$reportContent = '';

try {
    if ($type === 'occupancy') {
        $reportTitle = 'Doluluk Raporu';

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
        $reportContent = '
            <table style="width:100%; border-collapse:collapse;">
                <tr style="background:#f5f5f5;">
                    <th style="border:1px solid #ddd; padding:10px;">Durum</th>
                    <th style="border:1px solid #ddd; padding:10px;">Adet</th>
                    <th style="border:1px solid #ddd; padding:10px;">Yüzde</th>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Boş</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . $data['available'] . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . round(($data['available'] / $total) * 100, 1) . '%</td>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Dolu</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . $data['occupied'] . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . round(($data['occupied'] / $total) * 100, 1) . '%</td>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Bakım</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . $data['maintenance'] . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . round(($data['maintenance'] / $total) * 100, 1) . '%</td>
                </tr>
                <tr style="font-weight:bold; background:#f5f5f5;">
                    <td style="border:1px solid #ddd; padding:10px;">Toplam</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . $total . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">100%</td>
                </tr>
            </table>
        ';

    } elseif ($type === 'revenue') {
        $reportTitle = 'Aylık Gelir Raporu - ' . $year . '/' . $month;

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

        $reportContent = '
            <table style="width:100%; border-collapse:collapse;">
                <tr style="background:#f5f5f5;">
                    <th style="border:1px solid #ddd; padding:10px;">Tarih</th>
                    <th style="border:1px solid #ddd; padding:10px;">Toplam Gelir</th>
                    <th style="border:1px solid #ddd; padding:10px;">Ödeme Sayısı</th>
                </tr>';

        while ($row = $stmt->fetch()) {
            $reportContent .= '
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">' . $row['date'] . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">₺' . number_format($row['total_amount'], 2) . '</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . $row['payment_count'] . '</td>
                </tr>';
        }

        $reportContent .= '</table>';

    } elseif ($type === 'usage') {
        $reportTitle = 'Kullanım Özeti Raporu';

        $condition = '';
        switch ($range) {
            case 'today':
                $condition = "DATE(entry_time) = CURDATE()";
                $reportTitle .= ' - Bugün';
                break;
            case 'week':
                $condition = "entry_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                $reportTitle .= ' - Son 7 Gün';
                break;
            default:
                $condition = "YEAR(entry_time) = YEAR(CURDATE()) AND MONTH(entry_time) = MONTH(CURDATE())";
                $reportTitle .= ' - Bu Ay';
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

        $reportContent = '
            <table style="width:100%; border-collapse:collapse;">
                <tr style="background:#f5f5f5;">
                    <th style="border:1px solid #ddd; padding:10px;">Metrik</th>
                    <th style="border:1px solid #ddd; padding:10px;">Değer</th>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Ortalama Süre</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . round($data['avg_duration_min'], 0) . ' dakika</td>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Ortalama Ücret</td>
                    <td style="border:1px solid #ddd; padding:10px;">₺' . number_format($data['avg_fee'], 2) . '</td>
                </tr>
                <tr>
                    <td style="border:1px solid #ddd; padding:10px;">Toplam Ziyaret</td>
                    <td style="border:1px solid #ddd; padding:10px;">' . number_format($data['visit_count']) . '</td>
                </tr>
            </table>
        ';
    }

} catch (PDOException $e) {
    die('Rapor oluşturulamadı: ' . $e->getMessage());
}

// Print-friendly HTML
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?= $reportTitle ?></title>
    <style>
        @media print {
            button {
                display: none;
            }
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .btn {
            padding: 10px 20px;
            margin: 10px 0;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>
    <h1><?= $reportTitle ?></h1>
    <p>Oluşturulma Tarihi: <?= date('d.m.Y H:i') ?></p>
    <hr>
    <?= $reportContent ?>
    <br>
    <button class="btn" onclick="window.print()">PDF Olarak Kaydet</button>
    <button class="btn" onclick="window.close()" style="background:#6b7280;">Kapat</button>
</body>

</html>
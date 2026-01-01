<?php
/**
 * Birleşik PDF Export (Print-Friendly HTML) - TÜM RAPORLAR
 * GET: ?range=today|week|month
 * Anlık Doluluk + Aylık Gelir + Kullanım Özeti
 */

require_once 'db.php';

$range = $_GET['range'] ?? 'month';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

$rangeText = '';
switch ($range) {
    case 'today':
        $rangeText = 'Bugün';
        break;
    case 'week':
        $rangeText = 'Bu Hafta';
        break;
    default:
        $rangeText = 'Bu Ay';
        break;
}

try {
    // ===== 1) ANLIK DOLULUK =====
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

    // ===== 2) AYLIK GELIR =====
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
    $revenueSeries = $stmt->fetchAll();

    $monthTotal = 0;
    foreach ($revenueSeries as $row) {
        $monthTotal += $row['total_amount'];
    }

    // ===== 3) KULLANIM OZETI =====
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

} catch (PDOException $e) {
    die('Rapor oluşturulamadı: ' . $e->getMessage());
}

// ===== HTML ÇIKTISI =====
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Otopark Raporları - <?= $rangeText ?></title>
    <style>
        @media print {
            button {
                display: none;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
        }

        h1 {
            color: #1f2937;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
        }

        h2 {
            color: #374151;
            margin-top: 30px;
            background: #f3f4f6;
            padding: 10px;
            border-left: 4px solid #3b82f6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .btn {
            padding: 10px 20px;
            margin: 10px 5px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
        }

        .summary {
            background: #eff6ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .total-row {
            font-weight: bold;
            background: #e5e7eb !important;
        }
    </style>
</head>

<body>
    <h1>Otopark Otomasyon Sistemi – Raporlar</h1>
    <div class="summary">
        <strong>Oluşturulma Tarihi:</strong> <?= date('d.m.Y H:i') ?><br>
        <strong>Kapsam:</strong> <?= $rangeText ?>
    </div>

    <div class="no-print">
        <button class="btn" onclick="window.print()">PDF Olarak Kaydet</button>
        <button class="btn btn-secondary" onclick="window.close()">Kapat</button>
    </div>

    <!-- ANLIK DOLULUK -->
    <h2>1. Anlık Doluluk Raporu</h2>
    <table>
        <tr>
            <th>Metrik</th>
            <th>Değer</th>
            <th>Yüzde</th>
        </tr>
        <tr>
            <td>Toplam Park Alanı</td>
            <td><?= $total ?></td>
            <td>100%</td>
        </tr>
        <tr>
            <td>Boş</td>
            <td><?= $occ['available'] ?></td>
            <td><?= $total > 0 ? round(($occ['available'] / $total) * 100, 1) : 0 ?>%</td>
        </tr>
        <tr>
            <td>Dolu</td>
            <td><?= $occ['occupied'] ?></td>
            <td><?= $total > 0 ? round(($occ['occupied'] / $total) * 100, 1) : 0 ?>%</td>
        </tr>
        <tr>
            <td>Bakım</td>
            <td><?= $occ['maintenance'] ?></td>
            <td><?= $total > 0 ? round(($occ['maintenance'] / $total) * 100, 1) : 0 ?>%</td>
        </tr>
    </table>

    <!-- AYLIK GELIR -->
    <h2>2. Aylık Gelir Raporu (<?= $year ?>/<?= str_pad($month, 2, '0', STR_PAD_LEFT) ?>)</h2>
    <table>
        <tr>
            <th>Tarih</th>
            <th>Toplam Gelir</th>
            <th>Ödeme Sayısı</th>
        </tr>
        <?php foreach ($revenueSeries as $row): ?>
            <tr>
                <td><?= $row['date'] ?></td>
                <td>₺<?= number_format($row['total_amount'], 2) ?></td>
                <td><?= $row['payment_count'] ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td><strong>AY TOPLAMI</strong></td>
            <td><strong>₺<?= number_format($monthTotal, 2) ?></strong></td>
            <td>-</td>
        </tr>
    </table>

    <!-- KULLANIM OZETI -->
    <h2>3. Kullanım Özeti Raporu</h2>
    <table>
        <tr>
            <th>Metrik</th>
            <th>Değer</th>
        </tr>
        <tr>
            <td>Ortalama Kalış Süresi</td>
            <td><?= round($usage['avg_duration_min'] ?? 0, 0) ?> dakika</td>
        </tr>
        <tr>
            <td>Ortalama Ücret</td>
            <td>₺<?= number_format($usage['avg_fee'] ?? 0, 2) ?></td>
        </tr>
        <tr>
            <td>Toplam Ziyaret Sayısı</td>
            <td><?= number_format($usage['visit_count'] ?? 0) ?></td>
        </tr>
    </table>

    <div class="no-print" style="margin-top:40px;">
        <button class="btn" onclick="window.print()">PDF Olarak Kaydet</button>
        <button class="btn btn-secondary" onclick="window.close()">Kapat</button>
    </div>
</body>

</html>
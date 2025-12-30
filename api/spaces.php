<?php
/**
 * Park Yerleri Listesi API
 * GET: ?status=Bos (opsiyonel filtre)
 */

require_once 'db.php';

// Sadece GET kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

// Filtre parametresi
$status_filter = $_GET['status'] ?? null;

try {
    // SQL sorgusu oluştur
    $sql = "
        SELECT 
            ps.space_id,
            ps.space_code,
            ps.space_type,
            ps.status,
            f.name as floor_name,
            f.level_no,
            pl.name as lot_name,
            pl.code as lot_code
        FROM parking_space ps
        JOIN floor f ON ps.floor_id = f.floor_id
        JOIN parking_lot pl ON f.lot_code = pl.code
    ";

    // Status filtresi varsa ekle
    if ($status_filter && in_array($status_filter, ['Bos', 'Dolu', 'Bakim'])) {
        $sql .= " WHERE ps.status = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY pl.code, f.level_no, ps.space_code");
        $stmt->execute([$status_filter]);
    } else {
        $sql .= " ORDER BY pl.code, f.level_no, ps.space_code";
        $stmt = $pdo->query($sql);
    }

    $spaces = $stmt->fetchAll();

    // İstatistikler
    $stats = [
        'total' => count($spaces),
        'bos' => 0,
        'dolu' => 0,
        'bakim' => 0
    ];

    foreach ($spaces as $space) {
        if ($space['status'] === 'Bos')
            $stats['bos']++;
        elseif ($space['status'] === 'Dolu')
            $stats['dolu']++;
        elseif ($space['status'] === 'Bakim')
            $stats['bakim']++;
    }

    // Başarılı
    sendJSON([
        'success' => true,
        'data' => [
            'spaces' => $spaces,
            'stats' => $stats
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Sorgu hatası: ' . $e->getMessage()], 500);
}

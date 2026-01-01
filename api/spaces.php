<?php
/**
 * Park Yerleri API
 * GET: Park yerlerini listele (filtreleme destekli)
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

// Filter parameters
$status = trim($_GET['status'] ?? '');
$lot_code = trim($_GET['lot_code'] ?? '');
$floor_id = intval($_GET['floor_id'] ?? 0);

try {
    // Build query with filters
    $sql = "
        SELECT 
            ps.space_id,
            ps.space_code,
            ps.status,
            ps.space_type,
            ps.floor_id,
            f.name as floor_name,
            f.lot_code,
            pl.name as lot_name,
            pl.code as lot_code_full
        FROM parking_space ps
        JOIN floor f ON ps.floor_id = f.floor_id
        JOIN parking_lot pl ON f.lot_code = pl.code
        WHERE 1=1
    ";

    $params = [];

    if (!empty($status)) {
        $sql .= " AND ps.status = ?";
        $params[] = $status;
    }

    if (!empty($lot_code)) {
        $sql .= " AND f.lot_code = ?";
        $params[] = $lot_code;
    }

    if ($floor_id > 0) {
        $sql .= " AND ps.floor_id = ?";
        $params[] = $floor_id;
    }

    $sql .= " ORDER BY pl.code, f.level_no, ps.space_code";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $spaces = $stmt->fetchAll();

    // Stats
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

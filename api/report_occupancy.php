<?php
/**
 * Doluluk Raporu API
 * GET: Anlık park yeri doluluk durumu
 */

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'error' => 'Sadece GET desteklenir'], 405);
}

try {
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
    $available = intval($data['available']);
    $occupied = intval($data['occupied']);
    $maintenance = intval($data['maintenance']);

    sendJSON([
        'success' => true,
        'data' => [
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'maintenance' => $maintenance,
            'available_rate' => $total > 0 ? round(($available / $total) * 100, 1) : 0,
            'occupied_rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
            'maintenance_rate' => $total > 0 ? round(($maintenance / $total) * 100, 1) : 0
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'error' => 'Rapor oluşturulamadı: ' . $e->getMessage()], 500);
}

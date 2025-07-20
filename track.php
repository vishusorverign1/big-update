<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $trackingId = $_GET['id'] ?? '';
    
    if (empty($trackingId)) {
        echo json_encode(['success' => false, 'message' => 'Tracking ID is required']);
        exit;
    }
    
    try {
        // Get courier details
        $stmt = $pdo->prepare("SELECT * FROM couriers WHERE courier_id = ?");
        $stmt->execute([$trackingId]);
        $courier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$courier) {
            echo json_encode(['success' => false, 'message' => 'Courier not found']);
            exit;
        }
        
        // Get tracking history
        $stmt = $pdo->prepare("SELECT * FROM tracking WHERE courier_id = ? ORDER BY timestamp DESC");
        $stmt->execute([$trackingId]);
        $tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'data' => [
                'courier_id' => $courier['courier_id'],
                'party_name' => $courier['party_name'],
                'from_city' => $courier['from_city'],
                'to_city' => $courier['to_city'],
                'status' => $courier['status'],
                'delivery_date' => $courier['delivery_date'] ? date('M d, Y', strtotime($courier['delivery_date'])) : 'TBD',
                'tracking_history' => array_map(function($item) {
                    return [
                        'location' => $item['location'],
                        'status' => $item['status'],
                        'timestamp' => date('M d, Y H:i', strtotime($item['timestamp']))
                    ];
                }, $tracking)
            ]
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to track package']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
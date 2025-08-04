<?php
// api.php - Main API Endpoint

session_start();

require_once 'db_connect.php';
require_once 'functions.php';

// Assign a stable client ID if one doesn't exist
if (empty($_SESSION['clientId'])) {
    $_SESSION['clientId'] = 'user_' . bin2hex(random_bytes(8));
}
$clientId = $_SESSION['clientId'];

$action = $_GET['action'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

// --- Handle POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'find_chat':
            if (!isset($data['offer'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing data']);
                exit;
            }
            $offerSdp = json_encode($data['offer']);

            // Clean up any previous sessions for this user before starting a new search
            $conn->query("DELETE FROM waiting_users WHERE client_id = '$clientId'");
            $stmt = $conn->prepare("SELECT peer1_id, peer2_id FROM active_calls WHERE peer1_id = ? OR peer2_id = ?");
            $stmt->bind_param("ss", $clientId, $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $call = $result->fetch_assoc();
                $peerId = ($call['peer1_id'] === $clientId) ? $call['peer2_id'] : $call['peer1_id'];
                writeSignal($conn, $peerId, ['type' => 'leave']);
                $conn->query("DELETE FROM active_calls WHERE (peer1_id = '{$call['peer1_id']}' AND peer2_id = '{$call['peer2_id']}')");
            }
            $stmt->close();


            $result = $conn->query("SELECT * FROM waiting_users WHERE client_id != '$clientId' ORDER BY timestamp ASC LIMIT 1");
            if ($result->num_rows > 0) {
                $waitingUser = $result->fetch_assoc();
                $peerId = $waitingUser['client_id'];
                $conn->query("DELETE FROM waiting_users WHERE id = " . $waitingUser['id']);
                
                $stmt = $conn->prepare("INSERT INTO active_calls (peer1_id, peer2_id) VALUES (?, ?)");
                $stmt->bind_param("ss", $peerId, $clientId);
                $stmt->execute();
                $stmt->close();

                writeSignal($conn, $peerId, ['type' => 'paired', 'peerId' => $clientId]);
                echo json_encode(['status' => 'paired', 'peerId' => $peerId, 'offer' => json_decode($waitingUser['offer_sdp'])]);
            } else {
                $stmt = $conn->prepare("INSERT INTO waiting_users (client_id, offer_sdp) VALUES (?, ?) ON DUPLICATE KEY UPDATE offer_sdp = VALUES(offer_sdp), timestamp = NOW()");
                $stmt->bind_param("ss", $clientId, $offerSdp);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'waiting']);
            }
            break;

        case 'send_signal':
            $peerId = $data['peerId'];
            $signal = $data['signal'];
            if ($signal['type'] === 'answer') {
                $signal['peerId'] = $clientId;
            }
            writeSignal($conn, $peerId, $signal);
            echo json_encode(['status' => 'signal_sent']);
            break;

        case 'leave':
            $conn->query("DELETE FROM waiting_users WHERE client_id = '$clientId'");
            $conn->query("DELETE FROM online_users WHERE client_id = '$clientId'");

            $stmt = $conn->prepare("SELECT peer1_id, peer2_id FROM active_calls WHERE peer1_id = ? OR peer2_id = ?");
            $stmt->bind_param("ss", $clientId, $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $call = $result->fetch_assoc();
                $peerId = ($call['peer1_id'] === $clientId) ? $call['peer2_id'] : $call['peer1_id'];
                writeSignal($conn, $peerId, ['type' => 'leave']);
                $conn->query("DELETE FROM active_calls WHERE (peer1_id = '{$call['peer1_id']}' AND peer2_id = '{$call['peer2_id']}')");
            }
            $stmt->close();

            echo json_encode(['status' => 'left_and_notified']);
            break;

        case 'heartbeat':
            $stmt = $conn->prepare("INSERT INTO online_users (client_id) VALUES (?) ON DUPLICATE KEY UPDATE last_seen = NOW()");
            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $stmt->close();
            
            $staleUsersResult = $conn->query("SELECT client_id FROM online_users WHERE last_seen < NOW() - INTERVAL 30 SECOND");
            while ($staleUser = $staleUsersResult->fetch_assoc()) {
                $staleClientId = $staleUser['client_id'];
                $conn->query("DELETE FROM waiting_users WHERE client_id = '$staleClientId'");
                
                $stmt = $conn->prepare("SELECT peer1_id, peer2_id FROM active_calls WHERE peer1_id = ? OR peer2_id = ?");
                $stmt->bind_param("ss", $staleClientId, $staleClientId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $call = $result->fetch_assoc();
                    $peerId = ($call['peer1_id'] === $staleClientId) ? $call['peer2_id'] : $call['peer1_id'];
                    writeSignal($conn, $peerId, ['type' => 'leave']);
                    $conn->query("DELETE FROM active_calls WHERE (peer1_id = '{$call['peer1_id']}' AND peer2_id = '{$call['peer2_id']}')");
                }
                $stmt->close();
            }
            $conn->query("DELETE FROM online_users WHERE last_seen < NOW() - INTERVAL 30 SECOND");

            echo json_encode(['status' => 'heartbeat_ok']);
            break;
    }
}

// --- Handle GET Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'poll':
            $stmt = $conn->prepare("SELECT id, signal_data FROM signals WHERE recipient_id = ? ORDER BY timestamp ASC");
            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $signals = [];
            $signalIdsToDelete = [];
            while ($row = $result->fetch_assoc()) {
                $signals[] = json_decode($row['signal_data'], true);
                $signalIdsToDelete[] = $row['id'];
            }
            $stmt->close();

            if (!empty($signalIdsToDelete)) {
                $idList = implode(',', $signalIdsToDelete);
                $conn->query("DELETE FROM signals WHERE id IN ($idList)");
                echo json_encode(['status' => 'signals', 'signals' => $signals]);
            } else {
                echo json_encode(['status' => 'no_change']);
            }
            break;

        case 'get_online_count':
            $result = $conn->query("SELECT COUNT(*) as count FROM online_users");
            $row = $result->fetch_assoc();
            echo json_encode(['online' => $row['count']]);
            break;
    }
}

$conn->close();
?>

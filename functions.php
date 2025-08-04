<?php
// functions.php

// Function to write a signal to the database
function writeSignal($conn, $peerId, $signalData) {
    $signalJson = json_encode($signalData);
    $stmt = $conn->prepare("INSERT INTO signals (recipient_id, signal_data) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $peerId, $signalJson);
        $stmt->execute();
        $stmt->close();
    }
}

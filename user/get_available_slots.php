<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode([]));
}

if (isset($_GET['therapist_id']) && isset($_GET['appointment_date'])) {
    $therapist_id = $_GET['therapist_id'];
    $appointment_date = $_GET['appointment_date'];
    
    // Get all booked slots for this therapist and date (exclude cancelled)
    $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE therapist_id=? AND appointment_date=? AND status != 'cancelled'");
    $stmt->bind_param("is", $therapist_id, $appointment_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($booked_slots);
    $stmt->close();
} else {
    echo json_encode([]);
}
$conn->close();
?>
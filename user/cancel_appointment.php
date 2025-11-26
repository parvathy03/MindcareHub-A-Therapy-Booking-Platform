<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the appointment belongs to the user
    $check = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $appointment_id, $user_id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        // Update appointment status to cancelled
        $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Appointment cancelled successfully.'); window.location.href='appointments.php';</script>";
        } else {
            echo "<script>alert('Error cancelling appointment.'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid appointment.'); window.history.back();</script>";
    }
    $check->close();
} else {
    header("Location: appointments.php");
}
$conn->close();
?>
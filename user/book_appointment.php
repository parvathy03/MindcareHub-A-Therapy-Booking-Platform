<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $therapist_id = $_POST['therapist_id'];
    $user_id = $_SESSION['user_id'];
    $therapy_type = $_POST['therapy_type'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $session_type = $_POST['session_type'];

    // Validate inputs
    if (empty($therapy_type) || empty($appointment_date) || empty($appointment_time) || empty($therapist_id)) {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit;
    }

    // Check if slot already booked for this therapist (exclude cancelled appointments)
    $check = $conn->prepare("SELECT id FROM appointments WHERE therapist_id=? AND appointment_date=? AND appointment_time=? AND status != 'cancelled'");
    $check->bind_param("iss", $therapist_id, $appointment_date, $appointment_time);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('This time slot is already booked. Please select another time.'); window.history.back();</script>";
        exit;
    }

    // Insert new appointment
    $stmt = $conn->prepare("INSERT INTO appointments (user_id, therapist_id, therapy_type, appointment_date, appointment_time, session_type) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iissss", $user_id, $therapist_id, $therapy_type, $appointment_date, $appointment_time, $session_type);

    if ($stmt->execute()) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='userpage.php';</script>";
    } else {
        echo "<script>alert('Error booking appointment: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }
    
    $stmt->close();
    $check->close();
}
$conn->close();
?>
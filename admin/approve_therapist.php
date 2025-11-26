<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['therapist_id'])) {
    $therapist_id = intval($_POST['therapist_id']);
    
    // Update therapist status to approved
    $stmt = $conn->prepare("UPDATE therapists SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $therapist_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Therapist approved successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to approve therapist.";
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "No therapist ID provided.";
}

// Redirect back to admin dashboard
header("Location: admin_dashboard.php");
exit();
?>
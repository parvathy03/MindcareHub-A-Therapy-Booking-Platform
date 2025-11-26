<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Function to update therapist statistics
function updateTherapistStats($conn, $therapist_id) {
    // Count only non-cancelled appointments for this therapist
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE therapist_id = ? AND status != 'cancelled'");
    $stmt->bind_param("i", $therapist_id);
    $stmt->execute();
    $stmt->bind_result($appointment_count);
    $stmt->fetch();
    $stmt->close();
    
    // Update therapist's appointment count (if you have this field)
    // This assumes you have an appointment_count field in the therapists table
    $update_stmt = $conn->prepare("UPDATE therapists SET appointment_count = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $appointment_count, $therapist_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['appointment_id'];
    $status = $_POST['status'];
    $previous_status = '';
    $therapist_id = null;
    
    // Get the current status before updating
    $stmt = $conn->prepare("SELECT status, therapist_id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($previous_status, $therapist_id);
    $stmt->fetch();
    $stmt->close();
    
    // Update the status
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    
    // If status changed to/from cancelled and there's a therapist assigned, update therapist stats
    if ($therapist_id && (($previous_status != 'cancelled' && $status == 'cancelled') || 
        ($previous_status == 'cancelled' && $status != 'cancelled'))) {
        updateTherapistStats($conn, $therapist_id);
    }
    
    // Use JavaScript redirect to prevent back button issues
    echo '<script>window.location.replace("'.$_SERVER['PHP_SELF'].'?updated=1");</script>';
    exit();
}

$sql = "SELECT a.*, u.name AS patient_name, t.name AS therapist_name 
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN therapists t ON a.therapist_id = t.id
        ORDER BY a.appointment_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare Hub - All Appointments</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --accent: #7ED321;
            --dark: #2C3E50;
            --light: #F8F9FA;
            --text: #333;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            background: var(--light);
            color: var(--text);
        }
        
        header {
            background: linear-gradient(135deg, var(--dark) 0%, #3a5068 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--dark);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .appointments-table th,
        .appointments-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .appointments-table th {
            background: var(--dark);
            color: white;
            font-weight: 600;
        }
        
        .appointments-table tr:hover {
            background: rgba(74, 144, 226, 0.03);
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success);
        }
        
        .no-appointments {
            background: white;
            padding: 2rem;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255,193,7,0.1);
            color: var(--warning);
        }
        
        .status-approved {
            background: rgba(40,167,69,0.1);
            color: var(--success);
        }
        
        .status-completed {
            background: rgba(74,144,226,0.1);
            color: var(--primary);
        }
        
        .status-cancelled {
            background: rgba(220,53,69,0.1);
            color: var(--danger);
        }
        
        @media (max-width: 992px) {
            .appointments-table {
                display: block;
                overflow-x: auto;
            }
            
            .container {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">MindCare Hub</div>
        <div><a href="admin_dashboard.php" style="color: white;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
    </header>

    <div class="container">
        <h1>All Appointments</h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Status updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Therapist</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                        $current_status = strtolower($row['status'] ?? 'approved');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['therapist_name'] ?? 'Pending') ?></td>
                            <td><?= date('F j, Y', strtotime($row['appointment_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                            <td><?= htmlspecialchars($row['therapy_type']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $current_status ?>">
                                    <?= ucfirst(htmlspecialchars($current_status)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <p>No appointments found.</p>
                <a href="admin_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Return to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
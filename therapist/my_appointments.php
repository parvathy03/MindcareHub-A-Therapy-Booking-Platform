<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['therapist_id'])) {
    header("Location: therapist_login.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];

// Get therapist name for header
$therapist_query = $conn->prepare("SELECT name FROM therapists WHERE id = ?");
$therapist_query->bind_param("i", $therapist_id);
$therapist_query->execute();
$therapist_result = $therapist_query->get_result();
$therapist = $therapist_result->fetch_assoc();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    // Verify that this appointment belongs to the logged-in therapist
    $verify_stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND therapist_id = ?");
    $verify_stmt->bind_param("ii", $id, $therapist_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Update the status
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        
        // Use JavaScript redirect to prevent back button issues
        echo '<script>window.location.replace("'.$_SERVER['PHP_SELF'].'?updated=1");</script>';
        exit();
    }
    $verify_stmt->close();
}

// Get appointments
$sql = "SELECT a.*, u.name AS patient_name 
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        WHERE a.therapist_id = ?
        ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare Hub - My Appointments</title>
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
        
        .status-select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-family: 'Montserrat', sans-serif;
        }
        
        .status-select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .update-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .update-btn:hover {
            background: #6EB91F;
        }
        
        .update-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .report-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .report-btn:hover {
            background: #3a7bd5;
        }
        
        .report-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
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
        
        .action-form {
            display: flex;
            gap: 0.5rem;
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
        <div>
            <span style="margin-right: 1rem;">Welcome, <?= htmlspecialchars($therapist['name'] ?? 'Therapist') ?></span>
            <a href="therapist_dashboard.php" style="color: white;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <h1>My Appointments</h1>
        
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
                        <th>Date</th>
                        <th>Time</th>
                        <th>Therapy Type</th>
                        <th>Session Type</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Patient Report</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                        $current_status = strtolower($row['status'] ?? 'pending');
                        $is_completed = ($current_status == 'completed');
                        $is_cancelled = ($current_status == 'cancelled');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= date('F j, Y', strtotime($row['appointment_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                            <td><?= htmlspecialchars($row['therapy_type']) ?></td>
                            <td><?= ucfirst(str_replace('-', ' ', $row['session_type'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= $current_status ?>">
                                    <?= ucfirst(htmlspecialchars($current_status)) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="" class="action-form">
                                    <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="status-select" <?= $is_completed || $is_cancelled ? 'disabled' : '' ?>>
                                        <option value="completed" <?= $current_status == 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                    <button type="submit" name="update_status" class="update-btn" <?= $is_completed || $is_cancelled ? 'disabled' : '' ?>>
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php if ($is_completed): ?>
                                    <a href="consultation_report.php?appointment_id=<?= $row['id'] ?>" class="report-btn">
                                        <i class="fas fa-file-medical"></i> Report
                                    </a>
                                <?php else: ?>
                                    <button class="report-btn" disabled>
                                        <i class="fas fa-file-medical"></i> Report
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <p>No appointments found.</p>
                <a href="therapist_dashboard.php" class="back-link">
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
<?php
$stmt->close();
$conn->close();
?>
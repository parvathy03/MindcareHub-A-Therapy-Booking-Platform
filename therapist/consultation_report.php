<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['therapist_id'])) {
    header("Location: therapist_login.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];
$appointment_id = $_GET['appointment_id'] ?? 0;

// Get appointment details with patient information
$sql = "SELECT a.*, u.name AS patient_name, u.email AS patient_email, u.id AS patient_id
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND a.therapist_id = ? AND a.status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appointment_id, $therapist_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    header("Location: my_appointments.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $findings = trim($_POST['findings']);
    
    if (!empty($findings)) {
        // Insert or update consultation report
        $check_sql = "SELECT id FROM consultation_reports WHERE appointment_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $appointment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing report
            $update_sql = "UPDATE consultation_reports SET findings = ?, updated_at = NOW() WHERE appointment_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $findings, $appointment_id);
            $update_stmt->execute();
            $message = "Report updated successfully!";
        } else {
            // Insert new report
            $insert_sql = "INSERT INTO consultation_reports (appointment_id, patient_id, therapist_id, findings) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiis", $appointment_id, $appointment['patient_id'], $therapist_id, $findings);
            $insert_stmt->execute();
            $message = "Report submitted successfully!";
        }
    } else {
        $error = "Please enter findings before submitting.";
    }
}

// Get existing report if any
$report_sql = "SELECT findings FROM consultation_reports WHERE appointment_id = ?";
$report_stmt = $conn->prepare($report_sql);
$report_stmt->bind_param("i", $appointment_id);
$report_stmt->execute();
$report_result = $report_stmt->get_result();
$existing_report = $report_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare Hub - Consultation Report</title>
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
            max-width: 1000px;
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
        
        .patient-info-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .patient-info-table th,
        .patient-info-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .patient-info-table th {
            background: var(--dark);
            color: white;
            font-weight: 600;
            width: 30%;
        }
        
        .report-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 200px;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        .char-count {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .char-count.limit-reached {
            color: var(--danger);
            font-weight: 600;
        }
        
        .submit-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #6EB91F;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .appointment-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .patient-info-table {
                display: block;
                overflow-x: auto;
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            header {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">MindCare Hub</div>
        <div>
            <a href="my_appointments.php" style="color: white; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to My Appointments
            </a>
        </div>
    </header>

    <div class="container">
        <h1>Patient Consultation Report</h1>
        
        <?php if (isset($message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="appointment-details">
            <h3>Appointment Details</h3>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
            <p><strong>Therapy Type:</strong> <?php echo htmlspecialchars($appointment['therapy_type']); ?></p>
            <p><strong>Session Type:</strong> <?php echo ucfirst(str_replace('-', ' ', $appointment['session_type'])); ?></p>
        </div>
        
        <table class="patient-info-table">
            <tr>
                <th>PATIENT NAME</th>
                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
            </tr>
            <tr>
                <th>PATIENT ID</th>
                <td>P-<?php echo str_pad($appointment['patient_id'], 3, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <th>EMAIL</th>
                <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
            </tr>
            <tr>
                <th>APPOINTMENT ID</th>
                <td>A-<?php echo str_pad($appointment['id'], 3, '0', STR_PAD_LEFT); ?></td>
            </tr>
        </table>
        
        <form method="POST" class="report-form">
            <div class="form-group">
                <label for="findings">
                    <i class="fas fa-file-medical"></i> CLINICAL FINDINGS & NOTES
                </label>
                <textarea id="findings" name="findings" placeholder="Enter clinical findings, observations, recommendations, and treatment notes..." maxlength="2000"><?php echo htmlspecialchars($existing_report['findings'] ?? ''); ?></textarea>
                <div class="char-count" id="char-count">0/2000 characters</div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> <?php echo isset($existing_report['findings']) ? 'Update Report' : 'Submit Report'; ?>
            </button>
            
            <div>
                <a href="my_appointments.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to My Appointments
                </a>
            </div>
        </form>
    </div>
    
    <script>
        // Character count functionality
        const textarea = document.getElementById('findings');
        const charCount = document.getElementById('char-count');
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/2000 characters`;
            
            if (length >= 2000) {
                charCount.classList.add('limit-reached');
            } else {
                charCount.classList.remove('limit-reached');
            }
        });
        
        // Initialize character count on page load
        textarea.dispatchEvent(new Event('input'));
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
<?php
$stmt->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($report_stmt)) $report_stmt->close();
$conn->close();
?>
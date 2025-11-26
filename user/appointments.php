<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if feedback form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $appointment_id = $_POST['appointment_id'];
    $rating = $_POST['rating'];
    $feedback_text = $_POST['feedback_text'];
    
    // Check if feedback already exists for this appointment
    $check_feedback_sql = "SELECT id FROM feedback WHERE appointment_id = ?";
    $check_stmt = $conn->prepare($check_feedback_sql);
    $check_stmt->bind_param("i", $appointment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing feedback
        $update_sql = "UPDATE feedback SET rating = ?, feedback_text = ?, feedback_date = NOW() WHERE appointment_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("isi", $rating, $feedback_text, $appointment_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new feedback
        $insert_sql = "INSERT INTO feedback (appointment_id, user_id, therapist_id, rating, feedback_text, feedback_date) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
        
        // Get therapist_id for this appointment
        $therapist_sql = "SELECT therapist_id FROM appointments WHERE id = ?";
        $therapist_stmt = $conn->prepare($therapist_sql);
        $therapist_stmt->bind_param("i", $appointment_id);
        $therapist_stmt->execute();
        $therapist_result = $therapist_stmt->get_result();
        $therapist_row = $therapist_result->fetch_assoc();
        $therapist_id = $therapist_row['therapist_id'];
        $therapist_stmt->close();
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiiis", $appointment_id, $user_id, $therapist_id, $rating, $feedback_text);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
}

// Fetch appointments with feedback status
$sql = "SELECT a.*, t.name AS therapist_name, f.rating, f.feedback_text, f.feedback_date
        FROM appointments a
        LEFT JOIN therapists t ON a.therapist_id = t.id
        LEFT JOIN feedback f ON a.id = f.appointment_id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: var(--primary);
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .pending { background-color: #ffecb3; color: #7d6608; }
        .approved { background-color: #c8e6c9; color: #2e7d32; }
        .completed { background-color: #bbdefb; color: #1565c0; }
        .cancelled { background-color: #ffcdd2; color: #c62828; }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .btn-feedback {
            background-color: var(--accent);
            color: white;
        }
        .btn-feedback:hover {
            background-color: #6bc41f;
        }
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 90%;
            max-width: 500px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .feedback-form h3 {
            margin-top: 0;
            color: var(--dark);
        }
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            margin: 15px 0;
        }
        .rating input {
            display: none;
        }
        .rating label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #ffc107;
        }
        .feedback-text {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: 'Montserrat', sans-serif;
        }
        .feedback-submit {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }
        .feedback-submit:hover {
            background-color: #3a7bd5;
        }
        .feedback-display {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid var(--accent);
        }
        .feedback-display .rating-display {
            color: #ffc107;
            margin-bottom: 5px;
        }
        .feedback-display .date {
            font-size: 0.8rem;
            color: #777;
            text-align: right;
        }
        
        /* Cancel Confirmation Modal */
        .cancel-modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 450px;
            text-align: center;
        }
        .cancel-icon {
            font-size: 48px;
            color: #f44336;
            margin-bottom: 15px;
        }
        .cancel-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        .cancel-message {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #555;
        }
        .refund-info {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--accent);
        }
        .refund-info i {
            color: var(--accent);
            margin-right: 8px;
        }
        .cancel-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-cancel-confirm {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-cancel-confirm:hover {
            background-color: #d32f2f;
        }
        .btn-cancel-cancel {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-cancel-cancel:hover {
            background-color: #545b62;
        }
        
        @media (max-width: 768px) {
            table {
                font-size: 0.85rem;
            }
            th, td {
                padding: 8px 10px;
            }
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            .cancel-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">MindCare Hub</div>
        <nav>
            <a href="userpage.php" style="color: white; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </nav>
    </header>

    <div class="container">
        <h1><i class="fas fa-calendar-check"></i> My Appointments</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Therapist</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['therapist_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($row['appointment_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                            <td><?= htmlspecialchars($row['therapy_type']) ?></td>
                            <td><?= htmlspecialchars($row['session_type']) ?></td>
                            <td>
                                <span class="status <?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'pending' || $row['status'] == 'approved'): ?>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="showCancelConfirmation(<?= $row['id'] ?>)">
                                        Cancel
                                    </button>
                                <?php elseif ($row['status'] == 'completed'): ?>
                                    <?php if (!empty($row['rating'])): ?>
                                        <!-- Show existing feedback -->
                                        <button class="btn btn-feedback" onclick="showFeedback(<?= $row['id'] ?>)">
                                            View Feedback
                                        </button>
                                        <div id="feedback-display-<?= $row['id'] ?>" style="display:none;">
                                            <div class="feedback-display">
                                                <div class="rating-display">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $row['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <p><?= htmlspecialchars($row['feedback_text']) ?></p>
                                                <div class="date">Submitted on <?= date('M j, Y', strtotime($row['feedback_date'])) ?></div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Show feedback form button -->
                                        <button class="btn btn-feedback" onclick="openFeedbackModal(<?= $row['id'] ?>)">
                                            Give Feedback
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn" disabled>No Action</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h2>No Appointments Yet</h2>
                <p>You haven't booked any appointments yet. <a href="userpage.php#book">Book your first session</a> to get started.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="feedback-form">
                <h3>Provide Feedback for Your Session</h3>
                <form method="POST" action="">
                    <input type="hidden" id="appointment_id" name="appointment_id" value="">
                    
                    <div>
                        <label>Rate your experience:</label>
                        <div class="rating">
                            <input type="radio" id="star5" name="rating" value="5">
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="feedback_text">Your feedback:</label>
                        <textarea id="feedback_text" name="feedback_text" class="feedback-text" placeholder="Share your experience with the therapist..."></textarea>
                    </div>
                    
                    <button type="submit" name="submit_feedback" class="feedback-submit">Submit Feedback</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal">
        <div class="cancel-modal-content">
            <div class="cancel-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="cancel-title">Cancel Appointment</h3>
            <p class="cancel-message">Are you sure you want to cancel this appointment?</p>
            
            <div class="refund-info">
                <i class="fas fa-info-circle"></i>
                <strong>Refund Information:</strong> Your payment of ₹1000 will be refunded to your original payment method within the next 24 hours.
            </div>
            
            <form id="cancelForm" method="POST" action="cancel_appointment.php">
                <input type="hidden" id="cancel_appointment_id" name="appointment_id" value="">
                
                <div class="cancel-buttons">
                    <button type="button" class="btn-cancel-cancel" onclick="closeCancelModal()">
                        <i class="fas fa-times"></i> Keep Appointment
                    </button>
                    <button type="submit" class="btn-cancel-confirm">
                        <i class="fas fa-check"></i> Yes, Cancel Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('feedbackModal');
        const cancelModal = document.getElementById('cancelModal');
        const closeBtn = document.querySelector('.close');
        
        function openFeedbackModal(appointmentId) {
            document.getElementById('appointment_id').value = appointmentId;
            modal.style.display = 'block';
        }
        
        function showCancelConfirmation(appointmentId) {
            document.getElementById('cancel_appointment_id').value = appointmentId;
            cancelModal.style.display = 'block';
        }
        
        function closeCancelModal() {
            cancelModal.style.display = 'none';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == cancelModal) {
                cancelModal.style.display = 'none';
            }
        }
        
        // Show existing feedback
        function showFeedback(appointmentId) {
            const feedbackDisplay = document.getElementById('feedback-display-' + appointmentId);
            if (feedbackDisplay.style.display === 'none') {
                feedbackDisplay.style.display = 'block';
            } else {
                feedbackDisplay.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
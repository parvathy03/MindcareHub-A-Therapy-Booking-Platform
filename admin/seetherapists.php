<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle therapist deletion
if (isset($_GET['delete_id'])) {
    $therapist_id = intval($_GET['delete_id']);
    
    // First, check if therapist exists
    $check_stmt = $conn->prepare("SELECT id, name FROM therapists WHERE id = ?");
    $check_stmt->bind_param("i", $therapist_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $therapist = $result->fetch_assoc();
        $therapist_name = $therapist['name'];
        
        // Delete related records first to maintain referential integrity
        // Delete consultation reports
        $conn->query("DELETE FROM consultation_reports WHERE therapist_id = $therapist_id");
        
        // Delete articles
        $conn->query("DELETE FROM articles WHERE therapist_id = $therapist_id");
        
        // Delete appointments
        $conn->query("DELETE FROM appointments WHERE therapist_id = $therapist_id");
        
        // Delete feedback
        $conn->query("DELETE FROM feedback WHERE therapist_id = $therapist_id");
        
        // Finally delete the therapist
        $delete_stmt = $conn->prepare("DELETE FROM therapists WHERE id = ?");
        $delete_stmt->bind_param("i", $therapist_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Therapist '{$therapist_name}' has been deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete therapist '{$therapist_name}'.";
        }
        $delete_stmt->close();
    } else {
        $_SESSION['error_message'] = "Therapist not found.";
    }
    
    $check_stmt->close();
    header("Location: seetherapists.php");
    exit();
}

// Fetch all approved therapists
$sql = "SELECT * FROM therapists WHERE status='approved' ORDER BY name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare Hub - Our Therapists</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4A90E2;
            --accent: #7ED321;
            --dark: #2C3E50;
            --light: #F8F9FA;
            --text: #333;
            --danger: #e74c3c;
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
        .therapists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .therapist-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        .therapist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .therapist-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            object-position: center;
            border-bottom: 3px solid var(--accent);
        }
        .therapist-info {
            padding: 1.5rem;
        }
        .therapist-name {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        .therapist-specialty {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .therapist-qualification {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .therapist-experience {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .book-btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: var(--accent);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            margin-right: 0.5rem;
        }
        .book-btn:hover {
            background: #6EB91F;
        }
        .delete-btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: var(--danger);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .no-therapists {
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
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: 500;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .modal-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .modal-confirm {
            background-color: var(--danger);
            color: white;
        }
        .modal-cancel {
            background-color: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">MindCare Hub</div>
        <div><a href="admin_dashboard.php" style="color: white;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
    </header>

    <div class="container">
        <h1>Our Approved Therapists</h1>
        
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="therapists-grid">
                <?php while ($therapist = $result->fetch_assoc()): ?>
                    <div class="therapist-card">
                        <?php if (!empty($therapist['profile_picture'])): ?>
                            <img src="../therapist/<?= htmlspecialchars($therapist['profile_picture']) ?>?v=<?= time() ?>" 
                                 alt="<?= htmlspecialchars($therapist['name']) ?>" class="therapist-img">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($therapist['name']) ?>&size=200&background=4A90E2&color=fff" 
                                 alt="<?= htmlspecialchars($therapist['name']) ?>" class="therapist-img">
                        <?php endif; ?>
                        <div class="therapist-info">
                            <h3 class="therapist-name"><?= htmlspecialchars($therapist['name']) ?></h3>
                            <div class="therapist-specialty">
                                <i class="fas fa-briefcase-medical"></i>
                                <?= htmlspecialchars($therapist['specialization']) ?>
                            </div>
                            <div class="therapist-qualification">
                                <i class="fas fa-user-graduate"></i>
                                <?= htmlspecialchars($therapist['qualification']) ?>
                            </div>
                            <div class="therapist-experience">
                                <i class="fas fa-business-time"></i>
                                <?= htmlspecialchars($therapist['experience']) ?> years experience
                            </div>
                            <div class="therapist-experience">
                                <i class="fas fa-id-card"></i>
                                License: <?= htmlspecialchars($therapist['license_number']) ?>
                            </div>
                            <div class="action-buttons">
                                <button class="delete-btn" onclick="confirmDelete(<?= $therapist['id'] ?>, '<?= htmlspecialchars(addslashes($therapist['name'])) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-therapists">
                <p>Currently, there are no approved therapists available.</p>
                <p>Please check back later or contact our support team.</p>
                <a href="admin_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Return to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete therapist: <strong id="therapistName"></strong>?</p>
            <p class="warning-text" style="color: var(--danger); font-weight: bold;">
                <i class="fas fa-exclamation-triangle"></i> This action cannot be undone and will permanently delete all therapist data including appointments and reports.
            </p>
            <div class="modal-buttons">
                <button id="confirmDelete" class="modal-btn modal-confirm">Yes, Delete</button>
                <button id="cancelDelete" class="modal-btn modal-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Delete confirmation modal functionality
        let deleteId = null;
        
        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('therapistName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = 'seetherapists.php?delete_id=' + deleteId;
            }
        });
        
        document.getElementById('cancelDelete').addEventListener('click', function() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.style.display = 'none';
                deleteId = null;
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
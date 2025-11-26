<?php
session_start();
if (!isset($_SESSION['therapist_id'])) {
    header("Location: therapist_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$therapist_id = $_SESSION['therapist_id'];

// Fetch therapist info
$therapist_stmt = $conn->prepare("SELECT name, email FROM therapists WHERE id = ?");
$therapist_stmt->bind_param("i", $therapist_id);
$therapist_stmt->execute();
$therapist_result = $therapist_stmt->get_result();
$therapist = $therapist_result->fetch_assoc();

// Fetch all feedback for this therapist
$feedback_query = $conn->prepare("
    SELECT f.*, u.name as patient_name, u.email as patient_email, 
           a.appointment_date, a.therapy_type, a.session_type
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN appointments a ON f.appointment_id = a.id
    WHERE f.therapist_id = ?
    ORDER BY f.feedback_date DESC
");
$feedback_query->bind_param("i", $therapist_id);
$feedback_query->execute();
$feedback_result = $feedback_query->get_result();

// Calculate statistics
$stats_query = $conn->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_feedback,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
    FROM feedback 
    WHERE therapist_id = ?
");
$stats_query->bind_param("i", $therapist_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();

$avg_rating = round($stats['avg_rating'], 1);
$total_feedback = $stats['total_feedback'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>MindCare Hub – Patient Feedback</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
        --peacock-blue: #004e7c;
        --teal: #00887a;
        --light-teal: #77c9d4;
        --mint: #a3e4d7;
        --light-gray: #f5f7fa;
        --dark-gray: #333;
        --white: #ffffff;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
        background-color: var(--light-gray);
        color: var(--dark-gray);
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 100vh;
    }
    
    /* Sidebar */
    .sidebar {
        background: var(--peacock-blue);
        color: var(--white);
        padding: 1.5rem;
        position: sticky;
        top: 0;
        height: 100vh;
        box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    }
    
    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .logo img {
        width: 36px;
    }
    
    .logo h1 {
        font-size: 1.25rem;
        font-weight: 700;
    }
    
    .nav-menu {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 0.95rem;
        position: relative;
    }
    
    .nav-item:hover, .nav-item.active {
        background: rgba(255,255,255,0.1);
        color: var(--white);
    }
    
    .nav-item i {
        width: 20px;
        text-align: center;
    }
    
    .feedback-badge {
        background: var(--teal);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
    }
    
    /* Main Content */
    .main-content {
        padding: 2rem;
    }
    
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .header h2 {
        font-size: 1.75rem;
        color: var(--peacock-blue);
        font-weight: 600;
        position: relative;
    }
    
    .header h2::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--teal);
        border-radius: 2px;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }
    
    .user-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .user-profile img {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--teal);
    }
    
    .user-info small {
        color: var(--peacock-blue);
        opacity: 0.7;
        font-size: 0.8rem;
    }
    
    .profile-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
        padding: 0.5rem 0;
        width: 160px;
        display: none;
        z-index: 100;
    }
    
    .profile-dropdown a {
        display: block;
        padding: 0.5rem 1rem;
        color: var(--dark-gray);
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .profile-dropdown a:hover {
        background: var(--light-gray);
    }
    
    /* Cards */
    .card {
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--peacock-blue);
        position: relative;
    }
    
    .card-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 30px;
        height: 2px;
        background: var(--teal);
    }
    
    .btn {
        background: var(--peacock-blue);
        color: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn:hover {
        background: var(--teal);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* Stats Overview */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, var(--peacock-blue), var(--teal));
    }
    
    .stat-card .title {
        color: var(--peacock-blue);
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
        opacity: 0.8;
    }
    
    .stat-card .value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--peacock-blue);
        margin-bottom: 0.25rem;
    }
    
    .stat-card .info {
        color: var(--peacock-blue);
        opacity: 0.6;
        font-size: 0.8rem;
    }
    
    /* Rating Distribution */
    .rating-distribution {
        margin: 1.5rem 0;
    }
    
    .rating-bar {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .rating-label {
        width: 80px;
        font-size: 0.9rem;
        color: var(--peacock-blue);
    }
    
    .rating-bar-inner {
        flex: 1;
        height: 8px;
        background: var(--light-gray);
        border-radius: 4px;
        overflow: hidden;
        margin: 0 1rem;
    }
    
    .rating-bar-fill {
        height: 100%;
        background: linear-gradient(to right, var(--teal), var(--peacock-blue));
        border-radius: 4px;
    }
    
    .rating-count {
        width: 40px;
        text-align: right;
        font-size: 0.9rem;
        color: var(--peacock-blue);
    }
    
    /* Feedback Items */
    .feedback-item {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: var(--white);
        margin-bottom: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .feedback-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .feedback-patient {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .patient-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--peacock-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }
    
    .patient-info h4 {
        color: var(--peacock-blue);
        margin-bottom: 0.25rem;
    }
    
    .patient-info .patient-email {
        font-size: 0.8rem;
        color: var(--peacock-blue);
        opacity: 0.7;
    }
    
    .feedback-date {
        font-size: 0.8rem;
        color: var(--peacock-blue);
        opacity: 0.6;
    }
    
    .feedback-rating {
        margin-bottom: 1rem;
    }
    
    .rating-stars {
        display: inline-flex;
        gap: 2px;
    }
    
    .rating-stars .fas.fa-star {
        color: #ffc107;
    }
    
    .rating-stars .far.fa-star {
        color: #ddd;
    }
    
    .rating-value {
        margin-left: 0.5rem;
        color: var(--peacock-blue);
        font-weight: 600;
    }
    
    .feedback-text {
        color: var(--dark-gray);
        line-height: 1.6;
        margin-bottom: 1rem;
        padding: 1rem;
        background: var(--light-gray);
        border-radius: 6px;
        border-left: 4px solid var(--teal);
    }
    
    .feedback-meta {
        display: flex;
        gap: 2rem;
        font-size: 0.8rem;
        color: var(--peacock-blue);
        opacity: 0.7;
    }
    
    .empty-state {
        padding: 3rem;
        text-align: center;
        color: var(--peacock-blue);
        opacity: 0.6;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: var(--peacock-blue);
        opacity: 0.3;
    }
    
    .avg-rating-large {
        text-align: center;
        margin: 2rem 0;
    }
    
    .avg-rating-value {
        font-size: 3rem;
        font-weight: 700;
        color: var(--peacock-blue);
        line-height: 1;
    }
    
    .avg-rating-stars {
        margin: 0.5rem 0;
    }
    
    .total-reviews {
        font-size: 1rem;
        color: var(--peacock-blue);
        opacity: 0.7;
    }
    
    @media (max-width: 992px) {
        body {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            display: none;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="logo">
      <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" alt="MindCare Logo">
      <h1>MindCare Hub</h1>
    </div>
    <nav class="nav-menu">
      <a href="therapist_dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
      <a href="my_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
      <a href="patient_feedback.php" class="nav-item active">
        <i class="fas fa-comment-medical"></i> Patient Feedback
       
      </a>
      <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> Articles</a>
      <a href="write_article.php" class="nav-item"><i class="fas fa-pen-nib"></i> Write Article</a>
      <a href="reports.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
      <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
      <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="header">
      <h2>Patient Feedback</h2>
      <div class="user-profile">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($therapist['name']); ?>&background=004e7c&color=fff" alt="Therapist">
        <div class="user-info">
          <div><?php echo htmlspecialchars($therapist['name']); ?></div>
          <small>Therapist</small>
        </div>
        <div class="profile-dropdown">
          <a href="settings.php">Settings</a>
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </header>

    <script>
      document.querySelector('.user-profile').addEventListener('click', function() {
        const dropdown = this.querySelector('.profile-dropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
      });

      document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-profile')) {
          const dropdowns = document.querySelectorAll('.profile-dropdown');
          dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
          });
        }
      });
    </script>

    <!-- Statistics Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="title">Average Rating</div>
        <div class="value"><?php echo $avg_rating > 0 ? $avg_rating : 'N/A'; ?></div>
        <div class="info">out of 5 stars</div>
      </div>
      
      <div class="stat-card">
        <div class="title">Total Reviews</div>
        <div class="value"><?php echo $total_feedback; ?></div>
        <div class="info">patient feedback</div>
      </div>
      
      <div class="stat-card">
        <div class="title">5-Star Reviews</div>
        <div class="value"><?php echo $stats['five_star']; ?></div>
        <div class="info">excellent ratings</div>
      </div>
      
      <div class="stat-card">
        <div class="title">Response Rate</div>
        <div class="value"><?php echo $total_feedback > 0 ? round(($total_feedback / $total_feedback) * 100) : 0; ?>%</div>
        <div class="info">feedback received</div>
      </div>
    </div>

    <?php if ($total_feedback > 0): ?>
    <!-- Rating Distribution -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Rating Distribution</div>
      </div>
      <div class="rating-distribution">
        
      <?php 
// Create a mapping from numbers to words
$rating_words = [5 => 'five', 4 => 'four', 3 => 'three', 2 => 'two', 1 => 'one'];
?>

<?php for ($i = 5; $i >= 1; $i--): ?>
  <?php 
  $word_key = $rating_words[$i] . '_star';
  $count = isset($stats[$word_key]) ? $stats[$word_key] : 0;
  $percentage = $total_feedback > 0 ? round(($count / $total_feedback) * 100) : 0;
  ?>
          <div class="rating-bar">
            <div class="rating-label"><?php echo $i; ?> stars</div>
            <div class="rating-bar-inner">
              <div class="rating-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <div class="rating-count"><?php echo $count; ?></div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- All Feedback -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">All Patient Feedback (<?php echo $total_feedback; ?>)</div>
      </div>
      
      <?php while($feedback = $feedback_result->fetch_assoc()): ?>
        <div class="feedback-item">
          <div class="feedback-header">
            <div class="feedback-patient">
              <div class="patient-avatar">
                <?php echo strtoupper(substr($feedback['patient_name'], 0, 1)); ?>
              </div>
              <div class="patient-info">
                <h4><?php echo htmlspecialchars($feedback['patient_name']); ?></h4>
                <div class="patient-email"><?php echo htmlspecialchars($feedback['patient_email']); ?></div>
              </div>
            </div>
            <div class="feedback-date">
              <?php echo date('F j, Y \a\t g:i A', strtotime($feedback['feedback_date'])); ?>
            </div>
          </div>
          
          <div class="feedback-rating">
            <div class="rating-stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php if ($i <= $feedback['rating']): ?>
                  <i class="fas fa-star"></i>
                <?php else: ?>
                  <i class="far fa-star"></i>
                <?php endif; ?>
              <?php endfor; ?>
              <span class="rating-value">(<?php echo $feedback['rating']; ?>/5)</span>
            </div>
          </div>
          
          <div class="feedback-text">
            <?php echo htmlspecialchars($feedback['feedback_text']); ?>
          </div>
          
          <div class="feedback-meta">
            <div><strong>Therapy Type:</strong> <?php echo htmlspecialchars($feedback['therapy_type']); ?></div>
            <div><strong>Session Type:</strong> <?php echo htmlspecialchars($feedback['session_type']); ?></div>
            <div><strong>Appointment Date:</strong> <?php echo date('M j, Y', strtotime($feedback['appointment_date'])); ?></div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="card">
      <div class="empty-state">
        <i class="fas fa-comment-medical"></i>
        <h3>No Feedback Yet</h3>
        <p>You haven't received any patient feedback yet. Feedback will appear here after patients complete their sessions.</p>
        <a href="therapist_dashboard.php" class="btn" style="margin-top: 1rem;">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>
    <?php endif; ?>
  </main>
</body>
</html>
<?php
$therapist_stmt->close();
$feedback_query->close();
$stats_query->close();
$conn->close();
?>
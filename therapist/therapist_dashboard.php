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
$stmt = $conn->prepare("SELECT name, email FROM therapists WHERE id = ?");
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$result = $stmt->get_result();
$therapist = $result->fetch_assoc();

// Fetch today's appointments for this therapist
$today = date('Y-m-d');
$today_appointments_query = $conn->prepare("
    SELECT a.*, u.name as patient_name 
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    WHERE a.therapist_id = ? AND a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");
$today_appointments_query->bind_param("is", $therapist_id, $today);
$today_appointments_query->execute();
$today_appointments_result = $today_appointments_query->get_result();

// Fetch recent patients (last 5 unique patients with appointments)
$recent_patients_query = $conn->prepare("
    SELECT DISTINCT u.id, u.name, u.email, MAX(a.appointment_date) as last_appointment
    FROM users u
    JOIN appointments a ON u.id = a.user_id
    WHERE a.therapist_id = ?
    GROUP BY u.id
    ORDER BY last_appointment DESC
    LIMIT 5
");
$recent_patients_query->bind_param("i", $therapist_id);
$recent_patients_query->execute();
$recent_patients_result = $recent_patients_query->get_result();

// Count total patients
$total_patients_query = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) as total 
    FROM appointments 
    WHERE therapist_id = ?
");
$total_patients_query->bind_param("i", $therapist_id);
$total_patients_query->execute();
$total_patients_result = $total_patients_query->get_result();
$total_patients = $total_patients_result->fetch_assoc()['total'];

// Count today's appointments
$today_appointments_count_query = $conn->prepare("
    SELECT COUNT(*) as count FROM appointments 
    WHERE therapist_id = ? AND appointment_date = ?
");
$today_appointments_count_query->bind_param("is", $therapist_id, $today);
$today_appointments_count_query->execute();
$today_appointments_count_result = $today_appointments_count_query->get_result();
$today_appointments_count = $today_appointments_count_result->fetch_assoc()['count'];

// Count upcoming appointments (next 7 days)
$upcoming_date = date('Y-m-d', strtotime('+7 days'));
$upcoming_appointments_query = $conn->prepare("
    SELECT COUNT(*) as count FROM appointments 
    WHERE therapist_id = ? AND appointment_date BETWEEN ? AND ?
");
$upcoming_appointments_query->bind_param("iss", $therapist_id, $today, $upcoming_date);
$upcoming_appointments_query->execute();
$upcoming_appointments_result = $upcoming_appointments_query->get_result();
$upcoming_appointments_count = $upcoming_appointments_result->fetch_assoc()['count'];

// Fetch recent feedback with patient names
$recent_feedback_query = $conn->prepare("
    SELECT f.*, u.name as patient_name, a.appointment_date, a.therapy_type
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN appointments a ON f.appointment_id = a.id
    WHERE f.therapist_id = ?
    ORDER BY f.feedback_date DESC
    LIMIT 5
");
$recent_feedback_query->bind_param("i", $therapist_id);
$recent_feedback_query->execute();
$recent_feedback_result = $recent_feedback_query->get_result();

// Calculate average rating
$avg_rating_query = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback 
    FROM feedback 
    WHERE therapist_id = ?
");
$avg_rating_query->bind_param("i", $therapist_id);
$avg_rating_query->execute();
$avg_rating_result = $avg_rating_query->get_result();
$rating_data = $avg_rating_result->fetch_assoc();
$avg_rating = round($rating_data['avg_rating'], 1);
$total_feedback = $rating_data['total_feedback'];

$quotes = [
    "You are making a difference every day.",
    "Healing is a process, and you are part of the journey.",
    "Compassion is your superpower.",
    "Keep holding space for growth and healing.",
    "Your guidance lights up someone's dark day."
];
$quote = $quotes[array_rand($quotes)];
$siteName = "MindCare Hub";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo $siteName; ?> – Therapist Dashboard</title>
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
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
    
    .stat-card .icon {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        color: var(--light-teal);
        font-size: 1.5rem;
        opacity: 0.3;
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
    
    /* Main Grid */
    .main-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    
    /* Status badges */
    .badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-success {
        background: rgba(40,167,69,0.1);
        color: var(--success);
    }
    
    .badge-warning {
        background: rgba(255,193,7,0.1);
        color: var(--warning);
    }
    
    .badge-info {
        background: rgba(23,162,184,0.1);
        color: var(--info);
    }
    
    /* Patients Table */
    .patients-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .patients-table th, .patients-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .patients-table th {
        background: var(--light-gray);
        color: var(--peacock-blue);
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .patients-table tr:hover {
        background: rgba(0,78,124,0.03);
    }
    
    .empty-state {
        padding: 2rem;
        text-align: center;
        color: var(--peacock-blue);
        opacity: 0.6;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--peacock-blue);
        opacity: 0.5;
    }
    
    .appointment-item {
        padding: .5rem 0; 
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .appointment-item:last-child {
        border-bottom: none;
    }
    
    /* Feedback Styles */
    .feedback-item {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .feedback-item:last-child {
        border-bottom: none;
    }
    
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .feedback-patient {
        font-weight: 600;
        color: var(--peacock-blue);
    }
    
    .feedback-date {
        font-size: 0.8rem;
        color: var(--peacock-blue);
        opacity: 0.6;
    }
    
    .feedback-rating {
        color: #ffc107;
        margin-bottom: 0.5rem;
    }
    
    .feedback-text {
        color: var(--dark-gray);
        line-height: 1.4;
        font-size: 0.9rem;
    }
    
    .feedback-therapy {
        font-size: 0.8rem;
        color: var(--peacock-blue);
        opacity: 0.7;
        margin-top: 0.25rem;
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
    
    .avg-rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .avg-rating-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--peacock-blue);
    }
    
    .total-feedback {
        font-size: 0.9rem;
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
        
        .main-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
      <a href="therapist_dashboard.php" class="nav-item active"><i class="fas fa-chart-line"></i> Dashboard</a>
      <a href="my_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
      <a href="patient_feedback.php" class="nav-item">
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
      <h2>Dashboard Overview</h2>
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

    <!-- Quote Card -->
    <div class="stat-card" style="margin-bottom:1.9rem; background:var(--light-gray);">
      <div class="title">Quote of the Day</div>
      <div class="value" style="font-size:1rem; font-weight:400;"><?php echo htmlspecialchars($quote); ?></div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <i class="fas fa-users icon"></i>
        <div class="title">Total Patients</div>
        <div class="value"><?php echo $total_patients; ?></div>
        <div class="info">Assigned to you</div>
      </div>
      
      <div class="stat-card">
        <i class="fas fa-calendar-day icon"></i>
        <div class="title">Today's Appointments</div>
        <div class="value"><?php echo $today_appointments_count; ?></div>
        <div class="info"><?php echo date('F j, Y'); ?></div>
      </div>
      
      <div class="stat-card">
        <i class="fas fa-calendar-alt icon"></i>
        <div class="title">Upcoming Appointments</div>
        <div class="value"><?php echo $upcoming_appointments_count; ?></div>
        <div class="info">Next 7 days</div>
      </div>
      
      <div class="stat-card">
        <i class="fas fa-star icon"></i>
        <div class="title">Average Rating</div>
        <div class="value"><?php echo $avg_rating > 0 ? $avg_rating : 'N/A'; ?></div>
        <div class="info"><?php echo $total_feedback; ?> reviews</div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="main-grid">
      <div class="patients-card card">
        <div class="card-header">
          <div class="card-title">Recent Patients</div>
          <a href="my_appointments.php" class="btn"><i class="fas fa-eye"></i> View All</a>
        </div>
        
        <?php if ($recent_patients_result->num_rows > 0): ?>
          <table class="patients-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Last Appointment</th>
              </tr>
            </thead>
            <tbody>
              <?php while($patient = $recent_patients_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($patient['name']); ?></td>
                  <td><?php echo htmlspecialchars($patient['email']); ?></td>
                  <td>
                    <?php 
                      if ($patient['last_appointment']) {
                        echo date('M j, Y', strtotime($patient['last_appointment']));
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-user-injured"></i>
            <h3>No patients found</h3>
            <p>You don't have any patients assigned to you yet.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="appointments-card card">
        <div class="card-header">
          <div class="card-title">Today's Appointments</div>
          <a href="my_appointments.php" class="btn"><i class="fas fa-eye"></i> View All</a>
        </div>
        
        <?php if ($today_appointments_result->num_rows > 0): ?>
          <?php while($appointment = $today_appointments_result->fetch_assoc()): ?>
            <div class="appointment-item">
              <div style="font-weight:500;"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
              <div style="color:var(--peacock-blue); opacity:0.7; font-size:.875rem;">
                <?php echo htmlspecialchars($appointment['patient_name']); ?>
              </div>
              <span class="badge badge-<?php 
                echo $appointment['status'] === 'completed' ? 'success' : 
                ($appointment['status'] === 'approved' ? 'info' : 'warning'); 
              ?>">
                <?php echo ucfirst($appointment['status']); ?>
              </span>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state" style="padding: 1rem;">
            <i class="fas fa-calendar-times"></i>
            <p>No appointments today</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Feedback Section -->
    <?php if ($total_feedback > 0): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title">Recent Patient Feedback</div>
        <a href="patient_feedback.php" class="btn"><i class="fas fa-eye"></i> View All</a>
      </div>
      
      <div class="avg-rating">
        <div class="avg-rating-value"><?php echo $avg_rating; ?>/5</div>
        <div class="rating-stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php if ($i <= floor($avg_rating)): ?>
              <i class="fas fa-star"></i>
            <?php elseif ($i == ceil($avg_rating) && $avg_rating - floor($avg_rating) >= 0.5): ?>
              <i class="fas fa-star-half-alt"></i>
            <?php else: ?>
              <i class="far fa-star"></i>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <div class="total-feedback">Based on <?php echo $total_feedback; ?> reviews</div>
      </div>
      
      <?php while($feedback = $recent_feedback_result->fetch_assoc()): ?>
        <div class="feedback-item">
          <div class="feedback-header">
            <div class="feedback-patient"><?php echo htmlspecialchars($feedback['patient_name']); ?></div>
            <div class="feedback-date"><?php echo date('M j, Y', strtotime($feedback['feedback_date'])); ?></div>
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
              <span style="margin-left: 5px; color: var(--peacock-blue);">(<?php echo $feedback['rating']; ?>/5)</span>
            </div>
          </div>
          <div class="feedback-text"><?php echo htmlspecialchars($feedback['feedback_text']); ?></div>
          <div class="feedback-therapy">Therapy: <?php echo htmlspecialchars($feedback['therapy_type']); ?> • <?php echo date('M j, Y', strtotime($feedback['appointment_date'])); ?></div>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </main>
</body>
</html>
<?php
$stmt->close();
$today_appointments_query->close();
$recent_patients_query->close();
$total_patients_query->close();
$today_appointments_count_query->close();
$upcoming_appointments_query->close();
$recent_feedback_query->close();
$avg_rating_query->close();
$conn->close();
?>
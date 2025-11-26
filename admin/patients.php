<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin authentication check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle patient deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First check if user exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_user->bind_param("i", $delete_id);
    $check_user->execute();
    $check_user->store_result();
    
    if ($check_user->num_rows > 0) {
        // Delete related consultation reports first
        $delete_reports = $conn->prepare("DELETE FROM consultation_reports WHERE patient_id = ?");
        $delete_reports->bind_param("i", $delete_id);
        $delete_reports->execute();
        $delete_reports->close();
        
        // Delete related feedback
        $delete_feedback = $conn->prepare("DELETE FROM feedback WHERE user_id = ?");
        $delete_feedback->bind_param("i", $delete_id);
        $delete_feedback->execute();
        $delete_feedback->close();
        
        // Delete related appointments
        $delete_appointments = $conn->prepare("DELETE FROM appointments WHERE user_id = ?");
        $delete_appointments->bind_param("i", $delete_id);
        $delete_appointments->execute();
        $delete_appointments->close();
        
        // Delete the user (patient) from users table
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Patient deleted successfully";
        } else {
            $_SESSION['error_message'] = "Error deleting patient: " . $conn->error;
        }
        
        $delete_stmt->close();
        
        // Redirect to avoid resubmission on refresh
        header("Location: patients.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Patient not found";
        header("Location: patients.php");
        exit();
    }
}

// Initialize variables
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Check if appointments table exists
$appt_table_check = $conn->query("SHOW TABLES LIKE 'appointments'");
$appointments_exist = ($appt_table_check->num_rows > 0);

// Main query to fetch patients from users table
$query = "SELECT u.*, 
          COUNT(a.id) as total_appointments,
          SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) as approved_appointments,
          SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
          SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
          MAX(a.appointment_date) as last_appointment
          FROM users u
          LEFT JOIN appointments a ON u.id = a.user_id
          WHERE 1=1";

// Apply filter based on appointment status
if ($filter === 'approved') {
    $query .= " AND a.status = 'approved'";
} elseif ($filter === 'completed') {
    $query .= " AND a.status = 'completed'";
} elseif ($filter === 'cancelled') {
    $query .= " AND a.status = 'cancelled'";
}

// Apply search if provided
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

// Execute query
$result = $conn->query($query);

// Count patients by appointment status for stats
$stats = [
    'all' => 0,
    'approved' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Count all users
$all_count_result = $conn->query("SELECT COUNT(*) as count FROM users");
$all_count_row = $all_count_result->fetch_assoc();
$stats['all'] = $all_count_row['count'];

// Count users with approved appointments
$approved_count_result = $conn->query("
    SELECT COUNT(DISTINCT u.id) as count
    FROM users u
    JOIN appointments a ON u.id = a.user_id
    WHERE a.status = 'approved'
");
$approved_count_row = $approved_count_result->fetch_assoc();
$stats['approved'] = $approved_count_row['count'];

// Count users with completed appointments
$completed_count_result = $conn->query("
    SELECT COUNT(DISTINCT u.id) as count
    FROM users u
    JOIN appointments a ON u.id = a.user_id
    WHERE a.status = 'completed'
");
$completed_count_row = $completed_count_result->fetch_assoc();
$stats['completed'] = $completed_count_row['count'];

// Count users with cancelled appointments
$cancelled_count_result = $conn->query("
    SELECT COUNT(DISTINCT u.id) as count
    FROM users u
    JOIN appointments a ON u.id = a.user_id
    WHERE a.status = 'cancelled'
");
$cancelled_count_row = $cancelled_count_result->fetch_assoc();
$stats['cancelled'] = $cancelled_count_row['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindCare - Patient Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    }
    
    .dashboard {
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
    }
    
    .nav-item:hover, .nav-item.active {
        background: rgba(255,255,255,0.1);
        color: var(--white);
    }
    
    .nav-item i {
        width: 20px;
        text-align: center;
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
        text-decoration: none;
        color: inherit;
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
    
    /* Search and Filter */
    .search-filter {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    
    .search-box {
        flex: 1;
        position: relative;
        min-width: 250px;
    }
    
    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    
    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--dark-gray);
        opacity: 0.6;
    }
    
    .filter-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 0.75rem 1.25rem;
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: var(--dark-gray);
        font-size: 0.9rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    
    .filter-btn.active {
        background: var(--peacock-blue);
        color: white;
        border-color: var(--peacock-blue);
    }
    
    /* Table */
    .table-container {
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow-x: auto;
        margin-bottom: 2rem;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    th {
        background: var(--light-gray);
        color: var(--peacock-blue);
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    tr:hover {
        background: rgba(0,78,124,0.03);
    }
    
    .badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-approved {
        background: rgba(40,167,69,0.1);
        color: var(--success);
    }
    
    .badge-completed {
        background: rgba(23,162,184,0.1);
        color: var(--info);
    }
    
    .badge-cancelled {
        background: rgba(220,53,69,0.1);
        color: var(--danger);
    }
    
    .badge-info {
        background: rgba(23,162,184,0.1);
        color: var(--info);
    }
    
    .btn-delete {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        background: var(--danger);
        color: white;
    }
    
    .btn-delete:hover {
        background: #bd2130;
    }
    
    .empty-state {
        padding: 3rem;
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
    
    .notification {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 6px;
        font-weight: 500;
    }
    
    .notification.success {
        background: #d3f9d8;
        color: #2b8a3e;
        border-left: 4px solid #2b8a3e;
    }
    
    .notification.error {
        background: #ffe3e3;
        color: #c92a2a;
        border-left: 4px solid #c92a2a;
    }
    
    /* Appointment status indicators */
    .appointment-counts {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .count-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .count-approved {
        background: rgba(40,167,69,0.1);
        color: var(--success);
        border: 1px solid rgba(40,167,69,0.2);
    }
    
    .count-completed {
        background: rgba(23,162,184,0.1);
        color: var(--info);
        border: 1px solid rgba(23,162,184,0.2);
    }
    
    .count-cancelled {
        background: rgba(220,53,69,0.1);
        color: var(--danger);
        border: 1px solid rgba(220,53,69,0.2);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .dashboard {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            display: none;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
        
        .search-filter {
            flex-direction: column;
        }
        
        .appointment-counts {
            flex-direction: column;
            gap: 0.25rem;
        }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar - Matches the dashboard sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" alt="MindCare">
        <h1>MindCare Hub</h1>
      </div>
      
      <nav class="nav-menu">
        <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="patients.php" class="nav-item active"><i class="fas fa-users"></i> Patients</a>
        <a href="seetherapists.php" class="nav-item"><i class="fas fa-user-md"></i> Therapists</a>
        <a href="view_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="reports.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h2>Patient Management</h2>
      </div>
      
      <!-- Stats Cards -->
      <div class="stats-grid">
        <a href="patients.php?filter=all" class="stat-card">
          <div class="title">All Patients</div>
          <div class="value"><?php echo $stats['all']; ?></div>
          <div class="info">Total registered patients</div>
          <div class="icon"><i class="fas fa-users"></i></div>
        </a>
        
        <a href="patients.php?filter=approved" class="stat-card">
          <div class="title">Approved</div>
          <div class="value"><?php echo $stats['approved']; ?></div>
          <div class="info">Approved appointments</div>
          <div class="icon"><i class="fas fa-check-circle"></i></div>
        </a>
        
        <a href="patients.php?filter=completed" class="stat-card">
          <div class="title">Completed</div>
          <div class="value"><?php echo $stats['completed']; ?></div>
          <div class="info">Completed appointments</div>
          <div class="icon"><i class="fas fa-check-double"></i></div>
        </a>
        
        <a href="patients.php?filter=cancelled" class="stat-card">
          <div class="title">Cancelled</div>
          <div class="value"><?php echo $stats['cancelled']; ?></div>
          <div class="info">Cancelled appointments</div>
          <div class="icon"><i class="fas fa-times-circle"></i></div>
        </a>
      </div>
      
      <!-- Search and Filter -->
      <div class="search-filter">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <form method="GET" action="patients.php" style="display: inline;">
            <input type="text" name="search" placeholder="Search patients..." value="<?php echo htmlspecialchars($search); ?>">
            <?php if (!empty($filter) && $filter != 'all'): ?>
              <input type="hidden" name="filter" value="<?php echo $filter; ?>">
            <?php endif; ?>
          </form>
        </div>
        
        <div class="filter-buttons">
          <a href="patients.php?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
          <a href="patients.php?filter=approved" class="filter-btn <?php echo $filter == 'approved' ? 'active' : ''; ?>">Approved</a>
          <a href="patients.php?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
          <a href="patients.php?filter=cancelled" class="filter-btn <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>
      </div>
      
      <!-- Notifications -->
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="notification success">
          <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="notification error">
          <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>
      
      <!-- Patients Table -->
      <div class="table-container">
        <?php if ($result && $result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Last Appointment Status</th>
                <th>Appointment Details</th>
                <th>Total Appointments</th>
                <th>Last Appointment</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): 
                // Determine the primary appointment status for this patient
                $appointment_status = ($row['approved_appointments'] > 0) ? 'approved' : 
                                    (($row['completed_appointments'] > 0) ? 'completed' : 
                                    (($row['cancelled_appointments'] > 0) ? 'cancelled' : 'none'));
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['name']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td>
                    <?php if ($appointment_status != 'none'): ?>
                    <span class="badge badge-<?php echo $appointment_status; ?>">
                      <?php echo ucfirst($appointment_status); ?>
                    </span>
                    <?php else: ?>
                    <span class="badge badge-info">No appointments</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="appointment-counts">
                      <?php if ($row['approved_appointments'] > 0): ?>
                        <span class="count-badge count-approved" title="Approved appointments">
                          ✓ <?php echo $row['approved_appointments']; ?>
                        </span>
                      <?php endif; ?>
                      
                      <?php if ($row['completed_appointments'] > 0): ?>
                        <span class="count-badge count-completed" title="Completed appointments">
                          ✓✓ <?php echo $row['completed_appointments']; ?>
                        </span>
                      <?php endif; ?>
                      
                      <?php if ($row['cancelled_appointments'] > 0): ?>
                        <span class="count-badge count-cancelled" title="Cancelled appointments">
                          ✕ <?php echo $row['cancelled_appointments']; ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?php echo $row['total_appointments']; ?></td>
                  <td>
                    <?php 
                      if ($row['last_appointment']) {
                        echo date('M j, Y', strtotime($row['last_appointment']));
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                  <td>
                    <a href="patients.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this patient? This will also delete all their appointments.')">Delete</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-user-injured"></i>
            <h3>No patients found</h3>
            <p>There are no patients matching your criteria.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
  
  <script>
    // Add active class to current filter button
    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.filter-btn');
      const currentFilter = '<?php echo $filter; ?>';
      
      filterButtons.forEach(button => {
        if (button.getAttribute('href').includes('filter=' + currentFilter)) {
          button.classList.add('active');
        }
      });
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>
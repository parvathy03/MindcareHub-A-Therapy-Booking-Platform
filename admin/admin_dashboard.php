<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
// Database connection
$conn = new mysqli('localhost', 'root', '', 'mindcare');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch stats
$stats = [
    'total_patients' => 0,
    'active_therapists' => 0,
    'today_appointments' => 0,
    'weekly_growth' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) $stats['total_patients'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM therapists WHERE status='approved'");
if ($result) $stats['active_therapists'] = $result->fetch_assoc()['total'];

$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
if ($result) $stats['today_appointments'] = $result->fetch_assoc()['total'];

// Calculate monthly appointments (approved and completed only)
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN '$current_month_start' AND '$current_month_end' AND status IN ('approved', 'completed')");
$monthly_appointments = $result ? $result->fetch_assoc()['total'] : 0;

$stats['weekly_growth'] = $monthly_appointments;

// Recent appointments
$recent_appointments = $conn->query("
    SELECT a.id, u.name as patient, t.name as therapist, a.appointment_date, a.status 
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN therapists t ON a.therapist_id = t.id
    ORDER BY a.appointment_date DESC LIMIT 5
");

// Recent patients
$recent_patients = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Top therapists 
$top_therapists = $conn->query("
    SELECT t.id, t.name, t.specialization, COUNT(a.id) as appointment_count 
    FROM therapists t
    LEFT JOIN appointments a ON t.id = a.therapist_id AND a.status != 'cancelled'
    WHERE t.status = 'approved'
    GROUP BY t.id
    ORDER BY appointment_count DESC
    LIMIT 3
");

// Pagination setup for pending therapists
$limit = 5; // Number of therapists per page
$page = isset($_GET['t_page']) ? (int)$_GET['t_page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Get total count of pending therapists
$total_result = $conn->query("SELECT COUNT(*) as total FROM therapists WHERE status = 'pending'");
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$pages = ceil($total / $limit);

// Fetch pending therapists with pagination and detailed information
$pending_therapists = $conn->query("
    SELECT id, name, specialization, email, license_number, qualification, experience, created_at 
    FROM therapists 
    WHERE status = 'pending' 
    ORDER BY created_at DESC 
    LIMIT $start, $limit
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
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
        
        /* Tables */
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
        
        .badge-success {
            background: rgba(40,167,69,0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(255,193,7,0.1);
            color: var(--warning);
        }
        
        .badge-danger {
            background: rgba(220,53,69,0.1);
            color: var(--danger);
        }
        
        .badge-info {
            background: rgba(23,162,184,0.1);
            color: var(--info);
        }
        
        /* Chart */
        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }
        
        /* Grid Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        /* Therapist Card */
        .therapist-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: var(--white);
            margin-bottom: 0.75rem;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .therapist-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .therapist-card img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-teal);
        }
        
        .therapist-info h4 {
            color: var(--peacock-blue);
            margin-bottom: 0.25rem;
        }
        
        .therapist-info p {
            color: var(--peacock-blue);
            opacity: 0.7;
            font-size: 0.8rem;
        }
        
        .therapist-stats {
            margin-left: auto;
            text-align: right;
        }
        
        .therapist-stats .count {
            font-weight: 700;
            color: var(--peacock-blue);
        }
        
        .therapist-stats .label {
            font-size: 0.7rem;
            color: var(--peacock-blue);
            opacity: 0.6;
        }
        
        /* Empty State */
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--peacock-blue);
            opacity: 0.6;
        }
        
        /* Pagination Styles */
        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .pagination-link {
            padding: 0.5rem 0.75rem;
            background: var(--light-gray);
            color: var(--peacock-blue);
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .pagination-link:hover, .pagination-link.active {
            background: var(--peacock-blue);
            color: var(--white);
        }
        
        /* Approve button */
        .btn-approve {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        /* Therapist Details Styles */
        .therapist-details {
            background: rgba(0, 78, 124, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--teal);
        }
        
        .therapist-details h4 {
            color: var(--peacock-blue);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .therapist-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: var(--peacock-blue);
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 0.9rem;
            color: var(--peacock-blue);
            font-weight: 500;
        }
        
        @media (max-width: 992px) {
            .dashboard {
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
            
            .therapist-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" alt="MindCare">
                <h1>MindCare Hub</h1>
            </div>
            
            <nav class="nav-menu">
                <a href="admin_dashboard.php" class="nav-item active"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="patients.php" class="nav-item"><i class="fas fa-users"></i> Patients</a>
                <a href="seetherapists.php" class="nav-item"><i class="fas fa-user-md"></i> Therapists</a>
                <a href="view_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="reports.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h2>Dashboard Overview</h2>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name'] ?? 'Admin') ?>&background=004e7c&color=fff" alt="Admin">
                    <div class="user-info">
                        <div><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                        <small>Administrator</small>
                    </div>
                </div>
            </header>
            
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users icon"></i>
                    <div class="title">Total Patients</div>
                    <div class="value"><?= number_format($stats['total_patients']) ?></div>
                    <div class="info">Registered users</div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-user-md icon"></i>
                    <div class="title">Active Therapists</div>
                    <div class="value"><?= number_format($stats['active_therapists']) ?></div>
                    <div class="info">Available for sessions</div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-calendar-day icon"></i>
                    <div class="title">Today's Appointments</div>
                    <div class="value"><?= number_format($stats['today_appointments']) ?></div>
                    <div class="info"><?= date('F j, Y') ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-chart-line icon"></i>
                    <div class="title">Current Month</div>
                   <div class="value"><?= number_format($stats['weekly_growth']) ?></div>
<div class="info">Monthly Appointments</div>
                </div>
            </div>
            
            <div class="main-grid">
                <div>
                    <!-- Recent Appointments -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Recent Appointments</div>
                            <a href="view_appointments.php" class="btn"><i class="fas fa-eye"></i> View All</a>
                        </div>
                        
                        <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Therapist</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $recent_appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['patient']) ?></td>
                                            <td><?= htmlspecialchars($row['therapist']) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['appointment_date'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $row['status'] === 'completed' ? 'success' : 
                                                    ($row['status'] === 'approved' ? 'info' : 
                                                    ($row['status'] === 'pending' ? 'warning' : 'danger')) 
                                                ?>">
                                                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">No recent appointments</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Performance Chart -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Monthly Performance</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Pending Therapists for Approval -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Pending Therapist Approval</div>
                            <a href="seetherapists.php" class="btn"><i class="fas fa-eye"></i> View All</a>
                        </div>
                        
                        <?php if ($pending_therapists && $pending_therapists->num_rows > 0): ?>
                            <?php while($therapist = $pending_therapists->fetch_assoc()): ?>
                                <div class="therapist-details">
                                    <h4><?= htmlspecialchars($therapist['name']) ?></h4>
                                    <div class="therapist-details-grid">
                                        <div class="detail-item">
                                            <span class="detail-label">Email</span>
                                            <span class="detail-value"><?= htmlspecialchars($therapist['email']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Specialization</span>
                                            <span class="detail-value"><?= htmlspecialchars($therapist['specialization']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">License Number</span>
                                            <span class="detail-value"><?= htmlspecialchars($therapist['license_number']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Qualification</span>
                                            <span class="detail-value"><?= htmlspecialchars($therapist['qualification']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Experience</span>
                                            <span class="detail-value"><?= htmlspecialchars($therapist['experience']) ?> years</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Applied On</span>
                                            <span class="detail-value"><?= date('M j, Y', strtotime($therapist['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div style="margin-top: 1rem; text-align: right;">
                                        <form action="approve_therapist.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="therapist_id" value="<?= $therapist['id'] ?>">
                                            <button type="submit" class="btn-approve" 
                                                    onclick="return confirm('Are you sure you want to approve <?= htmlspecialchars(addslashes($therapist['name'])) ?>?')">
                                                <i class="fas fa-check"></i> Approve Therapist
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            
                            <!-- Pagination Controls -->
                            <?php if ($pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?t_page=<?= $page - 1 ?>" class="pagination-link">&laquo; Previous</a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $pages; $i++): ?>
                                    <a href="?t_page=<?= $i ?>" class="pagination-link <?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $pages): ?>
                                    <a href="?t_page=<?= $page + 1 ?>" class="pagination-link">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="empty-state">No therapists pending approval</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <!-- Top Therapists -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Top Therapists</div>
                            <a href="seetherapists.php" class="btn"><i class="fas fa-eye"></i> View All</a>
                        </div>
                        
                        <?php if ($top_therapists && $top_therapists->num_rows > 0): ?>
                            <?php while($therapist = $top_therapists->fetch_assoc()): ?>
                                <div class="therapist-card">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($therapist['name']) ?>&background=77c9d4&color=fff" alt="<?= htmlspecialchars($therapist['name']) ?>">
                                    <div class="therapist-info">
                                        <h4><?= htmlspecialchars($therapist['name']) ?></h4>
                                        <p><?= htmlspecialchars($therapist['specialization']) ?></p>
                                    </div>
                                    <div class="therapist-stats">
                                        <div class="count"><?= $therapist['appointment_count'] ?></div>
                                        <div class="label">Sessions</div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No therapist data available</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Patients -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Recent Patients</div>
                            <a href="patients.php" class="btn"><i class="fas fa-eye"></i> View All</a>
                        </div>
                        
                        <?php if ($recent_patients && $recent_patients->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $recent_patients->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">No recent patients</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Treatment Plan -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Session Types</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="sessionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
<?php
// Fetch data for charts
$monthly_appointments = [];

// Initialize with only the session types that exist in your system
$session_types = ['In-Person' => 0, 'Teletherapy' => 0];

// Get monthly appointment counts
$result = $conn->query("
    SELECT DATE_FORMAT(appointment_date, '%b') as month, 
           COUNT(*) as count 
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
    GROUP BY MONTH(appointment_date), YEAR(appointment_date)
    ORDER BY YEAR(appointment_date), MONTH(appointment_date)
    LIMIT 7
");

while ($row = $result->fetch_assoc()) {
    $monthly_appointments[$row['month']] = $row['count'];
}

// Get only the session types that exist in your database
$result = $conn->query("
    SELECT session_type, COUNT(*) as count 
    FROM appointments 
    GROUP BY session_type
");

// Reset counts
$session_types = ['In-Person' => 0, 'Teletherapy' => 0];
while ($row = $result->fetch_assoc()) {
    $type = strtolower(trim($row['session_type']));
    if ($type === 'in-person' || $type === 'inperson') {
        $session_types['In-Person'] += $row['count'];
    } elseif ($type === 'teletherapy' || $type === 'tele-therapy' || $type === 'tele therapy') {
        $session_types['Teletherapy'] += $row['count'];
    }
}
?>
<script>
    // Performance Chart - Showing actual monthly appointments
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($monthly_appointments)) ?>,
            datasets: [{
                label: 'Appointments',
                data: <?= json_encode(array_values($monthly_appointments)) ?>,
                borderColor: '#004e7c',
                backgroundColor: 'rgba(0, 78, 124, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Appointments'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // Session Type Chart - Showing actual distribution
const sessionCtx = document.getElementById('sessionChart').getContext('2d');
new Chart(sessionCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(<?= json_encode($session_types) ?>),
        datasets: [{
            data: Object.values(<?= json_encode($session_types) ?>),
            backgroundColor: [
                '#004e7c', // Dark blue for In-Person
                '#00887a'  // Teal for Teletherapy
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    padding: 10,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const value = context.raw;
                        const percentage = Math.round((value / total) * 100);
                        return `${context.label}: ${value} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '65%'
    }
});
</script>
</body>
</html>
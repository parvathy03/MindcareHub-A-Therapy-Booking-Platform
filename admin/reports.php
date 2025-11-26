<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'mindcare');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data for reports
$report_data = [];

// Monthly appointments data
$result = $conn->query("
    SELECT DATE_FORMAT(appointment_date, '%b %Y') as month, 
           COUNT(*) as count 
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(appointment_date), YEAR(appointment_date)
    ORDER BY YEAR(appointment_date), MONTH(appointment_date)
");

$monthly_appointments = [];
$monthly_labels = [];
$monthly_counts = [];
while ($row = $result->fetch_assoc()) {
    $monthly_appointments[$row['month']] = $row['count'];
    $monthly_labels[] = $row['month'];
    $monthly_counts[] = $row['count'];
}

// Calculate percentage changes between months
$monthly_percentages = [];
$prev_count = null;
foreach ($monthly_counts as $count) {
    if ($prev_count !== null && $prev_count != 0) {
        $change = (($count - $prev_count) / $prev_count) * 100;
        $monthly_percentages[] = round($change, 1);
    } else {
        $monthly_percentages[] = 0;
    }
    $prev_count = $count;
}

// Session types data
$session_types = ['in-person' => 0, 'teletherapy' => 0];
$result = $conn->query("
    SELECT LOWER(session_type) as session_type, COUNT(*) as count 
    FROM appointments 
    WHERE LOWER(session_type) IN ('in-person', 'teletherapy')
    GROUP BY LOWER(session_type)
");

while ($row = $result->fetch_assoc()) {
    $session_types[$row['session_type']] = $row['count'];
}
$total_sessions = array_sum($session_types);

// Therapy type data (family, individual, teen, etc.)
$therapy_types = [
    'Individual Therapy' => 0,
    'Family Therapy' => 0,
    'Teen Therapy' => 0,
    'Couples Therapy' => 0,
    'Group Therapy' => 0
];

$result = $conn->query("
    SELECT therapy_type, COUNT(*) as count 
    FROM appointments 
    WHERE therapy_type IS NOT NULL
    GROUP BY therapy_type
");

// Reset counts with actual data from database
$therapy_types = [];
while ($row = $result->fetch_assoc()) {
    $therapy_types[$row['therapy_type']] = $row['count'];
}
$total_therapy_sessions = array_sum($therapy_types);

// Therapist performance
$therapist_performance = [];
$result = $conn->query("
    SELECT t.name, t.specialization, 
           COUNT(a.id) as appointments
    FROM therapists t
    LEFT JOIN appointments a ON t.id = a.therapist_id
    WHERE t.status = 'approved'
    GROUP BY t.id
    ORDER BY appointments DESC
    LIMIT 5
");

while ($row = $result->fetch_assoc()) {
    $therapist_performance[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare - Analytics Reports</title>
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
            padding: 1.5rem; /* Reduced from 2rem */
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem; /* Reduced from 2rem */
        }
        
        .header h2 {
            font-size: 1.5rem; /* Reduced from 1.75rem */
            color: var(--peacock-blue);
            font-weight: 600;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .user-profile img {
            width: 36px; /* Reduced from 42px */
            height: 36px; /* Reduced from 42px */
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--teal);
        }
        
        /* Report Cards */
        .report-card {
            background: var(--white);
            border-radius: 10px; /* Reduced from 12px */
            padding: 1rem; /* Reduced from 1.5rem */
            box-shadow: 0 3px 10px rgba(0,0,0,0.05); /* Lighter shadow */
            margin-bottom: 1.5rem; /* Reduced from 2rem */
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem; /* Reduced from 1.5rem */
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 0.75rem; /* Reduced from 1rem */
        }
        
        .report-title {
            font-size: 1.2rem; /* Reduced from 1.5rem */
            font-weight: 600;
            color: var(--peacock-blue);
        }
        
        .chart-container {
            height: 280px; /* Reduced from 400px */
            margin: 1rem 0; /* Reduced from 2rem */
        }
        
        .report-analysis {
            background: rgba(0,78,124,0.05);
            border-radius: 8px;
            padding: 1rem; /* Reduced from 1.5rem */
            margin-top: 1rem; /* Reduced from 1.5rem */
        }
        
        .analysis-title {
            font-size: 0.95rem; /* Reduced from 1.1rem */
            font-weight: 600;
            color: var(--peacock-blue);
            margin-bottom: 0.75rem; /* Reduced from 1rem */
        }
        
        .analysis-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Reduced from 250px */
            gap: 0.75rem; /* Reduced from 1.5rem */
        }
        
        .analysis-item {
            background: var(--white);
            padding: 0.75rem; /* Reduced from 1rem */
            border-radius: 6px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        
        .analysis-item h4 {
            color: var(--peacock-blue);
            margin-bottom: 0.25rem; /* Reduced from 0.5rem */
            font-size: 0.85rem; /* Reduced from 0.95rem */
        }
        
        .analysis-item p {
            font-size: 0.9rem; /* Reduced from 1.1rem */
            font-weight: 600;
            color: var(--teal);
        }
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem; /* Reduced from 0.85rem */
            margin-left: 0.3rem; /* Reduced from 0.5rem */
        }
        
        .trend-up {
            color: var(--success);
        }
        
        .trend-down {
            color: var(--danger);
        }
        
        .trend-neutral {
            color: var(--warning);
        }
        
        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem; /* Reduced from 1.5rem */
        }
        
        .data-table th, .data-table td {
            padding: 0.75rem; /* Reduced from 1rem */
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.85rem; /* Smaller font for table */
        }
        
        .data-table th {
            background: var(--light-gray);
            color: var(--peacock-blue);
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: rgba(0,78,124,0.03);
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem; /* Smaller stars */
        }
        
        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .analysis-content {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="patients.php" class="nav-item"><i class="fas fa-users"></i> Patients</a>
                <a href="seetherapists.php" class="nav-item"><i class="fas fa-user-md"></i> Therapists</a>
                <a href="view_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="reports.php" class="nav-item active"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h2>Analytics Reports</h2>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name'] ?? 'Admin') ?>&background=004e7c&color=fff" alt="Admin">
                    <div class="user-info">
                        <div><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                        <small>Administrator</small>
                    </div>
                </div>
            </header>
            
            <!-- Appointment Trends Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3 class="report-title">Appointment Trends</h3>
                </div>
                
                <div class="chart-container">
                    <canvas id="appointmentTrendsChart"></canvas>
                </div>
                
                <div class="report-analysis">
                    <h4 class="analysis-title">Key Insights</h4>
                    <div class="analysis-content">
                        <?php foreach ($monthly_appointments as $month => $count): ?>
                            <?php 
                            $index = array_search($month, array_keys($monthly_appointments));
                            $percentage = isset($monthly_percentages[$index]) ? $monthly_percentages[$index] : 0;
                            ?>
                            <div class="analysis-item">
                                <h4><?= $month ?></h4>
                                <p>
                                    <?= $count ?> appointments
                                    <?php if ($percentage != 0): ?>
                                        <span class="trend-indicator <?= $percentage > 0 ? 'trend-up' : 'trend-down' ?>">
                                            <?= abs($percentage) ?>%
                                            <i class="fas fa-arrow-<?= $percentage > 0 ? 'up' : 'down' ?>"></i>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Therapy Type Distribution -->
            <div class="report-card">
                <div class="report-header">
                    <h3 class="report-title">Therapy Type Distribution</h3>
                </div>
                
                <div class="chart-container">
                    <canvas id="therapyTypeChart"></canvas>
                </div>
                
                <div class="report-analysis">
                    <h4 class="analysis-title">Therapy Session Statistics</h4>
                    <div class="analysis-content">
                        <?php foreach ($therapy_types as $type => $count): ?>
                            <div class="analysis-item">
                                <h4><?= $type ?></h4>
                                <p>
                                    <?= $count ?> (<?= $total_therapy_sessions > 0 ? round(($count / $total_therapy_sessions) * 100) : 0 ?>%)
                                </p>
                            </div>
                        <?php endforeach; ?>
                        <div class="analysis-item">
                            <h4>Total Therapy Sessions</h4>
                            <p><?= $total_therapy_sessions ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Session Type Distribution -->
            <div class="report-card">
                <div class="report-header">
                    <h3 class="report-title">Session Format Distribution</h3>
                </div>
                
                <div class="chart-container">
                    <canvas id="sessionDistributionChart"></canvas>
                </div>
                
                <div class="report-analysis">
                    <h4 class="analysis-title">Session Format Statistics</h4>
                    <div class="analysis-content">
                       <?php foreach ($session_types as $type => $count): ?>
    <div class="analysis-item">
        <h4><?= ucfirst($type) ?> Sessions</h4>
        <p>
            <?= $count ?> (<?= $total_sessions > 0 ? round(($count / $total_sessions) * 100) : 0 ?>%)
        </p>
    </div>
<?php endforeach; ?>
                        <div class="analysis-item">
                            <h4>Total Sessions</h4>
                            <p><?= $total_sessions ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Therapist Performance -->
            <div class="report-card">
                <div class="report-header">
                    <h3 class="report-title">Top Therapists by Appointments</h3>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Therapist</th>
                            <th>Specialization</th>
                            <th>Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($therapist_performance as $therapist): ?>
                            <tr>
                                <td><?= htmlspecialchars($therapist['name']) ?></td>
                                <td><?= htmlspecialchars($therapist['specialization']) ?></td>
                                <td><?= $therapist['appointments'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Compact chart configuration
        const compactOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    bodyFont: {
                        size: 11
                    },
                    titleFont: {
                        size: 12
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        };

        // Appointment Trends Chart
        const trendsCtx = document.getElementById('appointmentTrendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($monthly_appointments)) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode(array_values($monthly_appointments)) ?>,
                    backgroundColor: '#004e7c',
                    borderColor: '#003a5d',
                    borderWidth: 1
                }]
            },
            options: {
                ...compactOptions,
                plugins: {
                    ...compactOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...compactOptions.plugins.tooltip,
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const percentage = <?= json_encode($monthly_percentages) ?>[index];
                                if (percentage !== 0) {
                                    return `Change: ${Math.abs(percentage)}% ${percentage > 0 ? '↑' : '↓'}`;
                                }
                                return '';
                            }
                        }
                    }
                }
            }
        });
        
        // Therapy Type Chart
        const therapyTypeCtx = document.getElementById('therapyTypeChart').getContext('2d');
        new Chart(therapyTypeCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($therapy_types)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($therapy_types)) ?>,
                    backgroundColor: [
                        '#004e7c',
                        '#00887a',
                        '#77c9d4',
                        '#a3e4d7',
                        '#4b9dbb'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...compactOptions,
                plugins: {
                    ...compactOptions.plugins,
                    tooltip: {
                        ...compactOptions.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Session Distribution Chart
        const sessionDistCtx = document.getElementById('sessionDistributionChart').getContext('2d');
        new Chart(sessionDistCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($session_types)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($session_types)) ?>,
                    backgroundColor: [
                        '#004e7c',
                        '#00887a'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...compactOptions,
                plugins: {
                    ...compactOptions.plugins,
                    tooltip: {
                        ...compactOptions.plugins.tooltip,
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
                cutout: '60%'
            }
        });
    </script>
</body>
</html>
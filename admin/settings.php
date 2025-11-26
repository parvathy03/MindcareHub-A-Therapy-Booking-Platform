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

// Initialize variables
$error = '';
$success = '';
$admin_data = [];

// Fetch admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT username, email FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Profile update
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Validate inputs
        if (empty($username) || empty($email)) {
            $error = "Username and email are required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email already exists (excluding current admin)
            $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already in use by another admin";
            } else {
                // Update profile
                $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $email, $admin_id);
                
                if ($stmt->execute()) {
                    $_SESSION['admin_name'] = $username;
                    $success = "Profile updated successfully";
                    // Refresh admin data
                    $admin_data['username'] = $username;
                    $admin_data['email'] = $email;
                } else {
                    $error = "Error updating profile: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $row['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $admin_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully";
                } else {
                    $error = "Error changing password: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error = "Current password is incorrect";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare - Admin Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Settings */
        .settings-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .settings-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .settings-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--peacock-blue);
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--peacock-blue);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--peacock-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,78,124,0.1);
        }
        
        .btn {
            background: var(--peacock-blue);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn:hover {
            background: var(--teal);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: rgba(40,167,69,0.1);
            color: var(--success);
            border: 1px solid rgba(40,167,69,0.2);
        }
        
        .alert-danger {
            background-color: rgba(220,53,69,0.1);
            color: var(--danger);
            border: 1px solid rgba(220,53,69,0.2);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--peacock-blue);
            opacity: 0.6;
        }
        
        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
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
                <a href="reports.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h2>Settings</h2>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name'] ?? 'Admin') ?>&background=004e7c&color=fff" alt="Admin">
                    <div class="user-info">
                        <div><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                        <small>Administrator</small>
                    </div>
                </div>
            </header>
            
            <div class="settings-container">
                <!-- Display success/error messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Profile Settings -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3 class="settings-title">Profile Settings</h3>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($admin_data['username'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3 class="settings-title">Change Password</h3>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group password-toggle">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('current_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('new_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
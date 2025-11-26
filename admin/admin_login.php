<?php
session_start();
$conn = new mysqli("localhost", "root","","mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = "";

// Handle admin login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $password = $_POST["password"];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginError = "Invalid email format";
    } 
    // Validate password length
    elseif (strlen($password) < 8) {
        $loginError = "Password must be at least 8 characters long";
    } 
    // Proceed with login if validation passes
    else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            if (password_verify($password, $admin["password"])) {
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_name"] = $admin["name"];
                $_SESSION["is_admin"] = true;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $loginError = "Incorrect password.";
            }
        } else {
            $loginError = "Admin not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindCare Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(120deg, #2980b9, #8e44ad, #6dd5fa, #ffffff);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes popup {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .login-card {
      background: white;
      border-radius: 10px;
      width: 100%;
      max-width: 400px;
      padding: 40px 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: visible;
      animation: popup 0.6s ease-in-out;
    }

    .login-card h1 {
      text-align: center;
      color: #d32f2f; /* Changed to red for admin distinction */
      margin-bottom: 10px;
      font-size: 35px;
    }

    .login-card p {
      text-align: center;
      color: #666;
      margin-bottom: 30px;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group i.form-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
    }

    .form-group input {
      width: 100%;
      padding: 12px 40px 12px 40px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
    }

    .form-group input:focus {
      outline: none;
      border-color: #6dd5fa;
      box-shadow: 0 0 0 2px rgba(109, 213, 250, 0.2);
    }

    .btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      font-weight: 600;
      color: white;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-login {
      background: #d32f2f; /* Changed to red for admin distinction */
    }

    .btn-login:hover {
      background: #b71c1c;
    }

    .message {
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }

    .error-msg {
      background: #ffebee;
      color: #d32f2f;
      border: 1px solid #ffcdd2;
    }

    .admin-badge {
      position: absolute;
      top: -10px;
      right: -10px;
      background: #d32f2f;
      color: white;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Password toggle styles */
    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
      z-index: 2;
    }

    .password-toggle:hover {
      color: #666;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="admin-badge" title="Admin Access">
      <i class="fas fa-shield-alt"></i>
    </div>
    <h1>MindCare Admin</h1>
    <p id="welcome-text">Administrator Access Only</p>

    <?php if ($loginError): ?>
      <div class="message error-msg"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>

    <!-- Admin Login Form -->
    <div id="login-form" class="form-container active">
      <form method="POST" action="">
        <div class="form-group">
          <i class="fas fa-envelope form-icon"></i>
          <input type="email" name="email" placeholder="admin@mindcare.com" required>
        </div>
        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input type="password" name="password" id="admin-password" placeholder="Admin Password" required>
          <i class="fas fa-eye password-toggle" id="admin-toggle"></i>
        </div>
        <button type="submit" name="login" class="btn btn-login">ADMIN SIGN IN</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const adminToggle = document.getElementById('admin-toggle');
      const adminPassword = document.getElementById('admin-password');
      
      // Admin password toggle
      adminToggle.addEventListener('click', function() {
        if (adminPassword.type === 'password') {
          adminPassword.type = 'text';
          adminToggle.classList.remove('fa-eye');
          adminToggle.classList.add('fa-eye-slash');
        } else {
          adminPassword.type = 'password';
          adminToggle.classList.remove('fa-eye-slash');
          adminToggle.classList.add('fa-eye');
        }
      });
    });
  </script>
</body>
</html>
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = $registerSuccess = $registerError = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $password = $_POST["password"];
    
    // Validate password length
    if (strlen($password) < 8) {
        $loginError = "Password must be at least 8 characters long";
    } else {
        $stmt = $conn->prepare("SELECT * FROM therapists WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($user["status"] !== "approved") {
                $loginError = "Account pending approval.";
            } elseif (password_verify($password, $user["password"])) {
                $_SESSION["therapist_id"] = $user["id"];
                $_SESSION["therapist_name"] = $user["name"];
                header("Location: therapist_dashboard.php");
                exit();
            } else {
                $loginError = "Incorrect password.";
            }
        } else {
            $loginError = "Therapist not found.";
        }
    }
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $name = $conn->real_escape_string(trim($_POST["reg_name"]));
    $email = $conn->real_escape_string(trim($_POST["reg_email"]));
    $password = $_POST["reg_password"];
    $license = $conn->real_escape_string(trim($_POST["reg_license"]));
    $specialization = $conn->real_escape_string(trim($_POST["reg_specialization"]));
    $qualification = $conn->real_escape_string(trim($_POST["reg_qualification"]));
    $experience = $conn->real_escape_string(trim($_POST["reg_experience"]));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Invalid email format";
    } 
    // Validate password length
    elseif (strlen($password) < 8) {
        $registerError = "Password must be at least 8 characters long";
    } 
    else {
        $stmt = $conn->prepare("SELECT id FROM therapists WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $registerError = "Email already registered";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO therapists (name, email, password, license_number, specialization, qualification, experience, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssssss", $name, $email, $hashedPassword, $license, $specialization, $qualification, $experience);

            if ($stmt->execute()) {
                $registerSuccess = "Registration successful! Your account is pending approval.";
            } else {
                $registerError = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindCare Login</title>
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

    .form-container.active {
      animation: popup 0.5s ease-in-out;
    }

    .login-card h1 {
      text-align: center;
      color:rgba(22, 115, 177, 0.77);
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
      background: #2980b9;
    }

    .btn-login:hover {
      background: #2472a3;
    }

    .btn-register {
      background: #6dd5fa;
    }

    .btn-register:hover {
      background: #5bc4e9;
    }

    .toggle-text {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #666;
    }

    .toggle-text a {
      color: #2980b9;
      text-decoration: none;
      font-weight: 600;
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

    .success-msg {
      background: #e8f5e9;
      color: #388e3c;
      border: 1px solid #c8e6c9;
    }

    .form-container {
      display: none;
    }

    .form-container.active {
      display: block;
    }

    .doctor-img {
      position: absolute;
      bottom:40px;
      left: -69px;
      width: 119px;
      z-index: 3;
      transition: all 0.3s ease-in-out;
    }

    .doctor-img:hover {
      transform: scale(1.05);
    }

    @media (max-width: 760px) {
      .doctor-img {
        display: none;
      }

      .login-card {
        padding: 30px 20px;
      }
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
    <img src="..\assets\Background.svg" class="doctor-img" alt="Doctor">

    <h1>MindCare</h1>
    <p id="welcome-text">Welcome back therapist</p>

    <?php if ($loginError): ?>
      <div class="message error-msg"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>

    <?php if ($registerSuccess): ?>
      <div class="message success-msg"><?php echo htmlspecialchars($registerSuccess); ?></div>
    <?php endif; ?>

    <?php if ($registerError): ?>
      <div class="message error-msg"><?php echo htmlspecialchars($registerError); ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <div id="login-form" class="form-container active">
      <form method="POST" action="">
        <div class="form-group">
          <i class="fas fa-envelope form-icon"></i>
          <input type="email" name="email" placeholder="therapist@example.com" required>
        </div>
        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input type="password" name="password" id="login-password" placeholder="Password (min. 8 characters)" required minlength="8">
          <i class="fas fa-eye password-toggle" id="login-toggle"></i>
        </div>
        <button type="submit" name="login" class="btn btn-login">SIGN IN</button>
      </form>

      <div class="toggle-text">
        Don't have an account? <a href="#" onclick="toggleForms(); return false;">Sign up</a>
      </div>
    </div>

    <!-- Registration Form -->
    <div id="register-form" class="form-container">
      <form method="POST" action="">
        <div class="form-group">
          <i class="fas fa-user form-icon"></i>
          <input type="text" name="reg_name" placeholder="Full Name" required>
        </div>
        <div class="form-group">
          <i class="fas fa-envelope form-icon"></i>
          <input type="email" name="reg_email" placeholder="therapist@example.com" required>
        </div>
        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input type="password" name="reg_password" id="register-password" placeholder="Password (min. 8 characters)" required minlength="8">
          <i class="fas fa-eye password-toggle" id="register-toggle"></i>
        </div>
        <div class="form-group">
          <i class="fas fa-id-card form-icon"></i>
          <input type="text" name="reg_license" placeholder="License Number" required>
        </div>
        <div class="form-group">
          <i class="fas fa-briefcase-medical form-icon"></i>
          <input type="text" name="reg_specialization" placeholder="Specialization" required>
        </div>
        <div class="form-group">
          <i class="fas fa-user-graduate form-icon"></i>
          <input type="text" name="reg_qualification" placeholder="Qualification (e.g., M.Sc Clinical Psychology)" required>
        </div>
        <div class="form-group">
          <i class="fas fa-business-time form-icon"></i>
          <input type="text" name="reg_experience" placeholder="Experience (e.g., 3 years)" required>
        </div>
        <button type="submit" name="register" class="btn btn-register">REGISTER</button>
      </form>

      <div class="toggle-text">
        Already have an account? <a href="#" onclick="toggleForms(); return false;">Sign in</a>
      </div>
    </div>
  </div>

  <script>
    function toggleForms() {
      const loginForm = document.getElementById('login-form');
      const registerForm = document.getElementById('register-form');
      const welcomeText = document.getElementById('welcome-text');

      if (loginForm.classList.contains('active')) {
        loginForm.classList.remove('active');
        registerForm.classList.add('active');
        welcomeText.textContent = 'Join MindCare community';
      } else {
        registerForm.classList.remove('active');
        loginForm.classList.add('active');
        welcomeText.textContent = 'Welcome back therapist';
      }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      const loginToggle = document.getElementById('login-toggle');
      const loginPassword = document.getElementById('login-password');
      
      const registerToggle = document.getElementById('register-toggle');
      const registerPassword = document.getElementById('register-password');
      
      // Login form toggle
      loginToggle.addEventListener('click', function() {
        if (loginPassword.type === 'password') {
          loginPassword.type = 'text';
          loginToggle.classList.remove('fa-eye');
          loginToggle.classList.add('fa-eye-slash');
        } else {
          loginPassword.type = 'password';
          loginToggle.classList.remove('fa-eye-slash');
          loginToggle.classList.add('fa-eye');
        }
      });
      
      // Registration form toggle
      registerToggle.addEventListener('click', function() {
        if (registerPassword.type === 'password') {
          registerPassword.type = 'text';
          registerToggle.classList.remove('fa-eye');
          registerToggle.classList.add('fa-eye-slash');
        } else {
          registerPassword.type = 'password';
          registerToggle.classList.remove('fa-eye-slash');
          registerToggle.classList.add('fa-eye');
        }
      });
    });
  </script>
</body>
</html>
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$therapist_id = $_SESSION['therapist_id'] ?? null;

if (!$therapist_id) {
    header("Location: login.php");
    exit;
}

$updateSuccess = "";
$updateError = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM therapists WHERE id = ?");
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $updateError = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $updateError = "New password and confirmation do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE therapists SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $therapist_id);
        if ($stmt->execute()) {
            $updateSuccess = "Password changed successfully!";
        } else {
            $updateError = "Failed to update password.";
        }
    }
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $profile_picture = $user['profile_picture'] ?? null;

    // Handle file upload
    if (!empty($_FILES['profile_image']['name'])) {
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads/profile_picture')) {
            mkdir('uploads/profile_picture', 0755, true);
        }

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('profile_') . '.' . $ext;
        $target = "uploads/profile_picture/" . $image_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            // Delete old image if it exists
            if (!empty($profile_picture) && file_exists($profile_picture)) {
                @unlink($profile_picture);
            }
            $profile_picture = $target;
        } else {
            $updateError = "Failed to upload image.";
        }
    }

    // Update database
    $stmt = $conn->prepare("UPDATE therapists SET name=?, specialization=?, profile_picture=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $specialization, $profile_picture, $therapist_id);
    
    if ($stmt->execute()) {
        $updateSuccess = "Profile updated successfully!";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM therapists WHERE id = ?");
        $stmt->bind_param("i", $therapist_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $updateError = "Failed to update profile: " . $conn->error;
    }
}

$siteName = "MindCare Hub";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Therapist Settings | <?php echo $siteName; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #6c757d;
            --accent-color: #7ED321;
            --light-bg: #F8F9FA;
            --dark-bg: #2C3E50;
            --text-color: #333;
            --light-text: #fff;
            --border-color: #E0E0E0;
            --card-shadow: rgba(0,0,0,0.08);
            --border-radius: 12px;
            --spacing: 16px;
            --transition: 0.4s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background: var(--light-bg);
            color: var(--text-color);
            min-height: 100vh;
            position: relative;
        }
        
        header {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #3a5068 100%);
            color: var(--light-text);
            padding: calc(var(--spacing)*1.2) calc(var(--spacing)*2);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2), inset 0 0 0 1px rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Lora', serif;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--light-text);
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform var(--transition) ease, color var(--transition) ease;
        }
        
        .logo:hover {
            transform: scale(1.03) translateY(-2px);
            color: var(--accent-color);
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .settings-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 6px 20px var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: transform var(--transition) ease, box-shadow var(--transition) ease;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.18);
        }
        
        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), #6DD5ED 50%, var(--accent-color));
            color: var(--light-text);
            padding: 1.5rem;
            text-align: center;
        }
        
        .settings-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-family: 'Playfair Display', serif;
        }
        
        .settings-body {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-bg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Playfair Display', serif;
        }
        
        .section-title i {
            font-size: 1.1rem;
            color: var(--accent-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-bg);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(126,211,33,0.2);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--accent-color), #6EB91F);
            color: var(--dark-bg);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition) ease;
            box-shadow: 0 4px 15px rgba(126,211,33,0.3);
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #6EB91F, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(126,211,33,0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn i {
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .alert.success {
            background-color: rgba(76, 201, 240, 0.1);
            color: #0a7c4a;
        }
        
        .alert.success::before {
            background-color: #4cc9f0;
        }
        
        .alert.error {
            background-color: rgba(247, 37, 133, 0.1);
            color: #d32f2f;
        }
        
        .alert.error::before {
            background-color: #d32f2f;
        }
        
        .profile-image-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        
        .profile-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(114, 9, 183, 0.3);
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            background: linear-gradient(135deg, var(--primary-color), #6DD5ED);
            color: var(--light-text);
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .file-upload-label:hover {
            background: linear-gradient(135deg, #6DD5ED, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(74, 144, 226, 0.3), transparent);
            margin: 2rem 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .settings-body {
                padding: 1.5rem;
            }
            
            .profile-image-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        /* Fade in animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeIn 0.9s ease-out forwards;
        }
        
        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo"><?php echo $siteName; ?></div>
        <a href="therapist_dashboard.php" style="color: white; margin-left: 1rem;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </header>
    
    <div class="container">
        <div class="settings-card fade-in">
            <div class="settings-header">
                <h2>Account Settings</h2>
                <p>Manage your profile and security settings</p>
            </div>
            
            <div class="settings-body">
                <?php if ($updateSuccess): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i> <?php echo $updateSuccess; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($updateError): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $updateError; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Update -->
                <form method="POST" enctype="multipart/form-data">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Profile Information
                    </h3>
                    
                    <div class="profile-image-container">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?= $user['profile_picture'] ?>" alt="Profile" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image" style="background: linear-gradient(135deg, var(--primary-color), #6DD5ED); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="font-size: 2rem; color: white;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <i class="fas fa-camera"></i> Change Photo
                                <input type="file" name="profile_image" class="file-upload-input" accept="image/*">
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" name="specialization" id="specialization" 
                               class="form-control" value="<?= htmlspecialchars($user['specialization']) ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>

                <div class="divider"></div>

                <!-- Change Password -->
                <form method="POST">
                    <h3 class="section-title">
                        <i class="fas fa-lock"></i> Change Password
                    </h3>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" 
                               class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               class="form-control" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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
        }
        .book-btn:hover {
            background: #6EB91F;
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
    </style>
</head>
<body>
    <header>
        <div class="logo">MindCare Hub</div>
        <div><a href="userpage.php" style="color: white;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
    </header>

    <div class="container">
        <h1>Our Approved Therapists</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="therapists-grid">
                <?php while ($therapist = $result->fetch_assoc()): ?>
                    <div class="therapist-card">
                       <?php if (!empty($therapist['profile_picture'])): ?>
                       <img src="../therapist/<?= htmlspecialchars($therapist['profile_picture']) ?>?v=<?= time() ?>" 
                       alt="<?= htmlspecialchars($therapist['name']) ?>" class="therapist-img">
        <?php else: ?>
        <img src="https://ui-avatars.com/api/..." class="therapist-img">
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
                                <?= htmlspecialchars($therapist['experience']) ?> experience
                            </div>
                            <a href="userpage.php?therapy_type=<?= urlencode($therapist['specialization']) ?>#book" class="book-btn">
                                <i class="fas fa-calendar-check"></i> Book Session
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-therapists">
                <p>Currently, there are no approved therapists available.</p>
                <p>Please check back later or contact our support team.</p>
                <a href="userpage.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Return to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>
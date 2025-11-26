<?php
session_start();
// Prevent caching of therapist pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['therapist_id'])) {
    header("Location: therapist_login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "mindcare");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Define limits
$TITLE_LIMIT = 255;
$CONTENT_LIMIT = 5000;

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $therapist_id = $_SESSION['therapist_id'];

    // Validate limits
    if (strlen($title) > $TITLE_LIMIT) {
        $msg = "❌ Title must be less than $TITLE_LIMIT characters (currently: " . strlen($title) . ")";
    } elseif (strlen($content) > $CONTENT_LIMIT) {
        $msg = "❌ Content must be less than $CONTENT_LIMIT characters (currently: " . strlen($content) . ")";
    } else {
        // Check if status column exists
        $check_status = $conn->query("SHOW COLUMNS FROM articles LIKE 'status'");
        $has_status = ($check_status->num_rows > 0);
        
        // Check if author column exists
        $check_author = $conn->query("SHOW COLUMNS FROM articles LIKE 'author'");
        $has_author = ($check_author->num_rows > 0);
        
        // Get therapist name for author field
        $therapist_query = $conn->prepare("SELECT name FROM therapists WHERE id = ?");
        $therapist_query->bind_param("i", $therapist_id);
        $therapist_query->execute();
        $therapist_result = $therapist_query->get_result();
        $therapist = $therapist_result->fetch_assoc();
        $author_name = $therapist['name'] ?? 'Therapist';

        if ($has_status && $has_author) {
            $sql = "INSERT INTO articles (title, category, content, status, author) 
                    VALUES ('$title', '$category', '$content', 'approved', '$author_name')";
        } else if ($has_status) {
            $sql = "INSERT INTO articles (title, category, content, status) 
                    VALUES ('$title', '$category', '$content', 'approved')";
        } else if ($has_author) {
            $sql = "INSERT INTO articles (title, category, content, author) 
                    VALUES ('$title', '$category', '$content', '$author_name')";
        } else {
            $sql = "INSERT INTO articles (title, category, content) 
                    VALUES ('$title', '$category', '$content')";
        }

        if (mysqli_query($conn, $sql)) {
            $msg = "✅ Article submitted successfully!";
            // Clear form fields after successful submission
            $_POST['title'] = '';
            $_POST['category'] = '';
            $_POST['content'] = '';
        } else {
            $msg = "❌ Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Write Article - MindCare</title>
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
            width: 100%;
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
        }
        
        .user-profile img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--teal);
        }
        
        /* Article Form Container */
        .article-container {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--peacock-blue);
        }
        
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        input[type="text"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--peacock-blue);
            box-shadow: 0 0 0 3px rgba(0,78,124,0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 200px;
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            font-weight: 500;
        }
        
        .message.success {
            background: rgba(40,167,69,0.1);
            color: var(--success);
            border: 1px solid rgba(40,167,69,0.2);
        }
        
        .message.error {
            background: rgba(220,53,69,0.1);
            color: var(--danger);
            border: 1px solid rgba(220,53,69,0.2);
        }
        
        /* Character Counter Styles */
        .char-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .char-counter.near-limit {
            color: var(--warning);
            font-weight: bold;
        }
        
        .char-counter.over-limit {
            color: var(--danger);
            font-weight: bold;
        }
        
        .limit-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--peacock-blue);
        }
        
        .limit-info h3 {
            margin-top: 0;
            color: var(--peacock-blue);
        }
        
        @media (max-width: 992px) {
            body {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" alt="MindCare Logo">
            <h1>MindCare Hub</h1>
        </div>
        <nav class="nav-menu">
            <a href="therapist_dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="my_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> Articles</a>
            <a href="write_article.php" class="nav-item active"><i class="fas fa-pen-nib"></i> Write Article</a>
            <a href="reports.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h2>Write a New Article</h2>
            <div class="user-profile">
                <?php
                // Get therapist name for profile
                $therapist_id = $_SESSION['therapist_id'];
                $stmt = $conn->prepare("SELECT name FROM therapists WHERE id = ?");
                $stmt->bind_param("i", $therapist_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $therapist = $result->fetch_assoc();
                ?>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($therapist['name']); ?>&background=004e7c&color=fff" alt="Therapist">
                <div class="user-info">
                    <div><?php echo htmlspecialchars($therapist['name']); ?></div>
                    <small>Therapist</small>
                </div>
            </div>
        </header>

        <div class="article-container">
            <!-- Writing Limits Info -->
            <div class="limit-info">
                <h3>📝 Writing Limits</h3>
                <p><strong>Title:</strong> Maximum <?php echo $TITLE_LIMIT; ?> characters</p>
                <p><strong>Content:</strong> Maximum <?php echo $CONTENT_LIMIT; ?> characters</p>
            </div>

            <form method="post" action="" id="articleForm">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                           maxlength="<?php echo $TITLE_LIMIT; ?>" required>
                    <div class="char-counter" id="titleCounter">
                        <?php echo isset($_POST['title']) ? strlen($_POST['title']) : '0'; ?>/<?php echo $TITLE_LIMIT; ?> characters
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category">
                        <option value="Mental Health" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Mental Health') ? 'selected' : ''; ?>>Mental Health</option>
                        <option value="Therapy Tips" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Therapy Tips') ? 'selected' : ''; ?>>Therapy Tips</option>
                        <option value="Anxiety" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Anxiety') ? 'selected' : ''; ?>>Anxiety</option>
                        <option value="Depression" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Depression') ? 'selected' : ''; ?>>Depression</option>
                        <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" required maxlength="<?php echo $CONTENT_LIMIT; ?>"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <div class="char-counter" id="contentCounter">
                        <?php echo isset($_POST['content']) ? strlen($_POST['content']) : '0'; ?>/<?php echo $CONTENT_LIMIT; ?> characters
                    </div>
                </div>

                <button type="submit" class="btn">Publish Article</button>
            </form>

            <?php if ($msg): ?>
                <div class="message <?php echo strpos($msg, '❌') !== false ? 'error' : 'success'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Character limit constants
        const TITLE_LIMIT = <?php echo $TITLE_LIMIT; ?>;
        const CONTENT_LIMIT = <?php echo $CONTENT_LIMIT; ?>;

        function updateCounter(element, counter, limit) {
            const length = element.value.length;
            counter.textContent = `${length}/${limit} characters`;
            
            // Update styling based on remaining characters
            const remaining = limit - length;
            counter.className = 'char-counter';
            
            if (remaining < 50) {
                counter.classList.add('near-limit');
            }
            if (remaining < 0) {
                counter.classList.add('over-limit');
            }
        }

        // Title counter
        const titleInput = document.getElementById('title');
        const titleCounter = document.getElementById('titleCounter');
        
        titleInput.addEventListener('input', function() {
            updateCounter(titleInput, titleCounter, TITLE_LIMIT);
        });

        // Content counter
        const contentInput = document.getElementById('content');
        const contentCounter = document.getElementById('contentCounter');
        
        contentInput.addEventListener('input', function() {
            updateCounter(contentInput, contentCounter, CONTENT_LIMIT);
        });

        // Form validation
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const titleLength = titleInput.value.length;
            const contentLength = contentInput.value.length;
            
            if (titleLength > TITLE_LIMIT) {
                e.preventDefault();
                alert(`Title is too long! Maximum ${TITLE_LIMIT} characters allowed.`);
                titleInput.focus();
                return false;
            }
            
            if (contentLength > CONTENT_LIMIT) {
                e.preventDefault();
                alert(`Content is too long! Maximum ${CONTENT_LIMIT} characters allowed.`);
                contentInput.focus();
                return false;
            }
        });

        // Prevent page from being cached by the browser
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</body>
</html>
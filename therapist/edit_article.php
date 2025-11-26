<?php
$conn = mysqli_connect("localhost", "root", "", "mindcare");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Define limits
$TITLE_LIMIT = 255;
$CONTENT_LIMIT = 5000;

$msg = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM articles WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $article = mysqli_fetch_assoc($result);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);

        // Validate limits
        if (strlen($title) > $TITLE_LIMIT) {
            $msg = "❌ Title must be less than $TITLE_LIMIT characters (currently: " . strlen($title) . ")";
        } elseif (strlen($content) > $CONTENT_LIMIT) {
            $msg = "❌ Content must be less than $CONTENT_LIMIT characters (currently: " . strlen($content) . ")";
        } else {
            $update = "UPDATE articles SET title='$title', category='$category', content='$content' WHERE id=$id";

            if (mysqli_query($conn, $update)) {
                $msg = "✅ Article updated successfully!";
                // Refresh data
                $result = mysqli_query($conn, $sql);
                $article = mysqli_fetch_assoc($result);
            } else {
                $msg = "❌ Error: " . mysqli_error($conn);
            }
        }
    }
} else {
    header("Location: articles.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Article - MindCare</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #fdfbfb, #ebedee);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 700px;
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #3f37c9;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.9rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background: #4361ee;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 50px;
            margin-top: 1rem;
        }

        input[type="submit"]:hover {
            background: #3a4dcf;
        }

        .message {
            text-align: center;
            font-weight: 500;
            margin-top: 1rem;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .char-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .char-counter.near-limit {
            color: #ff9800;
            font-weight: bold;
        }

        .char-counter.over-limit {
            color: #f44336;
            font-weight: bold;
        }

        .limit-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #4361ee;
        }

        .limit-info h3 {
            margin-top: 0;
            color: #4361ee;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Article</h2>
        
        <div class="limit-info">
            <h3>📝 Writing Limits</h3>
            <p><strong>Title:</strong> Maximum <?php echo $TITLE_LIMIT; ?> characters</p>
            <p><strong>Content:</strong> Maximum <?php echo $CONTENT_LIMIT; ?> characters</p>
        </div>

        <form method="post" id="articleForm">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" 
                   value="<?php echo htmlspecialchars($article['title']); ?>" 
                   maxlength="<?php echo $TITLE_LIMIT; ?>" required>
            <div class="char-counter" id="titleCounter">
                <?php echo strlen($article['title']); ?>/<?php echo $TITLE_LIMIT; ?> characters
            </div>

            <label for="category">Category</label>
            <select name="category">
                <option value="Mental Health" <?php if ($article['category'] == 'Mental Health') echo 'selected'; ?>>Mental Health</option>
                <option value="Therapy Tips" <?php if ($article['category'] == 'Therapy Tips') echo 'selected'; ?>>Therapy Tips</option>
                <option value="Anxiety" <?php if ($article['category'] == 'Anxiety') echo 'selected'; ?>>Anxiety</option>
                <option value="Depression" <?php if ($article['category'] == 'Depression') echo 'selected'; ?>>Depression</option>
                <option value="Other" <?php if ($article['category'] == 'Other') echo 'selected'; ?>>Other</option>
            </select>

            <label for="content">Content</label>
            <textarea name="content" id="content" required maxlength="<?php echo $CONTENT_LIMIT; ?>"><?php echo htmlspecialchars($article['content']); ?></textarea>
            <div class="char-counter" id="contentCounter">
                <?php echo strlen($article['content']); ?>/<?php echo $CONTENT_LIMIT; ?> characters
            </div>

            <input type="submit" value="Update Article">
        </form>
        
        <?php if ($msg): ?>
            <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
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
    </script>
</body>
</html>
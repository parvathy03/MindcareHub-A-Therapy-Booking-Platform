<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch article details
$sql = "SELECT * FROM articles WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Article not found
    header("Location: index.php");
    exit();
}

$article = $result->fetch_assoc();
$conn->close();

$pageTitle = $article['title'] . " - MindCare Hub";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $pageTitle; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Montserrat', sans-serif;
      background: var(--light-bg);
      color: var(--text-color);
      line-height: 1.6;
    }
    
    header {
      background: linear-gradient(135deg, var(--dark-bg) 0%, #3a5068 100%);
      color: var(--light-text);
      padding: calc(var(--spacing)*1.2) calc(var(--spacing)*2);
      box-shadow: 0 6px 20px rgba(0,0,0,0.2), inset 0 0 0 1px rgba(255,255,255,0.05);
      position: sticky; 
      top: 0; 
      z-index: 1000;
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
    
    .back-btn {
      color: var(--light-text);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
      transition: opacity var(--transition);
    }
    
    .back-btn:hover {
      opacity: 0.8;
    }
    
    .container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 calc(var(--spacing)*2);
    }
    
    .article-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .article-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      color: var(--dark-bg);
      margin-bottom: 1rem;
      line-height: 1.2;
    }
    
    .article-meta {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 1rem;
      color: var(--secondary-color);
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .article-meta div {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .category-badge {
      background: var(--primary-color);
      color: white;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .article-content {
      background: white;
      border-radius: var(--border-radius);
      padding: 2rem;
      box-shadow: 0 4px 15px var(--card-shadow);
      line-height: 1.8;
      font-size: 1.05rem;
    }
    
    .article-content p {
      margin-bottom: 1.5rem;
    }
    
    .author-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border-color);
    }
    
    .author-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--primary-color);
    }
    
    .author-details h4 {
      margin-bottom: 0.3rem;
      color: var(--dark-bg);
    }
    
    .author-details p {
      color: var(--secondary-color);
      font-size: 0.9rem;
      margin: 0;
    }
    
    footer {
      background: var(--dark-bg);
      color: var(--light-text);
      text-align: center;
      padding: calc(var(--spacing)*4) 0;
      border-top: 1px solid rgba(255,255,255,0.1);
      margin-top: 3rem;
    }
    
    footer p {
      margin: 0;
      font-size: 0.95rem;
      opacity: 0.9;
    }
    
    @media (max-width: 768px) {
      .article-title {
        font-size: 2rem;
      }
      
      .article-meta {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
      }
      
      .author-info {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">MindCare Hub</div>
    <a href="index.php#blog" class="back-btn">
    </a>
  </header>

  <div class="container">
    <div class="article-header">
      <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
      <div class="article-meta">
        <div>
          <i class="far fa-calendar"></i>
          <span><?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
        </div>
        <div>
          <i class="far fa-clock"></i>
          <span>5 min read</span>
        </div>
      </div>
      <div class="category-badge"><?php echo htmlspecialchars($article['category']); ?></div>
    </div>
    
    <div class="article-content">
      <?php echo nl2br(htmlspecialchars($article['content'])); ?>
      
      <?php if (!empty($article['author'])): ?>
      <div class="author-info">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($article['author']); ?>&background=4A90E2&color=fff" alt="Author" class="author-avatar">
        <div class="author-details">
          <h4><?php echo htmlspecialchars($article['author']); ?></h4>
          <p>Article Author</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> MindCare Hub. All rights reserved.</p>
    <p>Promoting emotional well‑being, one connection at a time.</p>
  </footer>
</body>
</html>
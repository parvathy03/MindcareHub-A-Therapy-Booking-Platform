<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check if articles table has status column
$check_status_column = $conn->query("SHOW COLUMNS FROM articles LIKE 'status'");
$has_status_column = ($check_status_column->num_rows > 0);

// Fetch all articles (with or without status filter)
if ($has_status_column) {
    $articles_sql = "SELECT * FROM articles WHERE status='approved' ORDER BY created_at DESC";
} else {
    $articles_sql = "SELECT * FROM articles ORDER BY created_at DESC";
}
$articles_result = $conn->query($articles_sql);

$siteName = "MindCare Hub";
$pageTitle = "$siteName - Blog: Resources for Mental Wellness";
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
    
    nav ul { 
      display: flex; 
      list-style: none; 
      margin: 0; 
      padding: 0; 
    }
    
    nav ul li { 
      margin-left: calc(var(--spacing)*2.5); 
    }
    
    nav ul li a { 
      font-weight: 500; 
      padding: 5px 0; 
      position: relative; 
      color: var(--light-text);
      text-decoration: none;
    }
    
    nav ul li a::after { 
      content:''; 
      position: absolute; 
      width:0; 
      height:2px; 
      bottom:-5px; 
      left:0; 
      background: var(--primary-color); 
      transition: width var(--transition) ease; 
    }
    
    nav ul li a:hover::after, 
    nav ul li a.active::after { 
      width:100%; 
      background: var(--accent-color);
    }
    
    nav ul li a:hover { 
      transform: translateY(-3px); 
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
      max-width: 1200px;
      margin: 0 auto;
      padding: calc(var(--spacing)*2);
    }
    
    .page-header {
      text-align: center;
      margin: 3rem 0;
    }
    
    .page-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.8rem;
      font-weight: 700;
      position: relative;
      margin-bottom: 1rem;
    }
    
    .page-title::after {
      content: '';
      width: 80px;
      height: 4px;
      background: var(--accent-color);
      display: block;
      margin: var(--spacing) auto 0;
      border-radius: 2px;
    }
    
    .page-description {
      max-width: 700px;
      margin: 0 auto 2rem;
      font-size: 1.1rem;
      color: var(--secondary-color);
    }
    
    .filter-tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .filter-tab {
      padding: 0.8rem 1.5rem;
      background: white;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 500;
      transition: all var(--transition);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .filter-tab.active,
    .filter-tab:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }
    
    .content-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }
    
    .blog-card {
      background: white;
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: 0 6px 20px var(--card-shadow);
      transition: transform var(--transition) ease, box-shadow var(--transition) ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .blog-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.18);
    }
    
    .card-header {
      padding: 1.5rem 1.5rem 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }
    
    .card-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--light-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 1.2rem;
    }
    
    .card-category {
      color: var(--primary-color);
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .card-content {
      padding: 0 1.5rem 1.5rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    
    .card-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      margin-bottom: 1rem;
      color: var(--dark-bg);
    }
    
    .card-excerpt {
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      flex-grow: 1;
    }
    
    .card-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      font-size: 0.85rem;
      color: var(--secondary-color);
    }
    
    .card-date {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .read-more {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: color var(--transition);
    }
    
    .read-more:hover {
      color: var(--accent-color);
    }
    
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem;
      background: white;
      border-radius: var(--border-radius);
      box-shadow: 0 4px 15px var(--card-shadow);
    }
    
    .empty-state i {
      font-size: 3rem;
      color: var(--secondary-color);
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .empty-state h3 {
      margin-bottom: 1rem;
      color: var(--dark-bg);
    }
    
    .empty-state p {
      color: var(--secondary-color);
      max-width: 500px;
      margin: 0 auto;
    }
    
    footer {
      background: var(--dark-bg);
      color: var(--light-text);
      text-align: center;
      padding: calc(var(--spacing)*4) 0;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    footer p {
      margin: 0;
      font-size: 0.95rem;
      opacity: 0.9;
    }
    
    footer .social-links {
      margin-top: 20px;
    }
    
    footer .social-links a {
      color: var(--light-text);
      margin: 0 10px;
      font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
      
      nav ul {
        flex-direction: column;
        gap: 1rem;
      }
      
      nav ul li {
        margin-left: 0;
      }
      
      header {
        flex-direction: column;
        gap: 1rem;
      }
      
      .page-title {
        font-size: 2.2rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo"><?php echo $siteName; ?></div>
    <a href="index.php" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>
  </header>

  <div class="container">
    <div class="page-header">
      <h1 class="page-title">MindCare Blog</h1>
      <p class="page-description">Explore our collection of mental health articles from our licensed therapists.</p>
   
    </div>
    
    <div class="content-grid" id="content-grid">
      <?php
      $has_content = false;
      
      // Display articles
      if ($articles_result && $articles_result->num_rows > 0) {
        $has_content = true;
        while ($article = $articles_result->fetch_assoc()) {
          $excerpt = strlen($article['content']) > 150 ? substr($article['content'], 0, 150) . '...' : $article['content'];
          $date = date('F j, Y', strtotime($article['created_at']));
          $category = isset($article['category']) ? $article['category'] : 'Uncategorized';
          ?>
          <div class="blog-card" data-type="article" data-category="<?php echo htmlspecialchars($category); ?>">
            <div class="card-header">
              <div class="card-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <span class="card-category"><?php echo htmlspecialchars($category); ?></span>
            </div>
            <div class="card-content">
              <h3 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h3>
              <p class="card-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
              <div class="card-meta">
                <div class="card-date">
                  <i class="far fa-calendar"></i>
                  <span><?php echo $date; ?></span>
                </div>
                <a href="view_article.php?id=<?php echo $article['id']; ?>" class="read-more">Read More</a>
              </div>
            </div>
          </div>
          <?php
        }
      }
      
      if (!$has_content) {
        // Debug information - check what's in the database
        $debug_articles = $conn->query("SELECT COUNT(*) as count FROM articles");
        
        $article_count = $debug_articles->fetch_assoc()['count'];
        ?>
        <div class="empty-state">
          <i class="fas fa-file-alt"></i>
          <h3>No articles available yet</h3>
          <p>Our therapists are preparing valuable resources. Check back soon for articles on mental wellness.</p>
          <p style="font-size: 0.9rem; margin-top: 1rem;">
            Database info: <?php echo $article_count; ?> articles found.
            <?php if ($article_count > 0): ?>
              <br>Articles might not be approved yet or there may be a database issue.
            <?php endif; ?>
          </p>
        </div>
        <?php
      }
      ?>
    </div>
  </div>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> MindCare Hub. All rights reserved.</p>
    <p>Promoting emotional well‑being, one connection at a time.</p>
    <div class="social-links">
      <a href="#"><i class="fab fa-facebook"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
  </footer>

  <script>
    // Filter functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Update active tab
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        const filter = tab.getAttribute('data-filter');
        const cards = document.querySelectorAll('.blog-card');
        
        cards.forEach(card => {
          if (filter === 'all') {
            card.style.display = 'flex';
          } else {
            // For category filtering, you would need to implement this based on your categories
            card.style.display = 'flex'; // Placeholder - implement category filtering as needed
          }
        });
      });
    });
  </script>
</body>
</html>

<?php
$conn->close();
?>
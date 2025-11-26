<?php
$siteName = "MindCare Hub";
$pageTitle = "$siteName - Your Digital Wellness Solution";
$heroHeading = "MindCare Hub: Your Journey to Mental Well-being Starts Here";

// Database connection
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all approved therapists
$sql = "SELECT * FROM therapists WHERE status='approved' ORDER BY name ASC";
$therapists_result = $conn->query($sql);
//navigation links
function generateNavLinks() {
    $links = [
        ['href' => '#home', 'text' => 'Home'],
        ['href' => 'blog.php', 'text' => 'Blog'],
        ['href' => '#therapy-options', 'text' => 'Therapy Options'],
        ['href' => '#testimonials', 'text' => 'Success Stories'],
        ['href' => '#team', 'text' => 'Our Experts'],
        ['href' => '#login', 'text' => 'Logins'],
        ['href' => '#contact', 'text' => 'Contact'],
    ];
    $out = '';
    foreach ($links as $link) {
        $out .= '<li><a href="'.$link['href'].'">'.$link['text'].'</a></li>';
    }
    return $out;
}
//success stories
function generateTestimonials() {
    $testimonials = [
        ['name' => 'Jaison Peter', 'role' => 'Software Engineer', 'quote' => 'After just 3 months with MindCare Hub, my anxiety attacks reduced by 80%. The coping techniques my therapist taught me changed my life.', 'rating' => 5, 'img' => 'assets/testimonial1.jpg'],
        ['name' => 'Aaisha Basith', 'role' => 'College Student', 'quote' => 'I went from barely attending classes to graduating with honors. The teen counseling program gave me tools to manage stress and build confidence.', 'rating' => 5, 'img' => 'assets/testimonial22.jpg'],
        ['name' => 'The Kapoor Family', 'role' => '', 'quote' => 'Family therapy saved our relationships. We learned healthy communication skills that brought us closer than ever before.', 'rating' => 5, 'img' => 'assets/testimonial3.jpg'],
    ];
    $out = '';
    foreach ($testimonials as $t) {
        $stars = str_repeat('<i class="fas fa-star" style="color:gold;"></i>', $t['rating']);
        $out .= '<div class="card fade-in testimonial-card">
                    <div class="testimonial-img" style="background-image:url('.$t['img'].')"></div>
                    <div class="testimonial-content">
                        <p class="testimonial-quote">"'.$t['quote'].'"</p>
                        <div class="testimonial-footer">
                            <div>
                                <strong>'.$t['name'].'</strong><br>
                                <span class="testimonial-role">'.$t['role'].'</span>
                            </div>
                            <div class="testimonial-rating">'.$stars.'</div>
                        </div>
                    </div>
                </div>';
    }
    return $out;
}
//therapist card details
function generateTeamMembers($therapists_result) {
    $out = '';
    if ($therapists_result->num_rows > 0) {
        while ($therapist = $therapists_result->fetch_assoc()) {
            $profile_img = !empty($therapist['profile_picture']) ? 
                "../therapist/".htmlspecialchars($therapist['profile_picture'])."?v=".time() : 
                "https://ui-avatars.com/api/?name=".urlencode($therapist['name'])."&background=4A90E2&color=fff";
            
            $out .= '<div class="card fade-in team-card">
                        <div class="team-img" style="background-image:url('.$profile_img.')"></div>
                        <h3>'.htmlspecialchars($therapist['name']).'</h3>
                        <p class="team-role">'.htmlspecialchars($therapist['qualification']).'</p>
                        <p class="team-specialty">'.htmlspecialchars($therapist['specialization']).'</p>
                        <p class="team-experience">'.htmlspecialchars($therapist['experience']).' experience</p>
                    </div>';
        }
    } else {
        // Fallback content if no therapists in database
        $out .= '<div class="card fade-in team-card">
                    <div class="team-img" style="background-image:url(assets/team1.jpg)"></div>
                    <h3>Dr. Ananya Sharma</h3>
                    <p class="team-role">Clinical Psychologist</p>
                    <p class="team-specialty">Anxiety & Depression</p>
                </div>
                <div class="card fade-in team-card">
                    <div class="team-img" style="background-image:url(assets/team2.jpg)"></div>
                    <h3>Dr. Vikram Patel</h3>
                    <p class="team-role">Psychiatrist</p>
                    <p class="team-specialty">Mood Disorders</p>
                </div>
                <div class="card fade-in team-card">
                    <div class="team-img" style="background-image:url(assets/team3.jpg)"></div>
                    <h3>Meera Krishnan</h3>
                    <p class="team-role">Counselor</p>
                    <p class="team-specialty">Teen & Family Therapy</p>
                </div>';
    }
    return $out;
}
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
   @keyframes float {
   0%, 100% {
     transform: translateY(0);
   }
   50% {
     transform: translateY(-15px);
   }
  }
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
    body {
      font-family: 'Montserrat', sans-serif;
      margin: 0;
      padding: 0;
      background: var(--light-bg);
      color: var(--text-color);
      scroll-behavior: smooth;
    }
    a { text-decoration: none; color: var(--primary-color); transition: color var(--transition) ease;}
    a:hover { color: var(--accent-color);}
    header {
      background: linear-gradient(135deg, var(--dark-bg) 0%, #3a5068 100%);
      color: var(--light-text);
      padding: calc(var(--spacing)*1.2) calc(var(--spacing)*2);
      box-shadow: 0 6px 20px rgba(0,0,0,0.2), inset 0 0 0 1px rgba(255,255,255,0.05);
      position: sticky; top: 0; z-index: 1000;
      display: flex; justify-content: space-between; align-items: center;
    }
    .logo {
      font-family: 'Lora', serif;
      font-size: 2.2rem; font-weight: 700; letter-spacing: 2px;
      color: var(--light-text);
      background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      transition: transform var(--transition) ease, color var(--transition) ease;
    }
    .logo:hover { transform: scale(1.03) translateY(-2px); color: var(--accent-color);}
    nav ul { display: flex; list-style: none; margin: 0; padding: 0; }
    nav ul li { margin-left: calc(var(--spacing)*2.5); }
    nav ul li a { font-weight: 500; padding: 5px 0; position: relative; }
    nav ul li a::after { content:''; position: absolute; width:0; height:2px; bottom:-5px; left:0; background: var(--primary-color); transition: width var(--transition) ease; }
    nav ul li a:hover::after, nav ul li a.active::after { width:100%; background: var(--accent-color);}
    nav ul li a:hover { transform: translateY(-3px); }
    .hero {
      background: linear-gradient(135deg, var(--primary-color), #6DD5ED 50%, var(--accent-color));
      color: var(--light-text);
      text-align: center;
      padding: 8rem 2rem;
      border-bottom-left-radius:50px; border-bottom-right-radius:50px;
    }
    .hero-content { max-width:900px; margin:auto; }
    .hero h1 { font-family:'Playfair Display', serif; font-size:3.8rem; margin:0;}
    .hero p { font-size:1.3rem; opacity:0.95; font-weight:300; margin-bottom:calc(var(--spacing)*3);}
    .button {
      display:inline-block; padding:calc(var(--spacing)*1.2) calc(var(--spacing)*3);
      border-radius:50px; font-weight:600; transition: all var(--transition) ease;
      border:none; font-size:1.1rem; margin:0 var(--spacing);
    }
    .button-primary {
      background: var(--accent-color); color: var(--dark-bg);
      box-shadow:0 6px 12px rgba(126,211,33,0.4); border:2px solid var(--accent-color);
    }
    .button-primary:hover { background:#6EB91F; transform:translateY(-5px) scale(1.02); }
    .button-secondary { background:transparent; color:var(--light-text); border:2px solid var(--light-text); }
    .button-secondary:hover { background:var(--light-text); color:var(--primary-color); }
    section { padding: calc(var(--spacing)*7) 0; }
    .container { max-width:1200px; margin:0 auto; padding:0 calc(var(--spacing)*2);}
    h2 { font-family:'Playfair Display', serif; font-size:2.8rem; font-weight:700; text-align:center; position:relative; }
    h2::after { content:''; width:80px; height:4px; background:var(--accent-color); display:block; margin:var(--spacing) auto 0; border-radius:2px; }
    .grid-layout { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:calc(var(--spacing)*2.5); padding:calc(var(--spacing)*2) 0;}
    .card {
      background-color:white; border-radius:var(--border-radius);
      box-shadow:0 6px 20px var(--card-shadow);
      padding:calc(var(--spacing)*2.5); text-align:center;
      transition:transform var(--transition) ease, box-shadow var(--transition) ease;
    }
    .card:hover { transform:translateY(-8px); box-shadow:0 12px 30px rgba(0,0,0,0.18);}
    .card-icon { font-size:3rem; color:var(--accent-color); margin-bottom:var(--spacing);}
    .fade-in { opacity:0; transform:translateY(30px); animation: fadeIn 0.9s ease-out forwards; }
    @keyframes fadeIn { to { opacity:1; transform:translateY(0);} }
    footer { background:var(--dark-bg); color:var(--light-text); text-align:center; padding:calc(var(--spacing)*4) 0; border-top:1px solid rgba(255,255,255,0.1); }
    footer p { margin:0; font-size:0.95rem; opacity:0.9;}
    
    /* Team Styles */
    .team-card {
      padding-bottom: 15px;
    }
    .team-img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin: 0 auto 10px;
      background-size: cover;
      background-position: center;
      border: 3px solid var(--light-bg);
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .team-role {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 3px;
    }
    .team-specialty {
      font-size: 0.9rem;
      color: var(--secondary-color);
    }
    .team-experience {
      font-size: 0.85rem;
      color: #666;
      margin-top: 5px;
    }
    
    /* Stats Styles */
    .stats-container {
      display: flex;
      justify-content: space-around;
      flex-wrap: wrap;
      margin: 40px 0;
    }
    .stat-item {
      text-align: center;
      padding: 20px;
    }
    .stat-number {
      font-size: 3.5rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 5px;
    }
    .stat-label {
      font-size: 1.1rem;
      color: var(--text-color);
    }
    
    /* Mental Health Issues Cards */
    .issue-card {
      text-align: left;
      padding: 30px;
      transition: all 0.3s ease;
    }
    .issue-img {
      width: 100%;
      height: 180px;
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      background-size: cover;
      background-position: center;
      transition: transform 0.3s ease;
    }
    .issue-card:hover .issue-img {
      transform: scale(1.03);
    }
    .issue-title {
      color: var(--primary-color);
      margin-bottom: 15px;
    }
    
    /* Testimonial Styles */
    .testimonial-card {
      display: flex;
      flex-direction: column;
      height: 100%;
      padding: 0;
      overflow: hidden;
    }
    .testimonial-img {
      height: 200px;
      background-size: cover;
      background-position: center;
    }
    .testimonial-content {
      padding: 25px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .testimonial-quote {
      font-style: italic;
      margin-bottom: 20px;
      font-size: 1.05rem;
      line-height: 1.6;
      flex-grow: 1;
    }
    .testimonial-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
    }
    .testimonial-role {
      font-size: 0.9rem;
      color: var(--secondary-color);
    }
    .testimonial-rating {
      font-size: 1.2rem;
    }
    
    /* FAQ Styles */
    .faq-container {
      max-width: 800px;
      margin: 0 auto;
    }
    .faq-item {
      margin-bottom: 15px;
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    .faq-question {
      background: white;
      padding: 20px;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .faq-answer {
      background: #f9f9f9;
      padding: 0 20px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease;
    }
    .faq-item.active .faq-answer {
      padding: 20px;
      max-height: 500px;
    }
    
    /* Therapy Options */
    .therapy-img {
      width: 100%;
      height: 200px;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      margin-bottom: 20px;
      background-size: cover;
      background-position: center;
    }
    
    /* Navigation Button for Booking */
    .nav-book-btn {
      background: var(--accent-color);
      color: var(--dark-bg) !important;
      padding: 8px 20px !important;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .nav-book-btn:hover {
      background: #6EB91F;
      transform: translateY(-3px);
      color: var(--dark-bg) !important;
    }
    .nav-book-btn::after {
      display: none !important;
    }
    
    /* Therapist Carousel Styles */
    .carousel-container {
      position: relative;
      max-width: 1200px;
      margin: 0 auto;
      overflow: hidden;
      padding: 0 40px;
    }
    .carousel {
      display: flex;
      transition: transform 0.5s ease;
      gap: 2rem;
      padding: 1rem 0;
    }
    .carousel-item {
      flex: 0 0 calc(33.333% - 1.34rem);
      min-width: 0;
    }
    .carousel-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: var(--primary-color);
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      font-size: 1.2rem;
      cursor: pointer;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }
    .carousel-btn:hover {
      background: var(--accent-color);
      transform: translateY(-50%) scale(1.1);
    }
    .carousel-btn.prev {
      left: 0;
    }
    .carousel-btn.next {
      right: 0;
    }
    .carousel-dots {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 10px;
    }
    .carousel-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #ccc;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .carousel-dot.active {
      background: var(--primary-color);
    }
    
    @media (max-width: 992px) {
      .carousel-item {
        flex: 0 0 calc(50% - 1rem);
      }
    }
    
    @media (max-width: 768px) {
      .carousel-item {
        flex: 0 0 100%;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo"><?php echo $siteName; ?></div>
    <nav><ul><?php echo generateNavLinks(); ?></ul></nav>
  </header>
  <section id="home" class="hero">
    <div class="hero-content fade-in" style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 50px;">
      <!-- Left: Heading and Buttons -->
      <div style="flex: 1 1 520px; min-width: 300px; text-align: left;">
        <h1 style="font-size: 3.2rem; margin-bottom: 2rem;"><?php echo $heroHeading; ?></h1>
        <div>
          <a href="#mental-health-issues" class="button button-primary">Explore Support</a>
          <a href="user/login_user.php" class="button button-secondary" style="margin-left: 16px;">Book Session</a>
        </div>
      </div>
      <!-- Right: SVG Image -->
      <div style="flex: 1 1 200px; text-align: center;">
        <img src="assets/img1.svg" alt="MindCare Illustration" style="width: 310px; max-width: 110%; height: auto; animation: float 6s ease-in-out infinite;">
      </div>
    </div>
  </section>

  <!-- Mission Section with Stats -->
  <section id="mission" class="container">
    <h2>Our Mission</h2>
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 40px; margin-top: 40px;">
        <!-- Mission Text -->
        <div style="flex: 1 1 55%; min-width: 280px;" class="fade-in">
            <p style="font-size: 1.25rem; color: var(--text-color); font-weight: 500; line-height: 1.9; margin: 0 auto; max-width: 850px;">
                At <span style="color: var(--primary-color); font-weight: 600;">MindCare Hub</span>, we believe that no one should face emotional struggles alone.  
                Whether you're dealing with <span style="color: var(--accent-color); font-weight: 600;">stress</span>, <span style="color: var(--accent-color); font-weight: 600;">anxiety</span>, <span style="color: var(--accent-color); font-weight: 600;">anger</span>, or <span style="color: var(--accent-color); font-weight: 600;">sleep disorders</span>, we're here to support you.  
                Our licensed therapists offer personalized sessions in a safe, confidential, and supportive environment.  
                Because your <span style="color: var(--primary-color); font-weight: 600;">mental well-being</span> deserves the same care as your physical health.Investing in your mind is the most important investment you can make. Let us help you build a happier, healthier, and more fulfilling life.
            </p>
        </div>
    </div>
    
    <!-- Stats Section -->
    <div class="stats-container fade-in">
        <div class="stat-item">
            <div class="stat-number">500+</div>
            <div class="stat-label">Clients Helped</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfaction Rate</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">15+</div>
            <div class="stat-label">Licensed Therapists</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Support Available</div>
        </div>
    </div>
  </section>

  <!-- Mental Health Issues Section -->
  <section id="mental-health-issues" class="container">
    <h2>Mental Health Issues We Address</h2>
    <p style="max-width: 700px; margin: 0 auto 40px; font-size: 1.1rem; text-align: center;">Our therapists specialize in treating a wide range of mental health conditions</p>
    
    <div class="grid-layout">
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/depression.jpg');"></div>
            <h3 class="issue-title">Depression</h3>
            <p>Persistent sadness, loss of interest in activities, and difficulty functioning in daily life.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/anxiety.jpg');"></div>
            <h3 class="issue-title">Anxiety Disorders</h3>
            <p>Excessive worry, panic attacks, phobias, and generalized anxiety that interferes with daily life.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/ocd.jpeg"');"></div>
            <h3 class="issue-title">OCD</h3>
            <p>Unwanted recurring thoughts (obsessions) and repetitive behaviors (compulsions).</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/ptsd.jpg');"></div>
            <h3 class="issue-title">PTSD</h3>
            <p>Difficulty recovering after experiencing or witnessing a traumatic event.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/bipolar.jpg');"></div>
            <h3 class="issue-title">Bipolar Disorder</h3>
            <p>Extreme mood swings that include emotional highs (mania) and lows (depression).</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/eating.jpg');"></div>
            <h3 class="issue-title">Eating Disorders</h3>
            <p>Unhealthy eating habits and preoccupation with food and body weight.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/adhd.jpg');"></div>
            <h3 class="issue-title">ADHD</h3>
            <p>Difficulty maintaining attention, hyperactivity, and impulsive behavior.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/addiction.jpg');"></div>
            <h3 class="issue-title">Addiction</h3>
            <p>Compulsive substance use or behavior despite harmful consequences.</p>
        </div>
        
        <div class="card fade-in issue-card">
            <div class="issue-img" style="background-image: url('assets/sleep.jpg');"></div>
            <h3 class="issue-title">Sleep Disorders</h3>
            <p>Problems with the quality, timing, and amount of sleep affecting daily life.</p>
        </div>
    </div>
  </section>

  <!-- Therapy Options Section -->
<section id="therapy-options" class="container text-center">
    <h2>Our Therapy Options</h2>
    <div class="grid-layout">
        <div class="card fade-in" onclick="window.location.href='user/login_user.php'" style="cursor: pointer;">
            <div class="therapy-img" style="background-image: url('assets/individual.jpg');"></div>
            <h3>Individual</h3>
            <p>Private sessions focused on your personal growth and emotional well‑being.</p>
        </div>
        <div class="card fade-in" onclick="window.location.href='user/login_user.php'" style="cursor: pointer;">
            <div class="therapy-img" style="background-image: url('assets/teen.jpg');"></div>
            <h3>Teen</h3>
            <p>Specialized support for teens navigating stress, identity, and growing pains.</p>
        </div>
        <div class="card fade-in" onclick="window.location.href='user/login_user.php'" style="cursor: pointer;">
            <div class="therapy-img" style="background-image: url('assets/family.jpg');"></div>
            <h3>Family</h3>
            <p>Therapy for families to build communication, trust, and stronger bonds together.</p>
        </div>
    </div>
</section>
  <!-- Testimonials Section -->
  <section id="testimonials" class="container text-center" style="background-color: #f9f9f9; padding: 60px 20px; border-radius: var(--border-radius);">
    <h2>Transformative Journeys</h2>
    <p style="max-width: 690px; margin: 0 auto 40px; font-size: 1.1rem;">Voices from our clients and patients, telling stories of trust and transformation</p>
    <div class="grid-layout"><?php echo generateTestimonials(); ?></div>
  </section>

  <!-- Therapists Section with Carousel -->
  <section id="team" class="container">
    <h2 style="text-align: center;">Meet Our Therapists</h2>
    <p style="max-width: 700px; margin: 0 auto 40px; font-size: 1.1rem; text-align: center;">
        Our licensed professionals are ready to support your mental health journey
    </p>
    
    <?php 
    // Fetch all approved therapists
    $sql = "SELECT * FROM therapists WHERE status='approved' ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0): 
      $therapists = [];
      while ($row = $result->fetch_assoc()) {
        $therapists[] = $row;
      }
      $totalTherapists = count($therapists);
    ?>
      <div class="carousel-container">
        <button class="carousel-btn prev" onclick="moveCarousel(-1)">❮</button>
        <button class="carousel-btn next" onclick="moveCarousel(1)">❯</button>
        
        <div class="carousel" id="therapist-carousel">
          <?php foreach ($therapists as $therapist): ?>
            <div class="carousel-item">
              <div class="card" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; height: 100%;">
                
                <!-- Profile Image -->
                <?php if (!empty($therapist['profile_picture'])): ?>
                  <img src="therapist/<?= htmlspecialchars($therapist['profile_picture']) ?>?v=<?= time() ?>" 
                       alt="<?= htmlspecialchars($therapist['name']) ?>" 
                       style="width: 100%; height: 200px; object-fit: cover; border-bottom: 3px solid #7ED321;">
                <?php else: ?>
                  <img src="https://ui-avatars.com/api/?name=<?= urlencode($therapist['name']) ?>&background=4A90E2&color=fff" 
                       style="width: 100%; height: 200px; object-fit: cover; border-bottom: 3px solid #7ED321;">
                <?php endif; ?>
                
                <!-- Therapist Details -->
                <div style="padding: 1.5rem;">
                  <h3 style="font-size: 1.4rem; margin-bottom: 0.8rem; color: #2C3E50; text-align: center;">
                    <?= htmlspecialchars($therapist['name']) ?>
                  </h3>
                  
                  <div style="color: #4A90E2; font-weight: 500; margin-bottom: 0.8rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-briefcase-medical"></i>
                    <span><?= htmlspecialchars($therapist['specialization']) ?></span>
                  </div>
                  
                  <div style="margin-bottom: 0.8rem; font-size: 0.9rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user-graduate"></i>
                    <span><?= htmlspecialchars($therapist['qualification']) ?></span>
                  </div>
                  
                  <div style="color: #666; font-size: 0.9rem; margin-bottom: 1.2rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-business-time"></i>
                    <span><?= htmlspecialchars($therapist['experience']) ?> years experience</span>
                  </div>
                  
                  <div style="text-align: center;">
                    <a href="user/login_user.php" 
                       style="display: inline-block; padding: 0.6rem 1.2rem; background: #7ED321; color: white; border-radius: 4px; text-decoration: none; font-weight: 500;">
                        <i class="fas fa-calendar-check"></i> Book Session
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <!-- Dots indicator -->
        <div class="carousel-dots" id="carousel-dots">
          <?php 
          $numSlides = ceil($totalTherapists / 3);
          for ($i = 0; $i < $numSlides; $i++): 
          ?>
            <div class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></div>
          <?php endfor; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- No Therapists Available -->
      <div style="background: white; padding: 2rem; text-align: center; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <p style="margin-bottom: 0.8rem;">Currently, there are no approved therapists available.</p>
        <p>Please check back later or contact our support team.</p>
      </div>
    <?php endif; ?>
    
    <!-- View All Button -->
    <div style="text-align: center; margin-top: 2rem;">
      <a href="user/login_user.php" 
         style="display: inline-block; padding: 0.8rem 1.5rem; background: #4A90E2; color: white; border-radius: 4px; text-decoration: none; font-weight: 500;">
          <i class="fas fa-users"></i> View All Therapists
      </a>
    </div>
  </section>
    <!-- Login Section -->
  <section id="login" class="container text-center">
    <h2>Logins</h2>
    <div class="grid-layout">
      <div class="card fade-in" style="background-color:#e0f7fa;"><i class="fas fa-user card-icon" style="color:#00796b;"></i><h3>User Login</h3><p>Access your dashboard, tools, and appointments.</p><a href="user/login_user.php" class="button button-primary">Login</a></div>
      <div class="card fade-in" style="background-color:#fce4ec;"><i class="fas fa-stethoscope card-icon" style="color:#c2185b;"></i><h3>Therapist Login</h3><p>Manage your profile, clients, and scheduling.</p><a href="therapist/therapist_login.php" class="button button-primary">Login</a></div>
      <div class="card fade-in" style="background-color:#f3e5f5;"><i class="fas fa-shield-alt card-icon" style="color:#6a1b9a;"></i><h3>Admin Login</h3><p>Oversee platform content, users, and community.</p><a href="admin/admin_login.php" class="button button-primary">Login</a></div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section id="faq" class="container">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-container">
      <div class="faq-item fade-in">
        <div class="faq-question">How do I book a therapy session? <i class="fas fa-chevron-down"></i></div>
        <div class="faq-answer">
          <p>Booking a session is simple! Click on "Book Session" in the navigation menu, create an account or log in, select your preferred therapist, choose a convenient time slot, and confirm your booking. You'll receive a confirmation email with all the details.</p>
        </div>
      </div>
      
      <div class="faq-item fade-in">
        <div class="faq-question">Are the therapy sessions confidential? <i class="fas fa-chevron-down"></i></div>
        <div class="faq-answer">
          <p>Absolutely. We adhere to strict confidentiality protocols in accordance with professional ethical guidelines and privacy laws. Your sessions and personal information are completely private and secure.</p>
        </div>
      </div>
      
      <div class="faq-item fade-in">
        <div class="faq-question">What if I need to reschedule or cancel my appointment? <i class="fas fa-chevron-down"></i></div>
        <div class="faq-answer">
          <p>You can reschedule or cancel your appointment up to 24 hours in advance at no charge. Simply log into your account, go to "My Appointments," and make the necessary changes. Late cancellations may incur a fee as per our cancellation policy.</p>
        </div>
      </div>
      
      <div class="faq-item fade-in">
        <div class="faq-question">Do you offer emergency mental health services? <i class="fas fa-chevron-down"></i></div>
        <div class="faq-answer">
          <p>While we provide therapeutic support, we are not an emergency service. If you're experiencing a mental health crisis, please contact emergency services (112) or go to your nearest hospital emergency room immediately.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- Contact Section -->
  <section id="contact" class="container text-center">
    <h2>Contact</h2>
    <div class="fade-in" style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 40px; margin-top: 30px; font-size: 1.1rem; font-weight: 500; color: var(--text-color); text-align: center;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.3rem;">⌂</span>
        <span>Mystic Hills,Kochi</span>
      </div>
      <span style="font-size: 1.4rem; color: #bbb;">•</span>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.3rem;">✉</span>
        <a href="mailto:support@mindcarehub.com" style="color: var(--primary-color); text-decoration: none;">support@mindcarehub.com</a>
      </div>
      <span style="font-size: 1.4rem; color: #bbb;">•</span>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.3rem;">☏</span>
        <span>+91-98765-43210</span>
      </div>
    </div>
    <div style="height: 50px;"></div>
  </section>

   <footer>
    <p>&copy; <?php echo date("Y"); ?> MindCare Hub. All rights reserved.</p>
    <p>Promoting emotional well‑being, one connection at a time.</p>
    <div style="margin-top: 20px;">
      <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
      <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
      <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
      <a href="#" style="color: var(--light-text); margin: 0 10px; font-size: 1.2rem;"><i class="fab fa-linkedin"></i></a>
    </div>
  </footer>
  <script>
    // FAQ functionality
    document.querySelectorAll('.faq-question').forEach(question => {
      question.addEventListener('click', () => {
        const item = question.parentElement;
        item.classList.toggle('active');
      });
    });
    
    // Therapist Carousel functionality
    let currentSlide = 0;
    const carousel = document.getElementById('therapist-carousel');
    const dots = document.querySelectorAll('.carousel-dot');
    const itemsPerSlide = window.innerWidth < 768 ? 1 : window.innerWidth < 992 ? 2 : 3;
    const totalSlides = Math.ceil(carousel.children.length / itemsPerSlide);
    
    function updateCarousel() {
      const itemWidth = carousel.children[0].offsetWidth + 32; // width + gap
      const translateX = -currentSlide * itemsPerSlide * itemWidth;
      carousel.style.transform = `translateX(${translateX}px)`;
      
      // Update active dot
      dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
      });
    }
    
    function moveCarousel(direction) {
      currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
      updateCarousel();
    }
    
    function goToSlide(slideIndex) {
      currentSlide = slideIndex;
      updateCarousel();
    }
    
    // Initialize carousel
    updateCarousel();
    
    // Handle window resize
    window.addEventListener('resize', () => {
      const newItemsPerSlide = window.innerWidth < 768 ? 1 : window.innerWidth < 992 ? 2 : 3;
      if (newItemsPerSlide !== itemsPerSlide) {
        currentSlide = 0;
        updateCarousel();
      }
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>
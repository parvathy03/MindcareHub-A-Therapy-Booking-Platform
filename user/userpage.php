<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "mindcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User data
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

// Motivational quotes
$quotes = [
    "You are stronger than you know. Keep moving forward!",
    "Every step you take is a victory in your mental health journey.",
    "Embrace your emotions; they guide you to healing.",
    "You are not alone. We're here to support you.",
    "Small changes today lead to big transformations tomorrow."
];
$random_quote = $quotes[array_rand($quotes)];
// Initialize therapy_type if not set
$therapy_type = $_GET['therapy_type'] ?? '';

// Get booked time slots with therapist information
$booked_slots = [];
$slots_query = $conn->query("SELECT therapist_id, appointment_date, appointment_time FROM appointments 
                            WHERE status IN ('pending', 'approved') 
                            AND appointment_date >= CURDATE()");
while ($slot = $slots_query->fetch_assoc()) {
    $therapist_id = $slot['therapist_id'];
    $booked_slots[$therapist_id][$slot['appointment_date']][] = $slot['appointment_time'];
}

$siteName = "MindCare Hub";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $siteName; ?> - User Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&family=Lora:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

    body {
      font-family: 'Montserrat', sans-serif;
      margin: 0;
      padding: 0;
      background: var(--light-bg);
      color: var(--text-color);
      scroll-behavior: smooth;
    }

    a { 
      text-decoration: none; 
      color: var(--primary-color); 
      transition: color var(--transition) ease;
    }
    a:hover { color: var(--accent-color); }

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

    .user-greeting {
      font-weight: 500;
      margin-right: auto;
      margin-left: 2rem;
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

    .hero {
      background: linear-gradient(135deg, var(--primary-color), #6DD5ED 50%, var(--accent-color));
      color: var(--light-text);
      text-align: center;
      padding: 6rem 2rem;
      border-bottom-left-radius:50px; 
      border-bottom-right-radius:50px;
      margin-bottom: 2rem;
    }

    .hero h1 {
      font-family: 'Playfair Display', serif;
      font-size: 2.8rem;
      margin-bottom: 1rem;
    }

    .hero p {
      font-size: 1.2rem;
      max-width: 800px;
      margin: 0 auto;
      opacity: 0.95;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 calc(var(--spacing)*2);
    }

    h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.4rem;
      font-weight: 700;
      position: relative;
      margin: 2rem 0 1.5rem;
      color: var(--dark-bg);
    }

    h2::after {
      content: '';
      width: 80px;
      height: 4px;
      background: var(--accent-color);
      display: block;
      margin: var(--spacing) 0;
      border-radius: 2px;
    }

    .card {
      background: white;
      border-radius: var(--border-radius);
      padding: calc(var(--spacing)*2);
      margin-bottom: 2rem;
      box-shadow: 0 6px 20px var(--card-shadow);
      transition: transform var(--transition) ease, box-shadow var(--transition) ease;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.18);
    }

    .card-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: var(--accent-color);
    }

    .motivational-card {
      text-align: center;
      background: linear-gradient(135deg, #fff8e1, #ffecb3);
      border-left: 5px solid var(--accent-color);
    }

    .motivational-card p {
      font-family: 'Lora', serif;
      margin: 1rem 0;
      font-style: italic;
      font-size: 1.2rem;
    }

    .profile-section {
      display: flex;
      align-items: center;
      gap: 2rem;
    }

    .profile-section img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--accent-color);
    }

    .profile-section h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
      color: var(--dark-bg);
    }

    .profile-section p {
      margin-bottom: 1rem;
      color: #555;
    }

    .grid-layout {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: calc(var(--spacing)*2);
      margin-bottom: 2rem;
    }

    .button {
      display: inline-block;
      padding: calc(var(--spacing)*1) calc(var(--spacing)*2);
      border-radius: 50px;
      font-weight: 600;
      transition: all var(--transition) ease;
      border: none;
      cursor: pointer;
    }

    .button-primary {
      background: var(--accent-color);
      color: var(--dark-bg);
      box-shadow: 0 6px 12px rgba(126,211,33,0.4);
      border: 2px solid var(--accent-color);
    }

    .button-primary:hover {
      background: #6EB91F;
      transform: translateY(-5px) scale(1.02);
    }

    form {
      display: grid;
      gap: 1rem;
    }

    label {
      font-weight: 500;
      margin-bottom: -0.5rem;
    }

    input, select, textarea {
      padding: 0.8rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: inherit;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 0 2px rgba(79, 195, 247, 0.3);
    }

    .fade-in {
      opacity: 0;
      transform: translateY(30px);
      animation: fadeIn 0.9s ease-out forwards;
    }

    @keyframes fadeIn {
      to { opacity: 1; transform: translateY(0); }
    }

    footer {
      background: var(--dark-bg);
      color: var(--light-text);
      text-align: center;
      padding: calc(var(--spacing)*4) 0;
      margin-top: 3rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    footer p {
      margin: 0;
      font-size: 0.95rem;
      opacity: 0.9;
    }

    .social-icons {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 1rem;
    }

    .social-icons a {
      color: white;
      font-size: 1.2rem;
      transition: var(--transition);
    }

    .social-icons a:hover {
      color: var(--accent-color);
      transform: translateY(-3px);
    }

    /* Payment Options Section */
    .payment-section {
      margin-top: 2rem;
      border-top: 1px dashed #ddd;
      padding-top: 2rem;
    }
    
    .payment-options {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1.5rem;
    }
    
    .payment-option {
      display: flex;
      align-items: center;
      padding: 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .payment-option:hover {
      border-color: var(--primary-color);
      background-color: #f8f9fa;
    }
    
    .payment-option.selected {
      border-color: var(--accent-color);
      background-color: #e8f5e9;
    }
    
    .payment-option input[type="radio"] {
      margin-right: 1rem;
      accent-color: var(--accent-color);
    }
    
    .payment-option-content {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .payment-icon {
      font-size: 1.5rem;
      color: var(--primary-color);
    }
    
    .payment-details {
      display: flex;
      flex-direction: column;
    }
    
    .payment-details h4 {
      margin: 0 0 0.25rem 0;
      color: var(--dark-bg);
    }
    
    .payment-details p {
      margin: 0;
      font-size: 0.9rem;
      color: #666;
    }
    
    .payment-badge {
      background-color: var(--accent-color);
      color: white;
      font-size: 0.7rem;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      margin-left: auto;
    }
    
    .payment-instructions {
      background: #f8f9fa;
      padding: 1.5rem;
      border-radius: var(--border-radius);
      margin-top: 1.5rem;
    }
    
    .payment-instructions h3 {
      color: var(--primary-color);
      margin-bottom: 1rem;
    }
    
    .payment-instructions ol {
      padding-left: 1.5rem;
    }
    
    .payment-instructions li {
      margin-bottom: 0.8rem;
    }
    
    /* Payment Details Form */
    .payment-details-form {
      margin-top: 1.5rem;
      padding: 1.5rem;
      background-color: #f8f9fa;
      border-radius: var(--border-radius);
      border-left: 4px solid var(--primary-color);
      display: none;
    }
    
    .payment-details-form.active {
      display: block;
    }
    
    .payment-details-form h4 {
      margin-top: 0;
      color: var(--primary-color);
      margin-bottom: 1rem;
    }
    
    .payment-form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
    }
    
    .form-group label {
      margin-bottom: 0.5rem;
      font-weight: 500;
    }
    
    .form-row {
      display: flex;
      gap: 1rem;
    }
    
    .form-row .form-group {
      flex: 1;
    }
    
    /* Time Slot Styles */
    .time-slots {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.5rem;
      margin-top: 1rem;
    }
    
    .time-slot {
      padding: 0.6rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .time-slot:hover {
      background-color: #f0f8ff;
    }
    
    .time-slot.selected {
      background-color: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
    }
    
    .time-slot.booked {
      background-color: #ffebee;
      color: #b71c1c;
      cursor: not-allowed;
      text-decoration: line-through;
    }
    
    .time-slot.past {
      background-color: #f5f5f5;
      color: #9e9e9e;
      cursor: not-allowed;
      text-decoration: line-through;
    }
    
    .time-slot-label {
      font-weight: 500;
      margin-bottom: 0.5rem;
    }
    
    .time-slot-info {
      margin-top: 1rem;
      padding: 1rem;
      background-color: #f8f9fa;
      border-radius: 4px;
      border-left: 4px solid var(--primary-color);
    }

    /* Payment Amount Display */
    .payment-amount {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
      padding: 1rem 1.5rem;
      border-radius: var(--border-radius);
      margin-bottom: 1.5rem;
      border-left: 4px solid var(--accent-color);
    }
    
    .amount-label {
      font-weight: 600;
      color: var(--dark-bg);
    }
    
    .amount-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 1rem;
      }

      .logo {
        margin-bottom: 1rem;
      }

      .user-greeting {
        margin: 1rem 0;
      }

      nav ul {
        margin-top: 1rem;
        flex-wrap: wrap;
        justify-content: center;
      }

      nav li {
        margin: 0.5rem 1rem;
      }

      .hero h1 {
        font-size: 2.2rem;
      }

      .profile-section {
        flex-direction: column;
        text-align: center;
      }
      
      .payment-options {
        flex-direction: column;
      }
      
      .payment-form-grid {
        grid-template-columns: 1fr;
      }
      
      .form-row {
        flex-direction: column;
        gap: 0.5rem;
      }
      
      .time-slots {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .payment-amount {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo"><?php echo $siteName; ?></div>
    <div class="user-greeting">Welcome, <?php echo htmlspecialchars($user['name']); ?></div>
    <nav>
      <ul>
        <li><a href="therapists.php">Therapists</a></li>
        <li><a href="appointments.php">My Appointments</a></li>
        <li><a href="#profile" class="active">Profile</a></li>
        <li><a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a><li>
    </nav>
      </ul>
    </nav>
  </header>

  <section class="hero">
    <h1>Your Journey to Wellness</h1>
    <p>Explore personalized therapy options and take charge of your mental health with MindCare Hub.</p>
  </section>

  <div class="container">
    <h2>Daily Inspiration</h2>
    <div class="card motivational-card fade-in">
      <i class="fas fa-quote-left card-icon" style="color: #FF8C00;"></i>
      <h3>Today's Motivation</h3>
      <p><?php echo htmlspecialchars($random_quote); ?></p>
      <button class="button button-primary" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i> New Quote
      </button>
    </div>

    <h2 id="profile">Your Profile</h2>
    <div class="card profile-section fade-in">
      <?php if (!empty($user['profile_picture'])): ?>
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
      <?php else: ?>
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=4a90e2&color=fff&size=120" alt="Profile Picture">
      <?php endif; ?>
      <div>
        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
        <a href="edit_profile.php" class="button button-primary">
          <i class="fas fa-user-edit"></i> Edit Profile
        </a>
      </div>
    </div>

    <h2>Our Therapy Options</h2>
    <div class="grid-layout">
      <div class="card fade-in" style="animation-delay: 0s; background-color: #e6f2ff;">
        <i class="fas fa-user card-icon" style="color: var(--primary-color);"></i>
        <h3>Individual Therapy</h3>
        <p>Personalized one-on-one sessions tailored to your specific mental health needs and goals.</p>
      </div>
      <div class="card fade-in" style="animation-delay: 0.1s; background-color: #fff3e6;">
        <i class="fas fa-child card-icon" style="color: #FF8C00;"></i>
        <h3>Teen Therapy</h3>
        <p>Specialized support for adolescents dealing with emotional, social, and academic challenges.</p>
      </div>
      <div class="card fade-in" style="animation-delay: 0.2s; background-color: #e6ffe6;">
        <i class="fas fa-users card-icon" style="color: #28a745;"></i>
        <h3>Family Therapy</h3>
        <p>Improve communication and resolve conflicts within family systems for healthier relationships.</p>
      </div>
    </div>

<h2 id="book">Book a Session</h2>
<div class="card fade-in">
    <p>Choose between teletherapy (online) or in-person sessions:</p>
    <form id="booking-form" action="book_appointment.php" method="POST">
        <!-- Session Type Selection -->
        <label><i class="fas fa-laptop-house"></i> Session Type</label>
        <div style="display: flex; gap: 20px; margin-bottom: 1rem;">
            <label style="display: flex; align-items: center;">
                <input type="radio" name="session_type" value="teletherapy" checked> 
                <span style="margin-left: 5px;">Teletherapy (Online)</span>
            </label>
            <label style="display: flex; align-items: center;">
                <input type="radio" name="session_type" value="in-person"> 
                <span style="margin-left: 5px;">In-Person</span>
            </label>
        </div>

        <!-- Therapist Selection -->
        <label for="therapist_id"><i class="fas fa-user-md"></i> Select Therapist</label>
        <select name="therapist_id" id="therapist_id" required>
            <option value="">--Select a Therapist--</option>
            <?php
            $therapists = $conn->query("SELECT id, name, specialization FROM therapists WHERE status='approved'");
            while ($therapist = $therapists->fetch_assoc()):
            ?>
                <option value="<?= $therapist['id'] ?>" 
                    <?= (!empty($therapy_type) && $therapist['specialization'] == $therapy_type) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($therapist['name']) ?> (<?= htmlspecialchars($therapist['specialization']) ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <!-- Therapy Type Selection -->
        <label for="therapy_type"><i class="fas fa-hand-holding-heart"></i> Therapy Type</label>
        <select name="therapy_type" id="therapy_type" required>
            <option value="">--Select Therapy Type--</option>
            <option value="Individual">Individual Therapy</option>
            <option value="Teen">Teen Therapy</option>
            <option value="Family">Family Therapy</option>
        </select>

        <!-- Date Selection -->
        <label for="appointment_date"><i class="fas fa-calendar"></i> Preferred Date</label>
        <input type="date" name="appointment_date" id="appointment_date" required min="<?= date('Y-m-d') ?>">

        <!-- Time Slot Selection -->
        <label for="time"><i class="fas fa-clock"></i> Select Time Slot</label>
        <div class="time-slot-info">
            <p>Available time slots: 9:00 AM - 5:00 PM (1-hour sessions)</p>
            <p>Select a date first to see available time slots</p>
        </div>
        <div class="time-slots" id="time-slots-container">
            <!-- Time slots will be populated by JavaScript -->
        </div>
        <input type="hidden" name="appointment_time" id="selected-time" required>
        
        <!-- Payment Section -->
        <div class="payment-section">
            <!-- Payment Amount Display -->
            <div class="payment-amount">
                <div class="amount-label">Session Fee:</div>
                <div class="amount-value">₹1000</div>
            </div>
            
            <h3>Payment Options</h3>
            <p>Select your preferred payment method:</p>
            
            <div class="payment-options">
                <div class="payment-option" onclick="selectPayment(this, 'credit_card')">
                    <input type="radio" name="payment_method" value="credit_card" id="credit_card">
                    <div class="payment-option-content">
                        <i class="fas fa-credit-card payment-icon"></i>
                        <div class="payment-details">
                            <h4>Credit or Debit Card</h4>
                            <p>Pay securely with your credit or debit card</p>
                        </div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment(this, 'paypal')">
                    <input type="radio" name="payment_method" value="paypal" id="paypal">
                    <div class="payment-option-content">
                        <i class="fab fa-paypal payment-icon"></i>
                        <div class="payment-details">
                            <h4>PayPal</h4>
                            <p>Pay with your PayPal account</p>
                        </div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment(this, 'gpay')">
                    <input type="radio" name="payment_method" value="gpay" id="gpay">
                    <div class="payment-option-content">
                        <i class="fab fa-google payment-icon"></i>
                        <div class="payment-details">
                            <h4>Google Pay</h4>
                            <p>Pay quickly with your Google account</p>
                        </div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment(this, 'apple_pay')">
                    <input type="radio" name="payment_method" value="apple_pay" id="apple_pay">
                    <div class="payment-option-content">
                        <i class="fab fa-apple payment-icon"></i>
                        <div class="payment-details">
                            <h4>Apple Pay</h4>
                            <p>Pay securely with Apple Pay</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Details Forms -->
            <div class="payment-details-form" id="credit_card_form">
                <h4>Credit/Debit Card Details</h4>
                <div class="payment-form-grid">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_holder">Card Holder Name</label>
                        <input type="text" id="card_holder" name="card_holder" placeholder="John Doe">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-details-form" id="paypal_form">
                <h4>PayPal Account Details</h4>
                <div class="payment-form-grid">
                    <div class="form-group">
                        <label for="paypal_email">PayPal Email</label>
                        <input type="email" id="paypal_email" name="paypal_email" placeholder="your.email@example.com">
                    </div>
                </div>
            </div>
            
            <div class="payment-details-form" id="gpay_form">
                <h4>Google Pay Details</h4>
                <div class="payment-form-grid">
                    <div class="form-group">
                        <label for="gpay_upi_id">UPI ID</label>
                        <input type="text" id="gpay_upi_id" name="gpay_upi_id" placeholder="yourname@okhdfcbank">
                    </div>
                    <div class="form-group">
                        <label for="gpay_phone">Phone Number</label>
                        <input type="tel" id="gpay_phone" name="gpay_phone" placeholder="+91 9876543210">
                    </div>
                </div>
            </div>
            
            <div class="payment-details-form" id="apple_pay_form">
                <h4>Apple Pay Details</h4>
                <div class="payment-form-grid">
                    <div class="form-group">
                        <label for="apple_pay_email">Apple ID Email</label>
                        <input type="email" id="apple_pay_email" name="apple_pay_email" placeholder="your.appleid@example.com">
                    </div>
                    <div class="form-group">
                        <label for="apple_pay_phone">Registered Phone Number</label>
                        <input type="tel" id="apple_pay_phone" name="apple_pay_phone" placeholder="+91 9876543210">
                    </div>
                </div>
            </div>
            
            <div class="payment-instructions">
                <h3>Payment Information:</h3>
                <p>Session fee: ₹1000 per session</p>
                <p>Your payment information is secure and encrypted.</p>
                <p><strong>Note:</strong> For any help, contact us at +91-98765-43210.</p>
            </div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="button button-primary" id="book-button">
            <i class="fas fa-calendar-check"></i> Book Session
        </button>
    </form>
    </div>
  </div>

  <footer>
    <p>© <?php echo date("Y"); ?> MindCare Hub. All rights reserved.</p>
    <p>Promoting emotional well-being, one connection at a time.</p>
    <div class="social-icons">
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
      <a href="#"><i class="fab fa-linkedin-in"></i></a>
      <a href="#"><i class="fas fa-envelope"></i></a>
    </div>
  </footer>

  <script>
    // Form date validation
    document.getElementById('appointment_date').addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (selectedDate < today) {
        alert('Please select a current or future date');
        this.value = '';
        return;
      }
      
      // Load available time slots for the selected date
      updateTimeSlots(this.value);
    });
    
    // Update time slots based on selected date and therapist
    function updateTimeSlots(selectedDate) {
      const timeSlotsContainer = document.getElementById('time-slots-container');
      const selectedTimeInput = document.getElementById('selected-time');
      const therapistSelect = document.getElementById('therapist_id');
      const selectedTherapistId = therapistSelect.value;
      
      // Clear previous selection
      selectedTimeInput.value = '';
      
      // Clear container and show loading
      timeSlotsContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px;">Loading available time slots...</div>';
      
      if (!selectedTherapistId) {
        timeSlotsContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px;">Please select a therapist first</div>';
        return;
      }
      
      // Fetch available time slots via AJAX
      fetch(`get_available_slots.php?therapist_id=${selectedTherapistId}&appointment_date=${selectedDate}`)
        .then(response => response.json())
        .then(bookedSlots => {
          generateTimeSlotButtons(selectedDate, bookedSlots, selectedTherapistId);
        })
        .catch(error => {
          console.error('Error:', error);
          timeSlotsContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px; color: red;">Error loading time slots</div>';
        });
    }
    
    // Generate time slots (9:00 AM to 5:00 PM, 1-hour intervals)
    function generateTimeSlots() {
      const slots = [];
      for (let hour = 9; hour <= 17; hour++) {
        const timeString = `${hour.toString().padStart(2, '0')}:00:00`;
        slots.push(timeString);
      }
      return slots;
    }
    
    // Create time slot buttons
    function generateTimeSlotButtons(selectedDate, bookedSlots, therapistId) {
      const timeSlotsContainer = document.getElementById('time-slots-container');
      const selectedTimeInput = document.getElementById('selected-time');
      const allTimeSlots = generateTimeSlots();
      
      // Clear container
      timeSlotsContainer.innerHTML = '';
      
      // Check if selected date is today
      const today = new Date();
      const isToday = selectedDate === today.toISOString().split('T')[0];
      const currentHour = today.getHours();
      const currentMinutes = today.getMinutes();
      
      // Create time slot buttons
      allTimeSlots.forEach(slot => {
        const isBooked = bookedSlots.includes(slot);
        const timeSlotElement = document.createElement('div');
        
        // Check if this time slot is in the past for today
        let isPast = false;
        if (isToday) {
          const slotHour = parseInt(slot.split(':')[0]);
          const slotMinutes = parseInt(slot.split(':')[1]);
          
          // If current time is past this slot, mark it as past
          if (slotHour < currentHour || (slotHour === currentHour && slotMinutes < currentMinutes)) {
            isPast = true;
          }
        }
        
        timeSlotElement.className = `time-slot ${isBooked ? 'booked' : ''} ${isPast ? 'past' : ''}`;
        timeSlotElement.textContent = formatTime(slot);
        
        // Add tooltip for booked slots
        if (isBooked) {
          timeSlotElement.title = 'This time slot is already booked';
        }
        
        if (!isBooked && !isPast) {
          timeSlotElement.addEventListener('click', function() {
            // Remove selected class from all slots
            document.querySelectorAll('.time-slot').forEach(el => {
              el.classList.remove('selected');
            });
            
            // Add selected class to clicked slot
            this.classList.add('selected');
            
            // Update hidden input value
            selectedTimeInput.value = slot;
          });
          
          // Add hover effect for available slots
          timeSlotElement.style.cursor = 'pointer';
          timeSlotElement.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
              this.style.backgroundColor = '#f0f8ff';
            }
          });
          timeSlotElement.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
              this.style.backgroundColor = '';
            }
          });
        } else {
          timeSlotElement.style.cursor = 'not-allowed';
        }
        
        timeSlotsContainer.appendChild(timeSlotElement);
      });
      
      // If no slots available, show message
      if (timeSlotsContainer.children.length === 0) {
        timeSlotsContainer.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 4px;">No time slots available for this date</div>';
      }
    }
    
    // Format time from 24h to 12h format
    function formatTime(time24) {
      const [hours, minutes] = time24.split(':');
      const period = hours >= 12 ? 'PM' : 'AM';
      const hours12 = hours % 12 || 12;
      return `${hours12}:${minutes} ${period}`;
    }
    
    // Update time slots when therapist changes
    document.getElementById('therapist_id').addEventListener('change', function() {
      const selectedDate = document.getElementById('appointment_date').value;
      if (selectedDate) {
        updateTimeSlots(selectedDate);
      }
    });
    
    // Payment method selection
    function selectPayment(element, method) {
      // Remove selected class from all payment options
      document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
      });
      
      // Add selected class to clicked option
      element.classList.add('selected');
      
      // Check the radio button
      document.getElementById(method).checked = true;
      
      // Show the corresponding form
      document.querySelectorAll('.payment-details-form').forEach(form => {
        form.classList.remove('active');
      });
      
      const formId = `${method}_form`;
      const formElement = document.getElementById(formId);
      if (formElement) {
        formElement.classList.add('active');
      }
    }
    
    // Form validation
    document.getElementById('booking-form').addEventListener('submit', function(e) {
      const selectedTime = document.getElementById('selected-time').value;
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
      
      if (!selectedTime) {
        e.preventDefault();
        alert('Please select a time slot');
        return;
      }
      
      if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return;
      }
      
      // Additional validation based on payment method
      const method = paymentMethod.value;
      if (method === 'credit_card') {
        const cardNumber = document.getElementById('card_number')?.value;
        const cardHolder = document.getElementById('card_holder')?.value;
        const expiryDate = document.getElementById('expiry_date')?.value;
        const cvv = document.getElementById('cvv')?.value;
        
        if (!cardNumber || !cardHolder || !expiryDate || !cvv) {
          e.preventDefault();
          alert('Please fill in all credit card details');
          return;
        }
      }
      
      // Show confirmation message
      alert('Your session has been booked successfully! We will confirm your appointment shortly.');
    });
    
    // Input formatting for credit card fields
    document.getElementById('card_number')?.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 16) value = value.slice(0, 16);
      
      // Format with spaces every 4 digits
      let formattedValue = '';
      for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formattedValue += ' ';
        formattedValue += value[i];
      }
      
      e.target.value = formattedValue;
    });
    
    document.getElementById('expiry_date')?.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 4) value = value.slice(0, 4);
      
      // Format as MM/YY
      if (value.length > 2) {
        e.target.value = value.slice(0, 2) + '/' + value.slice(2);
      } else {
        e.target.value = value;
      }
    });
    
    document.getElementById('cvv')?.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
    });
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      // Set minimum date to today
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('appointment_date').setAttribute('min', today);
      
      // Initialize time slots if a date is already selected
      const selectedDate = document.getElementById('appointment_date').value;
      if (selectedDate) {
        updateTimeSlots(selectedDate);
      }
    });
</script>
</body>
</html>
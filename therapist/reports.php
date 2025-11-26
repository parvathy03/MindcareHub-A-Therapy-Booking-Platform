<?php
// report.php — Therapist Report Dashboard (styled like reference)
session_start();

// --- DB CONNECTION ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "mindcare";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Therapist ID (use session if available)
$therapist_id = isset($_SESSION['therapist_id']) ? (int)$_SESSION['therapist_id'] : 1;

// --- METRICS ---
function scalar($res, $key='c'){ return ($res && $res->num_rows) ? (int)$res->fetch_assoc()[$key] : 0; }

$totalSessions = scalar($conn->query("SELECT COUNT(*) c FROM appointments WHERE therapist_id = $therapist_id"));
$activeClients = scalar($conn->query("SELECT COUNT(DISTINCT user_id) c FROM appointments WHERE therapist_id = $therapist_id"));
$completedThisMonth = scalar($conn->query("SELECT COUNT(*) c FROM appointments WHERE therapist_id = $therapist_id AND status='completed' AND YEAR(appointment_date)=YEAR(CURDATE()) AND MONTH(appointment_date)=MONTH(CURDATE())"));
$upcoming7 = scalar($conn->query("SELECT COUNT(*) c FROM appointments WHERE therapist_id = $therapist_id AND status='approved' AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)"));

// --- CHART: Sessions per month (current year) ---
$months = []; $totals = [];
$monthNames = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$qMonthly = $conn->query("SELECT MONTH(appointment_date) m, COUNT(*) t FROM appointments WHERE therapist_id=$therapist_id AND YEAR(appointment_date)=YEAR(CURDATE()) GROUP BY MONTH(appointment_date) ORDER BY MONTH(appointment_date)");
if ($qMonthly) { while($r=$qMonthly->fetch_assoc()){ $months[]=$monthNames[(int)$r['m']]; $totals[]=(int)$r['t']; } }

// --- Upcoming appointments (next 10) ---
$upcoming = [];
$qUpcoming = $conn->query("SELECT a.id, u.name AS client_name, a.therapy_type, a.appointment_date, a.appointment_time, a.status, a.session_type
                           FROM appointments a JOIN users u ON u.id=a.user_id
                           WHERE a.therapist_id=$therapist_id AND a.status='approved' AND a.appointment_date>=CURDATE()
                           ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 10");
if ($qUpcoming) { while($r=$qUpcoming->fetch_assoc()){ $upcoming[]=$r; } }

// --- History (last 10 completed) ---
$history = [];
$qHistory = $conn->query("SELECT a.id, u.name AS client_name, a.therapy_type, a.appointment_date, a.appointment_time, a.status
                          FROM appointments a JOIN users u ON u.id=a.user_id
                          WHERE a.therapist_id=$therapist_id AND a.status='completed'
                          ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 10");
if ($qHistory) { while($r=$qHistory->fetch_assoc()){ $history[]=$r; } }

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Therapist Report · MindCare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-0" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  :root{ --bg:#f6fbf8; --panel:#ffffff; --ink:#0f1f1c; --muted:#6c7a75; --brand:#2ecc71; --brand-ink:#0f8a48; --accent:#27ae60; --warn:#f39c12; --danger:#e74c3c; --info:#3b5bdb; --radius:18px; --shadow:0 12px 30px rgba(10,40,20,.06); }
  *{box-sizing:border-box}
  body{margin:0;background:linear-gradient(120deg,#f8fdfb 0%, #eef8f2 100%);font-family:'Inter',system-ui,Arial;color:var(--ink);} 
 .layout {display:grid;grid-template-columns:1fr;gap:24px;padding:24px;min-height:100vh}

  /* Sidebar */
  .sidebar{background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;display:flex;flex-direction:column;gap:12px}
  .brand{display:flex;align-items:center;gap:12px}
  .logo{width:40px;height:40px;border-radius:12px;background:var(--brand);display:grid;place-items:center;color:#fff;font-weight:800}
  .nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;color:var(--ink);text-decoration:none}
  .nav a.active,.nav a:hover{background:#e9fbf1;color:var(--brand-ink)}
  .sidebar .foot{margin-top:auto;font-size:12px;color:var(--muted)}

  /* Main */
  .main{display:flex;flex-direction:column;gap:24px}
  .top{background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);padding:16px 18px;display:flex;align-items:center;justify-content:space-between}
  .title{font-size:22px;font-weight:800}
  .actions{display:flex;align-items:center;gap:10px}
  .btn{background:var(--brand);color:#fff;border:none;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer}

  /* KPI cards */
  .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
  .card{background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px}
  .kpi{display:flex;align-items:center;gap:14px}
  .kpi .icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;color:#fff}
  .kpi .meta{color:var(--muted);font-size:12px;font-weight:600}
  .kpi .val{font-size:30px;font-weight:800;color:var(--brand-ink)}
  .i-green{background:var(--accent)} .i-blue{background:#3498db} .i-amber{background:var(--warn)} .i-purple{background:#8e44ad}

  /* Sections */
  .section{background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px}
  .cols{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  h3{margin:0 0 12px 0}

  table{width:100%;border-collapse:collapse}
  th,td{padding:12px 10px;border-bottom:1px solid #edf3f0;font-size:14px}
  th{text-align:left;color:var(--muted);font-weight:700}

  .badge{display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .b-pending{background:#fff5e6;color:#ad6800}
  .b-approved{background:#e8fff3;color:#1e874e}
  .b-completed{background:#eef2ff;color:#3b5bdb}
  .b-cancelled{background:#fdecea;color:#c92a2a}

  /* Responsive */
  @media (max-width:1100px){ .layout{grid-template-columns:1fr} .grid{grid-template-columns:repeat(2,1fr)} .cols{grid-template-columns:1fr} }
  @media (max-width:700px){ .grid{grid-template-columns:1fr} }
</style>
</head>
<body>
  <div class="layout">
   

   <!-- MAIN -->
<main class="main">
  <div class="top">
    <div class="title">Report</div>
   
  </div>


      <!-- KPI CARDS -->
      <div class="grid">
        <div class="card">
          <div class="kpi"><div class="icon i-green"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <div>
              <div class="meta">Total Sessions</div>
              <div class="val"><?php echo $totalSessions; ?></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="kpi"><div class="icon i-blue"><i class="fa-regular fa-user"></i></div>
            <div>
              <div class="meta">Active Clients</div>
              <div class="val"><?php echo $activeClients; ?></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="kpi"><div class="icon i-amber"><i class="fa-regular fa-calendar-check"></i></div>
            <div>
              <div class="meta">Completed (This Month)</div>
              <div class="val"><?php echo $completedThisMonth; ?></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="kpi"><div class="icon i-purple"><i class="fa-regular fa-clock"></i></div>
            <div>
              <div class="meta">Upcoming (7 days)</div>
              <div class="val"><?php echo $upcoming7; ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- CHART + UPCOMING TABLE -->
      <div class="cols">
        <section class="section">
          <h3>Sessions per Month</h3>
          <canvas id="sessionsChart" height="190"></canvas>
        </section>

        <section class="section">
          <h3>Upcoming Appointments</h3>
          <table>
            <thead><tr><th>Client</th><th>Therapy</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
              <?php if(count($upcoming)): foreach($upcoming as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['therapy_type'] ?: 'Session'); ?></td>
                  <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                  <td><?php echo substr($row['appointment_time'],0,5); ?></td>
                  <td>
                    <?php $b = 'b-' . $row['status']; ?>
                    <span class="badge <?php echo $b; ?>"><?php echo ucfirst($row['status']); ?></span>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="5">No upcoming appointments.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </section>
      </div>

      <!-- HISTORY -->
      <section class="section">
        <h3>Recent Completed Sessions</h3>
        <table>
          <thead><tr><th>Client</th><th>Therapy</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
          <tbody>
            <?php if(count($history)): foreach($history as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td><?php echo htmlspecialchars($row['therapy_type'] ?: 'Session'); ?></td>
                <td><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></td>
                <td><?php echo substr($row['appointment_time'],0,5); ?></td>
                <td><span class="badge b-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5">No completed sessions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

    </main>
  </div>

  <script>
    const labels = <?php echo json_encode($months); ?>;
    const dataPoints = <?php echo json_encode($totals); ?>;
    const ctx = document.getElementById('sessionsChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: { labels: labels, datasets: [{ label: 'Sessions', data: dataPoints, borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,.12)', fill:true, tension:.35, pointRadius:3 }] },
      options: { plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
    });
  </script>
</body>
</html>




<?php
require '../includes/auth.php';
require '../includes/db.php';

$year = (int)($_GET['year'] ?? date('Y'));

// ============ SUMMARY STATS ============
$summary = $pdo->prepare("
  SELECT 
    COUNT(*) AS total_challans,
    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
    SUM(CASE WHEN status IN ('unpaid','overdue','pending') THEN 1 ELSE 0 END) AS unpaid_count,
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS total_collected,
    SUM(CASE WHEN status IN ('unpaid','overdue') THEN amount ELSE 0 END) AS total_due
  FROM monthly_challans
  WHERE year = ?
");
$summary->execute([$year]);
$s = $summary->fetch();

// ============ MONTH-BY-MONTH ============
$monthly = $pdo->prepare("
  SELECT 
    month,
    COUNT(*) AS total,
    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid,
    SUM(CASE WHEN status IN ('unpaid','overdue','pending') THEN 1 ELSE 0 END) AS unpaid,
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS collected,
    SUM(amount) AS total_amount
  FROM monthly_challans
  WHERE year = ?
  GROUP BY month
");
$monthly->execute([$year]);
$monthlyRows = $monthly->fetchAll();

// Order months correctly
$monthOrder = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$monthlyByMonth = [];
foreach ($monthlyRows as $r) {
  $monthlyByMonth[$r['month']] = $r;
}

// ============ TOP DEFAULTERS ============
$defaulters = $pdo->prepare("
  SELECT 
    su.id, su.fullname, su.email,
    COUNT(*) AS unpaid_count,
    SUM(c.amount) AS unpaid_amount
  FROM monthly_challans c
  JOIN student_users su ON su.id = c.student_id
  WHERE c.year = ? AND c.status IN ('unpaid','overdue')
  GROUP BY su.id
  ORDER BY unpaid_count DESC, unpaid_amount DESC
  LIMIT 10
");
$defaulters->execute([$year]);
$defaulterRows = $defaulters->fetchAll();

// ============ AVAILABLE YEARS ============
$years = $pdo->query("SELECT DISTINCT year FROM monthly_challans ORDER BY year DESC")->fetchAll();
$availableYears = array_column($years, 'year');
if (empty($availableYears)) $availableYears = [date('Y')];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reports <?= $year ?> | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
  <style>
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    .summary-card {
      background: #fff; padding: 25px; border-radius: 15px;
      box-shadow: 0 6px 20px rgba(10,61,98,.08); border-left: 5px solid #0a3d62;
    }
    .summary-card.green { border-left-color: #22c55e; }
    .summary-card.red { border-left-color: #ef4444; }
    .summary-card.gold { border-left-color: #f6b93b; }
    .summary-card .label {
      font-size: 12px; color: #6b7c93; letter-spacing: 1px;
      text-transform: uppercase; margin-bottom: 8px;
    }
    .summary-card .value {
      font-size: 32px; font-weight: 800; color: #0a3d62;
      font-family: 'Playfair Display', serif;
    }
    .summary-card .sub { font-size: 13px; color: #6b7c93; margin-top: 5px; }

    .month-row {
      background: #fff; padding: 18px 22px; border-radius: 10px;
      margin-bottom: 10px; display: flex; align-items: center; gap: 20px;
      box-shadow: 0 2px 10px rgba(10,61,98,.05);
    }
    .month-row .m-name {
      font-weight: 700; color: #0a3d62; font-size: 16px;
      min-width: 100px;
    }
    .month-row .bar-wrap {
      flex: 1; background: #e2e8f0; border-radius: 20px; height: 12px; overflow: hidden;
    }
    .month-row .bar-fill {
      height: 100%; background: #22c55e; border-radius: 20px; transition: width .4s;
    }
    .month-row .m-stat {
      font-size: 13px; color: #6b7c93; min-width: 120px; text-align: right;
    }
    .month-row .m-amount {
      font-weight: 700; color: #0a3d62; font-size: 14px;
      min-width: 110px; text-align: right;
    }
  </style>
</head>
<body>
<header>
  <h1>Yearly Reports</h1>
  <nav>
    <a href="dashboard.php">Students</a>
    <a href="admission_payments.php">Admission Fees</a>
    <a href="challans.php">Monthly Fees</a>
    <a href="generate_challans.php">Generate</a>
    <a href="reports.php">Reports</a>
    <a href="fees.php">Fee Structure</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:1200px;">

  <!-- Year Selector -->
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:30px;">
    <h2 style="margin:0;">Fee Report — <?= $year ?></h2>
    <form method="GET" style="display:flex; gap:10px;">
      <select name="year" onchange="this.form.submit()" style="padding:10px 16px; border:2px solid #e5eaf0; border-radius:8px; font-size:14px;">
        <?php foreach ($availableYears as $y): ?>
          <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <!-- Summary Cards -->
  <div class="summary-grid">
    <div class="summary-card green">
      <div class="label">Total Collected</div>
      <div class="value">Rs. <?= number_format($s['total_collected'] ?? 0) ?></div>
      <div class="sub"><?= $s['paid_count'] ?? 0 ?> challans paid</div>
    </div>
    <div class="summary-card red">
      <div class="label">Total Pending</div>
      <div class="value">Rs. <?= number_format($s['total_due'] ?? 0) ?></div>
      <div class="sub"><?= $s['unpaid_count'] ?? 0 ?> challans unpaid</div>
    </div>
    <div class="summary-card gold">
      <div class="label">Total Challans</div>
      <div class="value"><?= $s['total_challans'] ?? 0 ?></div>
      <div class="sub">Generated in <?= $year ?></div>
    </div>
    <div class="summary-card">
      <div class="label">Collection Rate</div>
      <?php 
        $rate = ($s['total_challans'] ?? 0) > 0 
          ? round(($s['paid_count'] / $s['total_challans']) * 100) 
          : 0;
      ?>
      <div class="value"><?= $rate ?>%</div>
      <div class="sub">Paid vs total</div>
    </div>
  </div>

  <!-- Month-by-Month -->
  <h3 style="color:#0a3d62; margin-bottom:20px;">📅 Month-by-Month Breakdown</h3>
  <?php if (empty($monthlyByMonth)): ?>
    <div class="alert success">No data for <?= $year ?>. Generate some challans first.</div>
  <?php else: ?>
    <?php foreach ($monthOrder as $m): ?>
      <?php if (!isset($monthlyByMonth[$m])) continue; ?>
      <?php 
        $row = $monthlyByMonth[$m];
        $pct = $row['total'] > 0 ? round(($row['paid'] / $row['total']) * 100) : 0;
      ?>
      <div class="month-row">
        <div class="m-name"><?= $m ?></div>
        <div class="bar-wrap">
          <div class="bar-fill" style="width:<?= $pct ?>%; background:<?= $pct == 100 ? '#22c55e' : ($pct >= 50 ? '#f59e0b' : '#ef4444') ?>;"></div>
        </div>
        <div class="m-stat"><?= $row['paid'] ?>/<?= $row['total'] ?> paid (<?= $pct ?>%)</div>
        <div class="m-amount">Rs. <?= number_format($row['collected']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Top Defaulters -->
  <h3 style="color:#0a3d62; margin:40px 0 20px;">⚠️ Top Defaulters — <?= $year ?></h3>
  <?php if (empty($defaulterRows)): ?>
    <div class="alert success">🎉 No defaulters! All students are up to date.</div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Student</th>
          <th>Grade</th>
          <th>Unpaid Months</th>
          <th>Total Due</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($defaulterRows as $i => $d): ?>
          <tr>
            <td><strong>#<?= $i + 1 ?></strong></td>
            <td>
              <strong><?= htmlspecialchars($d['fullname']) ?></strong><br>
              <small style="color:#6b7c93;"><?= htmlspecialchars($d['email']) ?></small>
            </td>
            <td>—</td>
            <td>
              <span style="padding:4px 12px; border-radius:20px; background:#fee2e2; color:#991b1b; font-size:12px; font-weight:600;">
                <?= $d['unpaid_count'] ?> months
              </span>
            </td>
            <td style="font-weight:700; color:#ef4444;">Rs. <?= number_format($d['unpaid_amount']) ?></td>
            <td>
              <a class="btn view" href="view_student.php?id=<?= $d['id'] ?>">View Student</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- Actions -->
  <div style="text-align:center; margin-top:40px;">
    <button onclick="window.print()" style="background:#0a3d62; color:#fff; padding:14px 32px; border:none; border-radius:50px; font-weight:600; cursor:pointer; font-size:15px;">
      🖨️ Print / Save Report as PDF
    </button>
  </div>

</section>
</body>
</html>
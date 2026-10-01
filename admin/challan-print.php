<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/mailer.php';

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $month = trim($_POST['month'] ?? '');
  $year  = (int)($_POST['year'] ?? date('Y'));
  $due   = $_POST['due_date'] ?? date('Y-m-d', strtotime('+10 days'));

  if (!$month || !$year) {
    $err = 'Please pick month and year.';
  } else {
    // Get all approved students
    $students = $pdo->query("
      SELECT DISTINCT su.id, su.fullname, su.email, a.grade
      FROM student_users su
      JOIN applications a ON a.student_id = su.id
      WHERE a.status = 'approved'
    ")->fetchAll();

    $created = 0; $skipped = 0; $emailed = 0;

    foreach ($students as $s) {
      // Get monthly fee for their grade
      $feeRow = $pdo->prepare("SELECT monthly_fee FROM fees WHERE class_name = ?");
      $feeRow->execute([$s['grade']]);
      $fee = $feeRow->fetch();
      if (!$fee) { $skipped++; continue; }

      // Check if challan already exists
      $chk = $pdo->prepare("SELECT id FROM monthly_challans WHERE student_id=? AND month=? AND year=?");
      $chk->execute([$s['id'], $month, $year]);
      if ($chk->fetch()) { $skipped++; continue; }

      // Create challan
      $ins = $pdo->prepare("INSERT INTO monthly_challans (student_id, grade, month, year, amount, due_date) VALUES (?,?,?,?,?,?)");
      $ins->execute([$s['id'], $s['grade'], $month, $year, $fee['monthly_fee'], $due]);
      $challanId = $pdo->lastInsertId();
      $created++;

      // ===== SEND EMAIL TO STUDENT =====
      $challanNo = 'WSS-' . str_pad($challanId, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr($month,0,3)) . $year;
      $downloadLink = "http://localhost/watan-school/student/challan-print.php?id=$challanId";
      $portalLink   = "http://localhost/watan-school/student/monthly-fees.php";

      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#0a3d62; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:22px;'>🎓 Watan School System</h1>
            <p style='margin:6px 0 0; color:#f6b93b; font-size:13px; letter-spacing:1px;'>MONTHLY FEE CHALLAN</p>
          </div>

          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Hi " . htmlspecialchars($s['fullname']) . ",</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              Your monthly fee challan for <strong>" . htmlspecialchars($month) . " " . $year . "</strong> has been generated.
            </p>

            <div style='background:#f7f9fc; border-left:5px solid #f6b93b; padding:20px; margin:25px 0;'>
              <table style='width:100%; font-size:14px;'>
                <tr><td style='padding:5px 0; color:#6b7c93;'>Challan No:</td><td style='text-align:right; font-weight:700; color:#0a3d62;'>" . $challanNo . "</td></tr>
                <tr><td style='padding:5px 0; color:#6b7c93;'>Grade:</td><td style='text-align:right; font-weight:600;'>" . htmlspecialchars($s['grade']) . "</td></tr>
                <tr><td style='padding:5px 0; color:#6b7c93;'>Month:</td><td style='text-align:right; font-weight:600;'>" . htmlspecialchars($month) . " " . $year . "</td></tr>
                <tr><td style='padding:5px 0; color:#6b7c93;'>Due Date:</td><td style='text-align:right; font-weight:600; color:#ef4444;'>" . date('d M Y', strtotime($due)) . "</td></tr>
              </table>
              <hr style='border:none; border-top:1px solid #e5eaf0; margin:15px 0;'>
              <div style='text-align:center;'>
                <span style='color:#6b7c93; font-size:12px; letter-spacing:1px;'>TOTAL PAYABLE</span><br>
                <span style='font-size:32px; font-weight:800; color:#0a3d62;'>Rs. " . number_format($fee['monthly_fee']) . "</span>
              </div>
            </div>

            <p style='color:#2c3e50; line-height:1.7;'>
              <strong>How to pay:</strong>
            </p>
            <ul style='color:#2c3e50; line-height:1.9; font-size:14px;'>
              <li>📱 <strong>EasyPaisa:</strong> 0346 7857152</li>
              <li>🏦 <strong>MCB Bank IBAN:</strong> PK52 MUCB 0828 0330 6100 1747</li>
              <li>🏫 <strong>Cash at office:</strong> Mon–Sat, 9 AM – 5 PM</li>
            </ul>

            <div style='text-align:center; margin:30px 0;'>
              <a href='$downloadLink' style='background:#f6b93b; color:#0a3d62; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block; margin:5px;'>
                📄 Download Challan
              </a>
              <a href='$portalLink' style='background:#0a3d62; color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block; margin:5px;'>
                💳 Go to Portal
              </a>
            </div>

            <p style='color:#6b7c93; font-size:13px; line-height:1.7;'>
              After payment, please upload the receipt in your Student Portal → Monthly Fees section.
            </p>

            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>
              Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";

      $result = send_mail(
        $s['email'],
        $s['fullname'],
        '📄 Your ' . $month . ' ' . $year . ' Fee Challan — Watan School System',
        $body
      );

      if ($result === true) $emailed++;
    }

    $msg = "✅ Generated <strong>$created</strong> challans for $month $year. Skipped $skipped (already existed or no fee set). Emailed <strong>$emailed</strong> students.";
  }
}

$recent = $pdo->query("
  SELECT month, year, COUNT(*) as total, 
         SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid,
         SUM(amount) as total_amount
  FROM monthly_challans
  GROUP BY month, year
  ORDER BY year DESC, id DESC
  LIMIT 12
")->fetchAll();

$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Generate Challans | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1>Generate Monthly Challans</h1>
  <nav>
    <a href="dashboard.php">Students</a>
    <a href="admission_payments.php">Admission Fees</a>
    <a href="challans.php">Monthly Fees</a>
    <a href="generate_challans.php">Generate</a>
    <a href="fees.php">Fee Structure</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:900px;">
  <h2>Generate Challans for a Month</h2>
  <p class="section-sub">Creates a fee bill for every approved student, and emails them automatically.</p>

  <?php if ($err): ?><p class="alert error"><?= $err ?></p><?php endif; ?>
  <?php if ($msg): ?><p class="alert success"><?= $msg ?></p><?php endif; ?>

  <form method="POST" class="form-box">
    <select name="month" required>
      <option value="">Select Month</option>
      <?php foreach ($months as $m): ?>
        <option <?= $m === date('F') ? 'selected' : '' ?>><?= $m ?></option>
      <?php endforeach; ?>
    </select>

    <input name="year" type="number" value="<?= date('Y') ?>" required>

    <label style="color:#6b7c93; font-size:14px;">Due Date</label>
    <input name="due_date" type="date" value="<?= date('Y-m-d', strtotime('+10 days')) ?>" required>

    <button type="submit">Generate Challans & Email Students</button>
    <small style="color:#6b7c93; text-align:center;">Only approved students will get a challan.</small>
  </form>
</section>

<section style="max-width:900px;">
  <h2>Recent Months</h2>
  <p class="section-sub">Overview of generated challans.</p>

  <?php if (!$recent): ?>
    <div class="alert success">No challans generated yet.</div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr><th>Month</th><th>Total Challans</th><th>Paid</th><th>Total Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['month']) ?> <?= $r['year'] ?></strong></td>
            <td><?= $r['total'] ?></td>
            <td><?= $r['paid'] ?> / <?= $r['total'] ?></td>
            <td>Rs. <?= number_format($r['total_amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
</body>
</html>
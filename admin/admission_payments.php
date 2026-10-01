<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/mailer.php';

// Handle verify/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id     = (int)$_POST['id'];
  $status = $_POST['status'] === 'verified' ? 'verified' : 'rejected';

  // Get payment + student info
  $stmt = $pdo->prepare("
    SELECT ap.*, su.fullname, su.email, su.id AS sid
    FROM admission_payments ap
    JOIN student_users su ON su.id = ap.student_id
    WHERE ap.id = ?
  ");
  $stmt->execute([$id]);
  $payment = $stmt->fetch();

  if ($payment) {
    // Update status
    $pdo->prepare("UPDATE admission_payments SET status=?, verified_at=NOW() WHERE id=?")
        ->execute([$status, $id]);

    // Send email
    if ($status === 'verified') {
      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#22c55e; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:24px;'>✅ Payment Verified</h1>
            <p style='margin:6px 0 0; font-size:13px; opacity:.9;'>WATAN SCHOOL SYSTEM</p>
          </div>
          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Great news, " . htmlspecialchars($payment['fullname']) . "!</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              Your <strong>admission fee payment</strong> has been verified by the school administration.
            </p>

            <div style='background:#e5f8ec; border-left:5px solid #22c55e; padding:20px; margin:25px 0;'>
              <p style='margin:5px 0;'><strong>Amount:</strong> Rs. " . number_format($payment['amount']) . "</p>
              <p style='margin:5px 0;'><strong>Method:</strong> " . htmlspecialchars($payment['method']) . "</p>
              <p style='margin:5px 0;'><strong>Date:</strong> " . date('d M Y', strtotime($payment['created_at'])) . "</p>
            </div>

            <p style='color:#2c3e50; line-height:1.7;'>
              <strong>What's next?</strong>
            </p>
            <ul style='color:#2c3e50; line-height:1.9;'>
              <li>Wait for your first monthly fee challan</li>
              <li>Upload any remaining documents in your portal</li>
              <li>Contact the office if you have questions</li>
            </ul>

            <div style='text-align:center; margin:30px 0;'>
              <a href='http://localhost/watan-school/student/dashboard.php' style='background:#0a3d62; color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;'>
                Go to My Dashboard →
              </a>
            </div>

            <p style='color:#6b7c93; font-size:13px;'>Welcome to the Watan School System family!</p>
            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";
      send_mail($payment['email'], $payment['fullname'], '✅ Your Admission Fee is Verified — Watan School System', $body);
    }

    if ($status === 'rejected') {
      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#ef4444; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:24px;'>⚠️ Payment Rejected</h1>
            <p style='margin:6px 0 0; font-size:13px; opacity:.9;'>WATAN SCHOOL SYSTEM</p>
          </div>
          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Hi " . htmlspecialchars($payment['fullname']) . ",</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              Unfortunately, we could not verify your <strong>admission fee payment</strong>.
            </p>

            <div style='background:#fee2e2; border-left:5px solid #ef4444; padding:20px; margin:25px 0;'>
              <p style='margin:5px 0;'><strong>Amount:</strong> Rs. " . number_format($payment['amount']) . "</p>
              <p style='margin:5px 0;'><strong>Method:</strong> " . htmlspecialchars($payment['method']) . "</p>
            </div>

            <p style='color:#2c3e50; line-height:1.7;'>
              <strong>What to do:</strong>
            </p>
            <ul style='color:#2c3e50; line-height:1.9;'>
              <li>Make sure you uploaded a clear receipt</li>
              <li>Double-check the payment amount matches the fee</li>
              <li>Re-upload a valid receipt in your portal</li>
              <li>Or contact the office: 📞 0334 9976001</li>
            </ul>

            <div style='text-align:center; margin:30px 0;'>
              <a href='http://localhost/watan-school/student/pay-admission.php' style='background:#f6b93b; color:#0a3d62; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;'>
                Re-upload Receipt →
              </a>
            </div>

            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";
      send_mail($payment['email'], $payment['fullname'], '⚠️ Payment Rejected — Watan School System', $body);
    }
  }
}

$rows = $pdo->query("
  SELECT ap.*, su.fullname, su.email, su.id AS user_id
  FROM admission_payments ap
  JOIN student_users su ON su.id = ap.student_id
  ORDER BY ap.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admission Payments | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1>Admission Payments</h1>
  <nav>
    <a href="dashboard.php">Students</a>
    <a href="admission_payments.php">Admission Fees</a>
    <a href="challans.php">Monthly Fees</a>
    <a href="generate_challans.php">Generate</a>
    <a href="fees.php">Fee Structure</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:1200px;">
  <h2>Admission Fee Payments (<?= count($rows) ?>)</h2>
  <p class="section-sub">Verify or reject student admission fee payments. Students are notified by email.</p>

  <?php if (!$rows): ?>
    <div class="alert success">No admission payments uploaded yet.</div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th><th>Student</th><th>Amount</th><th>Method</th>
          <th>Receipt</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= $r['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($r['fullname']) ?></strong><br>
              <small style="color:#6b7c93;"><?= htmlspecialchars($r['email']) ?></small>
            </td>
            <td>Rs. <?= number_format($r['amount']) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td>
              <a class="btn view" href="download_admission.php?id=<?= $r['id'] ?>">Download</a>
            </td>
            <td>
              <span style="padding:4px 12px; border-radius:20px; font-size:12px; color:#fff;
                background:<?= $r['status']==='verified' ? '#22c55e' : ($r['status']==='rejected' ? '#ef4444' : '#f59e0b') ?>;">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($r['status'] !== 'verified'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="status" value="verified">
                  <button class="btn approve" type="submit">Verify</button>
                </form>
              <?php endif; ?>
              <?php if ($r['status'] !== 'rejected'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="status" value="rejected">
                  <button class="btn reject" type="submit">Reject</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
</body>
</html>
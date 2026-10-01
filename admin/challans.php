<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/mailer.php';

// Verify / Reject monthly payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id     = (int)$_POST['id'];
  $status = in_array($_POST['status'], ['paid', 'unpaid']) ? $_POST['status'] : 'unpaid';

  // Get challan + student
  $stmt = $pdo->prepare("
    SELECT c.*, su.fullname, su.email
    FROM monthly_challans c
    JOIN student_users su ON su.id = c.student_id
    WHERE c.id = ?
  ");
  $stmt->execute([$id]);
  $challan = $stmt->fetch();

  if ($challan) {
    if ($status === 'paid') {
      $pdo->prepare("UPDATE monthly_challans SET status='paid', paid_at=NOW(), verified_by=? WHERE id=?")
          ->execute([$_SESSION['admin'], $id]);

      // Send confirmation email
      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#22c55e; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:24px;'>✅ Payment Confirmed</h1>
            <p style='margin:6px 0 0; font-size:13px; opacity:.9;'>WATAN SCHOOL SYSTEM</p>
          </div>
          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Hi " . htmlspecialchars($challan['fullname']) . ",</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              Your monthly fee payment for <strong>" . htmlspecialchars($challan['month']) . " " . $challan['year'] . "</strong> has been verified. Thank you!
            </p>

            <div style='background:#e5f8ec; border-left:5px solid #22c55e; padding:20px; margin:25px 0;'>
              <p style='margin:5px 0;'><strong>Amount Paid:</strong> Rs. " . number_format($challan['amount']) . "</p>
              <p style='margin:5px 0;'><strong>Method:</strong> " . htmlspecialchars($challan['method'] ?: '—') . "</p>
              <p style='margin:5px 0;'><strong>For:</strong> " . htmlspecialchars($challan['month']) . " " . $challan['year'] . "</p>
              <p style='margin:5px 0;'><strong>Grade:</strong> " . htmlspecialchars($challan['grade']) . "</p>
            </div>

            <p style='color:#2c3e50; line-height:1.7;'>
              Your fee has been successfully recorded. Keep this email as proof of payment.
            </p>

            <div style='text-align:center; margin:30px 0;'>
              <a href='http://localhost/watan-school/student/monthly-fees.php' style='background:#0a3d62; color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;'>
                View My Fees →
              </a>
            </div>

            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";
      send_mail($challan['email'], $challan['fullname'], '✅ Fee Payment Confirmed — ' . $challan['month'] . ' ' . $challan['year'], $body);

    } else {
      // Rejected — set back to unpaid
      $pdo->prepare("UPDATE monthly_challans SET status='unpaid', receipt_file=NULL, method=NULL WHERE id=?")
          ->execute([$id]);

      // Send rejection email
      $body = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; padding:30px; background:#f7f9fc;'>
          <div style='background:#ef4444; color:#fff; padding:25px; border-radius:10px 10px 0 0; text-align:center;'>
            <h1 style='margin:0; font-size:24px;'>⚠️ Payment Rejected</h1>
            <p style='margin:6px 0 0; font-size:13px; opacity:.9;'>WATAN SCHOOL SYSTEM</p>
          </div>
          <div style='background:#fff; padding:35px; border-radius:0 0 10px 10px;'>
            <h2 style='color:#0a3d62; margin-top:0;'>Hi " . htmlspecialchars($challan['fullname']) . ",</h2>
            <p style='color:#2c3e50; line-height:1.7;'>
              We could not verify your monthly fee payment for <strong>" . htmlspecialchars($challan['month']) . " " . $challan['year'] . "</strong>.
            </p>

            <div style='background:#fee2e2; border-left:5px solid #ef4444; padding:20px; margin:25px 0;'>
              <p style='margin:5px 0;'><strong>Amount:</strong> Rs. " . number_format($challan['amount']) . "</p>
              <p style='margin:5px 0;'><strong>Month:</strong> " . htmlspecialchars($challan['month']) . " " . $challan['year'] . "</p>
            </div>

            <p style='color:#2c3e50; line-height:1.7;'>
              Please re-upload a clear receipt, or contact the office if you believe this is a mistake.
            </p>

            <div style='text-align:center; margin:30px 0;'>
              <a href='http://localhost/watan-school/student/monthly-fees.php' style='background:#f6b93b; color:#0a3d62; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;'>
                Re-upload Receipt →
              </a>
            </div>

            <p style='color:#6b7c93; font-size:12px; margin-top:30px;'>
              — Watan School System<br>Darazinda · 📞 0334 9976001
            </p>
          </div>
        </div>
      ";
      send_mail($challan['email'], $challan['fullname'], '⚠️ Fee Payment Rejected — ' . $challan['month'] . ' ' . $challan['year'], $body);
    }
  }
}

$monthFilter  = $_GET['month'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT c.*, su.fullname, su.email 
        FROM monthly_challans c
        JOIN student_users su ON su.id = c.student_id
        WHERE 1=1";
$params = [];

if ($monthFilter)  { $sql .= " AND c.month = ?";  $params[] = $monthFilter; }
if ($statusFilter) { $sql .= " AND c.status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY c.year DESC, c.id DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Monthly Fees | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1>Monthly Fees</h1>
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
  <h2>All Monthly Challans (<?= count($rows) ?>)</h2>
  <p class="section-sub">Verify payments and track who has paid. Students are notified by email.</p>

  <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:25px;">
    <select name="month" style="padding:10px 14px; border:2px solid #e5eaf0; border-radius:8px;">
      <option value="">All Months</option>
      <?php foreach ($months as $m): ?>
        <option <?= $monthFilter === $m ? 'selected' : '' ?>><?= $m ?></option>
      <?php endforeach; ?>
    </select>

    <select name="status" style="padding:10px 14px; border:2px solid #e5eaf0; border-radius:8px;">
      <option value="">All Statuses</option>
      <option value="unpaid"  <?= $statusFilter==='unpaid'?'selected':'' ?>>Unpaid</option>
      <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending Verification</option>
      <option value="paid"    <?= $statusFilter==='paid'?'selected':'' ?>>Paid</option>
      <option value="overdue" <?= $statusFilter==='overdue'?'selected':'' ?>>Overdue</option>
    </select>

    <button type="submit" style="background:#0a3d62; color:#fff; padding:10px 22px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filter</button>
    <a href="challans.php" style="padding:10px 22px; color:#6b7c93;">Reset</a>
  </form>

  <?php if (!$rows): ?>
    <div class="alert success">No challans found.</div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th><th>Student</th><th>Grade</th><th>Month</th>
          <th>Amount</th><th>Due</th><th>Receipt</th><th>Status</th><th>Action</th>
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
            <td><?= htmlspecialchars($r['grade']) ?></td>
            <td><?= htmlspecialchars($r['month']) ?> <?= $r['year'] ?></td>
            <td>Rs. <?= number_format($r['amount']) ?></td>
            <td><?= $r['due_date'] ?></td>
            <td>
              <a class="btn view" href="challan-print.php?id=<?= $r['id'] ?>" target="_blank">Challan</a>
              <?php if ($r['receipt_file']): ?>
                <a class="btn approve" href="download_challan_receipt.php?id=<?= $r['id'] ?>">Receipt</a>
              <?php endif; ?>
            </td>
            <td>
              <span style="padding:4px 12px; border-radius:20px; font-size:12px; color:#fff;
                background:<?= 
                  $r['status']==='paid' ? '#22c55e' :
                  ($r['status']==='pending' ? '#f59e0b' :
                  ($r['status']==='overdue' ? '#ef4444' : '#64748b')) ?>;">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="status" value="paid">
                  <button class="btn approve" type="submit">Verify</button>
                </form>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="status" value="unpaid">
                  <button class="btn reject" type="submit">Reject</button>
                </form>
              <?php else: ?>
                —
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
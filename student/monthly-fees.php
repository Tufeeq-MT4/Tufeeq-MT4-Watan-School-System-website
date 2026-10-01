<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];

$appStmt = $pdo->prepare("SELECT grade FROM applications WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$appStmt->execute([$sid]);
$app = $appStmt->fetch();

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $challanId = (int)$_POST['challan_id'];
  $method    = trim($_POST['method'] ?? '');

  $chk = $pdo->prepare("SELECT * FROM monthly_challans WHERE id=? AND student_id=? AND status IN ('unpaid','overdue')");
  $chk->execute([$challanId, $sid]);
  $challan = $chk->fetch();

  if (!$challan) {
    $error = 'Invalid challan.';
  } elseif (!$method) {
    $error = 'Please choose a payment method.';
  } elseif (empty($_FILES['receipt']['name'])) {
    $error = 'Please upload your receipt.';
  } else {
    $f = $_FILES['receipt'];
    $allowed = ['image/jpeg','image/png','application/pdf'];

    if (!in_array(mime_content_type($f['tmp_name']), $allowed)) {
      $error = 'Only JPG, PNG, or PDF allowed.';
    } elseif ($f['size'] > 5 * 1024 * 1024) {
      $error = 'File too large. Max 5 MB.';
    } else {
      $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
      $newName = 'challan' . $challanId . '_student' . $sid . '_' . time() . '.' . $ext;
      $dest = __DIR__ . '/uploads/' . $newName;

      if (move_uploaded_file($f['tmp_name'], $dest)) {
        $pdo->prepare("UPDATE monthly_challans SET status='pending', method=?, receipt_file=? WHERE id=?")
            ->execute([$method, $newName, $challanId]);
        $success = '✅ Receipt uploaded! The principal will verify it soon.';
      } else {
        $error = 'Upload failed. Try again.';
      }
    }
  }
}

$stmt = $pdo->prepare("SELECT * FROM monthly_challans WHERE student_id = ? ORDER BY year DESC, id DESC");
$stmt->execute([$sid]);
$challans = $stmt->fetchAll();

$methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Monthly Fees | Watan School</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1><a href="dashboard.php">Watan School</a></h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="monthly-fees.php">Monthly Fees</a>
    <a href="upload.php">Documents</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:1000px;">
  <h2>My Monthly Fees</h2>
  <p class="section-sub">Download your challan, pay, and upload the receipt.</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <?php if (!$app): ?>
    <div class="alert error">You must submit your application first.</div>
  <?php elseif (!$challans): ?>
    <div class="alert success">No challans generated yet. Please wait for the school to issue them.</div>
  <?php else: ?>

    <?php
      $paidCount = 0; $unpaidCount = 0; $totalPaid = 0; $totalDue = 0;
      foreach ($challans as $c) {
        if ($c['status'] === 'paid') { $paidCount++; $totalPaid += $c['amount']; }
        else { $unpaidCount++; $totalDue += $c['amount']; }
      }
    ?>

    <div class="cards" style="margin-bottom:40px;">
      <div class="card" style="text-align:center;">
        <span class="card-icon">✅</span>
        <h3><?= $paidCount ?> Months Paid</h3>
        <p>Rs. <?= number_format($totalPaid) ?> total</p>
      </div>
      <div class="card" style="text-align:center;">
        <span class="card-icon">⏳</span>
        <h3><?= $unpaidCount ?> Months Pending</h3>
        <p>Rs. <?= number_format($totalDue) ?> total due</p>
      </div>
    </div>

    <h3>All Challans</h3>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Month</th><th>Amount</th><th>Due Date</th>
          <th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($challans as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['month']) ?> <?= $c['year'] ?></strong></td>
            <td>Rs. <?= number_format($c['amount']) ?></td>
            <td><?= $c['due_date'] ?></td>
            <td>
              <span style="padding:4px 12px; border-radius:20px; font-size:12px; color:#fff;
                background:<?=
                  $c['status']==='paid' ? '#22c55e' :
                  ($c['status']==='pending' ? '#f59e0b' :
                  ($c['status']==='overdue' ? '#ef4444' : '#64748b')) ?>;">
                <?= ucfirst($c['status']) ?>
              </span>
            </td>
            <td>
              <a href="challan-print.php?id=<?= $c['id'] ?>" target="_blank" class="btn view" style="background:#0a3d62;">📄 Challan</a>
              <?php if (in_array($c['status'], ['unpaid','overdue'])): ?>
                <button type="button" class="btn approve" onclick="openPayModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['month'].' '.$c['year']) ?>', <?= $c['amount'] ?>)">Pay</button>
              <?php elseif ($c['status'] === 'pending'): ?>
                <small style="color:#f59e0b;">Awaiting verification</small>
              <?php else: ?>
                <small style="color:#22c55e;">Paid on <?= date('d M', strtotime($c['paid_at'])) ?></small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div id="payModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
      <div style="background:#fff; padding:40px; border-radius:20px; max-width:640px; width:100%; max-height:90vh; overflow:auto;">
        <h3 id="payTitle" style="color:#0a3d62; margin-bottom:8px;"></h3>
        <p id="payAmount" style="color:#f6b93b; font-weight:700; font-size:22px; margin-bottom:25px;"></p>

        <h4 style="margin-bottom:10px;">Send payment to:</h4>
        <div class="cards" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:25px;">
          <?php foreach ($methods as $m): ?>
            <div class="card" style="padding:20px;">
              <strong style="color:#0a3d62;"><?= htmlspecialchars($m['method_name']) ?></strong>
              <p style="font-size:13px; margin-top:8px;">
                <?= htmlspecialchars($m['account_title']) ?><br>
                <code style="background:#f0f4f8; padding:2px 8px; border-radius:5px; font-size:12px;"><?= htmlspecialchars($m['account_number']) ?></code>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" class="form-box" style="box-shadow:none; padding:0; max-width:100%;">
          <input type="hidden" name="challan_id" id="challan_id">
          <select name="method" required>
            <option value="">Payment method used</option>
            <option>Bank Transfer</option>
            <option>JazzCash</option>
            <option>EasyPaisa</option>
            <option>Cash at Office</option>
          </select>
          <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
          <small style="color:#6b7c93;">Upload screenshot / receipt (JPG, PNG, PDF · max 5 MB)</small>
          <button type="submit">Upload Receipt</button>
          <button type="button" onclick="closePayModal()" style="background:#e2e8f0; color:#2c3e50; margin-top:8px;">Cancel</button>
        </form>
      </div>
    </div>

  <?php endif; ?>
</section>

<script>
function openPayModal(id, label, amount) {
  document.getElementById('challan_id').value = id;
  document.getElementById('payTitle').textContent = 'Pay Fee — ' + label;
  document.getElementById('payAmount').textContent = 'Rs. ' + amount.toLocaleString();
  document.getElementById('payModal').style.display = 'flex';
}
function closePayModal() {
  document.getElementById('payModal').style.display = 'none';
}
</script>
</body>
</html>
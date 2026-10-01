<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];

// Get student's application
$a = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$a->execute([$sid]);
$app = $a->fetch();

if (!$app) {
    header('Location: apply.php');
    exit;
}

// Get admission fee for their grade
$grade = $app['grade'];
$feeRow = $pdo->prepare("SELECT admission_fee FROM fees WHERE class_name = ?");
$feeRow->execute([$grade]);
$fee = $feeRow->fetch();
$amount = $fee ? $fee['admission_fee'] : 0;

// Check if already paid/uploaded
$chk = $pdo->prepare("SELECT * FROM admission_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$chk->execute([$sid]);
$existing = $chk->fetch();

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
  $method = trim($_POST['method'] ?? '');

  if (!$method) {
    $error = 'Please choose a payment method.';
  } elseif (empty($_FILES['receipt']['name'])) {
    $error = 'Please upload your payment receipt.';
  } else {
    $f = $_FILES['receipt'];
    $allowed = ['image/jpeg','image/png','application/pdf'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array(mime_content_type($f['tmp_name']), $allowed)) {
      $error = 'Only JPG, PNG, or PDF allowed.';
    } elseif ($f['size'] > $maxSize) {
      $error = 'File too large. Max 5 MB.';
    } else {
      $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
      $newName = 'admission_' . $sid . '_' . time() . '.' . $ext;
      $dest = __DIR__ . '/uploads/' . $newName;

      if (move_uploaded_file($f['tmp_name'], $dest)) {
        $stmt = $pdo->prepare("INSERT INTO admission_payments (student_id, amount, method, receipt_file) VALUES (?,?,?,?)");
        $stmt->execute([$sid, $amount, $method, $newName]);
        $success = '✅ Receipt uploaded! The principal will verify it soon.';
        // refresh existing
        $chk->execute([$sid]);
        $existing = $chk->fetch();
      } else {
        $error = 'Upload failed. Try again.';
      }
    }
  }
}

// Get payment methods
$methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pay Admission Fee | Watan School</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1><a href="dashboard.php">Watan School</a></h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:820px;">
  <h2 style="text-align:center;">Admission Fee Payment</h2>
  <p class="section-sub">Pay once to confirm your admission.</p>

  <?php if ($error): ?><p class="alert error"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p class="alert success"><?= $success ?></p><?php endif; ?>

  <!-- Amount Box -->
  <div class="card" style="background:linear-gradient(135deg,#0a3d62,#062a44); color:#fff; text-align:center; padding:40px; margin-bottom:30px;">
    <p style="letter-spacing:2px; font-size:13px; opacity:.85;">AMOUNT DUE</p>
    <h2 style="color:#f6b93b; font-size:52px; margin:10px 0;">Rs. <?= number_format($amount) ?></h2>
    <p style="opacity:.85;">For <?= htmlspecialchars($grade) ?> admission</p>
  </div>

  <?php if ($existing): ?>
    <!-- Already submitted -->
    <div class="card" style="text-align:center; padding:40px;">
      <?php if ($existing['status'] === 'pending'): ?>
        <span style="font-size:56px;">⏳</span>
        <h3 style="margin:15px 0;">Payment Under Review</h3>
        <p>You submitted Rs. <?= number_format($existing['amount']) ?> via <?= htmlspecialchars($existing['method']) ?>.</p>
        <p style="color:#6b7c93; margin-top:10px;">The principal will verify your payment soon.</p>
      <?php elseif ($existing['status'] === 'verified'): ?>
        <span style="font-size:56px;">✅</span>
        <h3 style="margin:15px 0; color:#22c55e;">Payment Verified!</h3>
        <p>Your admission fee has been confirmed. You're all set!</p>
      <?php else: ?>
        <span style="font-size:56px;">❌</span>
        <h3 style="margin:15px 0; color:#ef4444;">Payment Rejected</h3>
        <p>Please contact the school office or re-upload a valid receipt.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>

    <!-- Payment Methods -->
    <h3 style="margin-top:35px;">1. Choose How to Pay</h3>
    <p style="color:#6b7c93; margin-bottom:15px;">Send the amount to any of the accounts below, then upload your receipt in the next step.</p>

    <div class="cards" style="grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); margin-bottom:30px;">
      <?php foreach ($methods as $m): ?>
        <div class="card">
          <span class="card-icon">💳</span>
          <h3 style="font-size:18px;"><?= htmlspecialchars($m['method_name']) ?></h3>
          <p style="line-height:2; font-size:14px;">
            <strong>Title:</strong> <?= htmlspecialchars($m['account_title'] ?: '—') ?><br>
            <strong>Account:</strong> <code style="background:#f0f4f8; padding:3px 8px; border-radius:5px;"><?= htmlspecialchars($m['account_number'] ?: '—') ?></code>
            <?php if ($m['bank_name']): ?><br><strong>Bank:</strong> <?= htmlspecialchars($m['bank_name']) ?><?php endif; ?>
            <?php if ($m['branch']): ?><br><strong>Branch:</strong> <?= htmlspecialchars($m['branch']) ?><?php endif; ?>
          </p>
          <?php if ($m['instructions']): ?>
            <small style="color:#6b7c93; display:block; margin-top:10px;"><?= htmlspecialchars($m['instructions']) ?></small>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Upload Form -->
    <h3>2. Upload Your Receipt</h3>
    <form method="POST" enctype="multipart/form-data" class="form-box" style="margin-top:15px;">
      <select name="method" required>
        <option value="">Which method did you use?</option>
        <option>Bank Transfer</option>
        <option>JazzCash</option>
        <option>EasyPaisa</option>
        <option>Cash at Office</option>
      </select>

      <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
      <small style="color:#6b7c93;">Upload your payment screenshot or receipt (PDF/JPG/PNG · max 5 MB)</small>

      <button type="submit">Submit Payment Proof</button>
    </form>

    <p style="text-align:center; margin-top:20px; font-size:14px;">
      <a href="dashboard.php" style="color:#6b7c93;">← I'll pay later</a>
    </p>
  <?php endif; ?>
</section>
</body>
</html>
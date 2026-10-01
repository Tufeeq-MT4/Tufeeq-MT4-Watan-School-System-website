<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];

// Student info
$stmt = $pdo->prepare("SELECT * FROM student_users WHERE id = ?");
$stmt->execute([$sid]);
$user = $stmt->fetch();

// Latest application
$appStmt = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$appStmt->execute([$sid]);
$app = $appStmt->fetch();

// Documents
$docStmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY uploaded_at DESC");
$docStmt->execute([$sid]);
$documents = $docStmt->fetchAll();

// Admission payment
$ap = $pdo->prepare("SELECT * FROM admission_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$ap->execute([$sid]);
$admPay = $ap->fetch();

// Monthly challans summary
$mc = $pdo->prepare("SELECT 
                      SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid,
                      SUM(CASE WHEN status IN ('unpaid','overdue','pending') THEN 1 ELSE 0 END) AS due,
                      SUM(CASE WHEN status IN ('unpaid','overdue') THEN amount ELSE 0 END) AS amount_due
                    FROM monthly_challans WHERE student_id = ?");
$mc->execute([$sid]);
$mcStat = $mc->fetch();
$totalChallans = (int)($mcStat['paid'] ?? 0) + (int)($mcStat['due'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Student Dashboard | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1>
    <a href="dashboard.php">
      <img src="/watan-school/images/logo.png" alt="Watan School System" style="height:50px;">
      <span style="font-family:'Playfair Display',serif; font-weight:700;">Watan School System</span>
    </a>
  </h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="monthly-fees.php">Monthly Fees</a>
    <a href="upload.php">Documents</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:1100px;">
  <h2>Welcome, <?= htmlspecialchars($user['fullname']) ?> 👋</h2>
  <p class="section-sub">Track your admission journey here.</p>

  <div class="cards">

    <!-- 1. APPLICATION STATUS -->
    <div class="card">
      <span class="card-icon">📝</span>
      <h3>Application Status</h3>
      <?php if ($app): ?>
        <p><strong>Status:</strong>
          <span style="padding:4px 12px; border-radius:20px; font-size:13px; color:#fff;
            background:<?= $app['status']==='approved' ? '#22c55e' : ($app['status']==='rejected' ? '#ef4444' : '#f59e0b') ?>;">
            <?= ucfirst($app['status']) ?>
          </span>
        </p>
        <p><strong>Grade applied:</strong> <?= htmlspecialchars($app['grade']) ?></p>
        <p><strong>Submitted:</strong> <?= $app['created_at'] ?></p>
        <a href="application-print.php" target="_blank" style="display:inline-block; margin-top:12px; background:#0a3d62; color:#fff; padding:10px 22px; border-radius:8px; font-weight:600;">🖨️ Print Application</a>
      <?php else: ?>
        <p>You haven't submitted an application yet.</p>
        <a href="apply.php" style="display:inline-block; margin-top:12px; background:#f6b93b; color:#0a3d62; padding:10px 22px; border-radius:8px; font-weight:600;">Fill Application →</a>
      <?php endif; ?>
    </div>

    <!-- 2. ADMISSION FEE -->
    <div class="card">
      <span class="card-icon">💳</span>
      <h3>Admission Fee</h3>
      <?php if (!$app): ?>
        <p>Submit your application first.</p>
      <?php elseif (!$admPay): ?>
        <p>You haven't paid your admission fee yet.</p>
        <a href="pay-admission.php" style="display:inline-block; margin-top:12px; background:#f6b93b; color:#0a3d62; padding:10px 22px; border-radius:8px; font-weight:600;">Pay Now →</a>
      <?php elseif ($admPay['status'] === 'pending'): ?>
        <p><strong>Status:</strong>
          <span style="padding:4px 12px; border-radius:20px; font-size:13px; color:#fff; background:#f59e0b;">⏳ Under review</span>
        </p>
        <p><strong>Amount:</strong> Rs. <?= number_format($admPay['amount']) ?></p>
      <?php elseif ($admPay['status'] === 'verified'): ?>
        <p><strong>Status:</strong>
          <span style="padding:4px 12px; border-radius:20px; font-size:13px; color:#fff; background:#22c55e;">✅ Verified</span>
        </p>
        <p><strong>Amount:</strong> Rs. <?= number_format($admPay['amount']) ?></p>
      <?php else: ?>
        <p><strong>Status:</strong>
          <span style="padding:4px 12px; border-radius:20px; font-size:13px; color:#fff; background:#ef4444;">❌ Rejected</span>
        </p>
        <a href="pay-admission.php" style="display:inline-block; margin-top:12px; background:#f6b93b; color:#0a3d62; padding:10px 22px; border-radius:8px; font-weight:600;">Re-upload →</a>
      <?php endif; ?>
    </div>

    <!-- 3. MONTHLY FEES -->
    <div class="card">
      <span class="card-icon">💰</span>
      <h3>Monthly Fees</h3>
      <?php if ($totalChallans === 0): ?>
        <p>No challans generated yet.</p>
      <?php else: ?>
        <p><strong>Paid:</strong> <?= (int)$mcStat['paid'] ?> months</p>
        <p><strong>Pending:</strong> <?= (int)$mcStat['due'] ?> months</p>
        <p><strong>Amount Due:</strong> Rs. <?= number_format((int)$mcStat['amount_due']) ?></p>
      <?php endif; ?>
      <a href="monthly-fees.php" style="display:inline-block; margin-top:12px; background:#0a3d62; color:#fff; padding:10px 22px; border-radius:8px; font-weight:600;">View Monthly Fees →</a>
    </div>

    <!-- 4. DOCUMENTS -->
    <div class="card">
      <span class="card-icon">📄</span>
      <h3>My Documents (<?= count($documents) ?>)</h3>
      <?php if ($documents): ?>
        <?php foreach ($documents as $d): ?>
          <p>✅ <?= htmlspecialchars($d['doc_type']) ?></p>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No documents uploaded yet.</p>
      <?php endif; ?>
      <a href="upload.php" style="display:inline-block; margin-top:12px; background:#0a3d62; color:#fff; padding:10px 22px; border-radius:8px; font-weight:600;">Upload Document →</a>
    </div>

    <!-- 5. PROFILE -->
    <div class="card" style="text-align:center;">
  <?php if (!empty($user['profile_photo'])): ?>
    <img src="uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" 
         style="width:90px; height:90px; object-fit:cover; border-radius:50%; margin:0 auto 15px; display:block; border:4px solid #0a3d62; box-shadow:0 6px 15px rgba(10,61,98,.2);">
  <?php else: ?>
    <div style="width:90px; height:90px; border-radius:50%; background:#e5eaf0; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; font-size:38px; border:4px dashed #cbd5e1;">👤</div>
  <?php endif; ?>
  <h3>My Profile</h3>
  <p><strong>Name:</strong> <?= htmlspecialchars($user['fullname']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
  <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
  <a href="upload.php" style="display:inline-block; margin-top:12px; background:#0a3d62; color:#fff; padding:8px 18px; border-radius:8px; font-weight:600; font-size:14px;">
    <?= !empty($user['profile_photo']) ? 'Update Photo' : 'Upload Photo' ?>
  </a>
</div>

  </div>
</section>
</body>
</html>
<?php
require 'auth.php';
require '../includes/db.php';

$sid = $_SESSION['student_id'];

$stmt = $pdo->prepare("
  SELECT a.*, su.email AS account_email, su.profile_photo
  FROM applications a
  JOIN student_users su ON su.id = a.student_id
  WHERE a.student_id = ?
  ORDER BY a.created_at DESC
  LIMIT 1
");
$stmt->execute([$sid]);
$app = $stmt->fetch();

if (!$app) {
  die('❌ No application found. Please <a href="apply.php">fill the form</a> first.');
}

$school = [
  'name'    => 'Watan School System',
  'tagline' => 'Education is Power',
  'branch'  => 'Darazinda',
  'phone'   => '0334 9976001',
  'email'   => 'watanschoolsystem956@gmail.com',
  'logo'    => '/watan-school/images/logo.png'
];

$applicationNo = 'WSS-APP-' . str_pad($app['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Application #<?= $applicationNo ?> | Watan School System</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
    body { background:#e5e7eb; padding:30px 20px; color:#111; }
    .no-print {
      max-width:850px; margin:0 auto 20px; display:flex; justify-content:space-between; align-items:center;
      background:#fff; padding:15px 25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08);
    }
    .no-print h3 { color:#0a3d62; font-size:18px; }
    .no-print button, .no-print a {
      padding:10px 22px; background:#0a3d62; color:#fff; border:none; border-radius:8px;
      font-weight:600; cursor:pointer; text-decoration:none; font-size:14px;
    }
    .no-print a { background:#64748b; margin-left:8px; }

    .page { max-width:850px; margin:auto; background:#fff; padding:0; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,.1); overflow:hidden; }

    .header {
      background: linear-gradient(135deg, #0a3d62 0%, #1e5a8a 40%, #0a3d62 80%);
      color:#fff; padding:22px 28px; display:flex; align-items:center; gap:20px;
    }
    .header .logo-wrap { width:80px; height:80px; background:#fff; border-radius:50%; padding:6px; flex-shrink:0; }
    .header .logo-wrap img { width:100%; height:100%; object-fit:contain; }
    .header h1 { font-size:26px; margin-bottom:2px; }
    .header .tagline { font-size:13px; color:#f6b93b; font-weight:600; letter-spacing:1px; }
    .header .contact { font-size:12px; margin-top:6px; opacity:.9; }
    .header .app-no { margin-left:auto; text-align:right; }
    .header .app-no span { display:block; font-size:11px; opacity:.85; letter-spacing:1px; }
    .header .app-no strong { font-size:15px; color:#f6b93b; }

    .body-content { padding:30px 35px; }

    .title-bar {
      background:#f6b93b; color:#0a3d62; padding:12px 20px; border-radius:6px;
      text-align:center; font-weight:700; letter-spacing:1.5px; font-size:14px;
      margin-bottom:24px; text-transform:uppercase;
    }

    .section-title {
      background:#0a3d62; color:#fff; padding:8px 15px; border-radius:6px;
      font-size:13px; font-weight:700; letter-spacing:1px; margin:25px 0 15px;
      text-transform:uppercase;
    }
    .section-title:first-of-type { margin-top:0; }

    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 30px; }
    .info-grid div {
      border-bottom:1px dashed #cbd5e1; padding:7px 0;
      display:flex; justify-content:space-between; gap:15px;
    }
    .info-grid span:first-child { color:#6b7c93; font-size:13px; flex-shrink:0; }
    .info-grid span:last-child  { font-weight:600; color:#0a3d62; font-size:14px; text-align:right; word-break:break-word; }
    .info-grid .full-width { grid-column: 1 / -1; }

    .status-badge {
      display:inline-block; padding:5px 14px; color:#fff; border-radius:20px;
      font-size:11px; font-weight:700; letter-spacing:1px;
      background:<?= $app['status']==='approved' ? '#22c55e' : ($app['status']==='rejected' ? '#ef4444' : '#f59e0b') ?>;
    }

    .footer-signatures {
      display:flex; justify-content:space-between; margin-top:50px; padding-top:25px;
      border-top:2px solid #0a3d62;
    }
    .footer-signatures div { text-align:center; }
    .footer-signatures p { font-size:11px; color:#6b7c93; margin-top:8px; }
    .footer-signatures .line { width:150px; border-top:1px solid #111; margin-bottom:5px; }

    .school-footer {
      background:#0a3d62; color:#fff; padding:14px 28px; text-align:center;
      font-size:11px; letter-spacing:.5px;
    }
    .school-footer strong { color:#f6b93b; }

    @media print {
      body { background:#fff; padding:0; }
      .no-print { display:none; }
      .page { box-shadow:none; border-radius:0; max-width:100%; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <h3>Application: <?= $applicationNo ?></h3>
  <div>
    <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="dashboard.php">← Back</a>
  </div>
</div>

<div class="page">
  <div class="header">
    <div class="logo-wrap"><img src="<?= $school['logo'] ?>" alt="Logo"></div>
    <div>
      <h1><?= htmlspecialchars($school['name']) ?></h1>
      <div class="tagline">★ <?= htmlspecialchars($school['tagline']) ?> ★</div>
      <div class="contact">📍 <?= htmlspecialchars($school['branch']) ?> · 📞 <?= htmlspecialchars($school['phone']) ?> · ✉ <?= htmlspecialchars($school['email']) ?></div>
    </div>
    <div class="app-no">
      <span>APPLICATION NO.</span>
      <strong><?= $applicationNo ?></strong>
    </div>
  </div>

  <div class="body-content">
    <div class="title-bar">Admission Application Form</div>

    <!-- TOP ROW: Status left + Photo right -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:25px;">
      <div style="flex:1;">
        <p style="font-size:13px; margin-bottom:10px;">
          <strong>Status:</strong> <span class="status-badge"><?= strtoupper($app['status']) ?></span>
        </p>
        <p style="font-size:12px; color:#6b7c93; line-height:1.8;">
          <strong>Application No:</strong> <?= $applicationNo ?><br>
          <strong>Submitted:</strong> <?= date('d M Y', strtotime($app['created_at'])) ?>
        </p>
      </div>
      <div style="flex-shrink:0; text-align:center;">
        <?php if (!empty($app['profile_photo'])): ?>
          <img src="uploads/<?= htmlspecialchars($app['profile_photo']) ?>" 
               alt="Student Photo"
               style="width:130px; height:160px; object-fit:cover; border:3px solid #0a3d62; border-radius:8px; box-shadow:0 4px 12px rgba(10,61,98,.2);">
        <?php else: ?>
          <div style="width:130px; height:160px; border:3px dashed #cbd5e1; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:44px; color:#94a3b8;">👤</div>
        <?php endif; ?>
        <p style="font-size:11px; color:#6b7c93; margin-top:6px;">Student Photo</p>
      </div>
    </div>

    <!-- STUDENT INFO -->
    <div class="section-title">👤 Student Information</div>
    <div class="info-grid">
      <div><span>Full Name:</span> <span><?= htmlspecialchars($app['fullname']) ?></span></div>
      <div><span>Email:</span> <span><?= htmlspecialchars($app['email']) ?></span></div>
      <div><span>Phone:</span> <span><?= htmlspecialchars($app['phone']) ?></span></div>
      <div><span>Date of Birth:</span> <span><?= $app['dob'] ? date('d M Y', strtotime($app['dob'])) : '—' ?></span></div>
      <div><span>Gender:</span> <span><?= htmlspecialchars($app['gender'] ?: '—') ?></span></div>
      <div><span>Blood Group:</span> <span><?= htmlspecialchars($app['blood_group'] ?: '—') ?></span></div>
      <div><span>Religion:</span> <span><?= htmlspecialchars($app['religion'] ?: '—') ?></span></div>
      <div><span>Nationality:</span> <span><?= htmlspecialchars($app['nationality'] ?: '—') ?></span></div>
    </div>

    <!-- FATHER INFO -->
    <div class="section-title">👨‍👩‍👦 Father Information</div>
    <div class="info-grid">
      <div><span>Father's Name:</span> <span><?= htmlspecialchars($app['father_name'] ?: '—') ?></span></div>
      <div><span>Father's CNIC:</span> <span><?= htmlspecialchars($app['father_cnic'] ?: '—') ?></span></div>
    </div>

    <!-- ACADEMIC -->
    <div class="section-title">📚 Academic Information</div>
    <div class="info-grid">
      <div><span>Grade Applied For:</span> <span><?= htmlspecialchars($app['grade']) ?></span></div>
      <div><span>Previous School:</span> <span><?= htmlspecialchars($app['previous_school'] ?: '—') ?></span></div>
    </div>

    <!-- ADDRESS -->
    <div class="section-title">🏠 Address</div>
    <div class="info-grid">
      <div class="full-width"><span>Home Address:</span> <span><?= nl2br(htmlspecialchars($app['address'] ?: '—')) ?></span></div>
      <div><span>City:</span> <span><?= htmlspecialchars($app['city'] ?: '—') ?></span></div>
    </div>

    <?php if (!empty($app['hear_about']) || !empty($app['message'])): ?>
    <div class="section-title">📣 Additional Info</div>
    <div class="info-grid">
      <div><span>Heard About Us:</span> <span><?= htmlspecialchars($app['hear_about'] ?: '—') ?></span></div>
      <?php if (!empty($app['message'])): ?>
      <div class="full-width"><span>Message:</span> <span><?= nl2br(htmlspecialchars($app['message'])) ?></span></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="footer-signatures">
      <div><div class="line"></div><p>Student's Signature</p></div>
      <div><div class="line"></div><p>Parent / Guardian</p></div>
      <div><div class="line"></div><p>Principal</p></div>
    </div>
  </div>

  <div class="school-footer">
    <strong><?= htmlspecialchars($school['name']) ?></strong> · <?= htmlspecialchars($school['branch']) ?> · <?= htmlspecialchars($school['phone']) ?>
    <br>
    <span style="font-size:10px;">Submitted on <?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></span>
  </div>
</div>

</body>
</html>
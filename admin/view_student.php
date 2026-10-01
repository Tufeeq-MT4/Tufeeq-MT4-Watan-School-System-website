<?php
require '../includes/auth.php';
require '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);

$u = $pdo->prepare("SELECT * FROM student_users WHERE id = ?");
$u->execute([$id]);
$user = $u->fetch();
if (!$user) die('Student not found.');

$a = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$a->execute([$id]);
$app = $a->fetch();

$d = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY uploaded_at DESC");
$d->execute([$id]);
$docs = $d->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($user['fullname']) ?> | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
  <style>
    /* ============ DOCUMENTS SECTION ============ */
    .docs-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-top: 45px;
      margin-bottom: 8px;
    }
    .docs-header h3 {
      font-size: 26px;
      color: #0a3d62;
      margin: 0;
      font-family: 'Playfair Display', serif;
    }
    .docs-count-pill {
      background: linear-gradient(135deg, #f6b93b, #ffb700);
      color: #0a3d62;
      padding: 6px 18px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(246,185,59,.4);
    }
    .docs-sub {
      color: #6b7c93;
      font-size: 15px;
      margin-bottom: 30px;
    }

    .doc-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 28px;
      margin-top: 25px;
    }

    /* ============ BEAUTIFUL DOC CARD ============ */
    .doc-card {
      background: #fff;
      border-radius: 22px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(10,61,98,.10);
      transition: all .4s cubic-bezier(.2,.8,.2,1);
      border: 1px solid #eef2f6;
      position: relative;
    }
    .doc-card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: linear-gradient(90deg, #0a3d62 0%, #f6b93b 50%, #0a3d62 100%);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .5s ease;
      z-index: 5;
    }
    .doc-card:hover::before {
      transform: scaleX(1);
    }
    .doc-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 30px 60px rgba(10,61,98,.22);
      border-color: #f6b93b;
    }

    /* Preview Area */
    .doc-preview {
      height: 220px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
      background: linear-gradient(135deg, #f0f7ff 0%, #fafbfd 50%, #fffbf0 100%);
    }
    .doc-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .6s ease;
    }
    .doc-card:hover .doc-preview img {
      transform: scale(1.1);
    }

    .doc-preview .doc-icon {
      font-size: 90px;
      filter: drop-shadow(0 8px 16px rgba(10,61,98,.15));
      transition: transform .5s ease;
    }
    .doc-card:hover .doc-preview .doc-icon {
      transform: scale(1.15) rotate(-5deg);
    }

    /* Overlay */
    .doc-preview::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(10,61,98,.6) 0%, transparent 50%);
      opacity: 0;
      transition: opacity .3s ease;
      pointer-events: none;
    }
    .doc-card:hover .doc-preview::after {
      opacity: 1;
    }

    /* Extension badge */
    .ext-badge {
      position: absolute;
      top: 14px;
      right: 14px;
      background: rgba(10,61,98,.9);
      color: #f6b93b;
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1px;
      backdrop-filter: blur(8px);
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
      z-index: 3;
    }
    .ext-badge.pdf { background: rgba(239,68,68,.9); color: #fff; }
    .ext-badge.img { background: rgba(34,197,94,.9); color: #fff; }

    /* Info Area */
    .doc-info {
      padding: 22px 24px 24px;
      position: relative;
    }
    .doc-type-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }
    .doc-type-icon {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #0a3d62, #1e5a8a);
      color: #f6b93b;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(10,61,98,.25);
    }
    .doc-type {
      font-size: 17px;
      font-weight: 700;
      color: #0a3d62;
      line-height: 1.2;
    }
    .doc-date {
      font-size: 12px;
      color: #94a3b8;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .doc-name {
      font-size: 12px;
      color: #94a3b8;
      background: #f8fafc;
      padding: 8px 12px;
      border-radius: 8px;
      margin-bottom: 18px;
      word-break: break-all;
      font-family: 'Courier New', monospace;
      border-left: 3px solid #f6b93b;
    }

    /* Download Button */
    .btn-download {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(135deg, #0a3d62 0%, #1e5a8a 100%);
      color: #fff !important;
      padding: 14px 20px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 14px;
      text-decoration: none;
      transition: all .3s ease;
      letter-spacing: .8px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(10,61,98,.25);
    }
    .btn-download::before {
      content: "";
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
      transition: left .5s ease;
    }
    .btn-download:hover::before {
      left: 100%;
    }
    .btn-download:hover {
      background: linear-gradient(135deg, #f6b93b 0%, #d99a1a 100%);
      color: #0a3d62 !important;
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(246,185,59,.45);
    }

    /* Empty State */
    .empty-docs {
      text-align: center;
      padding: 70px 30px;
      background: linear-gradient(135deg, #f0f7ff 0%, #fffbf0 100%);
      border-radius: 22px;
      border: 2px dashed #cbd5e1;
      position: relative;
      overflow: hidden;
    }
    .empty-docs::before {
      content: "";
      position: absolute;
      top: -50px; right: -50px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(246,185,59,.15), transparent 70%);
    }
    .empty-docs .big-icon {
      font-size: 90px;
      display: block;
      margin-bottom: 20px;
      filter: grayscale(.3);
      opacity: .7;
    }
    .empty-docs h4 {
      color: #0a3d62;
      font-size: 20px;
      margin-bottom: 8px;
    }
    .empty-docs p {
      color: #6b7c93;
      font-size: 15px;
    }
  </style>
</head>
<body>
<header>
  <h1>Student Details</h1>
  <nav>
    <a href="dashboard.php">← Back to Dashboard</a>
    <a href="logout.php" class="apply-btn">Logout</a>
  </nav>
</header>

<section style="max-width:1100px;">
  <h2><?= htmlspecialchars($user['fullname']) ?></h2>

  <div class="cards" style="margin-top:25px;">

    <!-- ACCOUNT INFO + PHOTO -->
    <div class="card" style="text-align:center;">
      <?php if (!empty($user['profile_photo'])): ?>
        <img src="../student/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile"
             style="width:130px; height:130px; object-fit:cover; border-radius:50%; margin:0 auto 20px; display:block; border:5px solid #0a3d62; box-shadow:0 8px 20px rgba(10,61,98,.25);">
      <?php else: ?>
        <div style="width:130px; height:130px; border-radius:50%; background:#e5eaf0; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; font-size:50px; border:5px dashed #cbd5e1;">👤</div>
      <?php endif; ?>
      <h3>Account Info</h3>
      <p><strong>ID:</strong> #<?= $user['id'] ?></p>
      <p><strong>Name:</strong> <?= htmlspecialchars($user['fullname']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
      <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: '—') ?></p>
      <p><strong>Registered:</strong> <?= $user['created_at'] ?></p>
    </div>

    <!-- APPLICATION -->
    <?php if ($app): ?>
    <div class="card">
      <span class="card-icon">📝</span>
      <h3>
        Application
        <a href="application-print.php?student=<?= $user['id'] ?>" target="_blank" class="btn view" style="float:right; font-size:12px; padding:6px 14px;">🖨️ Print</a>
      </h3>
      <p><strong>Grade:</strong> <?= htmlspecialchars($app['grade']) ?></p>
      <p><strong>Date of Birth:</strong> <?= $app['dob'] ?: '—' ?></p>
      <p><strong>Gender:</strong> <?= htmlspecialchars($app['gender'] ?: '—') ?></p>
      <p><strong>Blood Group:</strong> <?= htmlspecialchars($app['blood_group'] ?: '—') ?></p>
      <p><strong>Religion:</strong> <?= htmlspecialchars($app['religion'] ?: '—') ?></p>
      <p><strong>Nationality:</strong> <?= htmlspecialchars($app['nationality'] ?: '—') ?></p>
      <hr style="margin:10px 0; border:none; border-top:1px solid #eee;">
      <p><strong>Father's Name:</strong> <?= htmlspecialchars($app['father_name'] ?: '—') ?></p>
      <p><strong>Father's CNIC:</strong> <?= htmlspecialchars($app['father_cnic'] ?: '—') ?></p>
      <p><strong>Previous School:</strong> <?= htmlspecialchars($app['previous_school'] ?: '—') ?></p>
      <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($app['address'] ?: '—')) ?></p>
      <p><strong>City:</strong> <?= htmlspecialchars($app['city'] ?: '—') ?></p>
      <p><strong>Heard about us:</strong> <?= htmlspecialchars($app['hear_about'] ?: '—') ?></p>
      <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($app['message'] ?: '—')) ?></p>
      <p><strong>Submitted:</strong> <?= $app['created_at'] ?></p>
      <p><strong>Status:</strong>
        <span style="padding:4px 12px; border-radius:20px; font-size:13px; color:#fff;
          background:<?= $app['status']==='approved' ? '#22c55e' : ($app['status']==='rejected' ? '#ef4444' : '#f59e0b') ?>;">
          <?= ucfirst($app['status']) ?>
        </span>
      </p>
    </div>
    <?php else: ?>
      <div class="card">
        <span class="card-icon">📝</span>
        <h3>Application</h3>
        <p>Not submitted yet.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- ================= BEAUTIFUL DOCUMENTS SECTION ================= -->
  <div class="docs-header">
    <h3>📂 Uploaded Documents</h3>
    <span class="docs-count-pill"><?= count($docs) ?> <?= count($docs) === 1 ? 'file' : 'files' ?></span>
  </div>
  <p class="docs-sub">Click any document card to download it.</p>

  <?php if ($docs): ?>
    <div class="doc-grid">
      <?php foreach ($docs as $doc): ?>
        <?php
          $filePath = __DIR__ . '/../student/uploads/' . $doc['file_name'];
          $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
          $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
          $fileUrl = '/watan-school/student/uploads/' . htmlspecialchars($doc['file_name']);

          // Icon per document type
          $typeIcon = '📎';
          $docTypeLower = strtolower($doc['doc_type']);
          if (strpos($docTypeLower, 'birth') !== false)      $typeIcon = '👶';
          elseif (strpos($docTypeLower, 'marksheet') !== false) $typeIcon = '📊';
          elseif (strpos($docTypeLower, 'cnic') !== false)   $typeIcon = '🆔';
          elseif (strpos($docTypeLower, 'transfer') !== false) $typeIcon = '📋';
          elseif (strpos($docTypeLower, 'photo') !== false)  $typeIcon = '📸';
        ?>
        <div class="doc-card">

          <!-- Preview -->
          <div class="doc-preview">
            <span class="ext-badge <?= $ext === 'pdf' ? 'pdf' : ($isImage ? 'img' : '') ?>">
              <?= strtoupper($ext) ?>
            </span>

            <?php if ($isImage && file_exists($filePath)): ?>
              <img src="<?= $fileUrl ?>" alt="<?= htmlspecialchars($doc['doc_type']) ?>">
            <?php elseif ($ext === 'pdf'): ?>
              <span class="doc-icon">📕</span>
            <?php else: ?>
              <span class="doc-icon">📎</span>
            <?php endif; ?>
          </div>

          <!-- Info -->
          <div class="doc-info">
            <div class="doc-type-row">
              <span class="doc-type-icon"><?= $typeIcon ?></span>
              <span class="doc-type"><?= htmlspecialchars($doc['doc_type']) ?></span>
            </div>
            <div class="doc-date">
              🕒 <?= date('d M Y · h:i A', strtotime($doc['uploaded_at'])) ?>
            </div>
            <div class="doc-name"><?= htmlspecialchars($doc['file_name']) ?></div>

            <a class="btn-download" href="download.php?id=<?= $doc['id'] ?>">
              ⬇️ Download File
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-docs">
      <span class="big-icon">📭</span>
      <h4>No Documents Yet</h4>
      <p>This student hasn't uploaded any documents.</p>
    </div>
  <?php endif; ?>

</section>
</body>
</html>
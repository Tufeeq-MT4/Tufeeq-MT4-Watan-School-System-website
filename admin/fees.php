<?php
require '../includes/auth.php';
require '../includes/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id            = (int)$_POST['id'];
  $monthly_fee   = (int)$_POST['monthly_fee'];
  $admission_fee = (int)$_POST['admission_fee'];
  $exam_fee      = (int)$_POST['exam_fee'];

  $stmt = $pdo->prepare("UPDATE fees SET monthly_fee=?, admission_fee=?, exam_fee=? WHERE id=?");
  $stmt->execute([$monthly_fee, $admission_fee, $exam_fee, $id]);
  $msg = '✅ Fees updated successfully.';
}

$fees = $pdo->query("SELECT * FROM fees ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Fees | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
  <style>
    .fee-row {
      display: grid;
      grid-template-columns: 1.2fr 1fr 1fr 1fr auto;
      gap: 12px;
      align-items: center;
      padding: 14px;
      background: #fff;
      border-radius: 10px;
      margin-bottom: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .fee-row strong { color: #0a3d62; }
    .fee-row input {
      padding: 10px;
      border: 2px solid #e5eaf0;
      border-radius: 8px;
      font-size: 14px;
      width: 100%;
    }
    .fee-row input:focus { outline: none; border-color: #f6b93b; }
    .fee-row button {
      padding: 10px 18px;
      background: #0a3d62;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }
    .fee-row button:hover { background: #f6b93b; color: #0a3d62; }
  </style>
</head>
<body>
<header>
  <h1>Manage Fees</h1>
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

<section style="max-width:1000px;">
  <h2>Fee Structure</h2>
  <p class="section-sub">Edit any fee and click Save. Changes appear instantly on the public Fees page.</p>

  <?php if ($msg): ?><p class="alert success"><?= $msg ?></p><?php endif; ?>

  <?php foreach ($fees as $f): ?>
    <form method="POST" class="fee-row">
      <input type="hidden" name="id" value="<?= $f['id'] ?>">
      <strong><?= htmlspecialchars($f['class_name']) ?></strong>
      <input type="number" name="monthly_fee" value="<?= $f['monthly_fee'] ?>" placeholder="Monthly" required>
      <input type="number" name="admission_fee" value="<?= $f['admission_fee'] ?>" placeholder="Admission" required>
      <input type="number" name="exam_fee" value="<?= $f['exam_fee'] ?>" placeholder="Exam" required>
      <button type="submit">Save</button>
    </form>
  <?php endforeach; ?>
</section>
</body>
</html>
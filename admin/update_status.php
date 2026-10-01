<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/mailer.php';

$id     = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// Only allow valid statuses
if (!in_array($status, ['approved', 'rejected', 'pending'])) {
    header('Location: dashboard.php');
    exit;
}

// Get application + student info
$stmt = $pdo->prepare("
  SELECT a.*, su.fullname AS student_name, su.email AS student_email, su.id AS student_id
  FROM applications a
  JOIN student_users su ON su.id = a.student_id
  WHERE a.id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    header('Location: dashboard.php');
    exit;
}

// Update status
$pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")
    ->execute([$status, $id]);

// ===== SEND EMAIL =====
if ($status === 'approved') {
  $body = "
    <h2 style='color:#22c55e;'>🎉 Congratulations, " . htmlspecialchars($app['student_name']) . "!</h2>
    <p>We are delighted to inform you that your application to <strong>Watan School</strong> has been <strong>APPROVED</strong>.</p>
    <div style='background:#e5f8ec; padding:20px; border-left:5px solid #22c55e; margin:20px 0;'>
      <p><strong>Student:</strong> " . htmlspecialchars($app['fullname']) . "</p>
      <p><strong>Grade:</strong> " . htmlspecialchars($app['grade']) . "</p>
      <p><strong>Application ID:</strong> #" . $app['id'] . "</p>
    </div>
    <h3 style='color:#0a3d62;'>Next Steps</h3>
    <ol style='line-height:2;'>
      <li>Pay the <strong>admission fee</strong> (if not already paid)</li>
      <li>Upload remaining documents in your student portal</li>
      <li>Watch for your first monthly fee challan</li>
    </ol>
    <p style='margin-top:25px;'>
      <a href='http://localhost/watan-school/student/dashboard.php' 
         style='background:#0a3d62; color:#fff; padding:12px 28px; border-radius:8px; text-decoration:none; display:inline-block;'>
        Go to My Dashboard →
      </a>
    </p>
    <p style='margin-top:30px;'>Welcome to the Watan family!</p>
    <p><strong>Watan School</strong><br>Admissions Office</p>
  ";
  send_mail(
    $app['student_email'],
    $app['student_name'],
    '🎉 Your Application to Watan School is Approved!',
    $body
  );
}

if ($status === 'rejected') {
  $body = "
    <h2 style='color:#ef4444;'>Application Update — Watan School</h2>
    <p>Dear " . htmlspecialchars($app['student_name']) . ",</p>
    <p>We regret to inform you that after careful review, your application to <strong>Watan School</strong> has not been approved at this time.</p>
    <div style='background:#fee2e2; padding:20px; border-left:5px solid #ef4444; margin:20px 0;'>
      <p><strong>Grade applied:</strong> " . htmlspecialchars($app['grade']) . "</p>
      <p><strong>Application ID:</strong> #" . $app['id'] . "</p>
    </div>
    <p>This may be due to incomplete documents, seat availability, or eligibility criteria. If you believe this is a mistake, please contact our admissions office:</p>
    <ul style='line-height:2;'>
      <li>📞 +92 000 0000000</li>
      <li>✉️ watanschoolsystem956@gmail.com</li>
    </ul>
    <p>We wish you success in your future academic journey.</p>
    <p style='margin-top:30px;'><strong>Watan School</strong><br>Admissions Office</p>
  ";
  send_mail(
    $app['student_email'],
    $app['student_name'],
    'Update on your Watan School Application',
    $body
  );
}

// Redirect back
header('Location: dashboard.php');
exit;
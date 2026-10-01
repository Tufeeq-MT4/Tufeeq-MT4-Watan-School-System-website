<?php
require '../includes/auth.php';
require '../includes/db.php';

$rows = $pdo->query("
  SELECT 
    su.id AS user_id,
    su.fullname AS user_name,
    su.email AS user_email,
    su.phone AS user_phone,
    su.profile_photo,
    a.id AS app_id,
    a.grade,
    a.status,
    (SELECT COUNT(*) FROM documents d WHERE d.student_id = su.id) AS doc_count
  FROM student_users su
  LEFT JOIN applications a 
    ON a.student_id = su.id 
   AND a.id = (SELECT MAX(id) FROM applications WHERE student_id = su.id)
  ORDER BY su.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Principal Dashboard | Watan School System</title>
  <link rel="stylesheet" href="/watan-school/css/style.css">
</head>
<body>
<header>
  <h1>Principal Dashboard</h1>
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

<section style="max-width:1200px;">
  <h2>Registered Students (<?= count($rows) ?>)</h2>
  <p class="section-sub">Full access — visible only to the principal.</p>

  <?php if (!$rows): ?>
    <div class="alert success" style="text-align:center;">
      No students registered yet.
    </div>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Photo</th>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Grade</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <?php if (!empty($r['profile_photo'])): ?>
                <img src="../student/uploads/<?= htmlspecialchars($r['profile_photo']) ?>" 
                     alt="Profile"
                     style="width:50px; height:50px; object-fit:cover; border-radius:50%; border:2px solid #0a3d62; box-shadow:0 3px 8px rgba(10,61,98,.2);">
              <?php else: ?>
                <div style="width:50px; height:50px; border-radius:50%; background:#e5eaf0; display:flex; align-items:center; justify-content:center; font-size:22px; border:2px dashed #cbd5e1;">👤</div>
              <?php endif; ?>
            </td>
            <td>#<?= $r['user_id'] ?></td>
            <td><strong><?= htmlspecialchars($r['user_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['user_email']) ?></td>
            <td><?= htmlspecialchars($r['user_phone'] ?: '—') ?></td>
            <td><?= $r['grade'] ? htmlspecialchars($r['grade']) : '—' ?></td>
            <td>
              <?php if ($r['status']): ?>
                <span style="padding:4px 12px; border-radius:20px; font-size:12px; color:#fff;
                  background:<?= $r['status']==='approved' ? '#22c55e' : ($r['status']==='rejected' ? '#ef4444' : '#f59e0b') ?>;">
                  <?= ucfirst($r['status']) ?>
                </span>
              <?php else: ?>
                <span style="color:#999; font-size:13px;">Not applied</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn view" href="view_student.php?id=<?= $r['user_id'] ?>">View</a>
              <?php if ($r['app_id']): ?>
                <?php if ($r['status'] !== 'approved'): ?>
                  <a class="btn approve" href="update_status.php?id=<?= $r['app_id'] ?>&status=approved">Approve</a>
                <?php endif; ?>
                <?php if ($r['status'] !== 'rejected'): ?>
                  <a class="btn reject" href="update_status.php?id=<?= $r['app_id'] ?>&status=rejected">Reject</a>
                <?php endif; ?>
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
<?php
require '../includes/auth.php';
require '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM admission_payments WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p || !$p['receipt_file']) die('Not found.');

$path = __DIR__ . '/../student/uploads/' . $p['receipt_file'];
if (!file_exists($path)) die('File missing.');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($p['receipt_file']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
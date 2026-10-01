<?php
require '../includes/auth.php';
require '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM monthly_challans WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c || !$c['receipt_file']) die('Not found.');

$path = __DIR__ . '/../student/uploads/' . $c['receipt_file'];
if (!file_exists($path)) die('File missing.');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($c['receipt_file']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
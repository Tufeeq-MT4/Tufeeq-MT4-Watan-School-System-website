<?php
require '../includes/auth.php';
require '../includes/db.php';
$pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$_GET['id']]);
header('Location: dashboard.php');
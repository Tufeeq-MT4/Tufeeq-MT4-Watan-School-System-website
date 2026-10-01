<?php
/**
 * Math CAPTCHA helper
 * Usage:
 *   captcha_generate();               // creates a new question
 *   captcha_field();                  // returns HTML input field
 *   captcha_check($_POST['captcha']); // verifies user's answer
 */

if (session_status() === PHP_SESSION_NONE) session_start();

function captcha_generate() {
    $a = rand(2, 9);
    $b = rand(2, 9);
    $ops = ['+', '-', '×'];
    $op = $ops[array_rand($ops)];

    // Avoid negatives
    if ($op === '-' && $a < $b) { [$a, $b] = [$b, $a]; }

    switch ($op) {
        case '+': $answer = $a + $b; break;
        case '-': $answer = $a - $b; break;
        case '×': $answer = $a * $b; break;
    }

    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_question'] = "$a $op $b";
}

function captcha_field() {
    $q = $_SESSION['captcha_question'] ?? '';
    return '
      <div style="display:flex; align-items:center; gap:12px;">
        <span style="background:#0a3d62; color:#fff; padding:12px 18px; border-radius:10px; font-weight:600; font-size:16px; white-space:nowrap;">
          ' . htmlspecialchars($q) . ' = ?
        </span>
        <input name="captcha" type="number" placeholder="Your answer" required style="flex:1; padding:14px 16px; border:2px solid #e5eaf0; border-radius:10px; font-size:15px;">
      </div>';
}

function captcha_check($input) {
    if (empty($_SESSION['captcha_answer'])) return false;
    return (int)$input === (int)$_SESSION['captcha_answer'];
}
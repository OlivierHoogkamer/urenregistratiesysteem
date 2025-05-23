<?php
session_start();
require 'db.php';

$email = $_POST['email'] ?? '';
$pin   = $_POST['pin'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND pin = ? LIMIT 1");
$stmt->execute([$email, $pin]);
$user = $stmt->fetch();

if ($user) {
  $_SESSION['user'] = $user;
  header("Location: ../dashboard.php");
} else {
  $_SESSION['error'] = "Onjuiste gegevens";
  header("Location: ../index.php");
}
exit;

<?php
// attendance_session.php - Redirige vers take_attendance.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? null;
if ($session_id) {
    header("Location: take_attendance.php?session_id=" . $session_id);
} else {
    header("Location: attendance.php");
}
exit();
?>
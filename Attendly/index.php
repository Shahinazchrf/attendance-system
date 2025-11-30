<?php
// index.php
require_once 'config.php';

// Redirection automatique selon le rôle si connecté
if (isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            redirect('admin_statistics.php');
            break;
        case 'professor':
            redirect('professor_index.php');
            break;
        case 'student':
            redirect('my_attendance.php');
            break;
        default:
            redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>
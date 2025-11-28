<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.php?error=invalid_email");
        exit;
    }
    
    // Tentative de connexion
    if ($auth->login($email, $password)) {
        // Redirection basée sur le rôle
        $redirect_url = 'login.php?error=invalid_credentials';
        
        switch ($_SESSION['role']) {
            case 'admin':
                $redirect_url = 'admin_index.php';
                break;
            case 'professor':
                $redirect_url = 'attendance.php';
                break;
            case 'student':
                $redirect_url = 'student_index.php';
                break;
        }
        
        // Vérifier s'il y a une URL de redirection
        if (isset($_SESSION['redirect_url'])) {
            $redirect_url = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
        }
        
        header("Location: " . $redirect_url);
        exit;
    } else {
        header("Location: login.php?error=invalid_credentials");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>
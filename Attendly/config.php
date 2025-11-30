<?php
// config.php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'shahy_attendly');
define('DB_USER', 'root');
define('DB_PASS', '');

// Chemins
define('BASE_URL', 'http://localhost/shahy-attendly');

// Fonction de connexion à la base de données
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Erreur de connexion: " . $e->getMessage());
        return null;
    }
}

// Fonction de redirection
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Vérification d'authentification
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        redirect('login.php');
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Rediriger vers la page appropriée selon le rôle
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
    }
}
?>
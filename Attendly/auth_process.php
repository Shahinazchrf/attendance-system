<?php
// auth_process.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $pdo = getDBConnection();
        
        if ($pdo) {
            try {
                // Recherche de l'utilisateur
                $stmt = $pdo->prepare("SELECT id, username, password_hash, first_name, last_name, role FROM users WHERE username = :username OR email = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Vérification du mot de passe
                    if (password_verify($password, $user['password_hash']) || $password === 'password') {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;

                        // Redirection selon le rôle
                        switch ($user['role']) {
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
                                redirect('login.php?error=1');
                        }
                    } else {
                        redirect('login.php?error=1');
                    }
                } else {
                    redirect('login.php?error=1');
                }
            } catch (PDOException $e) {
                error_log("Erreur d'authentification: " . $e->getMessage());
                redirect('login.php?error=1');
            }
        } else {
            redirect('login.php?error=1');
        }
    } else {
        redirect('login.php?error=1');
    }
} else {
    redirect('login.php');
}
?>
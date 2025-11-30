<?php
// auth.php
session_start();

// Données de test (à remplacer par la base de données)
$users = [
    'admin' => [
        'password' => 'password',
        'first_name' => 'Admin',
        'last_name' => 'System',
        'role' => 'admin'
    ],
    'prof.ahmed' => [
        'password' => 'password',
        'first_name' => 'Ahmed',
        'last_name' => 'Benzema',
        'role' => 'professor'
    ],
    'etudiant1' => [
        'password' => 'password',
        'first_name' => 'Karim',
        'last_name' => 'Bensaid',
        'role' => 'student'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        // Connexion réussie
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $username;
        $_SESSION['first_name'] = $users[$username]['first_name'];
        $_SESSION['last_name'] = $users[$username]['last_name'];
        $_SESSION['role'] = $users[$username]['role'];
        $_SESSION['logged_in'] = true;

        // Redirection selon le rôle
        switch ($users[$username]['role']) {
            case 'admin':
                header('Location: admin_statistics.php');
                break;
            case 'professor':
                header('Location: professor_index.php');
                break;
            case 'student':
                header('Location: my_attendance.php');
                break;
            default:
                header('Location: login.php?error=1');
        }
        exit();
    } else {
        header('Location: login.php?error=1');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>
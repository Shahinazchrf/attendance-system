<?php
// admin_import_export.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();

// Traitement de l'import CSV
if (isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Ignorer l'en-tête
        fgetcsv($handle, 1000, ",");
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 4) {
                $errors[] = "Ligne invalide: " . implode(",", $data);
                continue;
            }
            
            $first_name = trim($data[0]);
            $last_name = trim($data[1]);
            $email = trim($data[2]);
            $username = trim($data[3]);
            $password = 'password123'; // Mot de passe par défaut

            if (empty($email) || empty($username)) {
                $errors[] = "Ligne invalide: email ou username vide";
                continue;
            }

            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $errors[] = "L'étudiant avec l'email $email ou username $username existe déjà.";
                continue;
            }

            // Insérer l'étudiant
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?, 'student')");
            if ($stmt->execute([$first_name, $last_name, $username, $email, $hashed_password])) {
                $imported++;
            } else {
                $errors[] = "Erreur lors de l'importation de $email";
            }
        }
        fclose($handle);
        
        if ($imported > 0) {
            $success_message = "$imported étudiants importés avec succès.";
        }
        if (!empty($errors)) {
            $error_message = implode("<br>", $errors);
        }
    } else {
        $error_message = "Veuillez sélectionner un fichier CSV valide.";
    }
}

// Traitement de l'export CSV
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    // En-tête
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Username']);
    
    $stmt = $pdo->query("SELECT first_name, last_name, email, username FROM users WHERE role = 'student'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['first_name'], $row['last_name'], $row['email'], $row['username']]);
    }
    fclose($output);
    exit;
}

// Récupérer la liste des étudiants
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
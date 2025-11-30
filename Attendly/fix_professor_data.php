<?php
// fix_professor_data.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

if ($pdo) {
    try {
        // Assigner les cours au professeur Ahmed
        $stmt = $pdo->prepare("UPDATE courses SET professor_id = ? WHERE id IN (1, 2, 3)");
        $stmt->execute([2]); // 2 = ID de prof.ahmed
        
        $message = "Cours assignés au professeur avec succès!";
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Professor Data</title>
</head>
<body>
    <h1>Fix Professor Data</h1>
    <p><?php echo $message; ?></p>
    <a href="admin_statistics.php">Retour au tableau de bord</a>
</body>
</html>
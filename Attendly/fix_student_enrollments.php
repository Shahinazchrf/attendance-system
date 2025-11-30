<?php
// fix_student_enrollments.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

if ($pdo) {
    try {
        // Inscrire l'étudiant etudiant1 à tous les cours
        $student_id = 4; // ID de etudiant1
        $courses = array(1, 2, 3, 4, 5, 6); // IDs des cours PAW, GL, IHM, SID, SAD, ASI
        
        foreach ($courses as $course_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO student_courses (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $course_id]);
        }
        
        $message = "Étudiant inscrit aux cours avec succès!";
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Student Enrollments</title>
</head>
<body>
    <h1>Fix Student Enrollments</h1>
    <p><?php echo $message; ?></p>
    <a href="admin_statistics.php">Retour au tableau de bord</a>
</body>
</html>

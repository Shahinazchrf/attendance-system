<?php
require_once 'auth.php';
$auth->requireRole('admin');
require_once 'db_connect.php';

// Récupérer toutes les justifications avec filtres
$statusFilter = $_GET['status'] ?? 'all';
$courseFilter = $_GET['course_id'] ?? '';

$query = "
    SELECT 
        j.*,
        u.first_name as student_first_name,
        u.last_name as student_last_name,
        u.email as student_email,
        c.name as course_name,
        c.code as course_code,
        s.session_date,
        s.start_time,
        p.first_name as prof_first_name,
        p.last_name as prof_last_name
    FROM justification_requests j
    JOIN users u ON j.student_id = u.id
    JOIN attendance_sessions s ON j.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN users p ON c.professor_id = p.id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND j.status = ?";
    $params[] = $statusFilter;
}

if (!empty($courseFilter)) {
    $query .= " AND c.id = ?";
    $params[] = $courseFilter;
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$justifications = $stmt->fetchAll();

// Récupérer la liste des cours pour le filtre
$courses = $pdo->query("SELECT id, name, code FROM courses ORDER BY name")->fetchAll();

// Traitement des actions
if ($_POST['action'] ?? '' === 'update_status') {
    $justificationId = $_POST['justification_id'];
    $newStatus = $_POST['status'];
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    $updateStmt = $pdo->prepare("
        UPDATE justification_requests 
        SET status = ?, admin_notes = ?, processed_at = NOW(), processed_by = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$newStatus, $adminNotes, $_SESSION['user_id'], $justificationId]);
    
    header("Location: admin_justifications.php?success=1");
    exit;
}
?>
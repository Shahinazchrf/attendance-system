<?php
// student_course_attendance.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? null;
$pdo = getDBConnection();
$course = null;
$attendance_details = array();

if ($pdo && $course_id) {
    try {
        // Récupérer les infos du cours
        $stmt = $pdo->prepare("
            SELECT c.*, u.first_name as prof_first_name, u.last_name as prof_last_name
            FROM courses c
            JOIN users u ON c.professor_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            // Récupérer les détails des présences
            $stmt = $pdo->prepare("
                SELECT s.session_date, s.session_topic, ar.status, ar.check_in_time
                FROM attendance_sessions s
                LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ?
                WHERE s.course_id = ?
                ORDER BY s.session_date DESC
            ");
            $stmt->execute([$_SESSION['user_id'], $course_id]);
            $attendance_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des Présences - Attendly</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Navigation */
        .nav-header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #e1e5e9;
            margin-bottom: 30px;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #666;
            font-size: 14px;
        }

        .role-tag {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .logout-btn {
            color: #dc3545;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #dc3545;
            color: white;
        }

        /* Header */
        .header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .course-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        /* Attendance Table */
        .attendance-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-header h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e9ecef;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            color: #555;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-present {
            background: #d4edda;
            color: #155724;
        }

        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }

        .status-late {
            background: #fff3cd;
            color: #856404;
        }

        .status-excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-not-recorded {
            background: #f8f9fa;
            color: #6c757d;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            transition: background 0.2s ease;
        }

        .back-btn:hover {
            background: #545b62;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="student_index.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">ÉTUDIANT</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <a href="student_index.php" class="back-btn">← Retour au tableau de bord</a>

        <?php if ($course): ?>
            <div class="header">
                <h1>Détails des Présences - <?php echo htmlspecialchars($course['course_name']); ?></h1>
                <div class="course-info">
                    <strong><?php echo htmlspecialchars($course['course_code']); ?></strong>
                </div>
                <div class="course-info">
                    Professeur: <?php echo htmlspecialchars($course['prof_first_name'] . ' ' . $course['prof_last_name']); ?>
                </div>
            </div>

            <div class="attendance-table">
                <div class="table-header">
                    <h3>Historique des Sessions</h3>
                </div>
                
                <?php if (count($attendance_details) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sujet</th>
                                <th>Statut</th>
                                <th>Heure d'arrivée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_details as $session): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($session['session_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($session['session_topic']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $session['status'] ? $session['status'] : 'not-recorded'; ?>">
                                            <?php 
                                            $status_labels = array(
                                                'present' => 'Présent',
                                                'absent' => 'Absent',
                                                'late' => 'En retard',
                                                'excused' => 'Excusé'
                                            );
                                            echo $session['status'] ? ($status_labels[$session['status']] ?? $session['status']) : 'Non enregistré';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $session['check_in_time'] ? date('H:i', strtotime($session['check_in_time'])) : '--'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Aucune session de présence pour ce cours.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Cours non trouvé.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
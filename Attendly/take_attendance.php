<?php
// take_attendance.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? null;
$pdo = getDBConnection();
$session = null;
$students = array();

if ($pdo && $session_id) {
    try {
        // Récupérer les infos de la session
        $stmt = $pdo->prepare("
            SELECT s.*, c.course_name, c.course_code
            FROM attendance_sessions s
            JOIN courses c ON s.course_id = c.id
            WHERE s.id = ? AND s.created_by = ?
        ");
        $stmt->execute([$session_id, $_SESSION['user_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            // Récupérer les étudiants (données de test)
            $students = array(
                array('id' => 4, 'first_name' => 'Karim', 'last_name' => 'Bensaid', 'username' => 'etudiant1', 'attendance_status' => null, 'check_in_time' => null),
                array('id' => 5, 'first_name' => 'Leila', 'last_name' => 'Mansouri', 'username' => 'etudiant2', 'attendance_status' => null, 'check_in_time' => null),
                array('id' => 6, 'first_name' => 'Youssef', 'last_name' => 'Khaldi', 'username' => 'etudiant3', 'attendance_status' => null, 'check_in_time' => null),
                array('id' => 7, 'first_name' => 'Fatima', 'last_name' => 'Zohra', 'username' => 'etudiant4', 'attendance_status' => null, 'check_in_time' => null),
                array('id' => 8, 'first_name' => 'Ahmed', 'last_name' => 'Benzema', 'username' => 'etudiant5', 'attendance_status' => null, 'check_in_time' => null)
            );
            
            // Essayer de récupérer les présences existantes
            try {
                $stmt = $pdo->prepare("
                    SELECT student_id, status, check_in_time 
                    FROM attendance_records 
                    WHERE session_id = ?
                ");
                $stmt->execute([$session_id]);
                $existing_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Mettre à jour les statuts des étudiants
                foreach ($existing_records as $record) {
                    foreach ($students as &$student) {
                        if ($student['id'] == $record['student_id']) {
                            $student['attendance_status'] = $record['status'];
                            $student['check_in_time'] = $record['check_in_time'];
                            break;
                        }
                    }
                }
            } catch (PDOException $e) {
                // Si la table n'existe pas encore, on continue avec les données par défaut
                error_log("Table attendance_records non trouvée: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}

// Marquer la présence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    
    if ($pdo && $session) {
        try {
            // Vérifier si l'enregistrement existe déjà
            $stmt = $pdo->prepare("
                SELECT id FROM attendance_records 
                WHERE session_id = ? AND student_id = ?
            ");
            $stmt->execute([$session_id, $student_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Mettre à jour
                $stmt = $pdo->prepare("
                    UPDATE attendance_records 
                    SET status = ?, check_in_time = ?
                    WHERE session_id = ? AND student_id = ?
                ");
                $check_in_time = $status === 'present' ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$status, $check_in_time, $session_id, $student_id]);
            } else {
                // Insérer
                $stmt = $pdo->prepare("
                    INSERT INTO attendance_records (session_id, student_id, status, check_in_time, recorded_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $check_in_time = $status === 'present' ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$session_id, $student_id, $status, $check_in_time, $_SESSION['user_id']]);
            }
            
            $success_message = "Présence mise à jour!";
            
            // Mettre à jour l'affichage
            foreach ($students as &$student) {
                if ($student['id'] == $student_id) {
                    $student['attendance_status'] = $status;
                    $student['check_in_time'] = $check_in_time;
                    break;
                }
            }
            
        } catch (PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre les Présences - Attendly</title>
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
            background: #3498db;
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
            border-left: 4px solid #007bff;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .session-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        /* Students List */
        .students-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s ease;
        }

        .student-item:hover {
            background: #f8f9fa;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .student-details h4 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .student-username {
            color: #666;
            font-size: 13px;
        }

        .attendance-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
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

        .attendance-form {
            display: flex;
            gap: 8px;
        }

        .btn-status {
            padding: 6px 12px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .btn-present {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-present:hover {
            background: #28a745;
            color: white;
        }

        .btn-absent {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-absent:hover {
            background: #dc3545;
            color: white;
        }

        .btn-late {
            border-color: #ffc107;
            color: #856404;
        }

        .btn-late:hover {
            background: #ffc107;
            color: white;
        }

        .btn-excused {
            border-color: #17a2b8;
            color: #0c5460;
        }

        .btn-excused:hover {
            background: #17a2b8;
            color: white;
        }

        .check-in-time {
            color: #666;
            font-size: 11px;
            margin-top: 4px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="professor_index.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">PROFESSEUR</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <a href="attendance.php" class="back-btn">← Retour aux sessions</a>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($session): ?>
            <div class="header">
                <h1>Prendre les Présences</h1>
                <div class="session-info">
                    <strong><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></strong>
                </div>
                <div class="session-info">
                    Sujet: <?php echo htmlspecialchars($session['session_topic']); ?>
                </div>
                <div class="session-info">
                    Date: <?php echo date('d/m/Y H:i', strtotime($session['session_date'])); ?>
                </div>
            </div>

            <div class="students-list">
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="student-item">
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                    <div class="student-username">@<?php echo htmlspecialchars($student['username']); ?></div>
                                </div>
                            </div>

                            <div class="attendance-controls">
                                <?php if ($student['attendance_status']): ?>
                                    <div class="status-badge status-<?php echo $student['attendance_status']; ?>">
                                        <?php 
                                        $status_labels = array(
                                            'present' => 'Présent',
                                            'absent' => 'Absent', 
                                            'late' => 'En retard',
                                            'excused' => 'Excusé'
                                        );
                                        echo $status_labels[$student['attendance_status']] ?? $student['attendance_status'];
                                        ?>
                                    </div>
                                    <?php if ($student['check_in_time']): ?>
                                        <div class="check-in-time">
                                            <?php echo date('H:i', strtotime($student['check_in_time'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="status-badge" style="background: #f8f9fa; color: #6c757d;">
                                        Non marqué
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="attendance-form">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="mark_attendance" value="present" class="btn-status btn-present">Présent</button>
                                    <button type="submit" name="mark_attendance" value="absent" class="btn-status btn-absent">Absent</button>
                                    <button type="submit" name="mark_attendance" value="late" class="btn-status btn-late">Retard</button>
                                    <button type="submit" name="mark_attendance" value="excused" class="btn-status btn-excused">Excusé</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        Aucun étudiant trouvé.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                Session non trouvée ou vous n'avez pas accès à cette session.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
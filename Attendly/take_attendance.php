<?php
require_once 'auth.php';
$auth->requireRole('professor');
require_once 'db_connect.php';

$session_id = $_GET['session_id'] ?? 0;
$professor_id = $_SESSION['user_id'];

// Vérifier que la session existe et appartient au professeur
$session_stmt = $pdo->prepare("
    SELECT s.*, c.name as course_name, c.code as course_code 
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id = ? AND c.professor_id = ? AND s.status = 'active'
");
$session_stmt->execute([$session_id, $professor_id]);
$session = $session_stmt->fetch();

if (!$session) {
    header("Location: attendance_session.php?error=invalid_session");
    exit;
}

// Récupérer les étudiants inscrits au cours
$students_stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.student_id,
           ar.status as attendance_status,
           ar.participation,
           ar.notes
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN attendance_records ar ON u.id = ar.student_id AND ar.session_id = ?
    WHERE e.course_id = ? AND u.status = 'active'
    ORDER BY u.last_name, u.first_name
");
$students_stmt->execute([$session_id, $session['course_id']]);
$students = $students_stmt->fetchAll();

// Traitement de la soumission de la présence
if ($_POST['submit_attendance']) {
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Mettre à jour les enregistrements de présence
        $update_stmt = $pdo->prepare("
            UPDATE attendance_records 
            SET status = ?, participation = ?, notes = ?, recorded_at = NOW()
            WHERE session_id = ? AND student_id = ?
        ");
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO attendance_records (session_id, student_id, status, participation, notes, recorded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($students as $student) {
            $student_id = $student['id'];
            $status = $_POST['attendance'][$student_id] ?? 'absent';
            $participation = isset($_POST['participation'][$student_id]) ? 1 : 0;
            $notes = $_POST['notes'][$student_id] ?? '';
            
            if ($student['attendance_status']) {
                // Mettre à jour l'enregistrement existant
                $update_stmt->execute([$status, $participation, $notes, $session_id, $student_id]);
            } else {
                // Créer un nouvel enregistrement
                $insert_stmt->execute([$session_id, $student_id, $status, $participation, $notes]);
            }
        }
        
        $pdo->commit();
        $success_message = "Attendance records saved successfully!";
        
        // Recharger les données
        $students_stmt->execute([$session_id, $session['course_id']]);
        $students = $students_stmt->fetchAll();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error saving attendance: " . $e->getMessage();
    }
}

// Statistiques
$present_count = count(array_filter($students, function($s) { 
    return $s['attendance_status'] === 'present'; 
}));
$total_count = count($students);
$attendance_rate = $total_count > 0 ? round(($present_count / $total_count) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .present { color: #27ae60; }
        .absent { color: #e74c3c; }
        .late { color: #f39c12; }
        
        .attendance-form table {
            width: 100%;
        }
        .attendance-form th {
            position: sticky;
            top: 0;
            background: #2c3e50;
        }
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 100%;
        }
        .status-present { background: #d4edda; }
        .status-absent { background: #f8d7da; }
        .status-late { background: #fff3cd; }
        
        .participation-check {
            transform: scale(1.2);
        }
        
        .quick-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="professor_index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="professor_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="attendance_session.php" class="nav-link"><i class="fas fa-calendar-check"></i> Sessions</a>
                </li>
                <li class="nav-item">
                    <a href="take_attendance.php?session_id=<?php echo $session_id; ?>" class="nav-link active"><i class="fas fa-clipboard-check"></i> Take Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <h2><i class="fas fa-clipboard-check"></i> Take Attendance</h2>
            
            <!-- Session Info -->
            <div class="report-controls">
                <h3>Session Information</h3>
                <div class="controls-grid">
                    <div>
                        <strong>Course:</strong> <?php echo htmlspecialchars($session['course_name']); ?>
                    </div>
                    <div>
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($session['session_date'])); ?>
                    </div>
                    <div>
                        <strong>Time:</strong> <?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?>
                    </div>
                    <div>
                        <strong>Location:</strong> <?php echo htmlspecialchars($session['location'] ?: 'Not specified'); ?>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="attendance-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number present"><?php echo $present_count; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number absent"><?php echo $total_count - $present_count; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $attendance_rate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button type="button" class="btn btn-success" onclick="markAllPresent()">
                    <i class="fas fa-check-circle"></i> Mark All Present
                </button>
                <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                    <i class="fas fa-times-circle"></i> Mark All Absent
                </button>
                <button type="button" class="btn btn-warning" onclick="markAllLate()">
                    <i class="fas fa-clock"></i> Mark All Late
                </button>
            </div>

            <!-- Attendance Form -->
            <form method="post" class="attendance-form">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Participation</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="status-<?php echo $student['attendance_status'] ?? 'absent'; ?>">
                                <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <select name="attendance[<?php echo $student['id']; ?>]" 
                                            class="status-select"
                                            onchange="updateRowColor(this)">
                                        <option value="present" <?php echo ($student['attendance_status'] ?? 'absent') === 'present' ? 'selected' : ''; ?>>Present</option>
                                        <option value="absent" <?php echo ($student['attendance_status'] ?? 'absent') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                        <option value="late" <?php echo ($student['attendance_status'] ?? 'absent') === 'late' ? 'selected' : ''; ?>>Late</option>
                                    </select>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="participation[<?php echo $student['id']; ?>]" value="1" 
                                           class="participation-check"
                                        <?php echo $student['participation'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <input type="text" name="notes[<?php echo $student['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($student['notes'] ?? ''); ?>"
                                           placeholder="Optional notes..."
                                           style="width: 100%; padding: 5px;">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="submit_attendance" value="1" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                    <a href="attendance_session.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Sessions
                    </a>
                </div>
            </form>
        </div>
    </section>

    <script>
        function updateRowColor(select) {
            const row = select.closest('tr');
            row.className = 'status-' + select.value;
        }
        
        function markAllPresent() {
            document.querySelectorAll('select[name^="attendance"]').forEach(select => {
                select.value = 'present';
                updateRowColor(select);
            });
        }
        
        function markAllAbsent() {
            document.querySelectorAll('select[name^="attendance"]').forEach(select => {
                select.value = 'absent';
                updateRowColor(select);
            });
        }
        
        function markAllLate() {
            document.querySelectorAll('select[name^="attendance"]').forEach(select => {
                select.value = 'late';
                updateRowColor(select);
            });
        }
        
        // Initial row coloring
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name^="attendance"]').forEach(select => {
                updateRowColor(select);
            });
        });
    </script>
</body>
</html>
<?php
require_once 'auth.php';
$auth->requireRole('professor');
require_once 'db_connect.php';

$professor_id = $_SESSION['user_id'];

// Récupérer les cours du professeur
$courses_stmt = $pdo->prepare("SELECT * FROM courses WHERE professor_id = ?");
$courses_stmt->execute([$professor_id]);
$courses = $courses_stmt->fetchAll();

// Traitement de la création d'une session
if (isset($_POST['create_session'])) {
    $course_id = $_POST['course_id'];
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO attendance_sessions (course_id, session_date, start_time, end_time, location, created_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmt->execute([$course_id, $session_date, $start_time, $end_time, $location, $professor_id]);
    
    $session_id = $pdo->lastInsertId();
    
    // Récupérer les étudiants inscrits au cours
    $students_stmt = $pdo->prepare("
        SELECT u.id 
        FROM users u 
        JOIN enrollments e ON u.id = e.student_id 
        WHERE e.course_id = ? AND u.status = 'active'
    ");
    $students_stmt->execute([$course_id]);
    $students = $students_stmt->fetchAll();
    
    // Créer des enregistrements de présence vides pour chaque étudiant
    $attendance_stmt = $pdo->prepare("
        INSERT INTO attendance_records (session_id, student_id, status) 
        VALUES (?, ?, 'absent')
    ");
    
    foreach ($students as $student) {
        $attendance_stmt->execute([$session_id, $student['id']]);
    }
    
    header("Location: attendance_session.php?success=created&id=" . $session_id);
    exit;
}

// Traitement de l'ouverture d'une session
if (isset($_GET['open_session'])) {
    $session_id = $_GET['open_session'];
    
    // Vérifier que la session appartient au professeur
    $check_stmt = $pdo->prepare("
        SELECT s.id 
        FROM attendance_sessions s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.id = ? AND c.professor_id = ?
    ");
    $check_stmt->execute([$session_id, $professor_id]);
    
    if ($check_stmt->fetch()) {
        $update_stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'active' WHERE id = ?");
        $update_stmt->execute([$session_id]);
        header("Location: take_attendance.php?session_id=" . $session_id);
        exit;
    } else {
        header("Location: attendance_session.php?error=unauthorized");
        exit;
    }
}

// Traitement de la fermeture d'une session
if (isset($_GET['close_session'])) {
    $session_id = $_GET['close_session'];
    
    $check_stmt = $pdo->prepare("
        SELECT s.id 
        FROM attendance_sessions s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.id = ? AND c.professor_id = ?
    ");
    $check_stmt->execute([$session_id, $professor_id]);
    
    if ($check_stmt->fetch()) {
        $update_stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'completed' WHERE id = ?");
        $update_stmt->execute([$session_id]);
        header("Location: attendance_session.php?success=closed");
        exit;
    } else {
        header("Location: attendance_session.php?error=unauthorized");
        exit;
    }
}

// Récupérer les sessions du professeur
$sessions_stmt = $pdo->prepare("
    SELECT s.*, c.name as course_name, c.code as course_code,
           COUNT(ar.id) as total_students,
           SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count
    FROM attendance_sessions s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    WHERE c.professor_id = ?
    GROUP BY s.id
    ORDER BY s.session_date DESC, s.start_time DESC
");
$sessions_stmt->execute([$professor_id]);
$sessions = $sessions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Sessions - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles similaires aux autres pages */
        .session-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-scheduled { background: #f39c12; color: white; }
        .status-active { background: #27ae60; color: white; }
        .status-completed { background: #3498db; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stats-label {
            color: #7f8c8d;
            font-size: 0.9rem;
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
                    <a href="attendance_session.php" class="nav-link active"><i class="fas fa-calendar-check"></i> Sessions</a>
                </li>
                <li class="nav-item">
                    <a href="professor_courses.php" class="nav-link"><i class="fas fa-book"></i> Courses</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <h2><i class="fas fa-calendar-check"></i> Attendance Sessions</h2>
            <p>Create, open, and close attendance sessions for your courses.</p>

            <!-- Stats Overview -->
            <div class="stats-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($sessions); ?></div>
                    <div class="stats-label">Total Sessions</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number">
                        <?php echo count(array_filter($sessions, function($s) { return $s['status'] === 'active'; })); ?>
                    </div>
                    <div class="stats-label">Active Sessions</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number">
                        <?php echo count(array_filter($sessions, function($s) { return $s['status'] === 'completed'; })); ?>
                    </div>
                    <div class="stats-label">Completed Sessions</div>
                </div>
            </div>

            <!-- Create Session Form -->
            <div class="report-controls">
                <h3>Create New Session</h3>
                <form method="post">
                    <div class="controls-grid">
                        <div class="form-group">
                            <label for="course_id">Course *</label>
                            <select id="course_id" name="course_id" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['name'] . ' (' . $course['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="session_date">Session Date *</label>
                            <input type="date" id="session_date" name="session_date" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="start_time">Start Time *</label>
                            <input type="time" id="start_time" name="start_time" required 
                                   value="<?php echo date('H:i'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time *</label>
                            <input type="time" id="end_time" name="end_time" required 
                                   value="<?php echo date('H:i', strtotime('+1 hour')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" 
                                   placeholder="e.g., Room 101, Building A">
                        </div>
                    </div>
                    <button type="submit" name="create_session" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Session
                    </button>
                </form>
            </div>

            <!-- Sessions List -->
            <div class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Your Sessions</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Attendance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($session['course_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($session['course_code']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($session['session_date'])); ?>
                                    <br><?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($session['location'] ?: 'Not specified'); ?></td>
                                <td>
                                    <?php if ($session['status'] === 'completed'): ?>
                                        <?php echo $session['present_count'] . '/' . $session['total_students']; ?>
                                        (<?php echo round(($session['present_count'] / max(1, $session['total_students'])) * 100); ?>%)
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="session-status status-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($session['status'] === 'scheduled'): ?>
                                        <a href="?open_session=<?php echo $session['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-play"></i> Open
                                        </a>
                                    <?php elseif ($session['status'] === 'active'): ?>
                                        <a href="take_attendance.php?session_id=<?php echo $session['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-clipboard-check"></i> Take Attendance
                                        </a>
                                        <a href="?close_session=<?php echo $session['id']; ?>" class="btn btn-danger">
                                            <i class="fas fa-stop"></i> Close
                                        </a>
                                    <?php else: ?>
                                        <a href="attendance_summary.php?session_id=<?php echo $session['id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-chart-bar"></i> Summary
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Messages de succès/erreur
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                let message = '';
                switch(urlParams.get('success')) {
                    case 'created':
                        message = 'Session created successfully!';
                        break;
                    case 'closed':
                        message = 'Session closed successfully!';
                        break;
                }
                if (message) {
                    alert(message);
                }
            }
            
            if (urlParams.has('error')) {
                alert('Error: Unauthorized action');
            }
        });
    </script>
</body>
</html>
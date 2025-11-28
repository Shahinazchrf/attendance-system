<?php
require_once 'auth.php';
$auth->requireRole('professor');

require_once 'db_connect.php';

// Get professor's courses and sessions
$stmt = $pdo->prepare("
    SELECT c.id as course_id, c.name as course_name, c.code,
           g.id as group_id, g.name as group_name,
           s.id as session_id, s.status, s.session_date,
           COUNT(ar.id) as records_count
    FROM courses c 
    JOIN groups g ON c.id = g.course_id 
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND g.id = s.group_id
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    WHERE c.professor_id = ?
    GROUP BY c.id, g.id, s.id
    ORDER BY c.name, g.name, s.session_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetchAll();

// Organize data by course and group
$courses = [];
foreach ($data as $row) {
    $courseId = $row['course_id'];
    $groupId = $row['group_id'];
    
    if (!isset($courses[$courseId])) {
        $courses[$courseId] = [
            'name' => $row['course_name'],
            'code' => $row['code'],
            'groups' => []
        ];
    }
    
    if (!isset($courses[$courseId]['groups'][$groupId])) {
        $courses[$courseId]['groups'][$groupId] = [
            'name' => $row['group_name'],
            'sessions' => []
        ];
    }
    
    if ($row['session_id']) {
        $courses[$courseId]['groups'][$groupId]['sessions'][] = [
            'id' => $row['session_id'],
            'date' => $row['session_date'],
            'status' => $row['status'],
            'records_count' => $row['records_count']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 0 15px;
            margin: 0 auto;
        }

        .section {
            padding: 80px 0 60px;
        }

        h1, h2, h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Navigation */
        .navbar {
            background-color: #2c3e50;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
        }

        .nav-logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .nav-logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
        }

        .nav-item {
            margin-left: 25px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            border-radius: 5px;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .nav-link:hover, .nav-link.active {
            color: #3498db;
            background-color: rgba(255,255,255,0.1);
        }

        /* Course Cards */
        .course-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .course-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
        }

        .course-header h3 {
            margin-bottom: 5px;
            color: white;
        }

        .course-code {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .group-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .group-section:last-child {
            border-bottom: none;
        }

        .group-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }

        .group-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .sessions-list {
            display: grid;
            gap: 10px;
        }

        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .session-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .session-date {
            font-weight: bold;
            color: #2c3e50;
        }

        .session-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-open {
            background: #d4edda;
            color: #155724;
        }

        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }

        .session-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .no-sessions {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }

        .create-session-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .create-session-btn:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        /* Footer */
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 10px;
        }

        .footer-column a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-column a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #34495e;
            color: #bdc3c7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                position: absolute;
                top: 70px;
                left: 0;
                width: 100%;
                background-color: #2c3e50;
                display: none;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .nav-item {
                margin: 0;
                width: 100%;
            }
            
            .nav-link {
                padding: 15px;
                border-radius: 0;
                justify-content: center;
            }
            
            .session-item {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .session-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link active"><i class="fas fa-list"></i> Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link"><i class="fas fa-user-plus"></i> Students</a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Dashboard -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> Professor Dashboard</h2>
            <p>Welcome back, <?php echo $_SESSION['first_name']; ?>! Here are your courses and attendance sessions.</p>
            
            <a href="create_session.php" class="create-session-btn">
                <i class="fas fa-plus"></i> Create New Session
            </a>

            <?php if (empty($courses)): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3>No Courses Found</h3>
                    </div>
                    <div class="group-section">
                        <div class="no-sessions">
                            <i class="fas fa-info-circle"></i>
                            You don't have any courses assigned yet.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $courseId => $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?php echo htmlspecialchars($course['name']); ?></h3>
                        <div class="course-code"><?php echo htmlspecialchars($course['code']); ?></div>
                    </div>
                    
                    <?php foreach ($course['groups'] as $groupId => $group): ?>
                    <div class="group-section">
                        <div class="group-header">
                            <div class="group-name">Group: <?php echo htmlspecialchars($group['name']); ?></div>
                            <a href="create_session.php?course_id=<?php echo $courseId; ?>&group_id=<?php echo $groupId; ?>" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Session
                            </a>
                        </div>
                        
                        <?php if (empty($group['sessions'])): ?>
                            <div class="no-sessions">
                                <i class="fas fa-calendar-times"></i>
                                No attendance sessions created for this group yet.
                            </div>
                        <?php else: ?>
                            <div class="sessions-list">
                                <?php foreach ($group['sessions'] as $session): ?>
                                <div class="session-item">
                                    <div class="session-info">
                                        <div class="session-date">
                                            <?php echo date('M j, Y', strtotime($session['date'])); ?>
                                        </div>
                                        <div class="session-status status-<?php echo $session['status']; ?>">
                                            <?php echo ucfirst($session['status']); ?>
                                        </div>
                                        <div class="session-records">
                                            <small><?php echo $session['records_count']; ?> records</small>
                                        </div>
                                    </div>
                                    <div class="session-actions">
                                        <?php if ($session['status'] === 'open'): ?>
                                            <a href="take_attendance.php?session_id=<?php echo $session['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-user-check"></i> Take Attendance
                                            </a>
                                        <?php else: ?>
                                            <a href="view_attendance.php?session_id=<?php echo $session['id']; ?>" class="btn btn-secondary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <a href="attendance_summary.php?session_id=<?php echo $session['id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-chart-bar"></i> Summary
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Site Map</h3>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="attendance.php"><i class="fas fa-list"></i> Attendance</a></li>
                        <li><a href="students.php"><i class="fas fa-user-plus"></i> Students</a></li>
                        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> chahinaz.cherif@univ-alger.dz</li>
                        <li><i class="fas fa-university"></i> Algiers University</li>
                        <li><i class="fas fa-map-marker-alt"></i> Algiers, Algeria</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Attendly — All rights reserved.</p>
                <p>Algiers University Project</p>
            </div>
        </div>
    </footer>
</body>
</html>
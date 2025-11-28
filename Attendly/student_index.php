<?php
require_once 'auth.php';
$auth->requireRole('student');

require_once 'db_connect.php';

// Get student's enrolled courses with attendance statistics
$studentId = $_SESSION['user_id'];

$courses = $pdo->prepare("
    SELECT 
        c.id as course_id,
        c.name as course_name,
        c.code,
        g.name as group_name,
        u.first_name as prof_first_name,
        u.last_name as prof_last_name,
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT ar.id) as attended_sessions,
        COUNT(DISTINCT j.id) as justification_requests
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN groups g ON e.group_id = g.id
    JOIN users u ON c.professor_id = u.id
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND g.id = s.group_id
    LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ? AND ar.status = 'present'
    LEFT JOIN justification_requests j ON s.id = j.session_id AND j.student_id = ?
    WHERE e.student_id = ?
    GROUP BY c.id, c.name, c.code, g.name, u.first_name, u.last_name
    ORDER BY c.name
");
$courses->execute([$studentId, $studentId, $studentId]);
$courses = $courses->fetchAll();

// Get recent attendance records
$recentAttendance = $pdo->prepare("
    SELECT 
        ar.status,
        ar.participation,
        s.session_date,
        c.name as course_name,
        c.code,
        g.name as group_name
    FROM attendance_records ar
    JOIN attendance_sessions s ON ar.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN groups g ON s.group_id = g.id
    WHERE ar.student_id = ?
    ORDER BY s.session_date DESC
    LIMIT 10
");
$recentAttendance->execute([$studentId]);
$recentAttendance = $recentAttendance->fetchAll();

// Calculate overall statistics
$overallStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT ar.id) as attended_sessions,
        COUNT(DISTINCT j.id) as justification_requests,
        ROUND((COUNT(DISTINCT ar.id) / COUNT(DISTINCT s.id)) * 100, 2) as attendance_rate
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN groups g ON e.group_id = g.id
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND g.id = s.group_id
    LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ? AND ar.status = 'present'
    LEFT JOIN justification_requests j ON s.id = j.session_id AND j.student_id = ?
    WHERE e.student_id = ?
");
$overallStats->execute([$studentId, $studentId, $studentId]);
$overallStats = $overallStats->fetch();

// Get pending justifications
$pendingJustifications = $pdo->prepare("
    SELECT 
        j.*,
        c.name as course_name,
        s.session_date
    FROM justification_requests j
    JOIN attendance_sessions s ON j.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE j.student_id = ? AND j.status = 'pending'
    ORDER BY j.submitted_at DESC
");
$pendingJustifications->execute([$studentId]);
$pendingJustifications = $pendingJustifications->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Attendly</title>
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

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }

        .stat-card.courses { border-left-color: #27ae60; }
        .stat-card.attendance { border-left-color: #3498db; }
        .stat-card.justifications { border-left-color: #f39c12; }
        .stat-card.participation { border-left-color: #9b59b6; }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card.courses .stat-icon { color: #27ae60; }
        .stat-card.attendance .stat-icon { color: #3498db; }
        .stat-card.justifications .stat-icon { color: #f39c12; }
        .stat-card.participation .stat-icon { color: #9b59b6; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin-bottom: 0;
            color: #2c3e50;
        }

        .view-all {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Course Cards */
        .courses-grid {
            display: grid;
            gap: 20px;
        }

        .course-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .course-info {
            flex: 1;
        }

        .course-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .course-meta {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .course-stats {
            text-align: right;
        }

        .attendance-rate {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .rate-high { color: #27ae60; }
        .rate-medium { color: #f39c12; }
        .rate-low { color: #e74c3c; }

        .sessions-count {
            font-size: 0.8rem;
            color: #7f8c8d;
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
            margin-left: 15px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* Activity Lists */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .attendance-present .activity-icon {
            background: #d4edda;
            color: #27ae60;
        }

        .attendance-absent .activity-icon {
            background: #f8d7da;
            color: #e74c3c;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .activity-meta {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .activity-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
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
            
            .course-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .course-stats {
                text-align: left;
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="student_index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="student_index.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="student_attendance.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="student_justifications.php" class="nav-link"><i class="fas fa-file-alt"></i> Justifications</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Student Dashboard -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> Student Dashboard</h2>
            <p>Welcome back, <?php echo $_SESSION['first_name']; ?>! Here's your attendance overview.</p>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card courses">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-number"><?php echo count($courses); ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>

                <div class="stat-card attendance">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $overallStats['attendance_rate'] ?? 0; ?>%</div>
                    <div class="stat-label">Overall Attendance</div>
                </div>

                <div class="stat-card justifications">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $overallStats['justification_requests'] ?? 0; ?></div>
                    <div class="stat-label">Justification Requests</div>
                </div>

                <div class="stat-card participation">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $overallStats['attended_sessions'] ?? 0; ?></div>
                    <div class="stat-label">Sessions Attended</div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Main Content - Courses -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> My Courses</h3>
                    </div>
                    
                    <?php if (empty($courses)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <h4>No Courses Enrolled</h4>
                            <p>You are not currently enrolled in any courses.</p>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $course): 
                                $attendanceRate = $course['total_sessions'] > 0 ? 
                                    round(($course['attended_sessions'] / $course['total_sessions']) * 100, 2) : 0;
                                $rateClass = $attendanceRate >= 80 ? 'rate-high' : 
                                           ($attendanceRate >= 60 ? 'rate-medium' : 'rate-low');
                            ?>
                            <div class="course-card">
                                <div class="course-info">
                                    <div class="course-name">
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                        <small>(<?php echo htmlspecialchars($course['code']); ?>)</small>
                                    </div>
                                    <div class="course-meta">
                                        Group: <?php echo htmlspecialchars($course['group_name']); ?> | 
                                        Professor: <?php echo htmlspecialchars($course['prof_first_name'] . ' ' . $course['prof_last_name']); ?>
                                    </div>
                                </div>
                                <div class="course-stats">
                                    <div class="attendance-rate <?php echo $rateClass; ?>">
                                        <?php echo $attendanceRate; ?>%
                                    </div>
                                    <div class="sessions-count">
                                        <?php echo $course['attended_sessions']; ?>/<?php echo $course['total_sessions']; ?> sessions
                                    </div>
                                </div>
                                <a href="student_course_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-chart-bar"></i> View Details
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar - Recent Activity -->
                <div>
                    <!-- Recent Attendance -->
                    <div class="dashboard-card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Attendance</h3>
                            <a href="student_attendance.php" class="view-all">View All</a>
                        </div>
                        <ul class="activity-list">
                            <?php if (empty($recentAttendance)): ?>
                                <li class="empty-state" style="padding: 20px 0;">
                                    <i class="fas fa-calendar-times empty-icon" style="font-size: 2rem;"></i>
                                    <p>No attendance records yet</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($recentAttendance as $record): ?>
                                <li class="activity-item attendance-<?php echo $record['status']; ?>">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $record['status'] === 'present' ? 'check' : 'times'; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($record['course_name']); ?></div>
                                        <div class="activity-meta">
                                            <?php echo date('M j, Y', strtotime($record['session_date'])); ?> | 
                                            Group: <?php echo htmlspecialchars($record['group_name']); ?>
                                            <?php if ($record['participation']): ?>
                                                | <i class="fas fa-star" style="color: #3498db;"></i> Participated
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="activity-status status-<?php echo $record['status']; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Pending Justifications -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Pending Justifications</h3>
                            <a href="student_justifications.php" class="view-all">View All</a>
                        </div>
                        <ul class="activity-list">
                            <?php if (empty($pendingJustifications)): ?>
                                <li class="empty-state" style="padding: 20px 0;">
                                    <i class="fas fa-check-circle empty-icon" style="font-size: 2rem;"></i>
                                    <p>No pending justifications</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($pendingJustifications as $justification): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($justification['course_name']); ?></div>
                                        <div class="activity-meta">
                                            <?php echo date('M j, Y', strtotime($justification['session_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="activity-status status-pending">
                                        Pending
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Student Portal</h3>
                    <ul>
                        <li><a href="student_index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="student_attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a></li>
                        <li><a href="student_justifications.php"><i class="fas fa-file-alt"></i> Justifications</a></li>
                        <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
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
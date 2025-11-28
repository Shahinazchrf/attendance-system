<?php
require_once 'auth.php';
$auth->requireAuth();

require_once 'db_connect.php';

// Get statistics based on user role
if ($_SESSION['role'] === 'professor') {
    // Professor statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) as total_students,
               COUNT(DISTINCT c.id) as total_courses,
               COUNT(DISTINCT s.id) as total_sessions
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN attendance_sessions s ON c.id = s.course_id
        WHERE c.professor_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    
    // Attendance rate
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_records,
               SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_records
        FROM attendance_records ar
        JOIN attendance_sessions s ON ar.session_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE c.professor_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $attendanceStats = $stmt->fetch();
    $attendanceRate = $attendanceStats['total_records'] > 0 ? 
        round(($attendanceStats['present_records'] / $attendanceStats['total_records']) * 100, 2) : 0;
    
} elseif ($_SESSION['role'] === 'student') {
    // Student statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as total_sessions,
               COUNT(ar.id) as total_records,
               SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_records,
               SUM(CASE WHEN ar.participation = 1 THEN 1 ELSE 0 END) as participation_count
        FROM attendance_sessions s
        JOIN enrollments e ON s.course_id = e.course_id
        LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ?
        WHERE e.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch();
    $attendanceRate = $stats['total_records'] > 0 ? 
        round(($stats['present_records'] / $stats['total_records']) * 100, 2) : 0;
        
} else {
    // Admin statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_students FROM users WHERE role = 'student'
    ");
    $stmt->execute();
    $studentCount = $stmt->fetch()['total_students'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_professors FROM users WHERE role = 'professor'
    ");
    $stmt->execute();
    $professorCount = $stmt->fetch()['total_professors'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses");
    $stmt->execute();
    $courseCount = $stmt->fetch()['total_courses'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_sessions FROM attendance_sessions");
    $stmt->execute();
    $sessionCount = $stmt->fetch()['total_sessions'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Include all the same base styles as previous pages */
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

        /* Report Styles */
        .report-info {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-good { color: #27ae60; }
        .stat-warning { color: #f39c12; }
        .stat-danger { color: #e74c3c; }
        .stat-info { color: #3498db; }

        /* Chart Styles */
        .chart-container {
            margin: 30px 0;
        }

        .chart-bar {
            height: 40px;
            margin-bottom: 10px;
            color: white;
            padding: 10px;
            border-radius: 4px;
            transition: width 0.5s;
            display: flex;
            align-items: center;
            font-weight: bold;
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

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
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
                <?php if ($_SESSION['role'] === 'professor' || $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link"><i class="fas fa-list"></i> Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link"><i class="fas fa-user-plus"></i> Students</a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'student'): ?>
                <li class="nav-item">
                    <a href="my_attendance.php" class="nav-link"><i class="fas fa-list"></i> My Attendance</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Reports Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <?php if ($_SESSION['role'] === 'professor'): ?>
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo $stats['total_courses']; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-danger"><?php echo $stats['total_sessions']; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-info"><?php echo $attendanceRate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                
                <?php elseif ($_SESSION['role'] === 'student'): ?>
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $stats['total_sessions']; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo $stats['present_records']; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-danger"><?php echo $stats['total_records'] - $stats['present_records']; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-info"><?php echo $attendanceRate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                
                <?php else: ?>
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $studentCount; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo $professorCount; ?></div>
                    <div class="stat-label">Professors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-danger"><?php echo $courseCount; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-info"><?php echo $sessionCount; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Detailed Reports -->
            <div class="report-info">
                <h3><i class="fas fa-chart-pie"></i> Attendance Overview</h3>
                
                <?php if ($_SESSION['role'] === 'professor'): ?>
                <div class="chart-container">
                    <h4>Overall Attendance Rate</h4>
                    <div class="chart-bar" style="width: <?php echo $attendanceRate; ?>%; background-color: #27ae60;">
                        <?php echo $attendanceRate; ?>% Present
                    </div>
                    <div class="chart-bar" style="width: <?php echo 100 - $attendanceRate; ?>%; background-color: #e74c3c;">
                        <?php echo 100 - $attendanceRate; ?>% Absent
                    </div>
                </div>
                
                <?php elseif ($_SESSION['role'] === 'student'): ?>
                <div class="chart-container">
                    <h4>My Attendance Summary</h4>
                    <div class="chart-bar" style="width: <?php echo $attendanceRate; ?>%; background-color: #27ae60;">
                        Present: <?php echo $stats['present_records']; ?> (<?php echo $attendanceRate; ?>%)
                    </div>
                    <div class="chart-bar" style="width: <?php echo 100 - $attendanceRate; ?>%; background-color: #e74c3c;">
                        Absent: <?php echo $stats['total_records'] - $stats['present_records']; ?> (<?php echo 100 - $attendanceRate; ?>%)
                    </div>
                </div>
                
                <?php else: ?>
                <div class="chart-container">
                    <h4>System Overview</h4>
                    <p>Welcome to the admin reports dashboard. Here you can view overall system statistics and generate comprehensive reports.</p>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Students</td>
                                    <td><?php echo $studentCount; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Professors</td>
                                    <td><?php echo $professorCount; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Courses</td>
                                    <td><?php echo $courseCount; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Sessions</td>
                                    <td><?php echo $sessionCount; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Additional Reports -->
            <div class="report-info">
                <h3><i class="fas fa-list"></i> Detailed Reports</h3>
                <p>Select a report type to view detailed analytics:</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-calendar"></i> Monthly Report
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-user-graduate"></i> Student Performance
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-chart-line"></i> Trends
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                </div>
            </div>
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
                        <?php if ($_SESSION['role'] === 'professor' || $_SESSION['role'] === 'admin'): ?>
                        <li><a href="attendance.php"><i class="fas fa-list"></i> Attendance</a></li>
                        <li><a href="students.php"><i class="fas fa-user-plus"></i> Students</a></li>
                        <?php endif; ?>
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
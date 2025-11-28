<?php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Get date range from request or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$reportType = $_GET['report_type'] ?? 'attendance_summary';

// Overall system statistics
$systemStats = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        SUM(CASE WHEN u.role = 'student' THEN 1 ELSE 0 END) as total_students,
        SUM(CASE WHEN u.role = 'professor' THEN 1 ELSE 0 END) as total_professors,
        COUNT(DISTINCT c.id) as total_courses,
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT ar.id) as total_attendance_records,
        COUNT(DISTINCT j.id) as total_justifications
    FROM users u
    CROSS JOIN courses c
    CROSS JOIN attendance_sessions s
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    LEFT JOIN justification_requests j ON s.id = j.session_id
")->fetch();

// Attendance statistics for the date range
$attendanceStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN participation = 1 THEN 1 ELSE 0 END) as participation_count,
        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate,
        ROUND((SUM(CASE WHEN participation = 1 THEN 1 ELSE 0 END) / SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)) * 100, 2) as participation_rate
    FROM attendance_records ar
    JOIN attendance_sessions s ON ar.session_id = s.id
    WHERE s.session_date BETWEEN ? AND ?
")->execute([$startDate, $endDate]);
$attendanceStats = $attendanceStats->fetch();

// Department/Course performance
$coursePerformance = $pdo->prepare("
    SELECT 
        c.name as course_name,
        c.code,
        u.first_name as prof_first_name,
        u.last_name as prof_last_name,
        COUNT(DISTINCT s.id) as session_count,
        COUNT(DISTINCT e.student_id) as student_count,
        COUNT(ar.id) as total_records,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as attendance_rate
    FROM courses c
    JOIN users u ON c.professor_id = u.id
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND s.session_date BETWEEN ? AND ?
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id, c.name, c.code, u.first_name, u.last_name
    ORDER BY attendance_rate DESC
")->execute([$startDate, $endDate]);
$coursePerformance = $coursePerformance->fetchAll();

// Student performance ranking
$studentRanking = $pdo->prepare("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT ar.id) as attended_sessions,
        ROUND((COUNT(DISTINCT ar.id) / COUNT(DISTINCT s.id)) * 100, 2) as attendance_rate,
        COUNT(DISTINCT j.id) as justification_count
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND s.session_date BETWEEN ? AND ?
    LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = u.id AND ar.status = 'present'
    LEFT JOIN justification_requests j ON s.id = j.session_id AND j.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.first_name, u.last_name, u.email
    HAVING COUNT(DISTINCT s.id) > 0
    ORDER BY attendance_rate DESC
    LIMIT 50
")->execute([$startDate, $endDate]);
$studentRanking = $studentRanking->fetchAll();

// Monthly trends
$monthlyTrends = $pdo->query("
    SELECT 
        DATE_FORMAT(s.session_date, '%Y-%m') as month,
        COUNT(DISTINCT s.id) as session_count,
        COUNT(ar.id) as total_records,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as attendance_rate
    FROM attendance_sessions s
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    WHERE s.session_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(s.session_date, '%Y-%m')
    ORDER BY month
")->fetchAll();

// Professor activity
$professorActivity = $pdo->prepare("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        COUNT(DISTINCT c.id) as course_count,
        COUNT(DISTINCT s.id) as session_count,
        COUNT(DISTINCT ar.id) as total_records,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as avg_attendance_rate
    FROM users u
    JOIN courses c ON u.id = c.professor_id
    LEFT JOIN attendance_sessions s ON c.id = s.course_id AND s.session_date BETWEEN ? AND ?
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    WHERE u.role = 'professor'
    GROUP BY u.id, u.first_name, u.last_name, u.email
    ORDER BY session_count DESC
")->execute([$startDate, $endDate]);
$professorActivity = $professorActivity->fetchAll();

// Justification statistics
$justificationStats = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM justification_requests j 
                          JOIN attendance_sessions s ON j.session_id = s.id 
                          WHERE s.session_date BETWEEN ? AND ?)) * 100, 2) as percentage
    FROM justification_requests j
    JOIN attendance_sessions s ON j.session_id = s.id
    WHERE s.session_date BETWEEN ? AND ?
    GROUP BY status
")->execute([$startDate, $endDate, $startDate, $endDate]);
$justificationStats = $justificationStats->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            max-width: 1400px;
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

        /* Report Controls */
        .report-controls {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219653;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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

        .stat-card.students { border-left-color: #27ae60; }
        .stat-card.professors { border-left-color: #e74c3c; }
        .stat-card.courses { border-left-color: #f39c12; }
        .stat-card.sessions { border-left-color: #9b59b6; }
        .stat-card.attendance { border-left-color: #3498db; }
        .stat-card.justifications { border-left-color: #34495e; }

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

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            height: 400px;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Report Sections */
        .report-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-actions {
            display: flex;
            gap: 10px;
        }

        /* Table Styles */
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

        .percentage {
            font-weight: bold;
        }

        .percentage.high { color: #27ae60; }
        .percentage.medium { color: #f39c12; }
        .percentage.low { color: #e74c3c; }

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
            
            .controls-grid {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="admin_index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="admin_statistics.php" class="nav-link"><i class="fas fa-chart-bar"></i> Statistics</a>
                </li>
                <li class="nav-item">
                    <a href="admin_students.php" class="nav-link"><i class="fas fa-users"></i> Students</a>
                </li>
                <li class="nav-item">
                    <a href="admin_justifications.php" class="nav-link"><i class="fas fa-file-alt"></i> Justifications</a>
                </li>
                <li class="nav-item">
                    <a href="admin_reports.php" class="nav-link active"><i class="fas fa-file-export"></i> Reports</a>
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
            <h2><i class="fas fa-file-export"></i> System Reports</h2>
            <p>Generate comprehensive reports and export data for analysis.</p>

            <!-- Report Controls -->
            <div class="report-controls">
                <form method="GET" id="reportForm">
                    <div class="controls-grid">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                        </div>
                        <div class="form-group">
                            <label for="report_type">Report Type</label>
                            <select id="report_type" name="report_type">
                                <option value="attendance_summary" <?php echo $reportType === 'attendance_summary' ? 'selected' : ''; ?>>Attendance Summary</option>
                                <option value="course_performance" <?php echo $reportType === 'course_performance' ? 'selected' : ''; ?>>Course Performance</option>
                                <option value="student_analysis" <?php echo $reportType === 'student_analysis' ? 'selected' : ''; ?>>Student Analysis</option>
                                <option value="professor_activity" <?php echo $reportType === 'professor_activity' ? 'selected' : ''; ?>>Professor Activity</option>
                            </select>
                        </div>
                    </div>
                    <div class="export-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card students">
                    <div class="stat-number"><?php echo $systemStats['total_students']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card professors">
                    <div class="stat-number"><?php echo $systemStats['total_professors']; ?></div>
                    <div class="stat-label">Professors</div>
                </div>
                <div class="stat-card courses">
                    <div class="stat-number"><?php echo $systemStats['total_courses']; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-card sessions">
                    <div class="stat-number"><?php echo $systemStats['total_sessions']; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
                <div class="stat-card attendance">
                    <div class="stat-number"><?php echo $attendanceStats['attendance_rate'] ?? 0; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                <div class="stat-card justifications">
                    <div class="stat-number"><?php echo $systemStats['total_justifications']; ?></div>
                    <div class="stat-label">Justifications</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-title">Monthly Attendance Trend</div>
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Course Performance</div>
                    <canvas id="coursePerformanceChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Justification Status</div>
                    <canvas id="justificationChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">User Distribution</div>
                    <canvas id="userDistributionChart"></canvas>
                </div>
            </div>

            <!-- Course Performance Report -->
            <div class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-book"></i> Course Performance Report</h3>
                    <div class="section-actions">
                        <button class="btn btn-secondary" onclick="exportSectionToExcel('course-performance-table', 'course_performance')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="course-performance-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Professor</th>
                                <th>Sessions</th>
                                <th>Students</th>
                                <th>Attendance Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coursePerformance as $course): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($course['code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($course['prof_first_name'] . ' ' . $course['prof_last_name']); ?></td>
                                <td style="text-align: center;"><?php echo $course['session_count']; ?></td>
                                <td style="text-align: center;"><?php echo $course['student_count']; ?></td>
                                <td style="text-align: center;" class="percentage <?php echo $course['attendance_rate'] >= 80 ? 'high' : ($course['attendance_rate'] >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $course['attendance_rate']; ?>%
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($course['attendance_rate'] >= 80): ?>
                                        <span style="color: #27ae60;">Excellent</span>
                                    <?php elseif ($course['attendance_rate'] >= 60): ?>
                                        <span style="color: #f39c12;">Good</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">Needs Attention</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Student Performance Report -->
            <div class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-graduate"></i> Student Performance Ranking</h3>
                    <div class="section-actions">
                        <button class="btn btn-secondary" onclick="exportSectionToExcel('student-performance-table', 'student_performance')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="student-performance-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>Total Sessions</th>
                                <th>Attended</th>
                                <th>Attendance Rate</th>
                                <th>Justifications</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentRanking as $index => $student): ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($student['email']); ?></small>
                                </td>
                                <td style="text-align: center;"><?php echo $student['total_sessions']; ?></td>
                                <td style="text-align: center;"><?php echo $student['attended_sessions']; ?></td>
                                <td style="text-align: center;" class="percentage <?php echo $student['attendance_rate'] >= 80 ? 'high' : ($student['attendance_rate'] >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $student['attendance_rate']; ?>%
                                </td>
                                <td style="text-align: center;"><?php echo $student['justification_count']; ?></td>
                                <td style="text-align: center;">
                                    <?php if ($student['attendance_rate'] >= 80): ?>
                                        <span style="color: #27ae60;">Excellent</span>
                                    <?php elseif ($student['attendance_rate'] >= 60): ?>
                                        <span style="color: #f39c12;">Good</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">Concerning</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Professor Activity Report -->
            <div class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Professor Activity Report</h3>
                    <div class="section-actions">
                        <button class="btn btn-secondary" onclick="exportSectionToExcel('professor-activity-table', 'professor_activity')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="professor-activity-table">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Courses</th>
                                <th>Sessions</th>
                                <th>Attendance Rate</th>
                                <th>Activity Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professorActivity as $professor): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($professor['email']); ?></small>
                                </td>
                                <td style="text-align: center;"><?php echo $professor['course_count']; ?></td>
                                <td style="text-align: center;"><?php echo $professor['session_count']; ?></td>
                                <td style="text-align: center;" class="percentage <?php echo $professor['avg_attendance_rate'] >= 80 ? 'high' : ($professor['avg_attendance_rate'] >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $professor['avg_attendance_rate']; ?>%
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($professor['session_count'] >= 10): ?>
                                        <span style="color: #27ae60;">High</span>
                                    <?php elseif ($professor['session_count'] >= 5): ?>
                                        <span style="color: #f39c12;">Medium</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">Low</span>
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

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Admin Tools</h3>
                    <ul>
                        <li><a href="admin_students.php"><i class="fas fa-users"></i> Student Management</a></li>
                        <li><a href="admin_statistics.php"><i class="fas fa-chart-bar"></i> Statistics</a></li>
                        <li><a href="admin_justifications.php"><i class="fas fa-file-alt"></i> Justifications</a></li>
                        <li><a href="admin_reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
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

    <script>
        // Prepare data for charts
        const monthlyData = <?php echo json_encode($monthlyTrends); ?>;
        const courseData = <?php echo json_encode($coursePerformance); ?>;
        const justificationData = <?php echo json_encode($justificationStats); ?>;
        const userStats = <?php echo json_encode($systemStats); ?>;

        // Monthly Attendance Trend Chart
        const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(m => m.month),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: monthlyData.map(m => m.attendance_rate),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)'
                        }
                    }
                }
            }
        });

        // Course Performance Chart
        const courseCtx = document.getElementById('coursePerformanceChart').getContext('2d');
        new Chart(courseCtx, {
            type: 'bar',
            data: {
                labels: courseData.slice(0, 10).map(c => c.course_name),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: courseData.slice(0, 10).map(c => c.attendance_rate),
                    backgroundColor: courseData.slice(0, 10).map(c => 
                        c.attendance_rate >= 80 ? '#27ae60' : 
                        c.attendance_rate >= 60 ? '#f39c12' : '#e74c3c'
                    ),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)'
                        }
                    }
                }
            }
        });

        // Justification Chart
        const justificationCtx = document.getElementById('justificationChart').getContext('2d');
        new Chart(justificationCtx, {
            type: 'doughnut',
            data: {
                labels: justificationData.map(j => j.status.charAt(0).toUpperCase() + j.status.slice(1)),
                datasets: [{
                    data: justificationData.map(j => j.count),
                    backgroundColor: [
                        '#f39c12', // pending
                        '#27ae60', // approved
                        '#e74c3c'  // rejected
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // User Distribution Chart
        const userCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userCtx, {
            type: 'pie',
            data: {
                labels: ['Students', 'Professors', 'Admins'],
                datasets: [{
                    data: [
                        userStats.total_students,
                        userStats.total_professors,
                        1 // Assuming 1 admin
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#2c3e50'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Export functions
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(20);
            doc.text('Attendly System Report', 20, 20);
            
            // Add date range
            doc.setFontSize(12);
            doc.text(`Date Range: ${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`, 20, 35);
            
            // Add statistics
            doc.text('System Statistics:', 20, 50);
            let yPos = 60;
            doc.text(`Total Students: ${userStats.total_students}`, 20, yPos); yPos += 10;
            doc.text(`Total Professors: ${userStats.total_professors}`, 20, yPos); yPos += 10;
            doc.text(`Total Courses: ${userStats.total_courses}`, 20, yPos); yPos += 10;
            doc.text(`Attendance Rate: ${attendanceStats.attendance_rate}%`, 20, yPos); yPos += 20;
            
            // Save the PDF
            doc.save(`attendly_report_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function exportToExcel() {
            // Simple CSV export for demonstration
            let csvContent = "data:text/csv;charset=utf-8,";
            
            // Add headers
            csvContent += "Report Type,Value\n";
            csvContent += `Total Students,${userStats.total_students}\n`;
            csvContent += `Total Professors,${userStats.total_professors}\n`;
            csvContent += `Total Courses,${userStats.total_courses}\n`;
            csvContent += `Attendance Rate,${attendanceStats.attendance_rate}%\n`;
            csvContent += `Date Range,${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}\n`;
            
            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `attendly_report_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportSectionToExcel(tableId, filename) {
            const table = document.getElementById(tableId);
            let csv = [];
            
            // Add headers
            const headers = [];
            for (let i = 0; i < table.rows[0].cells.length; i++) {
                headers.push(table.rows[0].cells[i].innerText);
            }
            csv.push(headers.join(','));
            
            // Add rows
            for (let i = 1; i < table.rows.length; i++) {
                const row = [];
                for (let j = 0; j < table.rows[i].cells.length; j++) {
                    row.push(table.rows[i].cells[j].innerText.replace(/,/g, ''));
                }
                csv.push(row.join(','));
            }
            
            // Create download
            const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function printReport() {
            window.print();
        }

        // Auto-generate report when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Report is already generated via PHP
        });
    </script>
</body>
</html>
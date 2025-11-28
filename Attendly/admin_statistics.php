<?php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Get date range from request or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Overall statistics
$stats = $pdo->prepare("
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

// Attendance statistics
$attendanceStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN participation = 1 THEN 1 ELSE 0 END) as participation_count,
        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate,
        ROUND((SUM(CASE WHEN participation = 1 THEN 1 ELSE 0 END) / SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)) * 100, 2) as participation_rate
    FROM attendance_records
")->fetch();

// Monthly attendance trend
$monthlyTrend = $pdo->query("
    SELECT 
        DATE_FORMAT(s.session_date, '%Y-%m') as month,
        COUNT(DISTINCT s.id) as session_count,
        COUNT(ar.id) as total_records,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as attendance_rate
    FROM attendance_sessions s
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    WHERE s.session_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(s.session_date, '%Y-%m')
    ORDER BY month
")->fetchAll();

// Course-wise statistics
$courseStats = $pdo->query("
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
    LEFT JOIN attendance_sessions s ON c.id = s.course_id
    LEFT JOIN attendance_records ar ON s.id = ar.session_id
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id, c.name, c.code, u.first_name, u.last_name
    ORDER BY attendance_rate DESC
")->fetchAll();

// Justification statistics
$justificationStats = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM justification_requests)) * 100, 2) as percentage
    FROM justification_requests
    GROUP BY status
")->fetchAll();

// Top students by attendance
$topStudents = $pdo->query("
    SELECT 
        u.first_name,
        u.last_name,
        u.email,
        COUNT(ar.id) as total_sessions,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as attendance_rate
    FROM users u
    JOIN attendance_records ar ON u.id = ar.student_id
    WHERE u.role = 'student'
    GROUP BY u.id, u.first_name, u.last_name, u.email
    HAVING COUNT(ar.id) >= 3
    ORDER BY attendance_rate DESC
    LIMIT 10
")->fetchAll();

// Bottom students by attendance
$bottomStudents = $pdo->query("
    SELECT 
        u.first_name,
        u.last_name,
        u.email,
        COUNT(ar.id) as total_sessions,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
        ROUND((SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) / COUNT(ar.id)) * 100, 2) as attendance_rate
    FROM users u
    JOIN attendance_records ar ON u.id = ar.student_id
    WHERE u.role = 'student'
    GROUP BY u.id, u.first_name, u.last_name, u.email
    HAVING COUNT(ar.id) >= 3
    ORDER BY attendance_rate ASC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
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
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #3498db;
        }

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

        /* Tables */
        .table-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-container {
            overflow-x: auto;
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
            
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
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
                    <a href="admin_statistics.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Statistics</a>
                </li>
                <li class="nav-item">
                    <a href="admin_students.php" class="nav-link"><i class="fas fa-users"></i> Students</a>
                </li>
                <li class="nav-item">
                    <a href="admin_justifications.php" class="nav-link"><i class="fas fa-file-alt"></i> Justifications</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Statistics Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-chart-bar"></i> System Statistics</h2>
            <p>Comprehensive analytics and insights about the attendance system.</p>

            <!-- Date Filter -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total_professors']; ?></div>
                    <div class="stat-label">Professors</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total_sessions']; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $attendanceStats['attendance_rate']; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Attendance Trend Chart -->
                <div class="chart-container">
                    <div class="chart-title">Monthly Attendance Trend</div>
                    <canvas id="attendanceTrendChart"></canvas>
                </div>

                <!-- Course Performance Chart -->
                <div class="chart-container">
                    <div class="chart-title">Course Attendance Rates</div>
                    <canvas id="coursePerformanceChart"></canvas>
                </div>

                <!-- User Distribution Chart -->
                <div class="chart-container">
                    <div class="chart-title">User Distribution</div>
                    <canvas id="userDistributionChart"></canvas>
                </div>

                <!-- Justification Status Chart -->
                <div class="chart-container">
                    <div class="chart-title">Justification Requests</div>
                    <canvas id="justificationChart"></canvas>
                </div>
            </div>

            <!-- Course Statistics Table -->
            <div class="table-section">
                <h3><i class="fas fa-book"></i> Course Performance</h3>
                <div class="table-container">
                    <table>
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
                            <?php foreach ($courseStats as $course): ?>
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

            <!-- Student Performance Tables -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Top Students -->
                <div class="table-section">
                    <h3><i class="fas fa-trophy"></i> Top Students</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Sessions</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topStudents as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td style="text-align: center;"><?php echo $student['total_sessions']; ?></td>
                                    <td style="text-align: center;" class="percentage high">
                                        <?php echo $student['attendance_rate']; ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Students Needing Attention -->
                <div class="table-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Students Needing Attention</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Sessions</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bottomStudents as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td style="text-align: center;"><?php echo $student['total_sessions']; ?></td>
                                    <td style="text-align: center;" class="percentage low">
                                        <?php echo $student['attendance_rate']; ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
        const monthlyData = <?php echo json_encode($monthlyTrend); ?>;
        const courseData = <?php echo json_encode($courseStats); ?>;
        const justificationData = <?php echo json_encode($justificationStats); ?>;
        const userStats = <?php echo json_encode($stats); ?>;

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
                }, {
                    label: 'Sessions Count',
                    data: monthlyData.map(m => m.session_count),
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    yAxisID: 'y1'
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
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sessions Count'
                        },
                        grid: {
                            drawOnChartArea: false
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
                labels: courseData.map(c => c.course_name),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: courseData.map(c => c.attendance_rate),
                    backgroundColor: courseData.map(c => 
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

        // User Distribution Chart
        const userCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userCtx, {
            type: 'doughnut',
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

        // Justification Chart
        const justificationCtx = document.getElementById('justificationChart').getContext('2d');
        new Chart(justificationCtx, {
            type: 'pie',
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
    </script>
</body>
</html>
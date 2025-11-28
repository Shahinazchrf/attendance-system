<?php
require_once 'auth.php';
$auth->requireRole('student');

require_once 'db_connect.php';

// Get student's attendance records
$stmt = $pdo->prepare("
    SELECT ar.*, s.session_date, c.name as course_name, g.name as group_name 
    FROM attendance_records ar
    JOIN attendance_sessions s ON ar.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN groups g ON s.group_id = g.id
    WHERE ar.student_id = ?
    ORDER BY s.session_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$attendanceRecords = $stmt->fetchAll();

// Calculate statistics
$totalRecords = count($attendanceRecords);
$presentRecords = array_filter($attendanceRecords, function($record) {
    return $record['status'] === 'present';
});
$absentRecords = $totalRecords - count($presentRecords);
$attendanceRate = $totalRecords > 0 ? round((count($presentRecords) / $totalRecords) * 100, 2) : 0;

// Get participation count
$participationCount = array_filter($attendanceRecords, function($record) {
    return $record['participation'] == 1;
});
$participationRate = $totalRecords > 0 ? round((count($participationCount) / $totalRecords) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Attendly</title>
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

        .status-present {
            color: #27ae60;
            font-weight: bold;
        }

        .status-absent {
            color: #e74c3c;
            font-weight: bold;
        }

        .participation-yes {
            color: #27ae60;
        }

        .participation-no {
            color: #95a5a6;
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
                    <a href="my_attendance.php" class="nav-link active"><i class="fas fa-list"></i> My Attendance</a>
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

    <!-- My Attendance Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-user-check"></i> My Attendance Records</h2>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $totalRecords; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo count($presentRecords); ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-danger"><?php echo $absentRecords; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-info"><?php echo $attendanceRate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="report-info">
                <h3><i class="fas fa-chart-pie"></i> Attendance Summary</h3>
                <p>Welcome, <?php echo $_SESSION['first_name']; ?>! Here's your attendance overview.</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                    <div>
                        <h4>Attendance Distribution</h4>
                        <div style="background-color: #27ae60; height: 30px; width: <?php echo $attendanceRate; ?>%; border-radius: 4px; margin-bottom: 5px;"></div>
                        <p>Present: <?php echo $attendanceRate; ?>%</p>
                        <div style="background-color: #e74c3c; height: 30px; width: <?php echo 100 - $attendanceRate; ?>%; border-radius: 4px; margin-bottom: 5px;"></div>
                        <p>Absent: <?php echo 100 - $attendanceRate; ?>%</p>
                    </div>
                    <div>
                        <h4>Quick Stats</h4>
                        <p><strong>Participation Rate:</strong> <?php echo $participationRate; ?>%</p>
                        <p><strong>Total Participations:</strong> <?php echo count($participationCount); ?></p>
                        <p><strong>Current Status:</strong> 
                            <span style="color: <?php echo $attendanceRate >= 80 ? '#27ae60' : ($attendanceRate >= 60 ? '#f39c12' : '#e74c3c'); ?>; font-weight: bold;">
                                <?php echo $attendanceRate >= 80 ? 'Excellent' : ($attendanceRate >= 60 ? 'Good' : 'Needs Improvement'); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detailed Records -->
            <div class="report-info">
                <h3><i class="fas fa-list"></i> Detailed Attendance History</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Group</th>
                                <th>Status</th>
                                <th>Participation</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($record['session_date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['group_name']); ?></td>
                                <td class="status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </td>
                                <td class="participation-<?php echo $record['participation'] ? 'yes' : 'no'; ?>">
                                    <?php echo $record['participation'] ? 'Yes' : 'No'; ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($record['recorded_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($attendanceRecords)): ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h4>No Attendance Records Found</h4>
                    <p>Your attendance records will appear here once your professors start taking attendance.</p>
                </div>
                <?php endif; ?>
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
                        <li><a href="my_attendance.php"><i class="fas fa-list"></i> My Attendance</a></li>
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
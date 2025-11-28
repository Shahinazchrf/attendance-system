<?php
require_once 'auth.php';
$auth->requireRole('professor');

require_once 'db_connect.php';

$sessionId = $_GET['session_id'] ?? 0;
$courseId = $_GET['course_id'] ?? 0;
$groupId = $_GET['group_id'] ?? 0;

// Get session details if session_id provided
if ($sessionId) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as course_name, g.name as group_name 
        FROM attendance_sessions s 
        JOIN courses c ON s.course_id = c.id 
        JOIN groups g ON s.group_id = g.id 
        WHERE s.id = ? AND c.professor_id = ?
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id']]);
    $session = $stmt->fetch();
    
    if (!$session) {
        die("Session not found or access denied.");
    }
    
    $courseId = $session['course_id'];
    $groupId = $session['group_id'];
}

// Get course and group details
$stmt = $pdo->prepare("
    SELECT c.name as course_name, c.code, g.name as group_name 
    FROM courses c 
    JOIN groups g ON c.id = g.course_id 
    WHERE c.id = ? AND g.id = ? AND c.professor_id = ?
");
$stmt->execute([$courseId, $groupId, $_SESSION['user_id']]);
$courseGroup = $stmt->fetch();

if (!$courseGroup) {
    die("Course or group not found or access denied.");
}

// Get all sessions for this course group
$stmt = $pdo->prepare("
    SELECT s.id, s.session_date, s.status,
           COUNT(ar.id) as total_records,
           SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN ar.participation = 1 THEN 1 ELSE 0 END) as participation_count
    FROM attendance_sessions s 
    LEFT JOIN attendance_records ar ON s.id = ar.session_id 
    WHERE s.course_id = ? AND s.group_id = ? 
    GROUP BY s.id 
    ORDER BY s.session_date DESC
");
$stmt->execute([$courseId, $groupId]);
$sessions = $stmt->fetchAll();

// Get students for this group
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email 
    FROM users u 
    JOIN enrollments e ON u.id = e.student_id 
    WHERE e.course_id = ? AND e.group_id = ? 
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$courseId, $groupId]);
$students = $stmt->fetchAll();

// Get attendance records for all sessions
$studentAttendance = [];
foreach ($students as $student) {
    $studentAttendance[$student['id']] = [];
}

foreach ($sessions as $session) {
    $stmt = $pdo->prepare("
        SELECT student_id, status, participation 
        FROM attendance_records 
        WHERE session_id = ?
    ");
    $stmt->execute([$session['id']]);
    $records = $stmt->fetchAll();
    
    foreach ($records as $record) {
        $studentAttendance[$record['student_id']][$session['id']] = [
            'status' => $record['status'],
            'participation' => $record['participation']
        ];
    }
}

// Calculate overall statistics
$totalSessions = count($sessions);
$overallStats = [
    'total_students' => count($students),
    'average_attendance' => 0,
    'average_participation' => 0
];

if ($totalSessions > 0) {
    $totalAttendanceRate = 0;
    $totalParticipationRate = 0;
    
    foreach ($sessions as $session) {
        if ($session['total_records'] > 0) {
            $attendanceRate = ($session['present_count'] / $session['total_records']) * 100;
            $participationRate = ($session['participation_count'] / $session['present_count']) * 100;
            
            $totalAttendanceRate += $attendanceRate;
            $totalParticipationRate += $participationRate;
        }
    }
    
    $overallStats['average_attendance'] = $totalSessions > 0 ? round($totalAttendanceRate / $totalSessions, 2) : 0;
    $overallStats['average_participation'] = $totalSessions > 0 ? round($totalParticipationRate / $totalSessions, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }

        .summary-card h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .summary-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .summary-card .percentage {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .percentage.high {
            color: #27ae60;
        }

        .percentage.medium {
            color: #f39c12;
        }

        .percentage.low {
            color: #e74c3c;
        }

        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            padding: 5px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: bold;
            color: #7f8c8d;
        }

        .tab.active {
            background: #3498db;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .student-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 1;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .status-present {
            color: #27ae60;
            font-weight: bold;
            text-align: center;
        }

        .status-absent {
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
        }

        .status-missing {
            color: #95a5a6;
            font-style: italic;
            text-align: center;
        }

        .participation-yes {
            color: #3498db;
            text-align: center;
        }

        .participation-no {
            color: #95a5a6;
            text-align: center;
        }

        /* Session header in table */
        .session-header {
            text-align: center;
            background: #34495e !important;
            white-space: nowrap;
        }

        .session-date {
            font-size: 0.8rem;
            font-weight: normal;
        }

        .session-stats {
            font-size: 0.7rem;
            font-weight: normal;
            opacity: 0.8;
        }

        /* Buttons */
        .btn-effect {
            background: linear-gradient(135deg, #3498db, #2980b9);
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
        }

        .btn-effect:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #7f8c8d, #95a5a6);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
            
            .tabs {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
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

    <!-- Attendance Summary -->
    <section class="section">
        <div class="container">
            <div class="action-buttons">
                <a href="attendance.php" class="btn-effect btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Sessions
                </a>
                <a href="create_session.php?course_id=<?php echo $courseId; ?>&group_id=<?php echo $groupId; ?>" class="btn-effect">
                    <i class="fas fa-plus"></i> New Session
                </a>
            </div>

            <h2><i class="fas fa-chart-bar"></i> Attendance Summary</h2>
            
            <!-- Course Info -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 15px rgba(0,0,0,0.1);">
                <h3><?php echo htmlspecialchars($courseGroup['course_name']); ?> (<?php echo htmlspecialchars($courseGroup['code']); ?>)</h3>
                <p><strong>Group:</strong> <?php echo htmlspecialchars($courseGroup['group_name']); ?></p>
                <p><strong>Total Students:</strong> <?php echo count($students); ?> | <strong>Total Sessions:</strong> <?php echo count($sessions); ?></p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Average Attendance Rate</h3>
                    <div class="number"><?php echo $overallStats['average_attendance']; ?>%</div>
                    <div class="percentage <?php echo $overallStats['average_attendance'] >= 80 ? 'high' : ($overallStats['average_attendance'] >= 60 ? 'medium' : 'low'); ?>">
                        <?php echo $overallStats['average_attendance'] >= 80 ? 'Excellent' : ($overallStats['average_attendance'] >= 60 ? 'Good' : 'Needs Improvement'); ?>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h3>Average Participation Rate</h3>
                    <div class="number"><?php echo $overallStats['average_participation']; ?>%</div>
                    <div class="percentage <?php echo $overallStats['average_participation'] >= 80 ? 'high' : ($overallStats['average_participation'] >= 60 ? 'medium' : 'low'); ?>">
                        <?php echo $overallStats['average_participation'] >= 80 ? 'High' : ($overallStats['average_participation'] >= 60 ? 'Moderate' : 'Low'); ?>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo count($students); ?></div>
                    <p>Enrolled in this group</p>
                </div>
                
                <div class="summary-card">
                    <h3>Completed Sessions</h3>
                    <div class="number"><?php echo count($sessions); ?></div>
                    <p>Attendance records</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" data-tab="detailed">Detailed View</div>
                <div class="tab" data-tab="student-summary">Student Summary</div>
                <div class="tab" data-tab="session-summary">Session Summary</div>
            </div>

            <!-- Detailed View Tab -->
            <div class="tab-content active" id="detailed-tab">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="student-col">Student</th>
                                <?php foreach ($sessions as $session): ?>
                                <th class="session-header">
                                    <div><?php echo date('M j', strtotime($session['session_date'])); ?></div>
                                    <div class="session-stats">
                                        <?php echo $session['present_count']; ?>/<?php echo $session['total_records']; ?>
                                    </div>
                                </th>
                                <?php endforeach; ?>
                                <th>Overall</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $studentPresentCount = 0;
                                $studentTotalSessions = 0;
                            ?>
                            <tr>
                                <td class="student-col">
                                    <strong><?php echo htmlspecialchars($student['last_name']); ?>, <?php echo htmlspecialchars($student['first_name']); ?></strong>
                                    <br><small><?php echo $student['email']; ?></small>
                                </td>
                                
                                <?php foreach ($sessions as $session): 
                                    $attendance = $studentAttendance[$student['id']][$session['id']] ?? null;
                                    $studentTotalSessions++;
                                    
                                    if ($attendance && $attendance['status'] === 'present') {
                                        $studentPresentCount++;
                                    }
                                ?>
                                <td>
                                    <?php if ($attendance): ?>
                                        <div class="status-<?php echo $attendance['status']; ?>">
                                            <?php echo $attendance['status'] === 'present' ? '✓' : '✗'; ?>
                                        </div>
                                        <div class="participation-<?php echo $attendance['participation'] ? 'yes' : 'no'; ?>">
                                            <?php echo $attendance['participation'] ? '★' : ''; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-missing">-</div>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                
                                <td style="text-align: center; font-weight: bold;">
                                    <?php 
                                    $attendanceRate = $studentTotalSessions > 0 ? round(($studentPresentCount / $studentTotalSessions) * 100) : 0;
                                    echo $attendanceRate . '%';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Student Summary Tab -->
            <div class="tab-content" id="student-summary-tab">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Sessions Attended</th>
                                <th>Total Sessions</th>
                                <th>Attendance Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $presentCount = 0;
                                $totalCount = 0;
                                
                                foreach ($sessions as $session) {
                                    $attendance = $studentAttendance[$student['id']][$session['id']] ?? null;
                                    if ($attendance) {
                                        $totalCount++;
                                        if ($attendance['status'] === 'present') {
                                            $presentCount++;
                                        }
                                    }
                                }
                                
                                $attendanceRate = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 2) : 0;
                                $statusClass = $attendanceRate >= 80 ? 'high' : ($attendanceRate >= 60 ? 'medium' : 'low');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['last_name']); ?>, <?php echo htmlspecialchars($student['first_name']); ?></strong>
                                    <br><small><?php echo $student['email']; ?></small>
                                </td>
                                <td style="text-align: center;"><?php echo $presentCount; ?></td>
                                <td style="text-align: center;"><?php echo $totalCount; ?></td>
                                <td style="text-align: center; font-weight: bold;" class="percentage <?php echo $statusClass; ?>">
                                    <?php echo $attendanceRate; ?>%
                                </td>
                                <td style="text-align: center;">
                                    <?php 
                                    if ($attendanceRate >= 80) {
                                        echo '<span style="color: #27ae60;">Excellent</span>';
                                    } elseif ($attendanceRate >= 60) {
                                        echo '<span style="color: #f39c12;">Good</span>';
                                    } else {
                                        echo '<span style="color: #e74c3c;">Concerning</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Session Summary Tab -->
            <div class="tab-content" id="session-summary-tab">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Session Date</th>
                                <th>Status</th>
                                <th>Students Present</th>
                                <th>Total Students</th>
                                <th>Attendance Rate</th>
                                <th>Participation Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): 
                                $attendanceRate = $session['total_records'] > 0 ? round(($session['present_count'] / $session['total_records']) * 100, 2) : 0;
                                $participationRate = $session['present_count'] > 0 ? round(($session['participation_count'] / $session['present_count']) * 100, 2) : 0;
                            ?>
                            <tr>
                                <td><?php echo date('F j, Y', strtotime($session['session_date'])); ?></td>
                                <td>
                                    <span class="status-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;"><?php echo $session['present_count']; ?></td>
                                <td style="text-align: center;"><?php echo $session['total_records']; ?></td>
                                <td style="text-align: center; font-weight: bold;" class="percentage <?php echo $attendanceRate >= 80 ? 'high' : ($attendanceRate >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $attendanceRate; ?>%
                                </td>
                                <td style="text-align: center; font-weight: bold;" class="percentage <?php echo $participationRate >= 80 ? 'high' : ($participationRate >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $participationRate; ?>%
                                </td>
                                <td style="text-align: center;">
                                    <a href="take_attendance.php?session_id=<?php echo $session['id']; ?>" class="btn-effect" style="padding: 8px 15px; font-size: 0.9rem;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
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

    <script>
        $(document).ready(function() {
            // Tab functionality
            $('.tab').click(function() {
                $('.tab').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-content').removeClass('active');
                $('#' + $(this).data('tab') + '-tab').addClass('active');
            });

            // Export functionality
            $('#export-btn').click(function() {
                alert('Export functionality would be implemented here');
                // This would typically generate a CSV or PDF export
            });
        });
    </script>
</body>
</html>
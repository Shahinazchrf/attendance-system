<?php
require_once 'auth.php';
$auth->requireRole('student');

require_once 'db_connect.php';

$studentId = $_SESSION['user_id'];
$courseId = $_GET['course_id'] ?? 0;

// Verify student is enrolled in this course
$enrollmentCheck = $pdo->prepare("
    SELECT c.name as course_name, c.code, g.name as group_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN groups g ON e.group_id = g.id
    WHERE e.student_id = ? AND e.course_id = ?
");
$enrollmentCheck->execute([$studentId, $courseId]);
$courseInfo = $enrollmentCheck->fetch();

if (!$courseInfo) {
    die("Course not found or access denied.");
}

// Get all sessions for this course with student's attendance
$sessions = $pdo->prepare("
    SELECT 
        s.id,
        s.session_date,
        s.status as session_status,
        ar.status as attendance_status,
        ar.participation,
        j.id as justification_id,
        j.status as justification_status,
        j.reason,
        j.file_path
    FROM attendance_sessions s
    LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ?
    LEFT JOIN justification_requests j ON s.id = j.session_id AND j.student_id = ?
    WHERE s.course_id = ?
    ORDER BY s.session_date DESC
");
$sessions->execute([$studentId, $studentId, $courseId]);
$sessions = $sessions->fetchAll();

// Calculate course statistics
$courseStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT ar.id) as attended_sessions,
        SUM(CASE WHEN ar.participation = 1 THEN 1 ELSE 0 END) as participation_count,
        COUNT(DISTINCT j.id) as justification_requests,
        COUNT(DISTINCT CASE WHEN j.status = 'approved' THEN j.id END) as approved_justifications
    FROM attendance_sessions s
    LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ? AND ar.status = 'present'
    LEFT JOIN justification_requests j ON s.id = j.session_id AND j.student_id = ?
    WHERE s.course_id = ?
");
$courseStats->execute([$studentId, $studentId, $courseId]);
$courseStats = $courseStats->fetch();

$attendanceRate = $courseStats['total_sessions'] > 0 ? 
    round(($courseStats['attended_sessions'] / $courseStats['total_sessions']) * 100, 2) : 0;

// Handle justification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_justification'])) {
    $sessionId = $_POST['session_id'];
    $reason = $_POST['reason'];
    
    // Handle file upload
    $filePath = null;
    if (isset($_FILES['justification_file']) && $_FILES['justification_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/justifications/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['justification_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['justification_file']['tmp_name'], $filePath)) {
            // File uploaded successfully
        } else {
            $error = "Error uploading file.";
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO justification_requests (student_id, session_id, reason, file_path, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$studentId, $sessionId, $reason, $filePath]);
        $success = "Justification submitted successfully!";
        
        // Refresh page to show updated data
        header("Location: student_course_attendance.php?course_id=$courseId");
        exit;
    } catch (PDOException $e) {
        $error = "Error submitting justification: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Attendance - Attendly</title>
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

        /* Navigation - Same as student_index.php */
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

        /* Course Header */
        .course-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .course-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .course-meta {
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
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

        .attendance-rate {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .rate-high { color: #27ae60; }
        .rate-medium { color: #f39c12; }
        .rate-low { color: #e74c3c; }

        /* Attendance Table */
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 15px;
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

        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }

        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }

        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }

        .participation-yes {
            color: #3498db;
        }

        .justification-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 12px 25px;
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

        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1rem;
        }

        /* Messages */
        .confirmation {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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
            
            .stats-cards {
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
                    <a href="student_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="student_attendance.php" class="nav-link active"><i class="fas fa-calendar-check"></i> My Attendance</a>
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

    <!-- Course Attendance -->
    <section class="section">
        <div class="container">
            <!-- Back Button -->
            <a href="student_index.php" class="btn btn-secondary" style="margin-bottom: 20px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Course Header -->
            <div class="course-header">
                <h1 class="course-title"><?php echo htmlspecialchars($courseInfo['course_name']); ?></h1>
                <div class="course-meta">
                    Code: <?php echo htmlspecialchars($courseInfo['code']); ?> | 
                    Group: <?php echo htmlspecialchars($courseInfo['group_name']); ?>
                </div>
                <div class="attendance-rate <?php echo $attendanceRate >= 80 ? 'rate-high' : ($attendanceRate >= 60 ? 'rate-medium' : 'rate-low'); ?>">
                    <?php echo $attendanceRate; ?>% Attendance Rate
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $courseStats['total_sessions']; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $courseStats['attended_sessions']; ?></div>
                    <div class="stat-label">Sessions Attended</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $courseStats['participation_count']; ?></div>
                    <div class="stat-label">Times Participated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $courseStats['justification_requests']; ?></div>
                    <div class="stat-label">Justification Requests</div>
                </div>
            </div>

            <!-- Attendance Records -->
            <h3><i class="fas fa-list"></i> Attendance Records</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Session Date</th>
                            <th>Attendance Status</th>
                            <th>Participation</th>
                            <th>Justification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 15px;"></i>
                                    <p>No attendance sessions recorded yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('F j, Y', strtotime($session['session_date'])); ?></strong>
                                </td>
                                <td>
                                    <span class="status-<?php echo $session['attendance_status'] ?? 'absent'; ?>">
                                        <?php echo isset($session['attendance_status']) ? ucfirst($session['attendance_status']) : 'Not Recorded'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($session['participation']): ?>
                                        <span class="participation-yes">
                                            <i class="fas fa-star"></i> Participated
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['justification_id']): ?>
                                        <span class="status-<?php echo $session['justification_status']; ?>">
                                            <?php echo ucfirst($session['justification_status']); ?>
                                        </span>
                                        <?php if ($session['file_path']): ?>
                                            <br><small><i class="fas fa-paperclip"></i> File attached</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['attendance_status'] === 'absent' && !$session['justification_id']): ?>
                                        <button class="justification-btn btn-primary" 
                                                onclick="openJustificationModal(<?php echo $session['id']; ?>, '<?php echo date('F j, Y', strtotime($session['session_date'])); ?>')">
                                            <i class="fas fa-file-upload"></i> Submit Justification
                                        </button>
                                    <?php elseif ($session['justification_id']): ?>
                                        <span class="justification-btn btn-secondary">
                                            <i class="fas fa-check"></i> Justification Submitted
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Justification Modal -->
    <div id="justificationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3><i class="fas fa-file-upload"></i> Submit Justification</h3>
            <p id="modalSessionDate"></p>
            
            <form method="POST" enctype="multipart/form-data" id="justificationForm">
                <input type="hidden" name="session_id" id="modalSessionId">
                
                <div class="form-group">
                    <label for="reason">Reason for Absence *</label>
                    <textarea id="reason" name="reason" required placeholder="Please explain why you were absent..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="justification_file">Supporting Document (Optional)</label>
                    <input type="file" id="justification_file" name="justification_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <small>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max: 5MB)</small>
                </div>
                
                <button type="submit" name="submit_justification" class="btn btn-success btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Justification
                </button>
            </form>
        </div>
    </div>

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

    <script>
        function openJustificationModal(sessionId, sessionDate) {
            document.getElementById('modalSessionId').value = sessionId;
            document.getElementById('modalSessionDate').textContent = 'Session: ' + sessionDate;
            document.getElementById('justificationModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('justificationModal').style.display = 'none';
            document.getElementById('justificationForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('justificationModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // File size validation
        document.getElementById('justification_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.size > 5 * 1024 * 1024) { // 5MB limit
                alert('File size must be less than 5MB');
                e.target.value = '';
            }
        });
    </script>
</body>
</html>
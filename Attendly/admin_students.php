<?php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        // Add new student
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, last_name, email, role) VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt->execute([$username, $password, $firstName, $lastName, $email]);
            $success = "Student added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding student: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['update_student'])) {
        // Update student
        $studentId = $_POST['student_id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$firstName, $lastName, $email, $username, $studentId]);
            $success = "Student updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating student: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['delete_student'])) {
        // Delete student
        $studentId = $_POST['student_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Delete related records first
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?");
            $stmt->execute([$studentId]);
            
            $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE student_id = ?");
            $stmt->execute([$studentId]);
            
            $stmt = $pdo->prepare("DELETE FROM justification_requests WHERE student_id = ?");
            $stmt->execute([$studentId]);
            
            // Delete student
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$studentId]);
            
            $pdo->commit();
            $success = "Student deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting student: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['enroll_student'])) {
        // Enroll student in course
        $studentId = $_POST['student_id'];
        $courseId = $_POST['course_id'];
        $groupId = $_POST['group_id'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, group_id) VALUES (?, ?, ?)");
            $stmt->execute([$studentId, $courseId, $groupId]);
            $success = "Student enrolled successfully!";
        } catch (PDOException $e) {
            $error = "Error enrolling student: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['import_students'])) {
        // Handle CSV import
        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['csv_file']['tmp_name'];
            $csvData = array_map('str_getcsv', file($tmpName));
            
            $imported = 0;
            $errors = [];
            
            // Skip header row
            array_shift($csvData);
            
            foreach ($csvData as $row) {
                if (count($row) >= 5) {
                    $username = $row[0];
                    $firstName = $row[1];
                    $lastName = $row[2];
                    $email = $row[3];
                    $password = password_hash($row[4], PASSWORD_DEFAULT);
                    
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, last_name, email, role) VALUES (?, ?, ?, ?, ?, 'student')");
                        $stmt->execute([$username, $password, $firstName, $lastName, $email]);
                        $imported++;
                    } catch (PDOException $e) {
                        $errors[] = "Failed to import $username: " . $e->getMessage();
                    }
                }
            }
            
            if ($imported > 0) {
                $success = "Successfully imported $imported students!";
            }
            if (!empty($errors)) {
                $error = implode("<br>", $errors);
            }
        } else {
            $error = "Error uploading file!";
        }
    }
}

// Get all students
$students = $pdo->query("
    SELECT u.*, 
           COUNT(e.course_id) as enrolled_courses,
           COUNT(ar.id) as attendance_records
    FROM users u 
    LEFT JOIN enrollments e ON u.id = e.student_id 
    LEFT JOIN attendance_records ar ON u.id = ar.student_id 
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.last_name, u.first_name
")->fetchAll();

// Get courses and groups for enrollment
$courses = $pdo->query("
    SELECT c.id as course_id, c.name as course_name, c.code,
           g.id as group_id, g.name as group_name
    FROM courses c 
    JOIN groups g ON c.id = g.course_id 
    ORDER BY c.name, g.name
")->fetchAll();

// Get student to edit
$editStudent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$_GET['edit']]);
    $editStudent = $stmt->fetch();
}

// Get enrollments for a student
$studentEnrollments = [];
if (isset($_GET['view_enrollments'])) {
    $studentId = $_GET['view_enrollments'];
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as course_name, c.code, g.name as group_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN groups g ON e.group_id = g.id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $studentEnrollments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Attendly</title>
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

        /* Forms */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
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

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
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

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Import/Export Section */
        .import-export {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .ie-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .ie-grid {
                grid-template-columns: 1fr;
            }
        }

        .ie-section {
            padding: 20px;
            border: 2px dashed #bdc3c7;
            border-radius: 8px;
            text-align: center;
        }

        .ie-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 15px;
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

        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
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
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
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
                    <a href="admin_students.php" class="nav-link active"><i class="fas fa-users"></i> Students</a>
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

    <!-- Student Management -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-users"></i> Student Management</h2>
            <p>Manage student accounts, enrollments, and import/export student data.</p>

            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" data-tab="students-list">Students List</div>
                <div class="tab" data-tab="add-student">Add Student</div>
                <div class="tab" data-tab="import-export">Import/Export</div>
            </div>

            <!-- Students List Tab -->
            <div class="tab-content active" id="students-list-tab">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Enrolled Courses</th>
                                <th>Attendance Records</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td style="text-align: center;"><?php echo $student['enrolled_courses']; ?></td>
                                    <td style="text-align: center;"><?php echo $student['attendance_records']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $student['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="?view_enrollments=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-book"></i> Enrollments
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" name="delete_student" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Student Tab -->
            <div class="tab-content" id="add-student-tab">
                <div class="form-container">
                    <h3><?php echo $editStudent ? 'Edit Student' : 'Add New Student'; ?></h3>
                    <form method="POST">
                        <?php if ($editStudent): ?>
                            <input type="hidden" name="student_id" value="<?php echo $editStudent['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['first_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['last_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['username']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo $editStudent ? htmlspecialchars($editStudent['email']) : ''; ?>">
                            </div>
                            
                            <?php if (!$editStudent): ?>
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" name="<?php echo $editStudent ? 'update_student' : 'add_student'; ?>" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $editStudent ? 'Update Student' : 'Add Student'; ?>
                        </button>
                        
                        <?php if ($editStudent): ?>
                            <a href="admin_students.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Import/Export Tab -->
            <div class="tab-content" id="import-export-tab">
                <div class="import-export">
                    <div class="ie-grid">
                        <!-- Import Section -->
                        <div class="ie-section">
                            <div class="ie-icon">
                                <i class="fas fa-file-import"></i>
                            </div>
                            <h3>Import Students</h3>
                            <p>Upload a CSV file to import multiple students at once.</p>
                            
                            <div class="warning">
                                <strong>CSV Format:</strong><br>
                                username,first_name,last_name,email,password<br>
                                Example: john_doe,John,Doe,john@student.univ-alger.dz,password123
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label for="csv_file">Select CSV File</label>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>
                                <button type="submit" name="import_students" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Import Students
                                </button>
                            </form>
                        </div>

                        <!-- Export Section -->
                        <div class="ie-section">
                            <div class="ie-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <h3>Export Students</h3>
                            <p>Download student data in CSV format compatible with Progres Excel.</p>
                            
                            <form method="POST" action="export_students.php" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label for="export_format">Export Format</label>
                                    <select id="export_format" name="export_format">
                                        <option value="csv">CSV (Comma Separated Values)</option>
                                        <option value="excel">Excel Format</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download"></i> Export Students
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollments Modal -->
            <?php if (isset($_GET['view_enrollments'])): ?>
            <div id="enrollmentsModal" class="modal" style="display: block;">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h3>Student Enrollments</h3>
                    
                    <?php if (empty($studentEnrollments)): ?>
                        <p>This student is not enrolled in any courses.</p>
                    <?php else: ?>
                        <div class="table-container" style="margin-top: 20px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Group</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentEnrollments as $enrollment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($enrollment['course_name']); ?></strong>
                                            <br><small><?php echo htmlspecialchars($enrollment['code']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($enrollment['group_name']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove student from this course?');">
                                                <input type="hidden" name="student_id" value="<?php echo $_GET['view_enrollments']; ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $enrollment['course_id']; ?>">
                                                <input type="hidden" name="group_id" value="<?php echo $enrollment['group_id']; ?>">
                                                <button type="submit" name="unenroll_student" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Enrollment Form -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4>Enroll in New Course</h4>
                        <form method="POST">
                            <input type="hidden" name="student_id" value="<?php echo $_GET['view_enrollments']; ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="course_id">Course</label>
                                    <select id="course_id" name="course_id" required>
                                        <option value="">Select a course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['course_id']; ?>">
                                                <?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['group_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="group_id">Group</label>
                                    <select id="group_id" name="group_id" required>
                                        <option value="">Select a group</option>
                                        <!-- Groups will be populated by JavaScript based on course selection -->
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="enroll_student" class="btn btn-success">
                                <i class="fas fa-plus"></i> Enroll Student
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
        $(document).ready(function() {
            // Tab functionality
            $('.tab').click(function() {
                $('.tab').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-content').removeClass('active');
                $('#' + $(this).data('tab') + '-tab').addClass('active');
            });

            // Course-Group dependency
            $('#course_id').change(function() {
                const courseId = $(this).val();
                const groups = <?php echo json_encode($courses); ?>;
                
                $('#group_id').empty().append('<option value="">Select a group</option>');
                
                groups.filter(course => course.course_id == courseId).forEach(group => {
                    $('#group_id').append(
                        $('<option>', {
                            value: group.group_id,
                            text: group.group_name
                        })
                    );
                });
            });

            // Auto-switch to edit tab if editing
            <?php if ($editStudent): ?>
                $('.tab').removeClass('active');
                $('[data-tab="add-student"]').addClass('active');
                $('.tab-content').removeClass('active');
                $('#add-student-tab').addClass('active');
            <?php endif; ?>
        });

        function closeModal() {
            window.location.href = 'admin_students.php';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('enrollmentsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
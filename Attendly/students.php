<?php
require_once 'auth.php';
// Autoriser à la fois professeurs et administrateurs
if ($_SESSION['role'] !== 'professor' && $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}

require_once 'db_connect.php';

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash('student123', PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, first_name, last_name, email) VALUES (?, ?, 'student', ?, ?, ?)");
        $stmt->execute([$username, $password, $firstName, $lastName, $email]);
        $success = "Student added successfully";
    } catch (PDOException $e) {
        $error = "Error adding student: " . $e->getMessage();
    }
}

// Delete student - NOUVELLE FONCTIONNALITÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $studentId = $_POST['student_id'];
    
    try {
        // Vérifier d'abord si l'étudiant a des enregistrements de présence
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_records WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $hasRecords = $stmt->fetchColumn();
        
        if ($hasRecords > 0) {
            $error = "Cannot delete student: Student has attendance records. Delete records first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$studentId]);
            $success = "Student deleted successfully";
        }
    } catch (PDOException $e) {
        $error = "Error deleting student: " . $e->getMessage();
    }
}

// Get students list
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' ORDER BY last_name, first_name");
$stmt->execute();
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Attendly</title>
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

        /* Form Styles */
        .report-info {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

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

        .btn-submit {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
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

        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
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

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
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
                    <a href="attendance.php" class="nav-link"><i class="fas fa-list"></i> Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link active"><i class="fas fa-user-plus"></i> Students</a>
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

    <!-- Students Management -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-users"></i> Student Management</h2>
            
            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Student Form -->
            <div class="report-info">
                <h3><i class="fas fa-user-plus"></i> Add Student</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="warning-message">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> The default password for new students is <strong>student123</strong>
                    </div>
                    
                    <button type="submit" name="add_student" class="btn-effect btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        Add Student
                    </button>
                </form>
            </div>

            <!-- Students List -->
            <div class="report-info">
                <h3><i class="fas fa-list"></i> Students List</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" name="delete_student" class="btn-effect btn-danger btn-small" onclick="return confirm('Are you sure you want to delete student: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h4>No Students Found</h4>
                    <p>Add your first student using the form above.</p>
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
        // Confirmation pour la suppression
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('button[name="delete_student"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
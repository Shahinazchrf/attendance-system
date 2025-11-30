<?php
// admin_students.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$students = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.first_name, u.last_name, u.username, u.email, 
                   COUNT(DISTINCT sc.course_id) as enrolled_courses,
                   COUNT(DISTINCT ar.id) as attendance_records
            FROM users u
            LEFT JOIN student_courses sc ON u.id = sc.student_id
            LEFT JOIN attendance_records ar ON u.id = ar.student_id
            WHERE u.role = 'student'
            GROUP BY u.id
            ORDER BY u.id
        ");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}

// Ajouter un étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = 'password123'; // Mot de passe par défaut

    if ($pdo) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt->execute([$first_name, $last_name, $username, $email, $hashed_password]);
            $success_message = "Étudiant ajouté avec succès!";
            
            // Recharger la liste
            $stmt = $pdo->query("
                SELECT u.id, u.first_name, u.last_name, u.username, u.email, 
                       COUNT(DISTINCT sc.course_id) as enrolled_courses,
                       COUNT(DISTINCT ar.id) as attendance_records
                FROM users u
                LEFT JOIN student_courses sc ON u.id = sc.student_id
                LEFT JOIN attendance_records ar ON u.id = ar.student_id
                WHERE u.role = 'student'
                GROUP BY u.id
                ORDER BY u.id
            ");
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Attendly</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #f5f6fa;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .navbar-brand {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #5a6778;
            font-size: 14px;
        }

        .role-tag {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .logout-btn {
            color: #e74c3c;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            border: 1px solid #e74c3c;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #e74c3c;
            color: white;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .page-header h1 {
            color: #2d3436;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #636e72;
            font-size: 14px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 2px;
            background: #e9ecef;
            padding: 4px;
            border-radius: 8px;
            margin-bottom: 20px;
            width: fit-content;
        }

        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab.active {
            background: white;
            color: #495057;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .action-btn {
            padding: 10px 20px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }

        /* Add Student Form */
        .add-student-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: none;
        }

        .add-student-form.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        th {
            padding: 16px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #495057;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-edit {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .btn-edit:hover {
            background: #ffeaa7;
        }

        .btn-enroll {
            background: #d1ecf1;
            border-color: #b6e0f1;
            color: #0c5460;
        }

        .btn-enroll:hover {
            background: #b6e0f1;
        }

        .btn-delete {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .btn-delete:hover {
            background: #f5c6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin_statistics.php" class="navbar-brand">ATTENDLY</a>
        <div class="user-menu">
            <div class="user-info">
                <span>Connecté en tant que <strong>Admin</strong></span>
                <span class="role-tag">ADMIN</span>
            </div>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Student Management</h1>
            <p>Manage student accounts, enrollments, and import/export student data.</p>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('students-list')">Students List</button>
            <button class="tab" onclick="showTab('add-student')">Add Student</button>
            <button class="tab" onclick="showTab('import-export')">Import/Export</button>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Students List Tab -->
        <div id="students-list" class="tab-content">
            <div class="action-buttons">
                <button class="action-btn" onclick="showTab('add-student')">+ Add Student</button>
                <button class="action-btn" onclick="showTab('import-export')">Import/Export</button>
            </div>

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
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['enrolled_courses']); ?></td>
                                    <td><?php echo htmlspecialchars($student['attendance_records']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-edit">Edit</button>
                                            <button class="btn-sm btn-enroll">Enrollments</button>
                                            <button class="btn-sm btn-delete">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">
                                    Aucun étudiant trouvé.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Student Tab -->
        <div id="add-student" class="tab-content" style="display: none;">
            <div class="add-student-form active">
                <h3 style="margin-bottom: 20px; color: #2d3436;">Add New Student</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                        <button type="button" class="btn btn-secondary" onclick="showTab('students-list')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import/Export Tab -->
        <div id="import-export" class="tab-content" style="display: none;">
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="margin-bottom: 20px; color: #2d3436;">Import/Export Students</h3>
                <p style="color: #636e72; margin-bottom: 20px;">Import and export student data in CSV format.</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Import Section -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: #2d3436;">Import Students</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="csv_file">Select CSV File</label>
                                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                            </div>
                            <button type="submit" name="import_csv" class="btn btn-primary">Import CSV</button>
                        </form>
                    </div>

                    <!-- Export Section -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: #2d3436;">Export Students</h4>
                        <form method="POST">
                            <button type="submit" name="export_csv" class="btn btn-primary">Export to CSV</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabName).style.display = 'block';
            
            // Activer l'onglet cliqué
            event.target.classList.add('active');
        }

        // Afficher l'onglet Students List par défaut
        document.addEventListener('DOMContentLoaded', function() {
            showTab('students-list');
        });
    </script>
</body>
</html>
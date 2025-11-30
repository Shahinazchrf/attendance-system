<?php
// students.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$students = array();

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.created_at,
                   COUNT(DISTINCT sc.course_id) as course_count
            FROM users u
            LEFT JOIN student_courses sc ON u.id = sc.student_id
            WHERE u.role = 'student'
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Attendly</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Navigation */
        .nav-header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #e1e5e9;
            margin-bottom: 30px;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #666;
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
            color: #dc3545;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #dc3545;
            color: white;
        }

        /* Header */
        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        /* Students Table */
        .students-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-header h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            color: #555;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-name {
            font-weight: 600;
            color: #333;
        }

        .student-email {
            color: #666;
            font-size: 13px;
        }

        .btn {
            padding: 6px 12px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            background: white;
        }

        .btn-edit {
            border-color: #ffc107;
            color: #856404;
        }

        .btn-edit:hover {
            background: #ffc107;
            color: white;
        }

        .btn-delete {
            border-color: #dc3545;
            color: #721c24;
        }

        .btn-delete:hover {
            background: #dc3545;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="admin_statistics.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">ADMIN</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Gestion des Étudiants</h1>
            <p>Consultez et gérez tous les étudiants du système.</p>
        </div>

        <div class="students-table">
            <div class="table-header">
                <h3>Liste des Étudiants</h3>
            </div>
            
            <?php if (count($students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Cours</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo $student['course_count']; ?> cours</td>
                                <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-edit">Modifier</button>
                                    <button class="btn btn-delete">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucun étudiant trouvé.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
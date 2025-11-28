<?php
// admin_dashboard.php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Statistiques pour l'admin
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$totalStudents = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'professor'");
$totalProfessors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
$totalCourses = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance_sessions");
$totalSessions = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprendre le même style que les autres pages */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; }
        .container { width: 100%; max-width: 1200px; padding: 0 15px; margin: 0 auto; }
        .section { padding: 80px 0 60px; }
        
        /* Navigation identique aux autres pages */
        .navbar { background-color: #2c3e50; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 15px; display: flex; justify-content: space-between; align-items: center; height: 70px; }
        .nav-logo { display: flex; align-items: center; }
        .nav-logo a { color: white; text-decoration: none; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; }
        .nav-logo i { margin-right: 10px; font-size: 1.8rem; }
        .nav-menu { display: flex; list-style: none; }
        .nav-item { margin-left: 25px; }
        .nav-link { color: white; text-decoration: none; font-size: 1rem; transition: all 0.3s ease; padding: 10px 15px; display: flex; align-items: center; border-radius: 5px; }
        .nav-link i { margin-right: 8px; }
        .nav-link:hover, .nav-link.active { color: #3498db; background-color: rgba(255,255,255,0.1); }
        
        /* Cartes de statistiques */
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .stat-value { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; }
        .stat-good { color: #27ae60; } .stat-warning { color: #f39c12; } .stat-danger { color: #e74c3c; } .stat-info { color: #3498db; }
        
        /* Actions rapides */
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; }
        .action-card { background: white; padding: 25px; border-radius: 8px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .action-card i { font-size: 2.5rem; color: #3498db; margin-bottom: 15px; }
        
        .btn-effect { background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-effect:hover { background: linear-gradient(135deg, #2980b9, #3498db); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4); }
        
        footer { background-color: #2c3e50; color: white; padding: 50px 0 20px; margin-top: 50px; }
        .footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .footer-bottom { text-align: center; padding-top: 20px; border-top: 1px solid #34495e; color: #bdc3c7; }
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
                    <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                </li>
                <li class="nav-item">
                    <a href="admin_courses.php" class="nav-link"><i class="fas fa-book"></i> Courses</a>
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

    <!-- Admin Dashboard -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            <p>Welcome, <?php echo $_SESSION['first_name']; ?>! Here's an overview of the system.</p>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $totalStudents; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo $totalProfessors; ?></div>
                    <div class="stat-label">Professors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-danger"><?php echo $totalCourses; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-info"><?php echo $totalSessions; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card">
                    <i class="fas fa-users-cog"></i>
                    <h3>Manage Users</h3>
                    <p>Add, edit, or remove students and professors</p>
                    <a href="admin_users.php" class="btn-effect">Manage Users</a>
                </div>
                <div class="action-card">
                    <i class="fas fa-book"></i>
                    <h3>Course Management</h3>
                    <p>Create and manage courses and groups</p>
                    <a href="admin_courses.php" class="btn-effect">Manage Courses</a>
                </div>
                <div class="action-card">
                    <i class="fas fa-file-import"></i>
                    <h3>Import/Export</h3>
                    <p>Import student lists or export data</p>
                    <a href="admin_import.php" class="btn-effect">Data Tools</a>
                </div>
                <div class="action-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Advanced Reports</h3>
                    <p>Detailed analytics and system reports</p>
                    <a href="admin_reports.php" class="btn-effect">View Reports</a>
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
                        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Courses</a></li>
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
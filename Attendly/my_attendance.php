<?php
// my_attendance.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Présences - Attendly</title>
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
            max-width: 1000px;
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
            background: #28a745;
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
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .course-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        /* Course Selector */
        .course-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .course-selector label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .course-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-total { color: #333; }
        .stat-present { color: #28a745; }
        .stat-rate { color: #007bff; }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        /* Attendance History */
        .attendance-history {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .history-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .history-header h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-info h4 {
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .session-date {
            color: #666;
            font-size: 13px;
        }

        .session-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-present {
            background: #d4edda;
            color: #155724;
        }

        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }

        .status-late {
            background: #fff3cd;
            color: #856404;
        }

        .status-excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            transition: background 0.2s ease;
        }

        .back-btn:hover {
            background: #545b62;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="student_index.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">ÉTUDIANT</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <a href="student_index.php" class="back-btn">← Retour au tableau de bord</a>

        <div class="course-selector">
            <label for="course_select">Sélectionnez un cours :</label>
            <select id="course_select" onchange="window.location.href = 'my_attendance.php?course_id=' + this.value">
                <option value="1" <?php echo $course_id == 1 ? 'selected' : ''; ?>>PAW - Programmation Applications Web</option>
                <option value="2" <?php echo $course_id == 2 ? 'selected' : ''; ?>>GL - Génie Logiciel</option>
                <option value="3" <?php echo $course_id == 3 ? 'selected' : ''; ?>>IHM - Interactions Homme-Machine</option>
                <option value="4" <?php echo $course_id == 4 ? 'selected' : ''; ?>>SID - Systèmes d'Information Décisionnels</option>
                <option value="5" <?php echo $course_id == 5 ? 'selected' : ''; ?>>SAD - Systèmes d'Aide à la Décision</option>
                <option value="6" <?php echo $course_id == 6 ? 'selected' : ''; ?>>ASI - Architecture des Systèmes d'Information</option>
            </select>
        </div>

        <?php
        // Données des cours
        $courses = array(
            1 => array('code' => 'PAW', 'name' => 'Programmation Applications Web', 'prof' => 'Ahmed Benzema'),
            2 => array('code' => 'GL', 'name' => 'Génie Logiciel', 'prof' => 'Fatima Zohra'),
            3 => array('code' => 'IHM', 'name' => 'Interactions Homme-Machine', 'prof' => 'Ahmed Benzema'),
            4 => array('code' => 'SID', 'name' => 'Systèmes d\'Information Décisionnels', 'prof' => 'Fatima Zohra'),
            5 => array('code' => 'SAD', 'name' => 'Systèmes d\'Aide à la Décision', 'prof' => 'Ahmed Benzema'),
            6 => array('code' => 'ASI', 'name' => 'Architecture des Systèmes d\'Information', 'prof' => 'Fatima Zohra')
        );

        $course = $courses[$course_id] ?? $courses[1];
        ?>

        <div class="header">
            <h1>Mes Présences - <?php echo $course['name']; ?></h1>
            <div class="course-info">
                <strong><?php echo $course['code']; ?></strong>
            </div>
            <div class="course-info">
                Professeur: <?php echo $course['prof']; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number stat-total">5</div>
                <div class="stat-label">Sessions totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-present">3</div>
                <div class="stat-label">Sessions présentes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-rate">60%</div>
                <div class="stat-label">Taux de présence</div>
            </div>
        </div>

        <div class="attendance-history">
            <div class="history-header">
                <h3>Historique des Présences</h3>
            </div>
            
            <div class="session-item">
                <div class="session-info">
                    <h4>Introduction au cours</h4>
                    <div class="session-date">15/01/2024 08:00</div>
                </div>
                <div class="session-status status-present">Présent</div>
            </div>
            
            <div class="session-item">
                <div class="session-info">
                    <h4>Chapitre 1 - Les bases</h4>
                    <div class="session-date">22/01/2024 08:00</div>
                </div>
                <div class="session-status status-present">Présent</div>
            </div>
            
            <div class="session-item">
                <div class="session-info">
                    <h4>Chapitre 2 - Avancé</h4>
                    <div class="session-date">29/01/2024 08:00</div>
                </div>
                <div class="session-status status-late">En retard</div>
            </div>
            
            <div class="session-item">
                <div class="session-info">
                    <h4>TP Pratique</h4>
                    <div class="session-date">05/02/2024 08:00</div>
                </div>
                <div class="session-status status-absent">Absent</div>
            </div>
            
            <div class="session-item">
                <div class="session-info">
                    <h4>Révision</h4>
                    <div class="session-date">12/02/2024 08:00</div>
                </div>
                <div class="session-status status-present">Présent</div>
            </div>
        </div>
    </div>
</body>
</html>
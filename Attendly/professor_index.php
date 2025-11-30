<?php
// professor_index.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

// Récupérer les cours du professeur
$pdo = getDBConnection();
$courses = array();

// Cours disponibles pour le professeur
$available_courses = array(
    array('id' => 1, 'course_code' => 'PAW', 'course_name' => 'Programmation Applications Web', 'student_count' => 35),
    array('id' => 2, 'course_code' => 'GL', 'course_name' => 'Génie Logiciel', 'student_count' => 28),
    array('id' => 3, 'course_code' => 'IHM', 'course_name' => 'Interactions Homme-Machine', 'student_count' => 32),
    array('id' => 4, 'course_code' => 'SID', 'course_name' => 'Systèmes d\'Information Décisionnels', 'student_count' => 25),
    array('id' => 5, 'course_code' => 'SAD', 'course_name' => 'Systèmes d\'Aide à la Décision', 'student_count' => 22),
    array('id' => 6, 'course_code' => 'ASI', 'course_name' => 'Architecture des Systèmes d\'Information', 'student_count' => 30)
);

$courses = $available_courses;

// Récupérer les sessions récentes
$recent_sessions = array();
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.session_topic, s.session_date, s.status,
                   c.course_name, c.course_code
            FROM attendance_sessions s
            JOIN courses c ON s.course_id = c.id
            WHERE s.created_by = ?
            ORDER BY s.session_date DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur sessions: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Professeur - Attendly</title>
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
            background: #3498db;
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
            margin-bottom: 40px;
        }

        .header h1 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 50px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
            transition: transform 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-2px);
        }

        .feature-card h3 {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .course-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.2s ease;
        }

        .course-card:hover {
            transform: translateY(-2px);
        }

        .course-card h4 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .course-code {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .course-description {
            color: #888;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .course-stats {
            color: #888;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 4px;
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }

        .btn-sm:hover {
            background: #e9ecef;
        }

        .btn-primary-sm {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .btn-primary-sm:hover {
            background: #0056b3;
        }

        /* Recent Sessions */
        .recent-sessions {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .recent-sessions h3 {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
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

        .session-details {
            color: #666;
            font-size: 13px;
        }

        .session-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-open {
            background: #d4edda;
            color: #155724;
        }

        .status-closed {
            background: #f8d7da;
            color: #721c24;
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
            <a href="professor_index.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong>Professeur <?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">PROFESSEUR</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Tableau de Bord Professeur</h1>
            <p>Bienvenue, Professeur <?php echo $_SESSION['first_name']; ?>! Gérez vos cours et présences.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <h3>Créer une Session</h3>
                <p>Créez une nouvelle session de présence pour vos cours.</p>
                <a href="create_session.php" class="btn">Créer Session</a>
            </div>
            
            <div class="feature-card">
                <h3>Gérer les Présences</h3>
                <p>Prenez les présences et consultez les statistiques.</p>
                <a href="attendance.php" class="btn">Voir les Présences</a>
            </div>
            
            <div class="feature-card">
                <h3>Justifications</h3>
                <p>Gérez les demandes de justification d'absence.</p>
                <a href="justification_requests.php" class="btn">Voir les Justifications</a>
            </div>
        </div>

        <div style="margin-bottom: 50px;">
            <h2 style="color: #333; margin-bottom: 20px;">Mes Cours</h2>
            
            <?php if (count($courses) > 0): ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <div class="course-stats"><?php echo $course['student_count']; ?> étudiants inscrits</div>
                            <div class="course-actions">
                                <a href="take_attendance.php?course_id=<?php echo $course['id']; ?>" class="btn-sm btn-primary-sm">Prendre présence</a>
                                <a href="attendance_summary.php?course_id=<?php echo $course['id']; ?>" class="btn-sm">Résumé</a>
                                <a href="create_session.php?course_id=<?php echo $course['id']; ?>" class="btn-sm">Nouvelle session</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucun cours assigné pour le moment.</p>
                    <a href="create_session.php" class="btn" style="margin-top: 15px;">Créer votre premier cours</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sessions Récentes -->
        <div class="recent-sessions">
            <h3>Sessions Récentes</h3>
            
            <?php if (count($recent_sessions) > 0): ?>
                <?php foreach ($recent_sessions as $session): ?>
                    <div class="session-item">
                        <div class="session-info">
                            <h4><?php echo htmlspecialchars($session['session_topic']); ?></h4>
                            <div class="session-details">
                                <?php echo htmlspecialchars($session['course_code']); ?> • 
                                <?php echo date('d/m/Y H:i', strtotime($session['session_date'])); ?>
                            </div>
                        </div>
                        <div class="session-status status-<?php echo $session['status']; ?>">
                            <?php echo $session['status'] === 'open' ? 'Ouverte' : 'Fermée'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune session récente.</p>
                    <a href="create_session.php" class="btn-sm" style="margin-top: 10px;">Créer une session</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
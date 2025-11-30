<?php
// attendance.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$pdo = getDBConnection();
$sessions = [];

if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.session_date, s.session_topic, s.status, s.qr_code_token,
                   c.course_code, c.course_name,
                   COUNT(ar.id) as records_count
            FROM attendance_sessions s
            JOIN courses c ON s.course_id = c.id
            LEFT JOIN attendance_records ar ON s.id = ar.session_id
            WHERE s.created_by = ?
            GROUP BY s.id
            ORDER BY s.session_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Gestion des Présences - Attendly</title>
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

        /* Sessions Grid */
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .session-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .session-title h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .session-course {
            color: #666;
            font-size: 14px;
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

        .session-details {
            margin-bottom: 20px;
        }

        .session-date {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .session-topic {
            color: #333;
            font-size: 14px;
        }

        .session-stats {
            color: #888;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .session-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        .empty-state {
            background: white;
            padding: 60px 40px;
            border-radius: 12px;
            text-align: center;
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
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <span class="role-tag">PROFESSEUR</span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Gestion des Présences</h1>
            <p>Consultez et gérez vos sessions de présence.</p>
        </div>

        <?php if (count($sessions) > 0): ?>
            <div class="sessions-grid">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-card">
                        <div class="session-header">
                            <div class="session-title">
                                <h3><?php echo htmlspecialchars($session['session_topic']); ?></h3>
                                <div class="session-course"><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></div>
                            </div>
                            <div class="session-status status-<?php echo $session['status']; ?>">
                                <?php echo $session['status'] === 'open' ? 'Ouverte' : 'Fermée'; ?>
                            </div>
                        </div>

                        <div class="session-details">
                            <div class="session-date">
                                <?php echo date('d/m/Y H:i', strtotime($session['session_date'])); ?>
                            </div>
                            <div class="session-stats">
                                <?php echo $session['records_count']; ?> enregistrements de présence
                            </div>
                        </div>

                        <div class="session-actions">
                            <?php if ($session['status'] === 'open'): ?>
                                <a href="attendance_session.php?session_id=<?php echo $session['id']; ?>" class="btn btn-primary">
                                    Prendre les présences
                                </a>
                            <?php endif; ?>
                            <a href="attendance_summary.php?session_id=<?php echo $session['id']; ?>" class="btn btn-secondary">
                                Voir résumé
                            </a>
                            <?php if ($session['status'] === 'open'): ?>
                                <a href="close_session.php?session_id=<?php echo $session['id']; ?>" class="btn btn-success">
                                    Fermer session
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Aucune session de présence créée pour le moment.</p>
                <a href="create_session.php" class="btn btn-primary" style="margin-top: 15px;">Créer une session</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
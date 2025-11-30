<?php
// attendance_summary.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? null;
$pdo = getDBConnection();
$session = null;
$attendance_stats = array(
    'total_students' => 5,
    'present_count' => 0,
    'absent_count' => 0,
    'late_count' => 0,
    'excused_count' => 0
);

if ($pdo && $session_id) {
    try {
        // Récupérer les infos de la session
        $stmt = $pdo->prepare("
            SELECT s.*, c.course_name, c.course_code
            FROM attendance_sessions s
            JOIN courses c ON s.course_id = c.id
            WHERE s.id = ? AND s.created_by = ?
        ");
        $stmt->execute([$session_id, $_SESSION['user_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            // Récupérer les statistiques de présence
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_students,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
                    FROM attendance_records
                    WHERE session_id = ?
                ");
                $stmt->execute([$session_id]);
                $db_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($db_stats) {
                    $attendance_stats = $db_stats;
                }
            } catch (PDOException $e) {
                // Utiliser les stats par défaut si la table n'existe pas
                error_log("Erreur stats: " . $e->getMessage());
            }

            // Calculer le taux de présence
            if ($attendance_stats['total_students'] > 0) {
                $attendance_rate = round(($attendance_stats['present_count'] / $attendance_stats['total_students']) * 100, 1);
            } else {
                $attendance_rate = 0;
            }
        }
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
    <title>Résumé des Présences - Attendly</title>
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
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .session-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        /* Stats Grid */
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
        .stat-absent { color: #dc3545; }
        .stat-late { color: #ffc107; }
        .stat-excused { color: #17a2b8; }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .attendance-rate {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }

        .rate-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(#28a745 <?php echo isset($attendance_rate) ? $attendance_rate * 3.6 : 0; ?>deg, #e9ecef 0deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .rate-circle::before {
            content: '';
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }

        .rate-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            position: relative;
            z-index: 1;
        }

        .rate-label {
            color: #666;
            font-size: 16px;
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
        <a href="attendance.php" class="back-btn">← Retour aux sessions</a>

        <?php if ($session): ?>
            <div class="header">
                <h1>Résumé des Présences</h1>
                <div class="session-info">
                    <strong><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></strong>
                </div>
                <div class="session-info">
                    Sujet: <?php echo htmlspecialchars($session['session_topic']); ?>
                </div>
                <div class="session-info">
                    Date: <?php echo date('d/m/Y H:i', strtotime($session['session_date'])); ?>
                </div>
            </div>

            <div class="attendance-rate">
                <div class="rate-circle">
                    <div class="rate-value"><?php echo isset($attendance_rate) ? $attendance_rate : 0; ?>%</div>
                </div>
                <div class="rate-label">Taux de présence</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number stat-total"><?php echo $attendance_stats['total_students']; ?></div>
                    <div class="stat-label">Total étudiants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-present"><?php echo $attendance_stats['present_count']; ?></div>
                    <div class="stat-label">Présents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-absent"><?php echo $attendance_stats['absent_count']; ?></div>
                    <div class="stat-label">Absents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-late"><?php echo $attendance_stats['late_count']; ?></div>
                    <div class="stat-label">En retard</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-excused"><?php echo $attendance_stats['excused_count']; ?></div>
                    <div class="stat-label">Excusés</div>
                </div>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; color: #666;">
                Session non trouvée ou vous n'avez pas accès à cette session.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
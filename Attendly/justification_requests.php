<?php
// justification_requests.php
require_once 'config.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'professor')) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Justifications - Attendly</title>
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

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            background: white;
            padding: 80px 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .empty-state h2 {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .empty-state p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .divider {
            height: 1px;
            background: #dee2e6;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_statistics.php' : 'professor_index.php'; ?>" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong><?php echo $_SESSION['first_name']; ?></strong></span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Gestion des Justifications</h1>
        </div>

        <div class="empty-state">
            <h2>Aucune justification</h2>
            <div class="divider"></div>
            <p>Les justifications d'absence apparaîtront ici lorsqu'elles seront soumises par les étudiants.</p>
        </div>
    </div>
</body>
</html>
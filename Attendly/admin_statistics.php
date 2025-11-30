<?php
// admin_statistics.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - Attendly</title>
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

        /* Stats */
        .stats-section {
            margin-bottom: 40px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 16px;
            text-transform: lowercase;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
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

        /* Simple Navigation */
        .nav-header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #e1e5e9;
            margin-bottom: 40px;
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
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <div class="nav-header">
        <div class="nav-content">
            <a href="admin_statistics.php" class="nav-brand">ATTENDLY</a>
            <div class="user-info">
                <span>Connecté en tant que <strong>Admin</strong></span>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Tableau de Bord Administrateur</h1>
            <p>Bienvenue, Admin! Vous êtes connecté en tant qu'administrateur.</p>
        </div>

        <div class="stats-section">
            <div class="stat-number">150</div>
            <div class="stat-label">étudiants</div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <h3>Gérer les étudiants</h3>
                <p>Gérez les comptes étudiants</p>
                <a href="admin_students.php" class="btn">Accéder</a>
            </div>
            
            <div class="feature-card">
                <h3>Statistiques</h3>
                <p>Voir les analyses détaillées</p>
                <a href="admin_reports.php" class="btn">Accéder</a>
            </div>
            
            <div class="feature-card">
                <h3>Rapports</h3>
                <p>Générez des rapports</p>
                <a href="reports.php" class="btn">Accéder</a>
            </div>
        </div>
    </div>
</body>
</html>
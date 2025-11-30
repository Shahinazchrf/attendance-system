<?php
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
    <title>Statistiques - Attendly</title>
    <style>
        /* Même style que admin_statistics.php */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f5f6fa; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .navbar { background: white; padding: 0 20px; height: 60px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .navbar-brand { font-size: 20px; font-weight: 700; color: #2c3e50; text-decoration: none; }
        .nav-menu { display: flex; list-style: none; gap: 2px; }
        .nav-link { color: #5a6778; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .nav-link.active { color: #3498db; background: #f8f9fa; }
        .header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .header h1 { color: #2d3436; font-size: 28px; font-weight: 600; margin-bottom: 8px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin_statistics.php" class="navbar-brand">ATTENDLY</a>
        <ul class="nav-menu">
            <li><a href="admin_statistics.php" class="nav-link">Tableau de Bord</a></li>
            <li><a href="admin_students.php" class="nav-link">Étudiants</a></li>
            <li><a href="admin_reports.php" class="nav-link active">Statistiques</a></li>
            <li><a href="reports.php" class="nav-link">Rapports</a></li>
        </ul>
        <div class="user-menu">
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Statistiques Détaillées</h1>
            <p>Analyses et rapports avancés sur les présences.</p>
        </div>
        <p>Page des statistiques en cours de développement...</p>
    </div>
</body>
</html>
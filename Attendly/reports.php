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
    <title>Rapports - Attendly</title>
    <style>
        /* Même style que admin_statistics.php */
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin_statistics.php" class="navbar-brand">ATTENDLY</a>
        <ul class="nav-menu">
            <li><a href="admin_statistics.php" class="nav-link">Tableau de Bord</a></li>
            <li><a href="admin_students.php" class="nav-link">Étudiants</a></li>
            <li><a href="admin_reports.php" class="nav-link">Statistiques</a></li>
            <li><a href="reports.php" class="nav-link active">Rapports</a></li>
        </ul>
        <div class="user-menu">
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Génération de Rapports</h1>
            <p>Générez des rapports détaillés sur les présences.</p>
        </div>
        <p>Page des rapports en cours de développement...</p>
    </div>
</body>
</html>
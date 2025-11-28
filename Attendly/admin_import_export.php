<?php
require_once 'auth.php';
$auth->requireRole('admin');
require_once 'db_connect.php';

// Traitement de l'import
if (isset($_POST['import_students'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Ignorer l'en-tête
        fgetcsv($handle, 1000, ",");
        
        $imported = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Vérifier que nous avons au moins 4 colonnes
            if (count($data) < 4) {
                $errors[] = "Ligne invalide: " . implode(",", $data);
                continue;
            }
            
            $first_name = trim($data[0]);
            $last_name = trim($data[1]);
            $email = trim($data[2]);
            $password = trim($data[3]);
            
            if (empty($email) || empty($password)) {
                $errors[] = "Ligne invalide: email ou mot de passe vide";
                continue;
            }
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "L'étudiant avec l'email $email existe déjà.";
                continue;
            }
            
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer l'étudiant
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'student')");
            if ($stmt->execute([$first_name, $last_name, $email, $hashed_password])) {
                $imported++;
            } else {
                $errors[] = "Erreur lors de l'importation de $email";
            }
        }
        fclose($handle);
        
        if ($imported > 0) {
            $success_message = "$imported étudiants importés avec succès.";
        }
        if (!empty($errors)) {
            $error_message = implode("<br>", $errors);
        }
    } else {
        $error_message = "Veuillez sélectionner un fichier CSV valide.";
    }
}

// Traitement de l'export
if (isset($_POST['export_students'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    // En-tête
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Password']);
    
    $stmt = $pdo->query("SELECT first_name, last_name, email FROM users WHERE role = 'student'");
    while ($row = $stmt->fetch()) {
        // Pour l'export, nous ne pouvons pas exporter les mots de passe, donc on laisse vide
        fputcsv($output, [$row['first_name'], $row['last_name'], $row['email'], '']);
    }
    fclose($output);
    exit;
}

// Récupérer la liste des étudiants
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export Students - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles similaires aux autres pages admin */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 0 15px;
            margin: 0 auto;
        }

        .section {
            padding: 80px 0 60px;
        }

        h1, h2, h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Navigation */
        .navbar {
            background-color: #2c3e50;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
        }

        .nav-logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .nav-logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
        }

        .nav-item {
            margin-left: 25px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            border-radius: 5px;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .nav-link:hover, .nav-link.active {
            color: #3498db;
            background-color: rgba(255,255,255,0.1);
        }

        /* Form Styles */
        .report-controls {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219653;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                position: absolute;
                top: 70px;
                left: 0;
                width: 100%;
                background-color: #2c3e50;
                display: none;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .nav-item {
                margin: 0;
                width: 100%;
            }
            
            .nav-link {
                padding: 15px;
                border-radius: 0;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="admin_index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="admin_statistics.php" class="nav-link"><i class="fas fa-chart-bar"></i> Statistics</a>
                </li>
                <li class="nav-item">
                    <a href="admin_students.php" class="nav-link"><i class="fas fa-users"></i> Students</a>
                </li>
                <li class="nav-item">
                    <a href="admin_justifications.php" class="nav-link"><i class="fas fa-file-alt"></i> Justifications</a>
                </li>
                <li class="nav-item">
                    <a href="admin_reports.php" class="nav-link"><i class="fas fa-file-export"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a href="admin_import_export.php" class="nav-link active"><i class="fas fa-file-csv"></i> Import/Export</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-file-csv"></i> Import/Export Students</h2>
            <p>Import and export student lists in CSV format (compatible with Progres Excel).</p>

            <!-- Import/Export Section -->
            <div class="report-controls">
                <h3>Import / Export Students</h3>
                <div class="controls-grid">
                    <!-- Import Form -->
                    <form method="post" enctype="multipart/form-data" class="form-group">
                        <label for="csv_file"><i class="fas fa-file-import"></i> Import Students (CSV)</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                            CSV format: First Name, Last Name, Email, Password
                        </p>
                        <button type="submit" name="import_students" class="btn btn-primary" style="margin-top: 10px;">
                            <i class="fas fa-upload"></i> Import CSV
                        </button>
                    </form>

                    <!-- Export Form -->
                    <form method="post" class="form-group">
                        <label for="export"><i class="fas fa-file-export"></i> Export Students (CSV)</label>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                            Export all students to a CSV file compatible with Progres Excel.
                        </p>
                        <button type="submit" name="export_students" class="btn btn-success" style="margin-top: 10px;">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </form>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Students List -->
            <div class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Current Students</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Created At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo $student['created_at']; ?></td>
                                <td>
                                    <span style="color: <?php echo $student['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
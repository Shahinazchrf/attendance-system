<?php
// admin_import.php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Importer des étudiants depuis Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_students'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === 0) {
        // Simuler l'importation (dans un vrai projet, utiliser PHPExcel ou PhpSpreadsheet)
        $success = "Import simulation: File received. In a real implementation, this would process the Excel file.";
    } else {
        $error = "Please select a valid Excel file.";
    }
}

// Exporter les données
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    $exportType = $_POST['export_type'];
    
    // Simuler l'exportation
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.xls"');
    
    // Générer un fichier Excel simple
    echo "Attendance System Export\n";
    echo "Date: " . date('Y-m-d') . "\n\n";
    
    if ($exportType === 'students') {
        $stmt = $pdo->query("SELECT * FROM users WHERE role = 'student'");
        $students = $stmt->fetchAll();
        
        echo "Student List\n";
        echo "ID\tFirst Name\tLast Name\tEmail\tUsername\n";
        foreach ($students as $student) {
            echo $student['id'] . "\t" . $student['first_name'] . "\t" . $student['last_name'] . "\t" . $student['email'] . "\t" . $student['username'] . "\n";
        }
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprendre les styles existants */
    </style>
</head>
<body>
    <!-- Navigation Bar (identique à admin_dashboard.php) -->
    <nav class="navbar">...</nav>

    <!-- Import/Export Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-file-import"></i> Data Import & Export</h2>
            
            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Import Section -->
            <div class="report-info">
                <h3><i class="fas fa-file-import"></i> Import Students from Excel</h3>
                <p>Upload an Excel file (.xlsx) with student data. The file should have columns: First Name, Last Name, Email, Username.</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">Select Excel File:</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                        <small>Accepted formats: .xlsx, .xls (Excel files)</small>
                    </div>
                    
                    <button type="submit" name="import_students" class="btn-effect btn-submit">
                        <i class="fas fa-upload"></i>
                        Import Students
                    </button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background-color: #e8f4fd; border-radius: 5px;">
                    <h4><i class="fas fa-info-circle"></i> File Format Example:</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #3498db; color: white;">
                                <th style="padding: 8px; border: 1px solid #ddd;">First Name</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Last Name</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Email</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Username</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">John</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">Doe</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">john@univ-alger.dz</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">john.doe</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Section -->
            <div class="report-info">
                <h3><i class="fas fa-file-export"></i> Export Data</h3>
                <p>Export system data to Excel format for reporting and analysis.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="export_type">Select Data to Export:</label>
                        <select id="export_type" name="export_type" required>
                            <option value="students">Student List</option>
                            <option value="attendance">Attendance Records</option>
                            <option value="courses">Course Information</option>
                            <option value="sessions">Session Data</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="export_data" class="btn-effect" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                        <i class="fas fa-download"></i>
                        Export to Excel
                    </button>
                </form>
            </div>

            <!-- Data Management Tools -->
            <div class="report-info">
                <h3><i class="fas fa-tools"></i> Data Management Tools</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-sync"></i> Sync Data
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-archive"></i> Backup Database
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-trash"></i> Clean Old Data
                    </button>
                    <button class="btn-effect" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-chart-bar"></i> Generate Reports
                    </button>
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
                        <?php if ($_SESSION['role'] === 'professor' || $_SESSION['role'] === 'admin'): ?>
                        <li><a href="attendance.php"><i class="fas fa-list"></i> Attendance</a></li>
                        <li><a href="students.php"><i class="fas fa-user-plus"></i> Students</a></li>
                        <?php endif; ?>
                        <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
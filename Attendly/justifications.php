<?php
// justifications.php
require_once 'auth.php';
$auth->requireRole('student');

require_once 'db_connect.php';

// Soumettre une justification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_justification'])) {
    $sessionId = $_POST['session_id'];
    $reason = $_POST['reason'];
    
    // Gestion du fichier uploadé
    $filePath = null;
    if (isset($_FILES['justification_file']) && $_FILES['justification_file']['error'] === 0) {
        $uploadDir = 'uploads/justifications/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['justification_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['justification_file']['tmp_name'], $filePath)) {
            // File uploaded successfully
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO justification_requests (student_id, session_id, reason, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $sessionId, $reason, $filePath]);
        $success = "Justification submitted successfully!";
    } catch (PDOException $e) {
        $error = "Error submitting justification: " . $e->getMessage();
    }
}

// Récupérer les absences de l'étudiant
$stmt = $pdo->prepare("
    SELECT ar.*, s.session_date, c.name as course_name, g.name as group_name,
           jr.status as justification_status
    FROM attendance_records ar
    JOIN attendance_sessions s ON ar.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN groups g ON s.group_id = g.id
    LEFT JOIN justification_requests jr ON ar.session_id = jr.session_id AND jr.student_id = ar.student_id
    WHERE ar.student_id = ? AND ar.status = 'absent'
    ORDER BY s.session_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$absences = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justify Absence - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprendre les styles existants */
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-chart-line"></i> Attendly</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a href="my_attendance.php" class="nav-link"><i class="fas fa-list"></i> My Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="justifications.php" class="nav-link active"><i class="fas fa-file-upload"></i> Justifications</a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Justifications Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-file-upload"></i> Justify Absence</h2>
            
            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Submit Justification Form -->
            <div class="report-info">
                <h3><i class="fas fa-plus-circle"></i> Submit New Justification</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="session_id">Select Absence:</label>
                        <select id="session_id" name="session_id" required>
                            <option value="">Select an absence to justify</option>
                            <?php foreach ($absences as $absence): ?>
                                <?php if (empty($absence['justification_status']) || $absence['justification_status'] === 'rejected'): ?>
                                <option value="<?php echo $absence['session_id']; ?>">
                                    <?php echo date('M j, Y', strtotime($absence['session_date'])); ?> - 
                                    <?php echo htmlspecialchars($absence['course_name']); ?> -
                                    <?php echo htmlspecialchars($absence['group_name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Absence:</label>
                        <textarea id="reason" name="reason" rows="4" required placeholder="Please explain why you were absent..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="justification_file">Supporting Document (Optional):</label>
                        <input type="file" id="justification_file" name="justification_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small>Accepted formats: PDF, JPG, PNG, DOC (Max: 5MB)</small>
                    </div>
                    
                    <button type="submit" name="submit_justification" class="btn-effect btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Submit Justification
                    </button>
                </form>
            </div>

            <!-- My Justifications History -->
            <div class="report-info">
                <h3><i class="fas fa-history"></i> My Justification History</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Reason</th>
                                <th>Document</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("
                                SELECT jr.*, s.session_date, c.name as course_name 
                                FROM justification_requests jr
                                JOIN attendance_sessions s ON jr.session_id = s.id
                                JOIN courses c ON s.course_id = c.id
                                WHERE jr.student_id = ?
                                ORDER BY jr.submitted_at DESC
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            $justifications = $stmt->fetchAll();
                            
                            foreach ($justifications as $justification): 
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($justification['session_date'])); ?></td>
                                <td><?php echo htmlspecialchars($justification['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($justification['reason']); ?></td>
                                <td>
                                    <?php if ($justification['file_path']): ?>
                                        <a href="<?php echo $justification['file_path']; ?>" target="_blank" class="btn-effect" style="text-decoration: none; padding: 5px 10px;">
                                            <i class="fas fa-download"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #95a5a6;">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: 
                                        <?php echo $justification['status'] === 'approved' ? '#27ae60' : 
                                              ($justification['status'] === 'rejected' ? '#e74c3c' : '#f39c12'); ?>; 
                                        font-weight: bold;">
                                        <?php echo ucfirst($justification['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($justification['submitted_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($justifications)): ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-file-upload" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h4>No Justifications Submitted</h4>
                    <p>You haven't submitted any justification requests yet.</p>
                </div>
                <?php endif; ?>
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
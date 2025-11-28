<?php
require_once 'auth.php';
$auth->requireRole(['student', 'professor', 'admin']);
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Traitement de la soumission d'une justification (étudiant)
if (isset($_POST['submit_justification'])) {
    $session_id = $_POST['session_id'];
    $reason = $_POST['reason'];
    $supporting_file = null;
    
    // Vérifier que l'étudiant est autorisé à soumettre pour cette session
    $check_stmt = $pdo->prepare("
        SELECT 1 FROM attendance_records ar
        JOIN attendance_sessions s ON ar.session_id = s.id
        WHERE ar.session_id = ? AND ar.student_id = ?
    ");
    $check_stmt->execute([$session_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        $error_message = "You are not authorized to submit a justification for this session.";
    } else {
        // Gérer l'upload du fichier
        if (isset($_FILES['supporting_file']) && $_FILES['supporting_file']['error'] == 0) {
            $upload_dir = 'uploads/justifications/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Générer un nom de fichier unique
            $file_extension = pathinfo($_FILES['supporting_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'justification_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Vérifier le type de fichier
            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['supporting_file']['tmp_name'], $file_path)) {
                    $supporting_file = $file_path;
                } else {
                    $error_message = "Error uploading file. Please try again.";
                }
            } else {
                $error_message = "File type not allowed. Please upload PDF, JPG, PNG, or DOC files.";
            }
        }
        
        if (!isset($error_message)) {
            // Vérifier si une justification existe déjà
            $existing_stmt = $pdo->prepare("
                SELECT id FROM justification_requests 
                WHERE student_id = ? AND session_id = ?
            ");
            $existing_stmt->execute([$user_id, $session_id]);
            
            if ($existing_stmt->fetch()) {
                $error_message = "You have already submitted a justification for this session.";
            } else {
                // Insérer la demande de justification
                $insert_stmt = $pdo->prepare("
                    INSERT INTO justification_requests (student_id, session_id, reason, supporting_file, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                
                if ($insert_stmt->execute([$user_id, $session_id, $reason, $supporting_file])) {
                    $success_message = "Justification submitted successfully! It will be reviewed by your professor.";
                } else {
                    $error_message = "Error submitting justification. Please try again.";
                }
            }
        }
    }
}

// Traitement de l'approbation/rejet (professeur/admin)
if (isset($_POST['process_justification'])) {
    $justification_id = $_POST['justification_id'];
    $status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    // Vérifier les permissions
    if ($user_role === 'professor') {
        $check_stmt = $pdo->prepare("
            SELECT j.id FROM justification_requests j
            JOIN attendance_sessions s ON j.session_id = s.id
            JOIN courses c ON s.course_id = c.id
            WHERE j.id = ? AND c.professor_id = ?
        ");
        $check_stmt->execute([$justification_id, $user_id]);
    } else {
        // Admin peut tout traiter
        $check_stmt = $pdo->prepare("SELECT id FROM justification_requests WHERE id = ?");
        $check_stmt->execute([$justification_id]);
    }
    
    if ($check_stmt->fetch()) {
        $update_stmt = $pdo->prepare("
            UPDATE justification_requests 
            SET status = ?, admin_notes = ?, processed_at = NOW(), processed_by = ?
            WHERE id = ?
        ");
        
        if ($update_stmt->execute([$status, $admin_notes, $user_id, $justification_id])) {
            $success_message = "Justification " . $status . " successfully!";
        } else {
            $error_message = "Error processing justification.";
        }
    } else {
        $error_message = "You are not authorized to process this justification.";
    }
}

// Récupérer les justifications selon le rôle
if ($user_role === 'student') {
    // Justifications de l'étudiant
    $justifications_stmt = $pdo->prepare("
        SELECT j.*, 
               s.session_date, s.start_time, s.end_time,
               c.name as course_name, c.code as course_code,
               u.first_name as prof_first_name, u.last_name as prof_last_name
        FROM justification_requests j
        JOIN attendance_sessions s ON j.session_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN users u ON c.professor_id = u.id
        WHERE j.student_id = ?
        ORDER BY j.created_at DESC
    ");
    $justifications_stmt->execute([$user_id]);
    $justifications = $justifications_stmt->fetchAll();
    
    // Sessions où l'étudiant était absent
    $absent_sessions_stmt = $pdo->prepare("
        SELECT s.id, s.session_date, s.start_time, s.end_time,
               c.name as course_name, c.code as course_code
        FROM attendance_sessions s
        JOIN courses c ON s.course_id = c.id
        JOIN attendance_records ar ON s.id = ar.session_id
        LEFT JOIN justification_requests jr ON s.id = jr.session_id AND jr.student_id = ?
        WHERE ar.student_id = ? 
          AND ar.status = 'absent'
          AND jr.id IS NULL
          AND s.session_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY s.session_date DESC
    ");
    $absent_sessions_stmt->execute([$user_id, $user_id]);
    $absent_sessions = $absent_sessions_stmt->fetchAll();
    
} else {
    // Justifications à traiter (professeur/admin)
    $query = "
        SELECT j.*, 
               s.session_date, s.start_time, s.end_time,
               c.name as course_name, c.code as course_code,
               u.first_name as student_first_name, u.last_name as student_last_name,
               u.email as student_email,
               p.first_name as prof_first_name, p.last_name as prof_last_name
        FROM justification_requests j
        JOIN attendance_sessions s ON j.session_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN users u ON j.student_id = u.id
        JOIN users p ON c.professor_id = p.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($user_role === 'professor') {
        $query .= " AND c.professor_id = ?";
        $params[] = $user_id;
    }
    
    if (isset($_GET['status']) && $_GET['status'] !== 'all') {
        $query .= " AND j.status = ?";
        $params[] = $_GET['status'];
    }
    
    $query .= " ORDER BY j.created_at DESC";
    
    $justifications_stmt = $pdo->prepare($query);
    $justifications_stmt->execute($params);
    $justifications = $justifications_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justification Requests - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .justification-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #f39c12; color: white; }
        .status-approved { background: #27ae60; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo $user_role === 'student' ? 'student_index.php' : ($user_role === 'professor' ? 'professor_index.php' : 'admin_index.php'); ?>">
                    <i class="fas fa-chart-line"></i> Attendly
                </a>
            </div>
            <ul class="nav-menu">
                <?php if ($user_role === 'student'): ?>
                    <li class="nav-item">
                        <a href="student_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="justification_requests.php" class="nav-link active"><i class="fas fa-file-alt"></i> Justifications</a>
                    </li>
                <?php elseif ($user_role === 'professor'): ?>
                    <li class="nav-item">
                        <a href="professor_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="attendance_session.php" class="nav-link"><i class="fas fa-calendar-check"></i> Sessions</a>
                    </li>
                    <li class="nav-item">
                        <a href="justification_requests.php" class="nav-link active"><i class="fas fa-file-alt"></i> Justifications</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="admin_index.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_justifications.php" class="nav-link active"><i class="fas fa-file-alt"></i> Justifications</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <h2><i class="fas fa-file-alt"></i> Justification Requests</h2>
            
            <?php if ($user_role === 'student'): ?>
                <p>Submit and track your absence justifications.</p>
                
                <!-- Submit New Justification -->
                <div class="report-controls">
                    <h3>Submit New Justification</h3>
                    <?php if (!empty($absent_sessions)): ?>
                        <form method="post" enctype="multipart/form-data">
                            <div class="controls-grid">
                                <div class="form-group">
                                    <label for="session_id">Select Session *</label>
                                    <select id="session_id" name="session_id" required>
                                        <option value="">Choose a session</option>
                                        <?php foreach ($absent_sessions as $session): ?>
                                        <option value="<?php echo $session['id']; ?>">
                                            <?php echo htmlspecialchars($session['course_name'] . ' - ' . date('M j, Y', strtotime($session['session_date'])) . ' (' . $session['start_time'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Absence *</label>
                                    <textarea id="reason" name="reason" required rows="4" 
                                              placeholder="Please explain why you were absent..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="supporting_file">Supporting Document (Optional)</label>
                                    <input type="file" id="supporting_file" name="supporting_file" 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <small>Max file size: 5MB. Allowed types: PDF, JPG, PNG, DOC</small>
                                </div>
                            </div>
                            <button type="submit" name="submit_justification" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Justification
                            </button>
                        </form>
                    <?php else: ?>
                        <p>No absent sessions found in the last 30 days that require justification.</p>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p>Review and process student justification requests.</p>
                
                <!-- Filtres pour professeur/admin -->
                <div class="report-controls">
                    <h3>Filter Justifications</h3>
                    <form method="get">
                        <div class="controls-grid">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($_GET['status'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Justifications List -->
            <div class="report-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-list"></i> 
                        <?php echo $user_role === 'student' ? 'Your Justifications' : 'Justification Requests'; ?>
                    </h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php if ($user_role !== 'student'): ?>
                                    <th>Student</th>
                                <?php endif; ?>
                                <th>Course</th>
                                <th>Session</th>
                                <th>Reason</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <?php if ($user_role !== 'student'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($justifications as $justification): ?>
                            <tr>
                                <?php if ($user_role !== 'student'): ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($justification['student_first_name'] . ' ' . $justification['student_last_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($justification['student_email']); ?></small>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($justification['course_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($justification['course_code']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($justification['session_date'])); ?>
                                    <br><?php echo date('H:i', strtotime($justification['start_time'])); ?>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($justification['reason'])); ?></td>
                                <td>
                                    <?php if ($justification['supporting_file']): ?>
                                        <a href="<?php echo $justification['supporting_file']; ?>" target="_blank" class="btn btn-secondary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span>No file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="justification-status status-<?php echo $justification['status']; ?>">
                                        <?php echo ucfirst($justification['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($justification['created_at'])); ?></td>
                                <?php if ($user_role !== 'student'): ?>
                                    <td>
                                        <?php if ($justification['status'] == 'pending'): ?>
                                            <button class="btn btn-success" onclick="openProcessModal(<?php echo $justification['id']; ?>, 'approved')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-danger" onclick="openProcessModal(<?php echo $justification['id']; ?>, 'rejected')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <span>Processed</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($justifications)): ?>
                        <div style="text-align: center; padding: 20px;">
                            <p>No justification requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal for Processing Justification (professor/admin) -->
    <?php if ($user_role !== 'student'): ?>
    <div id="processModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Process Justification</h3>
            <form method="post">
                <input type="hidden" name="justification_id" id="process_justification_id">
                <input type="hidden" name="status" id="process_status">
                <div class="form-group">
                    <label for="admin_notes">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" 
                              placeholder="Add any notes for the student..."></textarea>
                </div>
                <button type="submit" name="process_justification" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Decision
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        <?php if ($user_role !== 'student'): ?>
        function openProcessModal(justificationId, status) {
            document.getElementById('process_justification_id').value = justificationId;
            document.getElementById('process_status').value = status;
            document.getElementById('processModal').style.display = 'block';
        }
        
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('processModal').style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            var modal = document.getElementById('processModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
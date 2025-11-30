<?php
// create_session.php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit();
}

// Liste des cours disponibles pour le professeur
$available_courses = array(
    array('id' => 1, 'code' => 'PAW', 'name' => 'Programmation Applications Web'),
    array('id' => 2, 'code' => 'GL', 'name' => 'Génie Logiciel'),
    array('id' => 3, 'code' => 'IHM', 'name' => 'Interactions Homme-Machine'),
    array('id' => 4, 'code' => 'SID', 'name' => 'Systèmes d\'Information Décisionnels'),
    array('id' => 5, 'code' => 'SAD', 'name' => 'Systèmes d\'Aide à la Décision'),
    array('id' => 6, 'code' => 'ASI', 'name' => 'Architecture des Systèmes d\'Information')
);

$pdo = getDBConnection();

// Créer une session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $course_code = $_POST['course_code'];
    $session_topic = $_POST['session_topic'];
    $session_date = $_POST['session_date'];
    
    // Trouver le cours sélectionné
    $selected_course = null;
    foreach ($available_courses as $course) {
        if ($course['code'] === $course_code) {
            $selected_course = $course;
            break;
        }
    }
    
    if ($selected_course && $pdo) {
        try {
            // Générer un token QR code unique
            $qr_token = bin2hex(random_bytes(16));
            
            $stmt = $pdo->prepare("
                INSERT INTO attendance_sessions (course_id, session_date, session_topic, qr_code_token, status, created_by)
                VALUES (?, ?, ?, ?, 'open', ?)
            ");
            $stmt->execute([$selected_course['id'], $session_date, $session_topic, $qr_token, $_SESSION['user_id']]);
            
            $success_message = "Session créée avec succès pour le cours " . $selected_course['code'] . "!";
            
        } catch (PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    } else {
        $error_message = "Cours non valide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Session - Attendly</title>
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
            max-width: 800px;
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

        /* Form */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
        }

        .form-card h2 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }

        .btn {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .course-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .course-code {
            font-weight: 600;
            color: #007bff;
            min-width: 60px;
        }

        .course-name {
            color: #333;
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
        <div class="form-card">
            <h2>Créer une Nouvelle Session</h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="course_code">Sélectionnez un Cours</label>
                    <select id="course_code" name="course_code" required>
                        <option value="">Choisissez un cours...</option>
                        <?php foreach ($available_courses as $course): ?>
                            <option value="<?php echo $course['code']; ?>">
                                <?php echo $course['code'] . ' - ' . $course['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div style="margin-top: 10px; font-size: 12px; color: #666;">
                        <strong>Cours disponibles :</strong>
                        <?php foreach ($available_courses as $course): ?>
                            <div class="course-option">
                                <span class="course-code"><?php echo $course['code']; ?></span>
                                <span class="course-name"><?php echo $course['name']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="session_topic">Sujet de la Session</label>
                    <input type="text" id="session_topic" name="session_topic" required 
                           placeholder="Ex: Chapitre 3 - Les bases de données relationnelles">
                </div>

                <div class="form-group">
                    <label for="session_date">Date et Heure de la Session</label>
                    <input type="datetime-local" id="session_date" name="session_date" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="create_session" class="btn">Créer la Session</button>
                    <a href="professor_index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Définir la date et heure actuelles par défaut
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            document.getElementById('session_date').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        });
    </script>
</body>
</html>
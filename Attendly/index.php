<?php
require_once 'auth.php';
$auth->requireAuth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .bar {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 3px 0;
            transition: 0.3s;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 120px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        header p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .btn-primary {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.4s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-primary:hover {
            background-color: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .feature-card i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 20px;
        }

        /* Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-good { color: #27ae60; }
        .stat-warning { color: #f39c12; }
        .stat-danger { color: #e74c3c; }

        /* Footer */
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 50px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 10px;
        }

        .footer-column a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-column a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #34495e;
            color: #bdc3c7;
        }

        /* Mobile Responsive */
        @media screen and (max-width: 768px) {
            .hamburger {
                display: flex;
            }
            
            .nav-menu {
                position: fixed;
                left: -100%;
                top: 70px;
                flex-direction: column;
                background-color: #2c3e50;
                width: 100%;
                text-align: center;
                transition: 0.3s;
                padding: 20px 0;
            }
            
            .nav-menu.active {
                left: 0;
            }
            
            .nav-item {
                margin: 15px 0;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
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
                    <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                </li>
                <?php if ($_SESSION['role'] === 'professor' || $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link"><i class="fas fa-list"></i> Attendance</a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link"><i class="fas fa-user-plus"></i> Students</a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'student'): ?>
                <li class="nav-item">
                    <a href="my_attendance.php" class="nav-link"><i class="fas fa-list"></i> My Attendance</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Home Section -->
    <header id="home">
        <div class="container">
            <h1>Welcome, <?php echo $_SESSION['first_name']; ?>!</h1>
            <p>Role: <?php echo ucfirst($_SESSION['role']); ?></p>
            <p>Attendance Management System - Algiers University</p>
            
            <?php if ($_SESSION['role'] === 'professor'): ?>
            <a href="attendance.php" class="btn-primary">
                <span class="btn-text">Manage Attendance</span>
                <i class="fas fa-arrow-right btn-icon"></i>
            </a>
            <?php elseif ($_SESSION['role'] === 'student'): ?>
            <a href="my_attendance.php" class="btn-primary">
                <span class="btn-text">View My Attendance</span>
                <i class="fas fa-arrow-right btn-icon"></i>
            </a>
            <?php else: ?>
            <a href="reports.php" class="btn-primary">
                <span class="btn-text">View Reports</span>
                <i class="fas fa-arrow-right btn-icon"></i>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Features Section -->
    <section class="features section">
        <div class="container">
            <h2>Why Choose Attendly?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-clock"></i>
                    <h3>Save Time</h3>
                    <p>Automate your attendance tracking and focus on teaching.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Smart Reports</h3>
                    <p>Get detailed analytics about student attendance.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Friendly</h3>
                    <p>Access your data from any device, anywhere.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-database"></i>
                    <h3>Data Security</h3>
                    <p>Your data is safe with secure storage.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats Section -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <h2>Quick Overview</h2>
            <div class="stats-container">
                <?php
                require_once 'db_connect.php';
                
                if ($_SESSION['role'] === 'professor') {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT e.student_id) as total_students 
                        FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE c.professor_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $stats = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total_courses 
                        FROM courses 
                        WHERE professor_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $courses = $stmt->fetch();
                ?>
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-warning"><?php echo $courses['total_courses']; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <?php } elseif ($_SESSION['role'] === 'student') {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total_sessions 
                        FROM attendance_sessions s 
                        JOIN enrollments e ON s.course_id = e.course_id 
                        WHERE e.student_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $sessions = $stmt->fetch();
                ?>
                <div class="stat-card">
                    <div class="stat-value stat-good"><?php echo $sessions['total_sessions']; ?></div>
                    <div class="stat-label">Sessions</div>
                </div>
                <?php } ?>
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

    <script>
        // Mobile navigation
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        
        if (hamburger && navMenu) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
            
            // Close mobile menu when clicking on a link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                });
            });
        }
    </script>
</body>
</html>
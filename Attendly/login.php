<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendly - Connexion</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
            font-size: 0.9rem;
        }

        .demo-accounts {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .demo-accounts h3 {
            margin-bottom: 0.8rem;
            color: #333;
            font-size: 1rem;
        }

        .demo-account {
            margin: 0.5rem 0;
            padding: 0.8rem;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>ATTENDLY</h1>
            <p>Système de Gestion de Présence</p>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="error">Identifiants incorrects. Veuillez réessayer.</div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Se connecter</button>
        </form>

        <div class="demo-accounts">
            <h3>Comptes de démonstration:</h3>
            <div class="demo-account">
                <strong>Admin:</strong> admin / password
            </div>
            <div class="demo-account">
                <strong>Professeur:</strong> prof.ahmed / password
            </div>
            <div class="demo-account">
                <strong>Étudiant:</strong> etudiant1 / password
            </div>
        </div>
    </div>
</body>
</html>
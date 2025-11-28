<?php
// admin_users.php
require_once 'auth.php';
$auth->requireRole('admin');

require_once 'db_connect.php';

// Ajouter un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, first_name, last_name, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role, $firstName, $lastName, $email]);
        $success = "User added successfully!";
    } catch (PDOException $e) {
        $error = "Error adding user: " . $e->getMessage();
    }
}

// Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY role, last_name, first_name");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprendre les styles de admin_dashboard.php */
        /* ... */
    </style>
</head>
<body>
    <!-- Navigation Bar (identique à admin_dashboard.php) -->
    <nav class="navbar">...</nav>

    <!-- User Management -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-users-cog"></i> User Management</h2>
            
            <?php if (isset($success)): ?>
                <div class="confirmation"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="report-info">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="student">Student</option>
                            <option value="professor">Professor</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_user" class="btn-effect btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        Add User
                    </button>
                </form>
            </div>

            <!-- Users List -->
            <div class="report-info">
                <h3><i class="fas fa-list"></i> All Users</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span style="color: 
                                        <?php echo $user['role'] === 'admin' ? '#e74c3c' : ($user['role'] === 'professor' ? '#3498db' : '#27ae60'); ?>; 
                                        font-weight: bold;">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="btn-effect" style="text-decoration: none; padding: 8px 15px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (identique) -->
    <footer>...</footer>
</body>
</html>
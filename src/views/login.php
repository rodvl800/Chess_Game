<?php
/**
 * Login View
 */
require_once APP_ROOT . '/../src/models/User.php';

// Check if user is already logged in
if (User::isLoggedIn()) {
    // Redirect to homepage
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Initialize error variable
$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Attempt to log in
        $user = new User();
        $result = $user->login($username, $password);
        
        if ($result['success']) {
            // Redirect to game page
            header('Location: ' . APP_URL . '/index.php?page=game');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chess Game</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/utils.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include APP_ROOT . '/views/layout/header.php'; ?>
    
    <!-- Main Content -->
    <main class="container">
        <div class="auth-container">
            <h2>Login to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form id="login-form" method="post" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="<?php echo APP_URL; ?>/index.php?page=register">Register</a></p>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include APP_ROOT . '/views/layout/footer.php'; ?>
</body>
</html> 
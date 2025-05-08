<?php
/**
 * Register View
 */

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to homepage
    header('Location: index.php');
    exit;
}

// Initialize variables
$error = isset($error) ? $error : '';
$success = isset($success) ? $success : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Chess Game</title>
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
            <h2>Create an Account</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Registration successful! You can now <a href="<?php echo APP_URL; ?>/index.php?page=login">login</a> with your credentials.</p>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form id="register-form" method="post" action="<?php echo APP_URL; ?>/index.php?page=register">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                        <small class="form-text text-muted">Must be at least 3 characters and can contain letters, numbers, and underscores.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <small class="form-text text-muted">Must be at least 6 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <p>Already have an account? <a href="<?php echo APP_URL; ?>/index.php?page=login">Login</a></p>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include APP_ROOT . '/views/layout/footer.php'; ?>
</body>
</html> 
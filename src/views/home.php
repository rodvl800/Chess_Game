<?php
// Title for the page
$pageTitle = "Chess Game - Home";

// Include header
include_once 'layout/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12 text-center">
            <h1>Welcome to Chess Game</h1>
            <p class="lead">Play chess online with friends or practice against the computer</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <h3>Ready to play?</h3>
                        <a href="index.php?page=game" class="btn btn-primary mt-3">Start a New Game</a>
                        <a href="index.php?page=profile" class="btn btn-secondary mt-3">View Profile</a>
                    <?php else: ?>
                        <h3>Get Started</h3>
                        <a href="index.php?page=login" class="btn btn-primary mt-3">Log In</a>
                        <a href="index.php?page=register" class="btn btn-secondary mt-3">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'layout/footer.php';
?> 
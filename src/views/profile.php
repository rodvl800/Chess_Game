<?php
/**
 * Profile View
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: index.php?page=login');
    exit;
}

// Load user data
require_once APP_ROOT . '/../src/models/User.php';
$user = new User($_SESSION['user_id']);
$userData = [
    'username' => $user->getUsername(),
    'email' => $user->getEmail(),
    'stats' => $user->getStats()
];

// Initialize variables
$error = isset($error) ? $error : '';
$success = isset($success) ? $success : '';
$completedGames = isset($completedGames) ? $completedGames : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Chess Game</title>
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
        <h1>Your Profile</h1>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="user-info">
                    <div class="avatar">
                        <!-- User avatar image or placeholder -->
                        <div class="placeholder"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></div>
                    </div>
                    <h2><?php echo htmlspecialchars($userData['username']); ?></h2>
                    <div class="stats">
                        <div class="stat">
                            <span class="label">Rating</span>
                            <span class="value"><?php echo $userData['stats']['rating']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Wins</span>
                            <span class="value"><?php echo $userData['stats']['wins']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Losses</span>
                            <span class="value"><?php echo $userData['stats']['losses']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Draws</span>
                            <span class="value"><?php echo $userData['stats']['draws']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h3>Edit Profile</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo APP_URL; ?>/index.php?page=profile">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                            <small class="form-text text-muted">Username cannot be changed.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                        
                        <h4>Change Password</h4>
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <small class="form-text text-muted">Leave blank to keep your current password.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h3>Game History</h3>
                    
                    <?php if (empty($completedGames)): ?>
                        <p>You haven't completed any games yet.</p>
                    <?php else: ?>
                        <div class="game-history">
                            <table class="game-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Opponent</th>
                                        <th>Your Color</th>
                                        <th>Result</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completedGames as $game): ?>
                                        <?php
                                        // Get opponent info
                                        $isWhite = $game['white_player_id'] == $_SESSION['user_id'];
                                        $opponentId = $isWhite ? $game['black_player_id'] : $game['white_player_id'];
                                        $opponent = new User($opponentId);
                                        $opponentName = $opponent->getUsername();
                                        $yourColor = $isWhite ? 'White' : 'Black';
                                        
                                        // Determine result
                                        $result = "Draw";
                                        if ($game['winner_id']) {
                                            $won = $game['winner_id'] == $_SESSION['user_id'];
                                            $result = $won ? "Won" : "Lost";
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($game['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($opponentName); ?></td>
                                            <td><?php echo $yourColor; ?></td>
                                            <td class="result <?php echo strtolower($result); ?>"><?php echo $result; ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/index.php?page=game&id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include APP_ROOT . '/views/layout/footer.php'; ?>
</body>
</html> 
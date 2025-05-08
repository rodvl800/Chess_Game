<?php
// Include configuration first
require_once dirname(__DIR__) . '/../config/config.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit;
}

// Set page title
$pageTitle = 'Chess Game - Play';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $pageTitle; ?></title>
	
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
	
	<!-- Custom CSS -->
	<link rel="stylesheet" href="/Chess_Game/css/styles.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<?php include dirname(__DIR__) . '/views/layout/header.php'; ?>

	<div class="container mt-4">
		<p id="turnInfo">Player's turn: <b>White</b></p>
		<section id="board">
			<div class="overlay" id="promotionMessage">
				<div class="overlay-inner">
					<span class="overlay-title">Promotion!</span>
					<p class="overlay-text" id="alertText"></p>
					<ul id="promotionList" class="white">
						<li><a href="#" class="promotion-button queen" onclick="promote('queen');"></a></li>
						<li><a href="#" class="promotion-button castle" onclick="promote('castle');"></a></li>
						<li><a href="#" class="promotion-button bishop" onclick="promote('bishop');"></a></li>
						<li><a href="#" class="promotion-button knight" onclick="promote('knight');"></a></li>
					</ul>
				</div>
			</div>
			<div class="overlay" id="errorMessage">
				<div class="overlay-inner">
					<span class="overlay-title">Error!</span>
					<p class="overlay-text" id="errorText"></p>
					<a href="#" class="overlay-button" onclick="closeError();">Close</a>
				</div>
			</div>
			<div class="overlay" id="endMessage">
				<div class="overlay-inner">
					<span class="overlay-title">Game Over!</span>
					<p class="overlay-text" id="endText"></p>
					<a href="#" class="overlay-button" onclick="newGame();">New Game</a>
				</div>
			</div>
		</section>
	</div>

	<!-- Bootstrap JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
	<!-- Game Script -->
	<script type="text/javascript" src="js/scripts.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			setup();
		});
	</script>
</body>
</html>
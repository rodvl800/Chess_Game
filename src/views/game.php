<?php
// Title for the page
$pageTitle = "Chess Game - Play";

// Include header
include_once 'layout/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: index.php?page=login');
    exit;
}

// Get game ID from URL or set to 'new'
$gameId = isset($_GET['game_id']) ? $_GET['game_id'] : 'new';
?>

<!-- Hidden fields for game and user IDs -->
<input type="hidden" id="game-id" value="<?php echo htmlspecialchars($gameId); ?>">
<input type="hidden" id="user-id" value="<?php echo $_SESSION['user_id']; ?>">

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Chess Game</h3>
                </div>
                <div class="card-body">
                    <p id="turnInfo" class="text-center mb-3">Player's turn: <b>White</b></p>
                    <div id="chess-board" class="mx-auto"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Game Controls</h4>
                </div>
                <div class="card-body">
                    <button id="new-game" class="btn btn-primary w-100 mb-3">New Game</button>
                    <button id="flip-board" class="btn btn-secondary w-100 mb-3">Flip Board</button>
                    <button id="undo-move" class="btn btn-warning w-100 mb-3">Undo Move</button>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h4>Game Status</h4>
                </div>
                <div class="card-body">
                    <div id="game-status" class="alert alert-info">
                        White to move
                    </div>
                    <div id="move-history" class="mt-3">
                        <h5>Move History</h5>
                        <div id="moves" class="p-2 border" style="height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>White</th>
                                        <th>Black</th>
                                    </tr>
                                </thead>
                                <tbody id="move-list">
                                    <!-- Move history will be displayed here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlays -->
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

<!-- Load required scripts -->
<script src="js/chess.js"></script>
<script src="js/pieces.js"></script>
<script src="js/game_rules_chess.js"></script>

<!-- Add game rules styles -->
<style>
/* Game container */
.game-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

/* Chess board */
#chess-board {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    grid-template-rows: repeat(8, 1fr);
    width: 560px;
    height: 560px;
    border: 2px solid #333;
    margin-bottom: 20px;
    position: relative;
}

/* Squares */
.square {
    width: 70px;
    height: 70px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    position: relative;
    background-size: 600% 200%;
    background-image: url("../img/pieces.png");
}

.square.white {
    background-color: #f0d9b5;
}

.square.black {
    background-color: #b58863;
}

.square.selected {
    box-shadow: 0 0 2px #0000ff, 0 0 4px #0000ff inset;
    z-index: 20;
}

.square.empty {
    background-image: none;
}

/* Pieces */
.square.black-king { background-position: 0% 100%; }
.square.black-queen { background-position: 20% 100%; }
.square.black-bishop { background-position: 40% 100%; }
.square.black-knight { background-position: 60% 100%; }
.square.black-castle { background-position: 80% 100%; }
.square.black-pawn { background-position: 100% 100%; }

.square.white-king { background-position: 0% 0%; }
.square.white-queen { background-position: 20% 0%; }
.square.white-bishop { background-position: 40% 0%; }
.square.white-knight { background-position: 60% 0%; }
.square.white-castle { background-position: 80% 0%; }
.square.white-pawn { background-position: 100% 0%; }

/* Player info */
.player-info {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 20px;
}

.player {
    background-color: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    width: 45%;
}

.player h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.player p {
    margin: 5px 0;
    color: #666;
}

/* Messages */
.message {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 15px 30px;
    border-radius: 5px;
    font-weight: bold;
    z-index: 1000;
}

.message.error {
    background-color: #ff4444;
    color: white;
}

.message.end {
    background-color: #4CAF50;
    color: white;
}

/* Promotion overlay */
.overlay {
    background-color: rgba(0,0,0,0.6);
    position: absolute;
    height: 100%;
    width: 100%;
    z-index: 0;
    opacity: 0;
    transition: all .3s ease-out;
}

.overlay.show {
    opacity: 1;
    z-index: 100;
}

.overlay .overlay-inner {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    margin: auto;
    width: 400px;
    height: 190px;
    background: #fff;
    border-radius: 15px;
    padding: 15px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    text-align: center;
}

.overlay .overlay-title {
    display: block;
    font-weight: bold;
    line-height: 32px;
    font-size: 20px;
}

.overlay .overlay-text {
    line-height: 22px;
    font-size: 16px;
}

.overlay .overlay-button {
    display: inline-block;
    line-height: 34px;
    padding: 0 12px;
    color: #fff;
    background: #049eff;
    border-radius: 5px;
    text-decoration: none;
    margin-top: 10px;
}

#promotionList {
    margin: 0;
    padding: 20px 0;
    list-style-type: none;
    text-align: center;
}

#promotionList li {
    display: inline-block;
    padding: 0 5px;
}

#promotionList li a {
    background-image: url("../img/pieces.png");
    background-size: 600% 200%;
    width: 60px;
    height: 60px;
    display: block;
}

#promotionList.white li a.queen { background-position: 20% 0%; }
#promotionList.white li a.castle { background-position: 80% 0%; }
#promotionList.white li a.bishop { background-position: 40% 0%; }
#promotionList.white li a.knight { background-position: 60% 0%; }

#promotionList.black li a.queen { background-position: 20% 100%; }
#promotionList.black li a.castle { background-position: 80% 100%; }
#promotionList.black li a.bishop { background-position: 40% 100%; }
#promotionList.black li a.knight { background-position: 60% 100%; }
</style>

<!-- Initialize the chessboard -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded, attempting to initialize chess game...");
        // Check if initGame exists
        if (typeof initGame === 'function') {
            // Initialize the chess game
            initGame();
        } else {
            console.error("initGame function not found. Check if chess.js is loaded correctly.");
        }
    });
</script>

<?php
// Include footer
include_once 'layout/footer.php';
?> 
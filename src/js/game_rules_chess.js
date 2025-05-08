// Game state variables
let pieces = [];
let boardSquares = [];
let selectedSquare = null;
let turn = 1;
let currentPlayer = null;
let gameId = null;
let userId = null;
let gameState = null;

// Player class
let Player = function(color) {
    this.checked = false;
    this.color = color;
    this.castled = false;
    this.king = null;
    this.kingMoved = false;
    this.promote = null;
    this.moved = null;
    this.userId = null;
};

let white = new Player("white");
let black = new Player("black");

// Square class
let SquareObject = function(x, y, color, selected, element, piece) {
    this.x = x;
    this.y = y;
    this.color = color;
    this.selected = selected;
    this.element = element;
    this.piece = piece;
};

SquareObject.prototype.setPiece = function(piece) {
    this.piece = piece;
    this.update();
};

SquareObject.prototype.unsetPiece = function() {
    this.piece = null;
    this.update();
};

SquareObject.prototype.update = function() {
    this.element.className = "square " + this.color + " " + (this.selected ? "selected" : "") + " " + (this.piece === null ? "empty" : this.piece.color + "-" + this.piece.type);
};

SquareObject.prototype.select = function() {
    this.selected = true;
    this.update();
};

SquareObject.prototype.deselect = function() {
    this.selected = false;
    this.update();
};

SquareObject.prototype.hasPiece = function() {
    return this.piece !== null;
};

// Initialize the game
function initGame() {
    const gameId = $('#game-id').val();
    const userId = $('#user-id').val();
    
    // Create the chess board
    const board = document.getElementById('chess-board');
    board.innerHTML = '';
    
    // Create squares
    for (let y = 0; y < 8; y++) {
        for (let x = 0; x < 8; x++) {
            const square = document.createElement('div');
            square.className = 'square ' + ((x + y) % 2 === 0 ? 'white' : 'black');
            square.onclick = function() { handleSquareClick(x, y); };
            board.appendChild(square);
            
            const squareObj = new SquareObject(x, y, (x + y) % 2 === 0 ? 'white' : 'black', false, square, null);
            boardSquares.push(squareObj);
        }
    }
    
    // Initialize game state
    gameState = new GameState(gameId, userId);
    gameState.boardSquares = boardSquares;
    gameState.init();
}

// Set up initial piece positions
function setupPieces() {
    // Clear existing pieces
    pieces = [];
    
    // Set up pawns
    for (let x = 0; x < 8; x++) {
        pieces.push(new Pawn(x, 1, 'black'));
        pieces.push(new Pawn(x, 6, 'white'));
    }
    
    // Set up other pieces
    const pieceOrder = ['castle', 'knight', 'bishop', 'queen', 'king', 'bishop', 'knight', 'castle'];
    for (let x = 0; x < 8; x++) {
        pieces.push(new window[pieceOrder[x].charAt(0).toUpperCase() + pieceOrder[x].slice(1)](x, 0, 'black'));
        pieces.push(new window[pieceOrder[x].charAt(0).toUpperCase() + pieceOrder[x].slice(1)](x, 7, 'white'));
    }
    
    // Place pieces on board
    pieces.forEach(piece => {
        if (!piece.captured) {
            getSquare(piece.x, piece.y).setPiece(piece);
        }
    });
    
    // Set king references
    pieces.forEach(piece => {
        if (piece.type === 'king') {
            if (piece.color === 'white') {
                white.king = piece;
            } else {
                black.king = piece;
            }
        }
    });
}

// Load game state from moves
function loadGameState(moves) {
    setupPieces();
    moves.forEach(move => {
        const fromSquare = getSquare(move.from_x, move.from_y);
        const toSquare = getSquare(move.to_x, move.to_y);
        movePiece(fromSquare, toSquare);
    });
}

// Update player information display
function updatePlayerInfo() {
    // Update white player info
    $.get('php/api.php/user/' + white.userId, function(response) {
        if (response.success) {
            $('#white-player .username').text(response.user.username);
            $('#white-player .rating').text('Rating: ' + response.user.rating);
        }
    });
    
    // Update black player info
    $.get('php/api.php/user/' + black.userId, function(response) {
        if (response.success) {
            $('#black-player .username').text(response.user.username);
            $('#black-player .rating').text('Rating: ' + response.user.rating);
        }
    });
}

// Handle square click
async function handleSquareClick(x, y) {
    if (gameState) {
        await gameState.handleSquareClick(x, y);
    }
}

// Move a piece
function movePiece(fromSquare, toSquare) {
    const piece = fromSquare.piece;
    const moveResult = piece.isValidMove(toSquare);
    
    if (moveResult.valid) {
        // Handle capture
        if (moveResult.capture) {
            moveResult.capture.piece.capture();
        }
        
        // Update piece position
        piece.x = toSquare.x;
        piece.y = toSquare.y;
        piece.lastmoved = turn;
        
        // Update board
        fromSquare.unsetPiece();
        toSquare.setPiece(piece);
        
        // Save move to database
        $.post('php/api.php/game/' + gameId + '/move', {
            from_x: fromSquare.x,
            from_y: fromSquare.y,
            to_x: toSquare.x,
            to_y: toSquare.y
        }, function(response) {
            if (!response.success) {
                showError("Failed to save move!");
            }
        });
        
        // Check for checkmate
        if (isCheckmate(currentPlayer.king)) {
            showEnd(currentPlayer.color + " is checkmated!");
        }
        
        // Next turn
        nextTurn();
    }
}

// Get square at coordinates
function getSquare(x, y) {
    return boardSquares[y * 8 + x];
}

// Show error message
function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(closeError, 3000);
}

// Close error message
function closeError() {
    const errorDiv = document.getElementById('error-message');
    errorDiv.style.display = 'none';
}

// Show end game message
function showEnd(message) {
    const endDiv = document.getElementById('end-message');
    endDiv.textContent = message;
    endDiv.style.display = 'block';
}

// Next turn
function nextTurn() {
    turn++;
    currentPlayer = currentPlayer === white ? black : white;
    $('#turnInfo b').text(currentPlayer.color.charAt(0).toUpperCase() + currentPlayer.color.slice(1));
    
    // Update game status
    $.get('php/api.php/game/' + gameId, function(response) {
        if (response.success) {
            $('#game-status').text(response.game.status);
        }
    });
}

// Initialize game when document is ready
$(document).ready(function() {
    initGame();
}); 
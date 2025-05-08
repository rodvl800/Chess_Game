/**
 * Chess Game Application
 * Main JavaScript file to handle game logic and UI
 */

// Global variables
let game = {
    id: null,
    status: null,
    currentTurn: 'white',
    selectedPiece: null,
    lastMove: null,
    board: null,
    moves: [],
    players: {
        white: null,
        black: null
    }
};

// Document ready function
$(document).ready(function() {
    console.log("Document ready");
    
    // Check if we're on a game page
    const $chessBoard = $('#chess-board');
    if ($chessBoard.length) {
        console.log("Chess board found, initializing game immediately...");
        // Initialize the game immediately
        initGame();
        
        // Fallback in case of timing issues
        if ($chessBoard.children().length === 0) {
            console.log("No children found in chess board, trying again after delay");
            setTimeout(initGame, 200);
        }
    }
});

// Initial board setup
const initialBoard = [
    ['bR', 'bN', 'bB', 'bQ', 'bK', 'bB', 'bN', 'bR'],
    ['bP', 'bP', 'bP', 'bP', 'bP', 'bP', 'bP', 'bP'],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    ['wP', 'wP', 'wP', 'wP', 'wP', 'wP', 'wP', 'wP'],
    ['wR', 'wN', 'wB', 'wQ', 'wK', 'wB', 'wN', 'wR']
];

// Piece type mapping
const pieceTypeMap = {
    'P': 'pawn',
    'R': 'rook',
    'N': 'knight',
    'B': 'bishop',
    'Q': 'queen',
    'K': 'king'
};

// Initialize the game
function initGame() {
    console.log("Chess game initializing...");
    
    try {
        // Set up a test game ID if none exists
        const gameId = $('#game-id').val() || 'new';
        console.log("Game ID:", gameId);
        
        // IMPORTANT: Always initialize the board
        console.log("Creating initial board state");
        game.board = JSON.parse(JSON.stringify(initialBoard));
        
        // Initialize basic game state if no specific state is loaded
        console.log("Setting up initial game state");
        game.status = 'active';
        game.currentTurn = 'white';
        game.players = {
            white: { id: 1, username: 'Player 1', rating: 1500 },
            black: { id: 2, username: 'Player 2', rating: 1500 }
        };
        
        // Ensure jQuery is loaded
        if (typeof $ !== 'function') {
            console.error("jQuery not loaded, cannot initialize the board");
            return;
        }
        
        // Check if the board element exists
        const $board = $('#chess-board');
        if (!$board.length) {
            console.error("Chess board element not found, cannot render");
            return;
        }
        
        // Render the board and set up event handlers
        console.log("Rendering board...");
        renderBoard();
        
        // Only set up event listeners if we're in a game context
        if (gameId) {
            setupEventListeners();
            
            if (gameId && gameId !== 'new') {
                // Load existing game
                game.id = gameId;
                loadGame();
            } else if (gameId === 'new') {
                // New game setup
                game.moves = [];
                updateGameInfo();
                updateGameActions();
            }
        }
        
        console.log("Game initialization complete");
    } catch (error) {
        console.error("Error during game initialization:", error);
        $('#chess-board').html(`<div class="alert alert-danger">Error initializing game: ${error.message}</div>`);
    }
}

// Load game from server
function loadGame() {
    $.get(`php/api.php/game/${game.id}`, function(response) {
        if (response.success) {
            const gameData = response.game;
            game.status = gameData.status;
            game.currentTurn = gameData.current_turn;
            game.players = {
                white: gameData.white_player,
                black: gameData.black_player
            };
            
            // Load moves and reconstruct board
            game.moves = gameData.moves;
            reconstructBoardFromMoves();
            
            // Render board and update UI
            renderBoard();
            updateGameInfo();
            updateMoveHistory();
            updateGameActions();
        } else {
            showNotification(response.message, 'error');
        }
    });
}

// Reconstruct the board state from move history
function reconstructBoardFromMoves() {
    // Start with initial board
    game.board = JSON.parse(JSON.stringify(initialBoard));
    
    // Apply each move
    for (const move of game.moves) {
        const from = algebraicToCoords(move.from_position);
        const to = algebraicToCoords(move.to_position);
        
        // Move the piece
        game.board[to.row][to.col] = game.board[from.row][from.col];
        game.board[from.row][from.col] = null;
        
        // Handle promotion
        if (move.is_promotion && move.promotion_piece) {
            const color = game.board[to.row][to.col].charAt(0);
            game.board[to.row][to.col] = color + move.promotion_piece;
        }
    }
}

// Render the chess board
function renderBoard() {
    try {
        const $board = $('#chess-board');
        console.log("Chess board element:", $board.length ? "Found" : "Not found");
        
        if (!$board.length) {
            console.error("Chess board element not found in the DOM");
            return;
        }
        
        // Clear previous board
        $board.empty();
        
        const isFlipped = getCurrentPlayerColor() === 'black';
        const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        const ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];

        // Create the board using divs for CSS grid
        for (let row = 0; row < 8; row++) {
            for (let col = 0; col < 8; col++) {
                const r = isFlipped ? 7 - row : row; // Actual board row
                const c = isFlipped ? 7 - col : col; // Actual board col

                const position = files[c] + ranks[r];
                const $square = $('<div>')
                    .addClass('chess-square')
                    .addClass((r + c) % 2 === 0 ? 'white' : 'black') // Visual color based on actual r, c
                    .attr('data-row', r)
                    .attr('data-col', c)
                    .attr('data-position', position);

                // Add piece if present
                try {
                    const piece = game.board[r][c];
                    if (piece) {
                        const pieceColor = piece.charAt(0) === 'w' ? 'white' : 'black';
                        const pieceType = pieceTypeMap[piece.charAt(1)];
                        
                        const $img = $('<img>')
                            .attr('src', `/Chess_Game/assets/pieces/${pieceColor}_${pieceType}.svg`)
                            .attr('alt', pieceColor + ' ' + pieceType)
                            .attr('data-piece', piece)
                            .addClass('chess-piece'); // CSS will handle size
                        
                        $img.on('error', function() {
                            console.log(`SVG failed to load: /Chess_Game/assets/pieces/${pieceColor}_${pieceType}.svg, trying PNG fallback`);
                            const pngPath = `/Chess_Game/assets/pieces/${pieceColor}${pieceType.charAt(0).toUpperCase() + pieceType.slice(1)}.png`;
                            $(this).attr('src', pngPath);
                            $(this).on('error', function() {
                                console.log(`PNG fallback also failed: ${pngPath}`);
                                $(this).replaceWith(`<div style="font-size: 24px; color: ${pieceColor === 'white' ? '#000' : '#fff'}">${piece}</div>`);
                            });
                        });
                        $square.append($img);
                    }
                } catch (pieceError) {
                    console.error("Error adding piece to square:", pieceError);
                    $square.html(`<div class="text-danger">Err</div>`);
                }
                $board.append($square);
            }
        }

        // Add fallback content if no squares were added
        if ($board.find('.chess-square').length === 0) {
            console.error("Failed to create chess squares");
            $board.html('<div class="alert alert-danger">Failed to render chess board.</div>');
        }
        
        // Debug: Add a visible message to confirm board was rendered
        $board.append('<div class="position-absolute top-0 end-0 bg-info text-white p-1">Board rendered</div>');
        
    } catch (error) {
        console.error("Error rendering board:", error);
        $('#chess-board').html(`<div class="alert alert-danger">Error rendering board: ${error.message}</div>`);
    }
}

// Setup event listeners
function setupEventListeners() {
    // Chess piece click
    $(document).on('click', '.chess-piece', function() {
        // Only allow player to move their pieces in their turn
        const pieceColor = $(this).attr('data-piece').charAt(0) === 'w' ? 'white' : 'black';
        
        if (game.status !== 'active' || game.currentTurn !== pieceColor || 
            game.currentTurn !== getCurrentPlayerColor()) {
            return;
        }
        
        const $square = $(this).parent();
        const position = $square.attr('data-position');
        
        // If piece is already selected, deselect it
        if (game.selectedPiece && game.selectedPiece.position === position) {
            deselectPiece();
            return;
        }
        
        // Select the piece
        selectPiece(position);
    });
    
    // Chess square click
    $(document).on('click', '.chess-square', function(e) {
        // Ignore clicks on pieces (they're handled separately)
        if ($(e.target).hasClass('chess-piece')) {
            return;
        }
        
        // If no piece is selected, do nothing
        if (!game.selectedPiece) {
            return;
        }
        
        const position = $(this).attr('data-position');
        
        // Move selected piece to this square
        movePiece(game.selectedPiece.position, position);
    });
    
    // Resign button
    $(document).on('click', '#resign-btn', function() {
        if (confirm('Are you sure you want to resign?')) {
            $.ajax({
                url: `php/api.php/game/${game.id}`,
                method: 'PUT',
                data: JSON.stringify({ action: 'resign' }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Game resigned', 'info');
                        loadGame();
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            });
        }
    });
    
    // Draw button
    $(document).on('click', '#draw-btn', function() {
        if (confirm('Are you sure you want to offer a draw?')) {
            $.ajax({
                url: `php/api.php/game/${game.id}`,
                method: 'PUT',
                data: JSON.stringify({ action: 'draw' }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Draw offered', 'info');
                        loadGame();
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            });
        }
    });
    
    // Accept draw button
    $(document).on('click', '#accept-draw-btn', function() {
        if (confirm('Accept the draw offer?')) {
            $.ajax({
                url: `php/api.php/game/${game.id}`,
                method: 'PUT',
                data: JSON.stringify({ action: 'accept_draw' }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Game drawn', 'info');
                        loadGame();
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            });
        }
    });
    
    // Poll for updates
    setInterval(pollGameUpdates, 5000);
}

// Select a piece
function selectPiece(position) {
    // First, deselect any previously selected piece
    deselectPiece();
    
    // Get piece data
    const row = $(`.chess-square[data-position="${position}"]`).attr('data-row');
    const col = $(`.chess-square[data-position="${position}"]`).attr('data-col');
    const piece = game.board[row][col];
    
    // Store selected piece info
    game.selectedPiece = {
        position: position,
        row: parseInt(row),
        col: parseInt(col),
        piece: piece
    };
    
    // Highlight the selected square
    $(`.chess-square[data-position="${position}"]`).css('background-color', '#bbcb2b');
    
    // Highlight valid moves
    highlightValidMoves();
}

// Deselect the current piece
function deselectPiece() {
    if (game.selectedPiece) {
        // Reset the square color back to its original
        const $square = $(`.chess-square[data-position="${game.selectedPiece.position}"]`);
        const isWhiteSquare = ($square.attr('data-row') + $square.attr('data-col')) % 2 === 0;
        $square.css('background-color', isWhiteSquare ? '#f0d9b5' : '#b58863');
        
        // Reset valid move indicators
        $('.chess-square').each(function() {
            const $this = $(this);
            const isWhite = ($this.attr('data-row') + $this.attr('data-col')) % 2 === 0;
            $this.css('background-color', isWhite ? '#f0d9b5' : '#b58863');
            
            // Remove valid move indicators
            $this.find('.valid-move-indicator').remove();
        });
        
        game.selectedPiece = null;
    }
}

// Highlight valid moves
function highlightValidMoves() {
    // In a real application, you would calculate valid moves based on piece type and game rules
    // For this example, we'll just highlight all empty squares
    $('.chess-square').each(function() {
        const $this = $(this);
        if (!$this.find('.chess-piece').length) {
            $this.css('position', 'relative')
                .append($('<div>').css({
                    'position': 'absolute',
                    'top': '50%',
                    'left': '50%',
                    'transform': 'translate(-50%, -50%)',
                    'width': '30%',
                    'height': '30%',
                    'background-color': 'rgba(0, 0, 0, 0.2)',
                    'border-radius': '50%',
                    'z-index': '1'
                }).addClass('valid-move-indicator'));
        }
    });
}

// Move a piece
function movePiece(fromPosition, toPosition) {
    $.ajax({
        url: `php/api.php/game/${game.id}`,
        method: 'POST',
        data: JSON.stringify({
            from: fromPosition,
            to: toPosition
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                game.lastMove = {
                    from: fromPosition,
                    to: toPosition
                };
                loadGame();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
}

// Poll for game updates
function pollGameUpdates() {
    $.get(`php/api.php/game/${game.id}`, function(response) {
        if (response.success) {
            const gameData = response.game;
            
            // Check if game state has changed
            if (gameData.status !== game.status || 
                gameData.current_turn !== game.currentTurn ||
                gameData.moves.length !== game.moves.length) {
                loadGame();
            }
        }
    });
}

// Update game information
function updateGameInfo() {
    // Update player info if it exists
    if (game.players && game.players.white && game.players.black) {
        if ($('#white-player').length) {
            $('#white-player .username').text(game.players.white.username);
            $('#white-player .rating').text(`(${game.players.white.rating})`);
        }
        
        if ($('#black-player').length) {
            $('#black-player .username').text(game.players.black.username);
            $('#black-player .rating').text(`(${game.players.black.rating})`);
        }
    }
    
    // Update game status
    let statusText = '';
    switch (game.status) {
        case 'waiting':
            statusText = 'Waiting for opponent...';
            break;
        case 'active':
            statusText = `${game.currentTurn === 'white' ? 'White' : 'Black'} to move`;
            break;
        case 'completed':
            statusText = 'Game completed';
            break;
        case 'resigned':
            statusText = 'Game resigned';
            break;
        case 'drawn':
            statusText = 'Game drawn';
            break;
        default:
            statusText = 'New game';
    }
    
    if ($('#game-status').length) {
        $('#game-status').text(statusText);
    }
}

// Update game actions visibility
function updateGameActions() {
    // Skip if these elements don't exist
    if (!$('#resign-btn').length && !$('#draw-btn').length && !$('#accept-draw-btn').length) {
        return;
    }
    
    // Check if current player (if we have player info)
    let isCurrentPlayer = true;
    let isPlayerInGame = true;
    
    if (game.players && game.players.white && game.players.black) {
        const userId = $('#user-id').val();
        isCurrentPlayer = game.currentTurn === getCurrentPlayerColor();
        isPlayerInGame = game.players.white.id == userId || game.players.black.id == userId;
    }
    
    $('#resign-btn').toggle(game.status === 'active' && isPlayerInGame);
    $('#draw-btn').toggle(game.status === 'active' && isCurrentPlayer);
    $('#accept-draw-btn').toggle(game.status === 'draw_offered' && !isCurrentPlayer);
}

// Update move history
function updateMoveHistory() {
    if (!$('#move-list').length) {
        return;
    }
    
    const $moveList = $('#move-list');
    $moveList.empty();
    
    if (!game.moves || game.moves.length === 0) {
        $moveList.append('<li>No moves yet</li>');
        return;
    }
    
    for (let i = 0; i < game.moves.length; i += 2) {
        const $move = $('<li>');
        $move.append(`<span class="move-number">${Math.floor(i/2) + 1}.</span>`);
        
        if (game.moves[i] && game.moves[i].notation) {
            $move.append(`<span class="move-white">${game.moves[i].notation}</span>`);
        } else if (game.moves[i]) {
            $move.append(`<span class="move-white">${game.moves[i].from_position}-${game.moves[i].to_position}</span>`);
        }
        
        if (game.moves[i + 1]) {
            if (game.moves[i + 1].notation) {
                $move.append(`<span class="move-black">${game.moves[i + 1].notation}</span>`);
            } else {
                $move.append(`<span class="move-black">${game.moves[i + 1].from_position}-${game.moves[i + 1].to_position}</span>`);
            }
        }
        
        $moveList.append($move);
    }
}

// Get current player's color
function getCurrentPlayerColor() {
    const userId = $('#user-id').val();
    
    // Default to white if no player info or user ID
    if (!game.players || !game.players.white || !game.players.black || !userId) {
        return 'white';
    }
    
    return game.players.white.id == userId ? 'white' : 'black';
}

// Convert algebraic notation to coordinates
function algebraicToCoords(position) {
    const file = position.charAt(0).charCodeAt(0) - 'a'.charCodeAt(0);
    const rank = 8 - parseInt(position.charAt(1));
    return { row: rank, col: file };
}

// Convert coordinates to algebraic notation
function coordsToAlgebraic(row, col) {
    const file = String.fromCharCode('a'.charCodeAt(0) + col);
    const rank = 8 - row;
    return file + rank;
}

// Show notification
function showNotification(message, type = 'info') {
    const $notification = $('<div>')
        .addClass('notification')
        .addClass(type)
        .text(message)
        .appendTo('body');
    
    // Show notification
    setTimeout(() => $notification.addClass('show'), 10);
    
    // Hide after 5 seconds
    setTimeout(() => {
        $notification.removeClass('show');
        setTimeout(() => $notification.remove(), 300);
    }, 5000);
}
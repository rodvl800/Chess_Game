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
    const gameId = $('#game-id').val();
    console.log("Game ID:", gameId);
    
    // Always initialize the board even if no game ID
    if (!game.board) {
        console.log("Creating initial board state");
        game.board = JSON.parse(JSON.stringify(initialBoard));
    }
    
    // Initialize basic game state if no specific state is loaded
    if (!game.status) {
        console.log("Setting up initial game state");
        game.status = 'active';
        game.currentTurn = 'white';
        game.players = {
            white: { id: 1, username: 'Player 1', rating: 1500 },
            black: { id: 2, username: 'Player 2', rating: 1500 }
        };
    }
    
    // Render the board even if we're loading a specific game later
    console.log("Rendering board...");
    renderBoard();
    
    // Setup event listeners
    setupEventListeners();
    
    if (gameId && gameId !== 'new') {
        // Load existing game
        game.id = gameId;
        loadGame();
    } else {
        // New game setup - just show the initial board
        updateGameInfo();
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
        
        // Create a table for the chess board (simpler than grid)
        const $table = $('<table>').css({
            'width': '100%',
            'height': '100%',
            'border-collapse': 'collapse'
        });
        
        const isFlipped = getCurrentPlayerColor() === 'black';
        
        // Create the board
        for (let row = 0; row < 8; row++) {
            const $row = $('<tr>').css({
                'height': '12.5%'
            });
            
            for (let col = 0; col < 8; col++) {
                // Calculate position based on whether board is flipped
                const r = isFlipped ? 7 - row : row;
                const c = isFlipped ? 7 - col : col;
                
                // Create square
                const squareColor = (row + col) % 2 === 0 ? '#f0d9b5' : '#b58863';
                const $cell = $('<td>').css({
                    'width': '12.5%',
                    'background-color': squareColor,
                    'text-align': 'center',
                    'vertical-align': 'middle',
                    'position': 'relative'
                });
                
                // Add coordinates as data attributes
                const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
                const ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];
                const position = files[c] + ranks[r];
                
                $cell.attr('data-row', r)
                     .attr('data-col', c)
                     .attr('data-position', position)
                     .addClass('chess-square')
                     .addClass((row + col) % 2 === 0 ? 'white' : 'black');
                
                // Add piece if present
                const piece = game.board[r][c];
                if (piece) {
                    const color = piece.charAt(0) === 'w' ? 'white' : 'black';
                    const type = pieceTypeMap[piece.charAt(1)];
                    
                    // Create image element for the piece
                    const $img = $('<img>')
                        .attr('src', `../public/assets/pieces/${color}${type.charAt(0).toUpperCase() + type.slice(1)}.png`)
                        .attr('alt', color + ' ' + type)
                        .attr('data-piece', piece)
                        .css({
                            'width': '80%',
                            'height': '80%',
                            'cursor': 'pointer'
                        })
                        .addClass('chess-piece');
                    
                    // Add error handling to try PNG if SVG fails
                    $img.on('error', function() {
                        const pngPath = `../public/assets/pieces/${color}${type.charAt(0).toUpperCase() + type.slice(1)}.png`;
                        $(this).attr('src', pngPath);
                    });
                    
                    $cell.append($img);
                }
                
                $row.append($cell);
            }
            
            $table.append($row);
        }
        
        // Add the table to the board container
        $board.append($table);
        
        // Highlight last move
        if (game.lastMove) {
            $(`.chess-square[data-position="${game.lastMove.from}"]`).css('background-color', 'rgba(255, 255, 0, 0.3)');
            $(`.chess-square[data-position="${game.lastMove.to}"]`).css('background-color', 'rgba(255, 255, 0, 0.3)');
        }
        
        console.log("Chess board rendered successfully");
    } catch (error) {
        console.error("Error rendering chess board:", error);
    }
}

// Setup event listeners
function setupEventListeners() {
    // New game button
    $('#new-game').on('click', function() {
        // Get list of available users
        $.get('php/api.php/users', function(response) {
            if (response.success && response.users.length > 0) {
                // For now, just pick the first available user
                const opponentId = response.users[0].user_id;
                
                // Create new game
                $.ajax({
                    url: 'php/api.php/game/new',
                    method: 'POST',
                    data: JSON.stringify({ opponent_id: opponentId }),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.success) {
                            game.id = response.game_id;
                            loadGame();
                            showNotification('New game created!', 'success');
                        } else {
                            showNotification(response.message, 'error');
                        }
                    }
                });
            } else {
                showNotification('No opponents available', 'error');
            }
        });
    });
    
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
    const $square = $(`.chess-square[data-position="${position}"]`);
    const row = $square.attr('data-row');
    const col = $square.attr('data-col');
    const piece = game.board[row][col];
    
    // Store selected piece info
    game.selectedPiece = {
        position: position,
        row: parseInt(row),
        col: parseInt(col),
        piece: piece
    };
    
    // Add selected class instead of directly modifying styles
    $square.addClass('selected');
    
    // Highlight valid moves
    highlightValidMoves();
}

// Deselect the current piece
function deselectPiece() {
    if (game.selectedPiece) {
        // Remove selected class
        $(`.chess-square[data-position="${game.selectedPiece.position}"]`).removeClass('selected');
        
        // Remove valid move indicators
        $('.valid-move-indicator').remove();
        
        game.selectedPiece = null;
    }
}

// Highlight valid moves
function highlightValidMoves() {
    // Remove any existing valid move indicators first
    $('.valid-move-indicator').remove();
    
    // Add valid move indicators only to empty squares
    $('.chess-square').each(function() {
        const $this = $(this);
        if (!$this.find('.chess-piece').length) {
            $this.append($('<div>').addClass('valid-move-indicator'));
        }
    });
}

// Move a piece
function movePiece(fromPosition, toPosition) {
    const $fromSquare = $(`.chess-square[data-position="${fromPosition}"]`);
    const $toSquare = $(`.chess-square[data-position="${toPosition}"]`);
    const $piece = $fromSquare.find('.chess-piece');
    
    // Add dragging class for visual feedback
    $piece.addClass('dragging');
    
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
                // Remove dragging class
                $piece.removeClass('dragging');
                // Update the board state without full re-render
                updateBoardState(response.board);
            } else {
                showNotification(response.message, 'error');
                $piece.removeClass('dragging');
            }
        },
        error: function() {
            showNotification("Failed to make move", 'error');
            $piece.removeClass('dragging');
        }
    });
}

// Update board state without full re-render
function updateBoardState(newBoard) {
    game.board = newBoard;
    
    // Update only the changed squares
    for (let row = 0; row < 8; row++) {
        for (let col = 0; col < 8; col++) {
            const position = getPositionFromCoords(row, col);
            const $square = $(`.chess-square[data-position="${position}"]`);
            const currentPiece = $square.find('.chess-piece');
            const newPiece = newBoard[row][col];
            
            if (newPiece) {
                if (!currentPiece.length || currentPiece.attr('data-piece') !== newPiece) {
                    // Piece changed or added
                    const color = newPiece.charAt(0) === 'w' ? 'white' : 'black';
                    const type = pieceTypeMap[newPiece.charAt(1)];
                    const $img = $('<img>')
                        .attr('src', `../public/assets/pieces/${color}${type.charAt(0).toUpperCase() + type.slice(1)}.png`)
                        .attr('alt', color + ' ' + type)
                        .attr('data-piece', newPiece)
                        .addClass('chess-piece');
                    $square.empty().append($img);
                }
            } else if (currentPiece.length) {
                // Piece removed
                currentPiece.remove();
            }
        }
    }
    
    // Update last move highlights
    $('.chess-square').removeClass('last-move');
    if (game.lastMove) {
        $(`.chess-square[data-position="${game.lastMove.from}"]`).addClass('last-move');
        $(`.chess-square[data-position="${game.lastMove.to}"]`).addClass('last-move');
    }
}

// Helper function to get position from coordinates
function getPositionFromCoords(row, col) {
    const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
    const ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];
    return files[col] + ranks[row];
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
    // Update player info
    $('#white-player .username').text(game.players.white.username);
    $('#white-player .rating').text(`(${game.players.white.rating})`);
    $('#black-player .username').text(game.players.black.username);
    $('#black-player .rating').text(`(${game.players.black.rating})`);
    
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
    }
    $('#game-status').text(statusText);
}

// Update game actions visibility
function updateGameActions() {
    const isCurrentPlayer = game.currentTurn === getCurrentPlayerColor();
    const isPlayerInGame = game.players.white.id == $('#user-id').val() || 
                          game.players.black.id == $('#user-id').val();
    
    $('#resign-btn').toggle(game.status === 'active' && isPlayerInGame);
    $('#draw-btn').toggle(game.status === 'active' && isCurrentPlayer);
    $('#accept-draw-btn').toggle(game.status === 'draw_offered' && !isCurrentPlayer);
}

// Update move history
function updateMoveHistory() {
    const $moveList = $('#move-list');
    $moveList.empty();
    
    if (!game.moves || game.moves.length === 0) {
        $moveList.append('<tr><td colspan="3" class="text-center">No moves yet</td></tr>');
        return;
    }
    
    for (let i = 0; i < game.moves.length; i += 2) {
        const moveNumber = Math.floor(i/2) + 1;
        const $row = $('<tr>');
        
        // Add move number
        $row.append(`<td class="move-number">${moveNumber}</td>`);
        
        // Add white move
        if (game.moves[i]) {
            const whiteMove = formatMove(game.moves[i]);
            $row.append(`<td class="move-white ${game.moves[i].is_last_move ? 'last-move' : ''}">${whiteMove}</td>`);
        } else {
            $row.append('<td></td>');
        }
        
        // Add black move
        if (game.moves[i + 1]) {
            const blackMove = formatMove(game.moves[i + 1]);
            $row.append(`<td class="move-black ${game.moves[i + 1].is_last_move ? 'last-move' : ''}">${blackMove}</td>`);
        } else {
            $row.append('<td></td>');
        }
        
        $moveList.append($row);
    }
    
    // Scroll to bottom of move history
    const $moves = $('#moves');
    $moves.scrollTop($moves[0].scrollHeight);
}

// Format move for display
function formatMove(move) {
    let moveText = move.notation || `${move.from_position}-${move.to_position}`;
    
    // Add special move indicators
    if (move.is_capture) moveText += '×';
    if (move.is_check) moveText += '+';
    if (move.is_checkmate) moveText += '#';
    if (move.is_castle) moveText = move.from_position === 'e1' ? 'O-O' : 'O-O-O';
    if (move.is_promotion) moveText += `=${move.promotion_piece}`;
    
    return moveText;
}

// Update last move highlights
function updateLastMoveHighlights() {
    // Remove previous highlights
    $('.chess-square').removeClass('last-move');
    
    // Add highlights for last move
    if (game.lastMove) {
        $(`.chess-square[data-position="${game.lastMove.from}"]`).addClass('last-move');
        $(`.chess-square[data-position="${game.lastMove.to}"]`).addClass('last-move');
    }
}

// Get current player's color
function getCurrentPlayerColor() {
    const userId = $('#user-id').val();
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

// Document ready function
$(document).ready(function() {
    console.log("Document ready");
    
    // Check if we're on a game page
    const $chessBoard = $('#chess-board');
    if ($chessBoard.length) {
        console.log("Chess board found, initializing game...");
        initGame();
    }
}); 
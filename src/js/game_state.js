// Game state management
class GameState {
    constructor(gameId, userId) {
        this.gameId = gameId;
        this.userId = userId;
        this.pieces = [];
        this.boardSquares = [];
        this.selectedSquare = null;
        this.turn = 1;
        this.currentPlayer = null;
        this.white = new Player("white");
        this.black = new Player("black");
    }

    // Initialize the game
    async init() {
        try {
            const response = await $.get('php/api.php/game/' + this.gameId);
            if (response.success) {
                // Set up players
                if (response.game.white_player_id == this.userId) {
                    this.white.userId = this.userId;
                    this.black.userId = response.game.black_player_id;
                    this.currentPlayer = this.white;
                } else {
                    this.white.userId = response.game.white_player_id;
                    this.black.userId = this.userId;
                    this.currentPlayer = this.black;
                }

                // Initialize pieces if game is new
                if (!response.game.moves) {
                    this.setupPieces();
                } else {
                    // Load game state from moves
                    await this.loadGameState(response.game.moves);
                }

                // Update player info
                this.updatePlayerInfo();
            }
        } catch (error) {
            console.error('Failed to initialize game:', error);
        }
    }

    // Save move to database
    async saveMove(fromSquare, toSquare) {
        try {
            const piece = fromSquare.piece;
            const moveData = {
                from_x: fromSquare.x,
                from_y: fromSquare.y,
                to_x: toSquare.x,
                to_y: toSquare.y,
                piece_type: piece.type,
                is_capture: toSquare.piece !== null,
                is_check: this.isCheck(this.currentPlayer.king),
                is_checkmate: this.isCheckmate(this.currentPlayer.king),
                is_castle: piece.type === 'king' && Math.abs(toSquare.x - fromSquare.x) > 1,
                is_promotion: piece.type === 'pawn' && (toSquare.y === 0 || toSquare.y === 7),
                promotion_piece: piece.promotionPiece || null,
                notation: this.getMoveNotation(fromSquare, toSquare, piece)
            };

            const response = await $.post('php/api.php/game/' + this.gameId + '/move', moveData);
            return response.success;
        } catch (error) {
            console.error('Failed to save move:', error);
            return false;
        }
    }

    // Load game state from moves
    async loadGameState(moves) {
        this.setupPieces();
        for (const move of moves) {
            const fromSquare = this.getSquare(move.from_x, move.from_y);
            const toSquare = this.getSquare(move.to_x, move.to_y);
            await this.movePiece(fromSquare, toSquare, false);
        }
    }

    // Move a piece
    async movePiece(fromSquare, toSquare, saveToDb = true) {
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
            piece.lastmoved = this.turn;

            // Update board
            fromSquare.unsetPiece();
            toSquare.setPiece(piece);

            // Save move to database if needed
            if (saveToDb) {
                const saved = await this.saveMove(fromSquare, toSquare);
                if (!saved) {
                    throw new Error("Failed to save move!");
                }
            }

            // Check for checkmate
            if (this.isCheckmate(this.currentPlayer.king)) {
                this.showEnd(this.currentPlayer.color + " is checkmated!");
            }

            // Next turn
            this.nextTurn();
            return true;
        }
        return false;
    }

    // Get square at coordinates
    getSquare(x, y) {
        return this.boardSquares[y * 8 + x];
    }

    // Handle square click
    async handleSquareClick(x, y) {
        const square = this.getSquare(x, y);

        if (this.selectedSquare) {
            if (this.selectedSquare === square) {
                this.selectedSquare.deselect();
                this.selectedSquare = null;
            } else {
                try {
                    await this.movePiece(this.selectedSquare, square);
                    this.selectedSquare.deselect();
                    this.selectedSquare = null;
                } catch (error) {
                    this.showError(error.message);
                }
            }
        } else if (square.hasPiece() && square.piece.color === this.currentPlayer.color) {
            this.selectedSquare = square;
            square.select();
        }
    }

    // Get move notation in algebraic notation
    getMoveNotation(fromSquare, toSquare, piece) {
        let notation = '';
        
        // Add piece letter (except for pawns)
        if (piece.type !== 'pawn') {
            notation += piece.type.charAt(0).toUpperCase();
        }
        
        // Add capture symbol
        if (toSquare.piece) {
            if (piece.type === 'pawn') {
                notation += fromSquare.x.toString();
            }
            notation += 'x';
        }
        
        // Add destination square
        notation += this.getSquareNotation(toSquare);
        
        // Add check/checkmate
        if (this.isCheckmate(this.currentPlayer.king)) {
            notation += '#';
        } else if (this.isCheck(this.currentPlayer.king)) {
            notation += '+';
        }
        
        // Add promotion
        if (piece.type === 'pawn' && (toSquare.y === 0 || toSquare.y === 7)) {
            notation += '=' + (piece.promotionPiece || 'Q');
        }
        
        return notation;
    }

    // Get square notation (e.g., 'e4')
    getSquareNotation(square) {
        const files = 'abcdefgh';
        const ranks = '87654321';
        return files[square.x] + ranks[square.y];
    }
}

// Export the GameState class
window.GameState = GameState; 
/**
 * Chess Piece Classes
 * Contains the implementation of all chess pieces and their movement rules
 */

// Base Piece class
class Piece {
    constructor(x, y, color) {
        this.x = x;
        this.y = y;
        this.color = color;
        this.type = 'piece';
        this.captured = false;
        this.lastmoved = 0;
    }

    capture() {
        this.captured = true;
    }

    isValidMove(square) {
        return { valid: false };
    }

    getValidMoves() {
        return [];
    }
}

// Pawn class
class Pawn extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'pawn';
        this.hasMoved = false;
    }

    isValidMove(square) {
        const direction = this.color === 'white' ? -1 : 1;
        const startRow = this.color === 'white' ? 6 : 1;
        
        // Basic forward move
        if (square.x === this.x && square.y === this.y + direction && !square.hasPiece()) {
            return { valid: true };
        }
        
        // Initial two-square move
        if (!this.hasMoved && square.x === this.x && square.y === this.y + (2 * direction) && 
            !square.hasPiece() && !getSquare(this.x, this.y + direction).hasPiece()) {
            return { valid: true };
        }
        
        // Capture moves
        if (Math.abs(square.x - this.x) === 1 && square.y === this.y + direction && 
            square.hasPiece() && square.piece.color !== this.color) {
            return { valid: true, capture: square };
        }
        
        return { valid: false };
    }
}

// Rook class
class Castle extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'castle';
    }

    isValidMove(square) {
        // Must move in straight line
        if (square.x !== this.x && square.y !== this.y) {
            return { valid: false };
        }
        
        // Check if path is clear
        const path = this.getPath(square);
        for (let i = 0; i < path.length - 1; i++) {
            if (path[i].hasPiece()) {
                return { valid: false };
            }
        }
        
        // Check if destination is empty or contains enemy piece
        if (square.hasPiece() && square.piece.color === this.color) {
            return { valid: false };
        }
        
        return { valid: true, capture: square.hasPiece() ? square : null };
    }

    getPath(square) {
        const path = [];
        const dx = square.x - this.x;
        const dy = square.y - this.y;
        const steps = Math.max(Math.abs(dx), Math.abs(dy));
        
        for (let i = 1; i <= steps; i++) {
            const x = this.x + (dx / steps) * i;
            const y = this.y + (dy / steps) * i;
            path.push(getSquare(Math.round(x), Math.round(y)));
        }
        
        return path;
    }
}

// Knight class
class Knight extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'knight';
    }

    isValidMove(square) {
        const dx = Math.abs(square.x - this.x);
        const dy = Math.abs(square.y - this.y);
        
        // Knight moves in L-shape
        if (!((dx === 2 && dy === 1) || (dx === 1 && dy === 2))) {
            return { valid: false };
        }
        
        // Check if destination is empty or contains enemy piece
        if (square.hasPiece() && square.piece.color === this.color) {
            return { valid: false };
        }
        
        return { valid: true, capture: square.hasPiece() ? square : null };
    }
}

// Bishop class
class Bishop extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'bishop';
    }

    isValidMove(square) {
        // Must move diagonally
        if (Math.abs(square.x - this.x) !== Math.abs(square.y - this.y)) {
            return { valid: false };
        }
        
        // Check if path is clear
        const path = this.getPath(square);
        for (let i = 0; i < path.length - 1; i++) {
            if (path[i].hasPiece()) {
                return { valid: false };
            }
        }
        
        // Check if destination is empty or contains enemy piece
        if (square.hasPiece() && square.piece.color === this.color) {
            return { valid: false };
        }
        
        return { valid: true, capture: square.hasPiece() ? square : null };
    }

    getPath(square) {
        const path = [];
        const dx = square.x - this.x;
        const dy = square.y - this.y;
        const steps = Math.abs(dx);
        
        for (let i = 1; i <= steps; i++) {
            const x = this.x + (dx / steps) * i;
            const y = this.y + (dy / steps) * i;
            path.push(getSquare(Math.round(x), Math.round(y)));
        }
        
        return path;
    }
}

// Queen class
class Queen extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'queen';
    }

    isValidMove(square) {
        // Must move in straight line or diagonally
        if (square.x !== this.x && square.y !== this.y && 
            Math.abs(square.x - this.x) !== Math.abs(square.y - this.y)) {
            return { valid: false };
        }
        
        // Check if path is clear
        const path = this.getPath(square);
        for (let i = 0; i < path.length - 1; i++) {
            if (path[i].hasPiece()) {
                return { valid: false };
            }
        }
        
        // Check if destination is empty or contains enemy piece
        if (square.hasPiece() && square.piece.color === this.color) {
            return { valid: false };
        }
        
        return { valid: true, capture: square.hasPiece() ? square : null };
    }

    getPath(square) {
        const path = [];
        const dx = square.x - this.x;
        const dy = square.y - this.y;
        const steps = Math.max(Math.abs(dx), Math.abs(dy));
        
        for (let i = 1; i <= steps; i++) {
            const x = this.x + (dx / steps) * i;
            const y = this.y + (dy / steps) * i;
            path.push(getSquare(Math.round(x), Math.round(y)));
        }
        
        return path;
    }
}

// King class
class King extends Piece {
    constructor(x, y, color) {
        super(x, y, color);
        this.type = 'king';
    }

    isValidMove(square) {
        // Must move one square in any direction
        if (Math.abs(square.x - this.x) > 1 || Math.abs(square.y - this.y) > 1) {
            return { valid: false };
        }
        
        // Check if destination is empty or contains enemy piece
        if (square.hasPiece() && square.piece.color === this.color) {
            return { valid: false };
        }
        
        return { valid: true, capture: square.hasPiece() ? square : null };
    }
}

// Export piece classes
window.Pawn = Pawn;
window.Castle = Castle;
window.Knight = Knight;
window.Bishop = Bishop;
window.Queen = Queen;
window.King = King; 
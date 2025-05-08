/**
 * Chess Game Application
 * Utility functions for the chess game
 */

// Show notification message 
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    if ($('#notification').length === 0) {
        $('body').append('<div id="notification" class="notification"></div>');
    }
    
    // Set message and type
    const $notification = $('#notification');
    $notification.text(message);
    $notification.removeClass('success error warning info');
    $notification.addClass(type);
    
    // Show notification
    $notification.addClass('show');
    
    // Hide after 3 seconds
    setTimeout(function() {
        $notification.removeClass('show');
    }, 3000);
}

// Format chess position from algebraic notation
function formatPosition(position) {
    if (!position || position.length !== 2) {
        return 'Invalid';
    }
    
    const file = position.charAt(0).toLowerCase();
    const rank = position.charAt(1);
    
    return file + rank;
}

// Convert piece code to name
function getPieceName(pieceCode) {
    if (!pieceCode || pieceCode.length !== 2) {
        return '';
    }
    
    const color = pieceCode.charAt(0) === 'w' ? 'White' : 'Black';
    let piece = '';
    
    switch (pieceCode.charAt(1)) {
        case 'K': piece = 'King'; break;
        case 'Q': piece = 'Queen'; break;
        case 'R': piece = 'Rook'; break;
        case 'B': piece = 'Bishop'; break;
        case 'N': piece = 'Knight'; break;
        case 'P': piece = 'Pawn'; break;
    }
    
    return color + ' ' + piece;
}

// Format date to readable format
function formatDate(dateString) {
    if (!dateString) {
        return '';
    }
    
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Debounce function to limit function calls
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

// Get API URL
function getApiUrl(endpoint) {
    return 'php/api.php/' + endpoint;
}

// Get asset URL for chess pieces
function getPieceImageUrl(color, piece) {
    return `../assets/pieces/${color}_${piece}.svg`;
}

// Get asset URL for chess pieces with fallback
function getPieceImageUrlWithFallback(color, piece) {
    // First try SVG
    const svgUrl = `../assets/pieces/${color}_${piece}.svg`;
    
    // PNG fallback (first letter capitalized)
    const pieceCap = piece.charAt(0).toUpperCase() + piece.slice(1);
    const pngUrl = `../assets/pieces/${color}${pieceCap}.png`;
    
    // Use the SVG by default, but the JS will handle fallback to PNG if needed
    return {
        primary: svgUrl,
        fallback: pngUrl
    };
}

// Handle image loading errors by trying fallback sources
function setupImageFallbacks() {
    $('img[data-fallback]').on('error', function() {
        const fallbackSrc = $(this).attr('data-fallback');
        if (fallbackSrc) {
            $(this).attr('src', fallbackSrc);
        }
    });
} 
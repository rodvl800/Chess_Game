<?php
// Load the sprite sheet
$sprite = imagecreatefrompng('game_rules/img/pieces.png');

// Define piece types and colors
$pieces = [
    'white' => ['king', 'queen', 'bishop', 'knight', 'castle', 'pawn'],
    'black' => ['king', 'queen', 'bishop', 'knight', 'castle', 'pawn']
];

// Each piece is 45x45 pixels
$pieceSize = 45;

// Create directory if it doesn't exist
if (!file_exists('img/pieces')) {
    mkdir('img/pieces', 0777, true);
}

// Extract each piece
foreach ($pieces as $color => $types) {
    $y = $color === 'white' ? 0 : $pieceSize;
    
    foreach ($types as $index => $type) {
        $x = $index * $pieceSize;
        
        // Create new image
        $piece = imagecreatetruecolor($pieceSize, $pieceSize);
        
        // Preserve transparency
        imagealphablending($piece, false);
        imagesavealpha($piece, true);
        $transparent = imagecolorallocatealpha($piece, 255, 255, 255, 127);
        imagefilledrectangle($piece, 0, 0, $pieceSize, $pieceSize, $transparent);
        
        // Copy piece from sprite sheet
        imagecopy($piece, $sprite, 0, 0, $x, $y, $pieceSize, $pieceSize);
        
        // Save piece
        imagepng($piece, "img/pieces/{$color}-{$type}.png");
        
        // Free memory
        imagedestroy($piece);
    }
}

// Free memory
imagedestroy($sprite);

echo "Piece images have been extracted successfully!\n"; 
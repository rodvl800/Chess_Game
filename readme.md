# Chess Game

A web-based chess game application built with PHP, JavaScript, and Bootstrap.

## Project Structure

```
Chess_Game/
├── config/            # Configuration files
│   └── config.php     # Application configuration
├── src/               # Source code
│   ├── controllers/   # Controller files
│   │   └── api.php    # API endpoints
│   ├── models/        # Model files
│   │   ├── database.php # Database connection
│   │   ├── Game.php   # Game model
│   │   └── User.php   # User model
│   ├── views/         # View templates
│   │   ├── layout/    # Layout templates
│   │   │   ├── header.php  # Header template
│   │   │   └── footer.php  # Footer template
│   │   ├── game.php   # Game view
│   │   └── home.php   # Home view
│   ├── public/        # Public assets
│   │   ├── css/       # CSS files
│   │   ├── js/        # JavaScript files
│   │   └── assets/    # Images and other assets
│   └── index.php      # Application entry point
├── .gitignore         # Git ignore file
└── index.php          # Root redirect to application
```

## Setup Instructions

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/Chess_Game.git
   ```

2. Configure your web server (Apache/Nginx) to point to the project directory.

3. Import the database schema from `db/chess_db.sql`.

4. Configure the database connection in `config/config.php`.

5. Access the application through your web browser at `http://localhost/Chess_Game/`.

## Features

- User registration and login
- Play chess against another player or against the computer
- Game history and statistics
- Real-time game updates

## Technologies Used

- PHP for server-side logic
- JavaScript for client-side interactions
- MySQL for data storage
- Bootstrap for responsive UI

## License

This project is licensed under the MIT License - see the LICENSE file for details.
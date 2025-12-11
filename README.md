# VHS Retro Riddle Game

A retro 80s/90s themed riddle game with a Mastermind-style code-breaking finale.

## Features

- 📼 **Retro Aesthetic**: CRT scanlines, neon green text, and terminal interface
- 🧩 **Riddle Categories**: Movies, Sports, Games, and Coding
- 🔐 **Authentication**: Password protected terminal access
- 🧠 **Mastermind Finale**: Collect pins to unlock the final code-breaking phase
- 📱 **Responsive Design**: Works on desktop and mobile

## Setup

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web Server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   ```

2. **Configure Environment**
   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your database credentials:
   ```ini
   DB_HOST=localhost
   DB_NAME=80s_video_store
   DB_USER=root
   DB_PASS=your_password
   ```

3. **Database Setup**
   Import the schema from `docs/database_schema.sql` into your MySQL database.

4. **Run the Application**
   If using the PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   Navigate to `http://localhost:8000/retro.php`

## Game Configurations

You can modify game settings in `.env`:
- `GAME_PASSCODE`: The password to enter the terminal (default: 1234)
- `MAX_PINS`: Number of pins to collect (default: 4)

## Project Structure

- `retro.php`: Main application entry point
- `config/`: Configuration files and constants
- `includes/`: Shared PHP logic (Database, Functions)
- `js/`: Frontend game logic
- `css/`: Stylesheets
- `docs/`: Documentation and database schema

## License

MIT

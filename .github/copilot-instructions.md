# COVID-19 Health Declaration System - AI Coding Instructions

## Architecture Overview

This is a traditional PHP web application built for COVID-19 health record management using XAMPP stack (Apache, MySQL, PHP). The application follows a straightforward MVC-inspired pattern with clear separation between data access, presentation, and business logic.

### Key Components

- **Core Pages**: `index.php` (landing), `dashboard_admin.php` (main dashboard), `add.php`/`update.php`/`delete.php` (CRUD operations)
- **Authentication**: Session-based auth via `includes/login.inc.php` and `includes/logout.inc.php`
- **Database Layer**: `includes/dbconn.inc.php` provides MySQLi connection to `COVID19RecordsDB`
- **Shared Layout**: `header.php` contains navigation, session handling, and responsive design components

## Database Schema

Two main tables in `COVID19RecordsDB`:

- **`users`**: Authentication (`id_users`, `uid_users`, `email_users`, `pwd_users` with bcrypt hashing)
- **`records`**: Health declarations with COVID-specific fields (`diagnosed`, `encountered`, `vaccinated` as ENUMs, `temp` as DECIMAL(5,2))

Use the provided SQL dump in `database/covid19recordsdb.sql` for schema reference.

## Authentication Patterns

- Sessions start in `header.php` with `session_start()`
- Login flow: `signin.php` → `includes/login.inc.php` → redirects to `dashboard_admin.php`
- Access control: Check `$_SESSION['userId']` existence for protected pages
- Logout: Form POST to `includes/logout.inc.php` with session destruction
- Navigation adapts based on `isset($_SESSION['userId'])` in `header.php`

## Frontend Architecture

- **CSS Organization**: Modular stylesheets per component (`header.css`, `dashboard-admin.css`, etc.) with `styles.css` as base
- **Dark Theme**: CSS custom properties in `:root` with `--clr-primary: #181818`, `--clr-complementary: #07c297`
- **Responsive Design**: Mobile-first with CSS Grid/Flexbox, enhanced by `scripts/main.js` for interactive navigation
- **JavaScript**: Class-based ES6 modules (`NavigationManager`, `LoadingManager`) with comprehensive keyboard accessibility

## Development Conventions

### PHP Patterns

- Direct MySQLi queries (not PDO) - follow existing pattern with `mysqli_query($conn, $sql)`
- Include database connection: `include './includes/dbconn.inc.php';`
- Form processing: Check `isset($_POST['submit'])` before processing
- Redirects: Use `header('location: target.php');` followed by `exit();`
- Error handling: Simple `die(mysqli_error($conn))` for database errors

### Security Considerations

- Password hashing: Use `password_hash()` and `password_verify()` (see `login.inc.php`)
- Session management: Proper session destruction in logout (see `logout.inc.php`)
- **Note**: Current codebase has SQL injection vulnerabilities - use prepared statements for new code

### File Organization

- **Includes**: Reusable PHP logic goes in `includes/` directory
- **Assets**: Images in `assets/images/`, CSS in `styles/`, JS in `scripts/`
- **Database**: SQL files and database-related documentation in `database/`

## Common Tasks

### Adding New Pages

1. Include `header.php` for navigation and session handling
2. Check authentication with `if (isset($_SESSION['userId']))` if needed
3. Include database connection for data operations
4. Follow existing form patterns from `add.php` for user input

### CRUD Operations

- **Create**: Follow `add.php` pattern - form processing with direct INSERT
- **Read**: Dashboard aggregation patterns in `dashboard_admin.php`
- **Update**: Use `update.php?id=X` URL pattern with GET parameter
- **Delete**: Simple `delete.php?id=X` with confirmation

### Styling New Components

- Add component-specific CSS file in `styles/` directory
- Include in `header.php` after existing stylesheets
- Use existing CSS custom properties for consistent theming
- Follow mobile-first responsive patterns

## Local Development

**Environment**: XAMPP with Apache/MySQL running
**Database**: Import `database/covid19recordsdb.sql` into MySQL
**Access**: http://localhost/covid-health-declaration/
**Admin Login**: Username "Admin", Password "Admin" (see README.md)

When adding features, maintain the straightforward PHP approach while improving security practices for new code.

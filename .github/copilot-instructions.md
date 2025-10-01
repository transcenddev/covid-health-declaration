# COVID-19 Health Declaration System - AI Coding Instructions

## Architecture Overview

This is a modern PHP web application built for COVID-19 health record management using XAMPP stack (Apache, MySQL, PHP). The system has evolved from a simple CRUD app to an enterprise-level secure application with freemium model, comprehensive UX, and security hardening.

### Key Components

- **Core Pages**: `index.php` (optimized landing), `dashboard_admin.php` (analytics dashboard), `add.php`/`update.php`/`delete.php` (CRUD operations)
- **Security Layer**: `includes/security.inc.php` provides CSRF protection, input validation, secure sessions, and logging
- **Freemium System**: `includes/freemium.inc.php` manages guest usage limits with IP-based tracking (3 submissions/day for guests)
- **Authentication**: Session-based auth via `includes/login.inc.php` with rate limiting and security logging
- **Database Layer**: `includes/dbconn.inc.php` provides MySQLi connection with prepared statements
- **Shared Layout**: `header.php` contains responsive navigation, session handling, guest usage tracking, and CSRF tokens
- **Maintenance Scripts**: `reset_daily_limits.php` runs daily via Windows Task Scheduler for freemium reset and cleanup

## Database Schema

Three main tables in `COVID19RecordsDB`:

- **`users`**: Authentication (`id_users`, `uid_users`, `email_users`, `pwd_users` with bcrypt hashing)
- **`records`**: Health declarations with COVID-specific fields (`diagnosed`, `encountered`, `vaccinated` as ENUMs, `temp` as DECIMAL(5,2), `created_at` for analytics)
- **`guest_usage`**: Freemium tracking (`ip_address`, `usage_count`, `date`, `created_at`, `updated_at`)

Use `database/covid19recordsdb.sql` + `database/add_created_at_column.sql` + `database/freemium_migration.sql` for complete schema.

## Authentication & Security Patterns

- **Secure Sessions**: All protected pages call `initializeSecureSession()` from `includes/security.inc.php` - handles secure cookie settings, session regeneration, and timeout (30 min)
- **CSRF Protection**: Every form includes `<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">` and validates with `validateCSRFToken()`
- **Prepared Statements**: ALL database queries use `mysqli_prepare()` with parameter binding - never direct string concatenation
- **Access Control**: Protected pages check `isValidSession()` instead of simple `isset($_SESSION['userId'])`
- **Input Validation**: Use security functions like `sanitizeInput()`, `validateEmail()`, `validateAge()` etc. from `includes/security.inc.php`
- **Security Logging**: Log events with `logSecurityEvent($message, $level)` - writes to `logs/security.log`
- **Rate Limiting**: Use `checkRateLimit($action, $attempts, $window)` for login attempts and sensitive operations

## Freemium System Patterns

- **Guest Tracking**: Use `get_client_ip()` to get real IP through proxies (handles Cloudflare, load balancers), `check_guest_limit($ip)` returns remaining submissions
- **Usage Recording**: Call `record_guest_usage($ip)` after successful guest submissions (max 3/day per IP)
- **Premium Check**: `is_premium_user()` checks if logged in (unlimited access), guests get limited access
- **Eligibility Validation**: `check_submission_eligibility($ip)` returns array with allowed status and remaining count
- **Usage Stats**: `get_usage_stats($ip)` provides comprehensive usage analytics including percentage_used, status messages
- **UI Integration**: `header.php` shows usage stats and upgrade prompts when guests reach 80%+ limit
- **Daily Reset**: `reset_daily_limits.php` runs via Windows Task Scheduler, includes cleanup of 30+ day old records
- **Error Handling**: Freemium functions include comprehensive error logging to `logs/freemium.log`

## Navigation & User Experience

- **Responsive Navigation**: `header.php` includes desktop/mobile menus with user context awareness
- **Guest Experience**: Shows usage stats and upgrade prompts when at 80%+ limit
- **Modern UI**: ES6 JavaScript classes (`NavigationManager`, `LoadingManager`) in `scripts/main.js`
- **Accessibility**: Full keyboard navigation, screen reader support, WCAG 2.1 AA compliance
- **Mobile-First**: Touch-friendly buttons (44px min), Floating Action Button, thumb-zone optimization

## Frontend Architecture

- **CSS Organization**: Modular stylesheets per component (`header.css`, `dashboard-admin.css`, etc.) with `styles.css` as base
- **Dark Theme**: CSS custom properties in `:root` with `--clr-primary: #181818`, `--clr-complementary: #07c297`
- **Responsive Design**: Mobile-first with CSS Grid/Flexbox, Floating Action Button, touch-friendly 44px+ buttons
- **JavaScript**: Class-based ES6 modules in `scripts/main.js`:
  - `NavigationManager`: Dynamic scroll effects, mobile menu, user dropdown, keyboard navigation
  - `LoadingManager`: Form submission states and feedback
  - Dynamic navbar transparency and blur effects based on scroll position
- **Accessibility**: WCAG 2.1 AA compliance, keyboard navigation, screen reader support, focus management

## Development Conventions

### PHP Security Patterns (CRITICAL)

- **Database Queries**: ALWAYS use prepared statements - `mysqli_prepare($conn, $sql)` then `mysqli_stmt_bind_param($stmt, "sss", $var1, $var2, $var3)`
- **Session Initialization**: Start every protected page with `initializeSecureSession()` - never raw `session_start()`
- **CSRF Tokens**: Include `<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">` in ALL forms
- **Input Handling**: Always sanitize with `sanitizeInput($data)` and validate with specific functions (`validateEmail()`, `validateAge()`, etc.)
- **Access Control**: Use `isValidSession()` for authentication checks, not direct session variable checks
- **Error Logging**: Use `logSecurityEvent()` for security events, `error_log()` for debugging
- **Include Patterns**: Use `include_once './includes/security.inc.php';` to prevent redeclaration errors

### Form Processing Workflow

```php
// 1. Initialize security and session
include_once './includes/security.inc.php';
initializeSecureSession();

// 2. Check form submission and CSRF
if (isset($_POST['submit'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF token validation failed");
        // Handle error
    }

    // 3. Sanitize and validate inputs
    $email = sanitizeInput($_POST['email']);
    if (!validateEmail($email)) {
        // Handle validation error
    }

    // 4. Use prepared statements for database
    $stmt = mysqli_prepare($conn, "INSERT INTO records (email) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}
```

### Freemium Integration Pattern

```php
// For guest-accessible pages (like add.php)
$is_guest = !is_premium_user();
if ($is_guest) {
    $eligibility = check_submission_eligibility();
    if (!$eligibility['allowed']) {
        // Show upgrade prompt or limit message
    } else {
        // Allow submission, record usage after success
        record_guest_usage(get_client_ip());
    }
}
```

### File Organization

- **Includes**: Reusable PHP logic goes in `includes/` directory
- **Security**: All forms and sensitive operations must include `includes/security.inc.php`
- **Assets**: Images in `assets/images/`, CSS in `styles/`, JS in `scripts/`
- **Database**: SQL files and documentation in `database/`
- **Logging**: Security logs in `logs/security.log`, system logs in `logs/php_errors.log`
- **Documentation**: Implementation guides in root (`SECURITY_IMPLEMENTATION.md`, `UX_IMPLEMENTATION_COMPLETE.md`)

## Critical Workflows

### Adding New Protected Pages

1. **Start with security**: `include_once './includes/security.inc.php';`
2. **Initialize session**: `initializeSecureSession();`
3. **Check authentication**: `if (!isValidSession()) { header('Location: signin.php'); exit(); }`
4. **Include header**: `include './header.php';` (provides navigation and CSRF tokens)
5. **Database operations**: Always use prepared statements from existing patterns

### CRUD Operations Security Pattern

- **Create**: Follow `add.php` - validate inputs, check freemium limits, use prepared INSERT
- **Read**: Use prepared SELECT statements with parameter binding like `dashboard_admin.php`
- **Update**: Follow `update.php` - validate ID parameter, use prepared UPDATE with WHERE clauses
- **Delete**: Include CSRF protection, confirm user permissions, prepared DELETE statements

### Daily Maintenance (Production)

- **Automated Reset**: `reset_daily_limits.php` runs via Windows Task Scheduler at midnight
- **Log Monitoring**: Check `logs/security.log` for failed attempts and `logs/daily_reset.log` for maintenance
- **Database Maintenance**: Script includes table optimization and old record cleanup (30+ days)

## Local Development

**Environment**: XAMPP with Apache/MySQL running on Windows
**Database Setup**:

1. Import `database/covid19recordsdb.sql`
2. Run `database/add_created_at_column.sql`
3. Run `database/freemium_migration.sql`
4. Optional: `database/performance_indexes.sql` for optimization

**Access**: http://localhost/covid-health-declaration/
**Admin Login**: Username "Admin", Password "Admin"
**Testing**: Use different browsers/incognito for guest vs admin testing

**Debug Mode**: Many files have comprehensive error logging enabled. Check `logs/php_errors.log` and `logs/security.log`

**Security Testing**:

- Try SQL injection attempts (should be blocked)
- Submit forms without CSRF tokens (should fail)
- Exceed guest limits (should show upgrade prompts)
- Monitor `logs/security.log` for events

## Debugging Patterns

- **Error Logging**: Use `error_log()` for debugging, `logSecurityEvent()` for security events
- **Form Debug**: Many forms include POST data logging - check `logs/php_errors.log`
- **Database Debug**: Use `includes/dbconn.inc.php` to verify database connections
- **Freemium Debug**: Check `logs/freemium.log` for usage tracking issues

## Production Deployment

**Windows Task Scheduler Setup** (for daily freemium reset):

```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\covid-health-declaration\reset_daily_limits.php
Schedule: Daily at 00:01
```

Alternatively, use `run_daily_reset.bat` for manual execution.

When adding features, ALWAYS implement security-first patterns. The codebase has been hardened against SQL injection, CSRF, XSS, and session attacks - maintain these standards.

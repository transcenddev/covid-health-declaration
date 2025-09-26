# Daily Reset Script Documentation

## Overview

The `reset_daily_limits.php` script handles the automated maintenance of the freemium guest usage system. It resets daily usage counts, cleans up old records, and optimizes the database.

## Features

- **Daily Limit Reset**: Resets all guest usage counts to 0 at midnight
- **Old Record Cleanup**: Removes guest usage records older than 30 days
- **Database Optimization**: Optimizes tables for better performance
- **Comprehensive Logging**: Detailed logs with timestamps and error tracking
- **Security**: Multiple security layers to prevent unauthorized access
- **Flexible Execution**: Can run via CLI or web-based cron services

## Security Features

1. **CLI Execution**: Preferred method, only allows command line execution
2. **IP Restriction**: Web execution only from localhost (127.0.0.1)
3. **Daily Auth Token**: Web access requires daily rotating SHA256 token
4. **Access Logging**: All execution attempts are logged

## Setup Instructions

### Option 1: Windows Task Scheduler (Recommended)

1. Open Windows Task Scheduler
2. Create Basic Task:
   - **Name**: COVID-19 Daily Reset
   - **Trigger**: Daily at 12:01 AM
   - **Action**: Start a program
   - **Program**: `C:\xampp\php\php.exe`
   - **Arguments**: `C:\xampp\htdocs\covid-health-declaration\reset_daily_limits.php`

### Option 2: Web-based Cron Service

If using a web-based cron service, use this URL format:

```
http://localhost/covid-health-declaration/reset_daily_limits.php?token=DAILY_TOKEN
```

The daily token is generated as: `hash('sha256', 'covid19_reset_' . date('Y-m-d'))`

### Option 3: Manual CLI Execution

```bash
# Navigate to project directory
cd C:\xampp\htdocs\covid-health-declaration

# Run the script
C:\xampp\php\php.exe reset_daily_limits.php
```

## Configuration

### Adjustable Parameters

You can modify these settings in the script:

- **Days to Keep Records**: Change `30` in `cleanup_old_records($conn, 30)`
- **Execution Time Limit**: Modify `set_time_limit(300)` (currently 5 minutes)
- **Auth Token Pattern**: Update the token generation logic for custom security

### Log Files

The script creates several log files in the `logs/` directory:

- `daily_reset.log` - Detailed execution logs
- `daily_reset_report_YYYY-MM-DD.json` - Daily operation reports in JSON format

## Monitoring

### Success Indicators

- Exit code 0: All operations successful
- Exit code 1: Some operations failed (check logs)
- Exit code 2: Fatal error occurred

### Log Monitoring

Check `logs/daily_reset.log` for:

- Successful reset confirmations
- Number of records processed
- Execution times
- Any errors or warnings

### Example Successful Log Entry

```
[2025-09-24 00:01:00] [INFO] === Daily Reset Script Started ===
[2025-09-24 00:01:00] [INFO] Execution mode: CLI
[2025-09-24 00:01:00] [INFO] Database connection established successfully
[2025-09-24 00:01:00] [INFO] Starting daily usage limit reset...
[2025-09-24 00:01:00] [INFO] Successfully reset daily limits for 25 guest records
[2025-09-24 00:01:00] [INFO] Starting cleanup of records older than 30 days...
[2025-09-24 00:01:00] [INFO] Successfully cleaned up 5 old records
[2025-09-24 00:01:01] [INFO] Starting database optimization...
[2025-09-24 00:01:01] [INFO] Database optimization completed successfully
[2025-09-24 00:01:01] [INFO] === Daily Reset Script Completed ===
[2025-09-24 00:01:01] [INFO] Total execution time: 1.25 seconds
[2025-09-24 00:01:01] [INFO] All operations completed successfully
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**

   - Check XAMPP MySQL service is running
   - Verify database credentials in `dbconn.inc.php`

2. **Permission Denied**

   - Ensure PHP has write access to `logs/` directory
   - Check file permissions on script

3. **Script Timeout**

   - Increase `set_time_limit()` value
   - Check for database performance issues

4. **Authentication Failed (Web)**
   - Verify the daily token is correctly generated
   - Check if executing from allowed IP addresses

### Debug Mode

To run in debug mode with verbose output:

```bash
php reset_daily_limits.php 2>&1 | tee debug_output.log
```

## Integration with Existing System

The script integrates seamlessly with the existing freemium system:

- Uses same database connection patterns (`dbconn.inc.php`)
- Follows project logging conventions
- Maintains data integrity with prepared statements
- Compatible with existing `guest_usage` table schema

## Best Practices

1. **Run Daily**: Schedule for just after midnight (12:01 AM)
2. **Monitor Logs**: Check daily reset logs regularly
3. **Backup First**: Ensure database backups before maintenance
4. **Test Regularly**: Manually test script execution monthly
5. **Keep Logs**: Retain reset logs for at least 90 days

## API Response (Web Execution)

When executed via web, the script returns JSON:

```json
{
  "status": "success",
  "message": "Daily reset completed",
  "report": {
    "timestamp": "2025-09-24 00:01:01",
    "execution_time": 1.25,
    "operations": {
      "reset": {
        "success": true,
        "records_reset": 25,
        "message": "Daily limits reset successfully"
      },
      "cleanup": {
        "success": true,
        "records_deleted": 5,
        "message": "Old records cleaned up successfully"
      },
      "optimize": {
        "success": true,
        "message": "Database optimized successfully"
      }
    }
  }
}
```

This comprehensive daily reset system ensures the freemium guest usage limits are properly maintained while keeping the database optimized and secure.

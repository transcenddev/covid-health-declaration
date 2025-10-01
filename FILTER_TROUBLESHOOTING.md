# Time Range Filter Troubleshooting Guide

## Quick Diagnosis Checklist

1. **Check if `created_at` column exists:**

   ```sql
   SHOW COLUMNS FROM records LIKE 'created_at';
   ```

   - If empty: Run `ALTER TABLE records ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;`

2. **Check if you have varied date data:**

   - Visit the main dashboard and check different time range filters
   - Should show records distributed across different dates
   - If all dates are the same: Add test data manually through the "Add Record" form with different dates

3. **Check browser console for JavaScript errors:**

   - Press F12 → Console tab
   - Click filter buttons and watch for error messages
   - Should see: "Found X time range filter buttons" and click event logs

4. **Test with debug mode:**
   - Add `?debug=1` to any dashboard URL
   - Should show debug panel with SQL conditions and record counts
   - Verify that counts change between different time ranges

## Common Issues & Solutions

### Issue: "All time ranges show same count"

**Cause:** All records have same created_at date
**Solution:** Add test data manually through the "Add Record" form with different dates

### Issue: "Filter buttons don't change URL"

**Cause:** JavaScript preventing default link behavior  
**Solution:** Check console for errors, ensure no ad blockers interfere

### Issue: "Page refreshes but no visual change"

**Cause:** Data looks the same, poor visual feedback
**Solution:** Use debug mode to confirm filtering is working

### Issue: "Console shows 'Found 0 time range filter buttons'"

**Cause:** CSS selector not finding buttons
**Solution:** Check if `.time-range-filter .filter-btn` elements exist in HTML

## Test Commands

```bash
# Check database structure
mysql -u root -p covid19recordsdb -e "DESCRIBE records;"

# Count records by date
mysql -u root -p covid19recordsdb -e "
SELECT
  DATE(created_at) as date,
  COUNT(*) as count
FROM records
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 10;"
```

## Working Filter URLs

- Today: `dashboard_admin.php?range=today`
- 7 Days: `dashboard_admin.php?range=7days`
- 30 Days: `dashboard_admin.php?range=30days`
- All Time: `dashboard_admin.php?range=all`
- Debug Mode: `dashboard_admin.php?range=today&debug=1`

## Expected Behavior

1. Click filter button → URL changes
2. Loading overlay appears briefly
3. Filter notification shows at top-right
4. Record counts change in KPI tiles
5. Active range indicator updates
6. Console logs show navigation events

If following this guide doesn't resolve the issue, check the PHP error logs at `logs/php_errors.log` for any server-side errors.

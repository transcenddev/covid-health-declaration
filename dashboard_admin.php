<?php
// Include the database connection
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

// Check if user is logged in
if (!isValidSession()) {
    header('Location: signin.php');
    exit();
}

// Handle time range filter
$timeRange = $_GET['range'] ?? $_SESSION['dashboard_time_range'] ?? 'all';
$validRanges = ['today', '7days', '30days', 'all'];
if (!in_array($timeRange, $validRanges)) {
    $timeRange = 'all';
}

// Handle KPI filters
$kpiFilter = $_GET['filter'] ?? '';
$validFilters = ['encountered', 'vaccinated', 'fever', 'adults', 'international'];
if (!empty($kpiFilter) && !in_array($kpiFilter, $validFilters)) {
    $kpiFilter = '';
}

// Store selected range in session
$_SESSION['dashboard_time_range'] = $timeRange;

// Build date conditions for current and previous periods
$currentCondition = '';
$previousCondition = '';
$dateParams = [];
$paramTypes = '';

switch ($timeRange) {
    case 'today':
        $currentCondition = 'AND DATE(created_at) = CURDATE()';
        $previousCondition = 'AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
        break;
    case '7days':
        $currentCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $previousCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)';
        break;
    case '30days':
        $currentCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        $previousCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)';
        break;
    case 'all':
    default:
        // For 'all', compare against the first half vs second half of all data
        $currentCondition = 'AND created_at >= (SELECT DATE_ADD(MIN(created_at), INTERVAL DATEDIFF(MAX(created_at), MIN(created_at))/2 DAY) FROM records)';
        $previousCondition = 'AND created_at < (SELECT DATE_ADD(MIN(created_at), INTERVAL DATEDIFF(MAX(created_at), MIN(created_at))/2 DAY) FROM records)';
        break;
}

// Build KPI condition
$kpiCondition = '';
switch ($kpiFilter) {
    case 'encountered':
        $kpiCondition = "AND encountered = 'YES'";
        break;
    case 'vaccinated':
        $kpiCondition = "AND vaccinated = 'YES'";
        break;
    case 'fever':
        $kpiCondition = 'AND temp > 37.5';
        break;
    case 'adults':
        $kpiCondition = 'AND age >= 18';
        break;
    case 'international':
        $kpiCondition = "AND LOWER(nationality) NOT LIKE '%philippines%' AND LOWER(nationality) NOT LIKE '%philippine%' AND LOWER(nationality) NOT LIKE '%filipino%' AND LOWER(nationality) NOT LIKE '%pilipino%'";
        break;
    default:
        $kpiCondition = '';
        break;
}

// Get range display text
$rangeDisplayText = [
    'today' => 'Today',
    '7days' => 'Last 7 days',
    '30days' => 'Last 30 days',
    'all' => 'All time'
][$timeRange];

// Get comparison display text
$comparisonText = [
    'today' => 'vs Yesterday',
    '7days' => 'vs Previous 7 days',
    '30days' => 'vs Previous 30 days',
    'all' => 'vs First Half'
][$timeRange];

// Get filter display text
$filterDisplayText = [
    'encountered' => 'COVID Encounters',
    'vaccinated' => 'Vaccinated Individuals',
    'fever' => 'High Temperature Cases',
    'adults' => 'Adult Records',
    'international' => 'International Visitors',
    '' => ''
][$kpiFilter];

// Function to calculate trend data
function calculateTrendData($conn, $currentCondition, $previousCondition, $kpiCondition) {
    $currentQuery = "SELECT * FROM records WHERE 1=1 $currentCondition";
    $previousQuery = "SELECT * FROM records WHERE 1=1 $previousCondition";
    
    $currentStmt = mysqli_prepare($conn, $currentQuery);
    $previousStmt = mysqli_prepare($conn, $previousQuery);
    
    if (!$currentStmt || !$previousStmt) {
        logSecurityEvent('Trend calculation prepared statement failed: ' . mysqli_error($conn));
        return [
            'current' => ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0],
            'previous' => ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0]
        ];
    }
    
    mysqli_stmt_execute($currentStmt);
    mysqli_stmt_execute($previousStmt);
    
    $currentResult = mysqli_stmt_get_result($currentStmt);
    $previousResult = mysqli_stmt_get_result($previousStmt);
    
    // Process current period data
    $current = ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0];
    if ($currentResult) {
        while ($row = mysqli_fetch_assoc($currentResult)) {
            $current['total']++;
            if ($row['encountered'] == 'YES') $current['encountered']++;
            if ($row['vaccinated'] == 'YES') $current['vaccinated']++;
            if ($row['temp'] > 37.5) $current['fever']++;
            if ($row['age'] >= 18) $current['adults']++;
            
            $nationality = strtolower(trim($row['nationality']));
            $philippineVariants = ['philippines', 'philippine', 'filipino', 'pilipino'];
            $isFilipino = false;
            foreach ($philippineVariants as $variant) {
                if (strpos($nationality, $variant) !== false) {
                    $isFilipino = true;
                    break;
                }
            }
            if (!$isFilipino) $current['international']++;
        }
    }
    
    // Process previous period data
    $previous = ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0];
    if ($previousResult) {
        while ($row = mysqli_fetch_assoc($previousResult)) {
            $previous['total']++;
            if ($row['encountered'] == 'YES') $previous['encountered']++;
            if ($row['vaccinated'] == 'YES') $previous['vaccinated']++;
            if ($row['temp'] > 37.5) $previous['fever']++;
            if ($row['age'] >= 18) $previous['adults']++;
            
            $nationality = strtolower(trim($row['nationality']));
            $philippineVariants = ['philippines', 'philippine', 'filipino', 'pilipino'];
            $isFilipino = false;
            foreach ($philippineVariants as $variant) {
                if (strpos($nationality, $variant) !== false) {
                    $isFilipino = true;
                    break;
                }
            }
            if (!$isFilipino) $previous['international']++;
        }
    }
    
    mysqli_stmt_close($currentStmt);
    mysqli_stmt_close($previousStmt);
    
    return ['current' => $current, 'previous' => $previous];
}

// Function to calculate trend indicator
function getTrendIndicator($current, $previous) {
    if ($previous === 0) {
        return $current > 0 ? ['arrow' => '▲', 'percentage' => '100%', 'trend' => 'up'] : ['arrow' => '―', 'percentage' => '0%', 'trend' => 'neutral'];
    }
    
    $percentChange = round((($current - $previous) / $previous) * 100, 1);
    $absChange = abs($percentChange);
    
    if ($percentChange > 0) {
        return ['arrow' => '▲', 'percentage' => "+{$absChange}%", 'trend' => 'up'];
    } elseif ($percentChange < 0) {
        return ['arrow' => '▼', 'percentage' => "-{$absChange}%", 'trend' => 'down'];
    } else {
        return ['arrow' => '―', 'percentage' => '0%', 'trend' => 'neutral'];
    }
}

// Calculate trend data
$trendData = calculateTrendData($conn, $currentCondition, $previousCondition, $kpiCondition);

// Extract current period data for display
$totalRecords = $trendData['current']['total'];
$encounterYesCount = $trendData['current']['encountered'];
$vaccinatedYesCount = $trendData['current']['vaccinated'];
$feverCount = $trendData['current']['fever'];
$adultCount = $trendData['current']['adults'];
$foreignerCount = $trendData['current']['international'];

// Calculate trend indicators for each KPI
$trends = [
    'total' => getTrendIndicator($trendData['current']['total'], $trendData['previous']['total']),
    'encountered' => getTrendIndicator($trendData['current']['encountered'], $trendData['previous']['encountered']),
    'vaccinated' => getTrendIndicator($trendData['current']['vaccinated'], $trendData['previous']['vaccinated']),
    'fever' => getTrendIndicator($trendData['current']['fever'], $trendData['previous']['fever']),
    'adults' => getTrendIndicator($trendData['current']['adults'], $trendData['previous']['adults']),
    'international' => getTrendIndicator($trendData['current']['international'], $trendData['previous']['international'])
];
?>
<?php include "./header.php"; ?>

<body>
  <main>
    <div class="wrapper">
      <!-- Primary Action Section with Time Filter -->
      <div class="primary-action-section">
        <div class="dashboard-header-left">
          <h1 class="dashboard-title">
            <i class="fa-solid fa-chart-line"></i>
            Health Records Dashboard
          </h1>
          <div class="active-range-indicator">
            <i class="fa-solid fa-calendar-alt"></i>
            <span>Showing data for: <strong><?php echo htmlspecialchars($rangeDisplayText); ?></strong></span>
          </div>
        </div>
        <div class="dashboard-header-right">
          <div class="time-range-filter">
            <div class="filter-label">Time Range:</div>
            <div class="filter-buttons">
              <a href="?range=today<?php echo $kpiFilter ? '&filter=' . $kpiFilter : ''; ?>" class="filter-btn <?php echo $timeRange === 'today' ? 'active' : ''; ?>" data-range="today">
                <i class="fa-solid fa-calendar-day"></i>
                Today
              </a>
              <a href="?range=7days<?php echo $kpiFilter ? '&filter=' . $kpiFilter : ''; ?>" class="filter-btn <?php echo $timeRange === '7days' ? 'active' : ''; ?>" data-range="7days">
                <i class="fa-solid fa-calendar-week"></i>
                7 Days
              </a>
              <a href="?range=30days<?php echo $kpiFilter ? '&filter=' . $kpiFilter : ''; ?>" class="filter-btn <?php echo $timeRange === '30days' ? 'active' : ''; ?>" data-range="30days">
                <i class="fa-solid fa-calendar-alt"></i>
                30 Days
              </a>
              <a href="?range=all<?php echo $kpiFilter ? '&filter=' . $kpiFilter : ''; ?>" class="filter-btn <?php echo $timeRange === 'all' ? 'active' : ''; ?>" data-range="all">
                <i class="fa-solid fa-infinity"></i>
                All Time
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Status Grid with Clickable Tiles and Trend Indicators -->
      <div class="status status-grid">
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === '' ? 'active' : ''; ?>" 
                data-filter="" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View all health records for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to view all records. Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $totalRecords; ?></div>
          <div class="status-grid-text">Total Records</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['total']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['total']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['total']['percentage']; ?></span>
          </div>
        </button>
        
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === 'encountered' ? 'active' : ''; ?>" 
                data-filter="encountered" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View COVID encounter cases for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to filter COVID encounter cases. Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $encounterYesCount; ?></div>
          <div class="status-grid-text">COVID Encounters</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['encountered']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['encountered']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['encountered']['percentage']; ?></span>
          </div>
        </button>
        
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === 'vaccinated' ? 'active' : ''; ?>" 
                data-filter="vaccinated" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View vaccinated individuals for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to filter vaccinated individuals. Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $vaccinatedYesCount; ?></div>
          <div class="status-grid-text">Vaccinated</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['vaccinated']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['vaccinated']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['vaccinated']['percentage']; ?></span>
          </div>
        </button>
        
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === 'fever' ? 'active' : ''; ?>" 
                data-filter="fever" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View high temperature cases for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to filter high temperature cases (>37.5°C). Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $feverCount; ?></div>
          <div class="status-grid-text">High Temperature</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['fever']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['fever']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['fever']['percentage']; ?></span>
          </div>
        </button>
        
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === 'adults' ? 'active' : ''; ?>" 
                data-filter="adults" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View adult records for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to filter adult records (18+). Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $adultCount; ?></div>
          <div class="status-grid-text">Adults</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['adults']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['adults']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['adults']['percentage']; ?></span>
          </div>
        </button>
        
        <button type="button" class="status-grid-item kpi-tile <?php echo $kpiFilter === 'international' ? 'active' : ''; ?>" 
                data-filter="international" 
                data-range="<?php echo $timeRange; ?>"
                role="button" 
                tabindex="0"
                aria-label="View international visitor records for <?php echo strtolower($rangeDisplayText); ?>"
                title="Click to filter international visitor records. Trend shows change <?php echo strtolower($comparisonText); ?>.">
          <div class="status-grid-count"><?php echo $foreignerCount; ?></div>
          <div class="status-grid-text">International</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
          <div class="trend-indicator trend-<?php echo $trends['international']['trend']; ?>">
            <span class="trend-arrow"><?php echo $trends['international']['arrow']; ?></span>
            <span class="trend-percentage"><?php echo $trends['international']['percentage']; ?></span>
          </div>
        </button>
      </div>

      <!-- Clear Filters Section -->
      <?php if (!empty($kpiFilter)): ?>
      <div class="active-filters-section">
        <div class="filter-indicator">
          <i class="fa-solid fa-filter"></i>
          <span>Filtered by: <strong><?php echo htmlspecialchars($filterDisplayText); ?></strong></span>
        </div>
        <a href="?range=<?php echo $timeRange; ?>" class="clear-filters-btn" title="Clear all filters">
          <i class="fa-solid fa-times"></i>
          Clear Filters
        </a>
      </div>
      <?php endif; ?>

      <!-- Clean Table Container -->
      <div class="table-container">
        <div class="table-header">
          <div class="table-header-left">
            <h2 class="table-title">
              <i class="fa-solid fa-table"></i>
              Health Records
              <span class="results-counter" id="resultsCounter">(0 records)</span>
            </h2>
          </div>
          <div class="table-header-right">
            <div class="search-container">
              <div class="search-input-wrapper">
                <i class="fa-solid fa-search search-icon"></i>
                <input 
                  type="text" 
                  class="search-input" 
                  id="tableSearch" 
                  placeholder="Search records..."
                  autocomplete="off"
                >
                <button type="button" class="search-clear" id="searchClear" title="Clear search">
                  <i class="fa-solid fa-times"></i>
                </button>
              </div>
              <div class="search-filters">
                <button type="button" class="filter-btn" id="filterBtn" title="Advanced filters">
                  <i class="fa-solid fa-sliders-h"></i>
                </button>
              </div>
            </div>
            <a href="./add.php" class="primary-add-btn">
              <i class="fa-solid fa-plus"></i>
              Add New Record
            </a>
          </div>
        </div>
        
        <div class="table-wrapper">
          <table>
          <thead>
            <tr>
              <th>Email</th>
              <th>Name</th>
              <th>Gender</th>
              <th>Age</th>
              <th>Temperature</th>
              <th>Diagnosed</th>
              <th>Encountered</th>
              <th>Vaccinated</th>
              <th>Nationality</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php
          // Fetch data for table display using prepared statement with date and KPI filtering
          $table_query = "SELECT * FROM records WHERE 1=1 $currentCondition $kpiCondition ORDER BY id DESC";
          $table_stmt = mysqli_prepare($conn, $table_query);
          
          if ($table_stmt) {
            // Bind parameters if needed
            if (!empty($dateParams)) {
              mysqli_stmt_bind_param($table_stmt, $paramTypes, ...$dateParams);
            }
            mysqli_stmt_execute($table_stmt);
            $table_result = mysqli_stmt_get_result($table_stmt);

            if ($table_result && mysqli_num_rows($table_result) > 0) {
              while ($row = mysqli_fetch_assoc($table_result)) {
                echo '<tr>';
                echo '<td data-cell="email">' . sanitizeOutput($row['email']) . '</td>';
                echo '<td data-cell="name">' . sanitizeOutput($row['full_name']) . '</td>';
                
                // Simple gender display without icons
                echo '<td data-cell="gender">' . sanitizeOutput($row['gender']) . '</td>';
                
                echo '<td data-cell="age">' . sanitizeOutput($row['age']) . '</td>';
                
                // Simple temperature display with color coding
                $temp = (float)$row['temp'];
                $tempClass = 'temp-normal';
                if ($temp > 37.5) $tempClass = 'temp-elevated';
                if ($temp > 38.5) $tempClass = 'temp-high';
                echo '<td data-cell="temperature" class="' . $tempClass . '">' . sanitizeOutput($row['temp']) . '°C</td>';
                
                // Simple status badges
                $diagnosed = strtoupper($row['diagnosed']);
                $diagnosedBadge = '<span class="status-badge ' . ($diagnosed === 'YES' ? 'yes' : 'no') . '">' . $diagnosed . '</span>';
                echo '<td data-cell="diagnosed">' . $diagnosedBadge . '</td>';
                
                $encountered = strtoupper($row['encountered']);
                $encounteredBadge = '<span class="status-badge ' . ($encountered === 'YES' ? 'yes' : 'no') . '">' . $encountered . '</span>';
                echo '<td data-cell="encountered">' . $encounteredBadge . '</td>';
                
                $vaccinated = strtoupper($row['vaccinated']);
                $vaccinatedBadge = '<span class="status-badge ' . ($vaccinated === 'YES' ? 'yes' : 'no') . '">' . $vaccinated . '</span>';
                echo '<td data-cell="vaccinated">' . $vaccinatedBadge . '</td>';
                
                echo '<td data-cell="nationality">' . sanitizeOutput($row['nationality']) . '</td>';
                
                // Improved action buttons
                echo '<td data-cell="actions">';
                echo '<div class="action-buttons">';
                $record_id = (int)$row['id'];
                
                echo '<a href="./update.php?id=' . $record_id . '" class="action-btn edit" title="Edit Record">';
                echo '<i class="fa-solid fa-edit"></i>';
                echo '</a>';
                
                echo '<form method="post" action="./delete.php" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this record? This action cannot be undone.\');">';
                echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
                echo '<input type="hidden" name="id" value="' . $record_id . '">';
                echo '<button type="submit" class="action-btn delete" title="Delete Record">';
                echo '<i class="fa-solid fa-trash"></i>';
                echo '</button>';
                echo '</form>';
                
                echo '</div>';
                echo '</td>';
                echo '</tr>';
              }
            } else {
              // Improved empty state
              echo '<tr class="empty-state">';
              echo '<td colspan="10">';
              echo '<i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: rgba(255, 255, 255, 0.4);"></i>';
              echo '<h3 style="margin: 0 0 0.5rem 0; color: var(--clr-white); font-size: 1.25rem;">No Health Records Found</h3>';
              echo '<p style="margin: 0 0 2rem 0; color: rgba(255, 255, 255, 0.7); font-size: 0.95rem;">Get started by adding your first health declaration record.</p>';
              echo '<a href="./add.php" class="primary-add-btn">';
              echo '<i class="fa-solid fa-plus"></i> Add First Record';
              echo '</a>';
              echo '</td>';
              echo '</tr>';
            }
            mysqli_stmt_close($table_stmt);
          } else {
            logSecurityEvent('Table prepared statement failed in dashboard_admin.php: ' . mysqli_error($conn));
            echo '<tr><td colspan="10">Error loading data</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
    
    <!-- Empty Search Results -->
    <div class="empty-search-results">
      <i class="fa-solid fa-search"></i>
      <h3>No records found</h3>
      <p>Try adjusting your search terms or clear the search to see all records.</p>
    </div>
  </div>
  </div>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
  </div>
</main>

<script>
// Enhanced Dashboard Interactions with KPI Filtering
document.addEventListener('DOMContentLoaded', function() {
  // KPI tile click handlers
  const kpiTiles = document.querySelectorAll('.kpi-tile');
  
  kpiTiles.forEach(tile => {
    tile.addEventListener('click', function() {
      const filter = this.dataset.filter;
      const range = this.dataset.range;
      
      // Build URL with filter and range
      let url = '?range=' + range;
      if (filter) {
        url += '&filter=' + filter;
      }
      
      // Add loading state
      const loadingOverlay = document.getElementById('loadingOverlay');
      this.classList.add('loading');
      loadingOverlay.classList.add('active');
      
      // Navigate to filtered view
      window.location.href = url;
    });
    
    // Keyboard navigation for accessibility
    tile.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.click();
      }
    });
  });
  
  // Add loading for navigation
  const actionLinks = document.querySelectorAll('.action-btn[href]');
  const loadingOverlay = document.getElementById('loadingOverlay');
  
  actionLinks.forEach(link => {
    link.addEventListener('click', function() {
      loadingOverlay.classList.add('active');
    });
  });
  
  // Simple confirmation for delete
  const deleteForms = document.querySelectorAll('form[action="./delete.php"]');
  deleteForms.forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        loadingOverlay.classList.add('active');
        this.submit();
      }
    });
  });
  
  // Check if table needs horizontal scrolling
  const tableWrapper = document.querySelector('.table-wrapper');
  const tableContainer = document.querySelector('.table-container');
  
  function checkTableScroll() {
    if (tableWrapper && tableContainer) {
      const needsScroll = tableWrapper.scrollWidth > tableWrapper.clientWidth;
      if (needsScroll) {
        tableContainer.classList.add('has-scroll');
      } else {
        tableContainer.classList.remove('has-scroll');
      }
    }
  }
  
  // Check on load and resize
  checkTableScroll();
  window.addEventListener('resize', checkTableScroll);
});
</script>
</body>

</html>
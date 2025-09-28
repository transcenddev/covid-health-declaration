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

// Store selected range in session
$_SESSION['dashboard_time_range'] = $timeRange;

// Build date condition for queries
$dateCondition = '';
$dateParams = [];
$paramTypes = '';

switch ($timeRange) {
    case 'today':
        $dateCondition = 'AND DATE(created_at) = CURDATE()';
        break;
    case '7days':
        $dateCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        break;
    case '30days':
        $dateCondition = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        break;
    case 'all':
    default:
        $dateCondition = '';
        break;
}

// Get range display text
$rangeDisplayText = [
    'today' => 'Today',
    '7days' => 'Last 7 days',
    '30days' => 'Last 30 days',
    'all' => 'All time'
][$timeRange];

// Fetch data from the database using prepared statement with date filtering
$query = "SELECT * FROM records WHERE 1=1 $dateCondition";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    logSecurityEvent('Prepared statement failed in dashboard_admin.php: ' . mysqli_error($conn));
    die('Database preparation error');
}

// Bind parameters if needed
if (!empty($dateParams)) {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$dateParams);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Counters for various categories
$encounterYesCount = 0;
$vaccinatedYesCount = 0;
$feverCount = 0;
$adultCount = 0;
$minorCount = 0;
$foreignerCount = 0;
$totalRecords = 0;

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $totalRecords++;
    
    if ($row['encountered'] == 'YES') {
      $encounterYesCount++;
    }

    if ($row['vaccinated'] == 'YES') {
      $vaccinatedYesCount++;
    }

    // Changed fever threshold to be more medically accurate
    if ($row['temp'] > 37.5) {
      $feverCount++;
    }

    if ($row['age'] >= 18) {
      $adultCount++;
    } else {
      $minorCount++;
    }

    // More inclusive nationality check
    $nationality = strtolower(trim($row['nationality']));
    $philippineVariants = ['philippines', 'philippine', 'filipino', 'pilipino'];
    $isFilipino = false;
    
    foreach ($philippineVariants as $variant) {
      if (strpos($nationality, $variant) !== false) {
        $isFilipino = true;
        break;
      }
    }
    
    if (!$isFilipino) {
      $foreignerCount++;
    }
  }
}

mysqli_stmt_close($stmt);
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
              <a href="?range=today" class="filter-btn <?php echo $timeRange === 'today' ? 'active' : ''; ?>" data-range="today">
                <i class="fa-solid fa-calendar-day"></i>
                Today
              </a>
              <a href="?range=7days" class="filter-btn <?php echo $timeRange === '7days' ? 'active' : ''; ?>" data-range="7days">
                <i class="fa-solid fa-calendar-week"></i>
                7 Days
              </a>
              <a href="?range=30days" class="filter-btn <?php echo $timeRange === '30days' ? 'active' : ''; ?>" data-range="30days">
                <i class="fa-solid fa-calendar-alt"></i>
                30 Days
              </a>
              <a href="?range=all" class="filter-btn <?php echo $timeRange === 'all' ? 'active' : ''; ?>" data-range="all">
                <i class="fa-solid fa-infinity"></i>
                All Time
              </a>
            </div>
          </div>
          <a href="./add.php" class="primary-add-btn">
            <i class="fa-solid fa-plus"></i>
            Add New Record
          </a>
        </div>
      </div>

      <!-- Enhanced Status Grid with Time Range Labels -->
      <div class="status status-grid">
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $totalRecords; ?></div>
          <div class="status-grid-text">Total Records</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $encounterYesCount; ?></div>
          <div class="status-grid-text">COVID Encounters</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $vaccinatedYesCount; ?></div>
          <div class="status-grid-text">Vaccinated</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $feverCount; ?></div>
          <div class="status-grid-text">High Temperature</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $adultCount; ?></div>
          <div class="status-grid-text">Adults</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
        <div class="status-grid-item">
          <div class="status-grid-count"><?php echo $foreignerCount; ?></div>
          <div class="status-grid-text">International</div>
          <div class="status-grid-period">· <?php echo $rangeDisplayText; ?></div>
        </div>
      </div>

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
          // Fetch data for table display using prepared statement with date filtering
          $table_query = "SELECT * FROM records WHERE 1=1 $dateCondition ORDER BY id DESC";
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
// Simple Dashboard Interactions
document.addEventListener('DOMContentLoaded', function() {
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
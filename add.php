<?php
// Include the header for consistent navigation
include './header.php';

// Include the database connection
include './includes/dbconn.inc.php';

// Include security functions for CSRF and validation (use include_once to prevent redeclaration)
include_once './includes/security.inc.php';

// TEMPORARILY DISABLE FREEMIUM SYSTEM FOR DEBUGGING
// include './includes/freemium.inc.php';

// Initialize secure session
initializeSecureSession();

// Add comprehensive debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', './logs/php_errors.log');

// Test database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed: " . $conn->connect_error);
} else {
    error_log("Database connection successful");
}

// Test if logs directory exists, create if not
if (!is_dir('./logs')) {
    mkdir('./logs', 0755, true);
    error_log("Created logs directory");
}

// Initialize guest tracking variables - SIMPLIFIED FOR DEBUG
$is_guest = !isset($_SESSION['userId']);
$limit_reached_after_submission = false; // Always false for debugging

// DEBUG: Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request detected!");
    if (isset($_POST['submit'])) {
        error_log("Submit button detected in POST data");
    } else {
        error_log("No submit button in POST data");
        error_log("Available POST keys: " . implode(', ', array_keys($_POST)));
    }
}

// Process form submission - SIMPLIFIED FOR DEBUG (check POST method only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  error_log("=== FORM SUBMISSION STARTED ===");
  error_log("POST data received: " . print_r($_POST, true));
  
  // Check if this is likely our health form (look for email field)
  if (isset($_POST['email'])) {
    error_log("Health form detected - proceeding with processing");
    
    // SKIP ALL FREEMIUM CHECKS FOR DEBUGGING
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
      error_log("CSRF validation failed");
      $error_message = 'Security validation failed. Please try again.';
    } else {
      error_log("CSRF validation passed");
    
    // Retrieve and sanitize form data
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $age = filter_var($_POST['age'] ?? '', FILTER_VALIDATE_INT);
    $temp = filter_var($_POST['temp'] ?? '', FILTER_VALIDATE_FLOAT);
    $diagnosed = sanitizeInput($_POST['diagnosed'] ?? '');
    $encounter = sanitizeInput($_POST['encounter'] ?? '');
    $vaccinated = sanitizeInput($_POST['vaccinated'] ?? '');
    $nationality = sanitizeInput($_POST['nationality'] ?? '');

    // Debug: Log all form data
    error_log("=== FORM DATA RECEIVED ===");
    error_log("Email: '$email'");
    error_log("Full Name: '$full_name'");
    error_log("Gender: '$gender'");
    error_log("Age: '$age' (type: " . gettype($age) . ")");
    error_log("Temperature: '$temp' (type: " . gettype($temp) . ")");
    error_log("Diagnosed: '$diagnosed'");
    error_log("Encounter: '$encounter'");
    error_log("Vaccinated: '$vaccinated'");
    error_log("Nationality: '$nationality'");

    // Basic validation - just check if required fields are not empty
    if (empty($email) || empty($full_name) || empty($gender) || 
        $age === false || $temp === false || 
        empty($diagnosed) || empty($encounter) || empty($vaccinated) || empty($nationality)) {
      error_log("Basic validation failed - some required fields are empty");
      $error_message = 'Please fill in all required fields.';
    } else {
      error_log("=== BASIC VALIDATION PASSED ===");
      error_log("Attempting database insert...");
      
      // Convert to uppercase for database ENUM compatibility
      $diagnosed = strtoupper($diagnosed);
      $encounter = strtoupper($encounter);
      $vaccinated = strtoupper($vaccinated);
      
      error_log("Converted ENUM values - Diagnosed: '$diagnosed', Encounter: '$encounter', Vaccinated: '$vaccinated'");
      
      // Insert data using prepared statement with created_at timestamp
      $sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
      error_log("SQL Query: $sql");
      
      $stmt = mysqli_prepare($conn, $sql);
      
      if ($stmt) {
        error_log("Prepared statement created successfully");
        
        // Bind parameters
        $bind_result = mysqli_stmt_bind_param($stmt, "sssidssss", $email, $full_name, $gender, $age, $temp, $diagnosed, $encounter, $vaccinated, $nationality);
        
        if ($bind_result) {
          error_log("Parameters bound successfully");
          error_log("Final values to insert:");
          error_log("  email='$email', full_name='$full_name', gender='$gender'");
          error_log("  age=$age, temp=$temp");
          error_log("  diagnosed='$diagnosed', encountered='$encounter', vaccinated='$vaccinated'");
          error_log("  nationality='$nationality'");
          
          if (mysqli_stmt_execute($stmt)) {
            $insert_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            error_log("=== DATABASE INSERT SUCCESSFUL ===");
            error_log("New record ID: $insert_id");
            
            // FREEMIUM DISABLED: Skip guest usage recording
            // if ($is_guest) {
            //     $usage_recorded = record_guest_usage($client_ip);
            //     error_log("Guest usage recorded: " . ($usage_recorded ? "SUCCESS" : "FAILED"));
            // }
            
            $success_message = 'Health declaration submitted successfully!';
            // Use JavaScript redirect instead of PHP header to avoid header issues
            echo "<script>setTimeout(function(){ window.location.href = 'dashboard_admin.php'; }, 2000);</script>";
          } else {
            $stmt_error = mysqli_stmt_error($stmt);
            $db_error = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            error_log("=== DATABASE INSERT FAILED ===");
            error_log("Statement error: $stmt_error");
            error_log("Database error: $db_error");
            logSecurityEvent('Database error in add.php: ' . $db_error);
            $error_message = "Database error occurred: $stmt_error | $db_error";
          }
        } else {
          error_log("Parameter binding failed: " . mysqli_stmt_error($stmt));
          mysqli_stmt_close($stmt);
          $error_message = 'Parameter binding failed.';
        }
      } else {
        $prepare_error = mysqli_error($conn);
        error_log("=== PREPARED STATEMENT CREATION FAILED ===");
        error_log("Prepare error: $prepare_error");
        logSecurityEvent('Prepared statement failed in add.php: ' . $prepare_error);
        $error_message = "Database preparation error: $prepare_error";
      }
    }
    } // End CSRF validation else block
  } else {
    error_log("POST request received but no email field - not our health form");
  }
  error_log("=== FORM SUBMISSION ENDED ===");
}
?>

<main class="form-container">
  <div class="form-wrapper">
    <!-- Progress Indicator -->
    <div class="progress-indicator">
      <div class="progress-step active" data-step="1">
        <i class="fa-solid fa-user"></i>
        <span>Personal Info</span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step" data-step="2">
        <i class="fa-solid fa-thermometer-half"></i>
        <span>Health Check</span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step" data-step="3">
        <i class="fa-solid fa-check-circle"></i>
        <span>Complete</span>
      </div>
    </div>

    <div class="form-header">
      <div class="form-icon">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <h1>COVID-19 Health Declaration</h1>
      <p class="form-subtitle">Complete your daily health screening - All fields are required</p>
      
      <!-- Debug: User Status Display (remove this in production) -->
      <div style="background: rgba(7, 194, 151, 0.1); border: 1px solid var(--clr-complementary); border-radius: 8px; padding: 10px; margin: 10px 0; font-size: 0.9em; text-align: center;">
        <strong>Current Status:</strong> 
        <?php if ($is_guest): ?>
          <span style="color: #ff6b6b;">👤 Guest User</span> - Subject to daily limits
        <?php else: ?>
          <span style="color: var(--clr-complementary);">✅ Logged In</span> - Unlimited access
        <?php endif; ?>
      </div>
      
      <!-- DEBUG: Show form submission status -->
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; border-radius: 8px; padding: 10px; margin: 10px 0; font-size: 0.9em;">
          <strong>DEBUG:</strong> Form was submitted! 
          <?php if (isset($_POST['submit'])): ?>
            ✅ Submit button detected
          <?php else: ?>
            ❌ No submit button detected
            <br><small>POST keys found: <?php echo implode(', ', array_keys($_POST)); ?></small>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="alert alert-success animate-fade-in">
        <i class="fa-solid fa-check-circle"></i>
        <div>
          <?php echo htmlspecialchars($success_message); ?>
          <p class="mb-0 mt-2"><small>Redirecting to dashboard...</small></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="alert alert-danger animate-shake">
        <i class="fa-solid fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <!-- ChatGPT-style Full-page Overlay (COMPLETELY REMOVED FOR DEBUGGING) -->
    <!-- OVERLAY CODE TEMPORARILY REMOVED -->
    
    <form method="post" action="" class="health-form" id="healthForm">
      <!-- CSRF Protection -->
      <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
      
      <!-- Personal Information Section -->
      <div class="form-section" id="section-personal">
        <h3 class="section-title">
          <i class="fa-solid fa-user"></i>
          Personal Information
          <div class="section-badge">Step 1 of 2</div>
        </h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="InputEmail" class="form-label">
              <i class="fa-solid fa-envelope"></i>
              Email Address *
            </label>
            <input type="email" class="form-control" id="InputEmail" name="email" 
                   placeholder="your.email@example.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <div class="form-help">We'll never share your email with anyone else.</div>
            <div class="field-validation" id="email-validation"></div>
          </div>
          
          <div class="form-group">
            <label for="InputFullName" class="form-label">
              <i class="fa-solid fa-id-card"></i>
              Full Name *
            </label>
            <input type="text" class="form-control" id="InputFullName" name="full_name" 
                   placeholder="Enter your full name" required
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            <div class="field-validation" id="fullname-validation"></div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="InputGender" class="form-label">
              <i class="fa-solid fa-venus-mars"></i>
              Gender *
            </label>
            <select class="form-control" id="InputGender" name="gender" required>
              <option value="">Select gender</option>
              <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="InputAge" class="form-label">
              <i class="fa-solid fa-calendar"></i>
              Age *
            </label>
            <input type="number" class="form-control" id="InputAge" name="age" 
                   placeholder="Enter your age" min="0" max="150" required
                   value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
            <div class="field-validation" id="age-validation"></div>
          </div>

          <div class="form-group">
            <label for="InputNationality" class="form-label">
              <i class="fa-solid fa-globe"></i>
              Nationality *
            </label>
            <input type="text" class="form-control" id="InputNationality" name="nationality" 
                   placeholder="Enter your nationality" required
                   value="<?php echo isset($_POST['nationality']) ? htmlspecialchars($_POST['nationality']) : ''; ?>">
            <div class="field-validation" id="nationality-validation"></div>
          </div>
        </div>
      </div>

      <!-- Health Information Section -->
      <div class="form-section" id="section-health">
        <h3 class="section-title">
          <i class="fa-solid fa-thermometer-half"></i>
          Health Information
          <div class="section-badge">Step 2 of 2</div>
        </h3>

        <div class="form-group temperature-group">
          <label for="InputTemp" class="form-label">
            <i class="fa-solid fa-temperature-high"></i>
            Body Temperature (°C) *
          </label>
          <div class="temperature-input-wrapper">
            <input type="number" step="0.1" class="form-control temperature-input" id="InputTemp" name="temp" 
                   placeholder="36.5" min="30" max="50" required
                   value="<?php echo isset($_POST['temp']) ? htmlspecialchars($_POST['temp']) : ''; ?>">
            <div class="temperature-indicator" id="tempIndicator">
              <span class="temp-status" id="tempStatus">Normal</span>
            </div>
          </div>
          <div class="form-help">Normal range: 36.0°C - 37.5°C</div>
          <div class="field-validation" id="temp-validation"></div>
        </div>

        <div class="health-questions">
          <div class="form-group question-card">
            <label class="form-label question-label">
              <i class="fa-solid fa-virus"></i>
              Have you been diagnosed with COVID-19?
            </label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="diagnosed" value="Yes" required
                       <?php echo (isset($_POST['diagnosed']) && $_POST['diagnosed'] === 'Yes') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="diagnosed" value="No" required
                       <?php echo (isset($_POST['diagnosed']) && $_POST['diagnosed'] === 'No') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">No</span>
              </label>
            </div>
          </div>

          <div class="form-group question-card">
            <label class="form-label question-label">
              <i class="fa-solid fa-handshake"></i>
              Have you had close contact with someone diagnosed with COVID-19?
            </label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="encounter" value="Yes" required
                       <?php echo (isset($_POST['encounter']) && $_POST['encounter'] === 'Yes') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="encounter" value="No" required
                       <?php echo (isset($_POST['encounter']) && $_POST['encounter'] === 'No') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">No</span>
              </label>
            </div>
          </div>

          <div class="form-group question-card">
            <label class="form-label question-label">
              <i class="fa-solid fa-syringe"></i>
              Have you been vaccinated against COVID-19?
            </label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="vaccinated" value="Yes" required
                       <?php echo (isset($_POST['vaccinated']) && $_POST['vaccinated'] === 'Yes') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="vaccinated" value="No" required
                       <?php echo (isset($_POST['vaccinated']) && $_POST['vaccinated'] === 'No') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">No</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-large" name="submit" value="submit" id="submitBtn">
          <i class="fa-solid fa-paper-plane"></i>
          <span>Submit Health Declaration</span>
        </button>
        <a href="<?php echo isset($_SESSION['userId']) ? './dashboard_admin.php' : './index.php'; ?>" class="btn btn-secondary">
          <i class="fa-solid fa-arrow-left"></i>
          <span>Cancel</span>
        </a>
      </div>
    </form>
  </div>
</main>

<!-- OVERLAY COMPLETELY REMOVED FOR DEBUGGING -->

<!-- Temporary CSS to debug any blur issues -->
<style>
  /* Force remove any blur or overlay effects */
  body, main, .form-container {
    backdrop-filter: none !important;
    filter: none !important;
    background: var(--clr-primary) !important;
  }
  
  /* Ensure form is visible */
  .form-wrapper {
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
  }
  
  /* Hide any overlay elements that might exist */
  .limit-reached-overlay,
  .overlay,
  [class*="overlay"],
  [class*="modal"] {
    display: none !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('healthForm');
  const submitBtn = document.getElementById('submitBtn');
  
  // Progress tracking
  let currentStep = 1;
  const totalSteps = 2;
  
  // Temperature monitoring
  const tempInput = document.getElementById('InputTemp');
  const tempIndicator = document.getElementById('tempIndicator');
  const tempStatus = document.getElementById('tempStatus');
  
  // Field validation setup
  setupFieldValidation();
  setupProgressTracking();
  setupTemperatureMonitoring();
  setupFormSubmission();
  
  function setupFieldValidation() {
    const requiredFields = form.querySelectorAll('input[required], select[required]');
    
    requiredFields.forEach(field => {
      field.addEventListener('blur', validateField);
      field.addEventListener('input', clearValidation);
    });
  }
  
  function validateField(e) {
    const field = e.target;
    const validationDiv = document.getElementById(field.id.replace('Input', '').toLowerCase() + '-validation');
    
    // Skip validation for radio buttons (handled separately in form submission)
    if (field.type === 'radio') {
      return true;
    }
    
    if (!validationDiv) return true;
    
    let isValid = true;
    let message = '';
    
    if (!field.value.trim()) {
      isValid = false;
      message = 'This field is required';
    } else {
      // Specific validations
      switch(field.type) {
        case 'email':
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(field.value)) {
            isValid = false;
            message = 'Please enter a valid email address';
          } else {
            message = 'Valid email ✓';
          }
          break;
        case 'number':
          if (field.name === 'age') {
            const age = parseInt(field.value);
            if (age < 0 || age > 150) {
              isValid = false;
              message = 'Please enter a valid age (0-150)';
            } else {
              message = 'Valid age ✓';
            }
          } else if (field.name === 'temp') {
            const temp = parseFloat(field.value);
            if (temp < 30 || temp > 50) {
              isValid = false;
              message = 'Please enter a realistic temperature (30-50°C)';
            } else {
              message = 'Temperature recorded ✓';
            }
          }
          break;
        case 'text':
          if (field.name === 'full_name' && field.value.length < 2) {
            isValid = false;
            message = 'Name must be at least 2 characters long';
          } else {
            message = 'Valid input ✓';
          }
          break;
        default:
          if (field.value.trim()) {
            message = 'Valid input ✓';
          }
      }
    }
    
    // Update validation display
    field.classList.toggle('valid', isValid);
    field.classList.toggle('invalid', !isValid);
    
    if (validationDiv) {
      validationDiv.textContent = message;
      validationDiv.className = `field-validation ${isValid ? 'valid' : 'invalid'}`;
    }
    
    return isValid;
  }
  
  function clearValidation(e) {
    const field = e.target;
    field.classList.remove('valid', 'invalid');
    const validationDiv = document.getElementById(field.id.replace('Input', '').toLowerCase() + '-validation');
    if (validationDiv) {
      validationDiv.textContent = '';
      validationDiv.className = 'field-validation';
    }
  }
  
  function setupProgressTracking() {
    const personalSection = document.getElementById('section-personal');
    const healthSection = document.getElementById('section-health');
    const progressSteps = document.querySelectorAll('.progress-step');
    
    // Monitor field completion
    const personalFields = personalSection.querySelectorAll('input[required], select[required]');
    const healthFields = healthSection.querySelectorAll('input[required]');
    
    function updateProgress() {
      const personalComplete = Array.from(personalFields).every(field => field.value.trim());
      const healthComplete = Array.from(healthFields).every(field => {
        if (field.type === 'radio') {
          return form.querySelector(`input[name="${field.name}"]:checked`);
        }
        return field.value.trim();
      });
      
      // Update progress indicators
      progressSteps[0].classList.toggle('completed', personalComplete);
      progressSteps[1].classList.toggle('active', personalComplete);
      progressSteps[1].classList.toggle('completed', healthComplete);
      progressSteps[2].classList.toggle('active', personalComplete && healthComplete);
      
      // Update section badges
      const personalBadge = personalSection.querySelector('.section-badge');
      const healthBadge = healthSection.querySelector('.section-badge');
      
      if (personalComplete) {
        personalBadge.textContent = 'Completed ✓';
        personalBadge.classList.add('completed');
      } else {
        personalBadge.textContent = 'Step 1 of 2';
        personalBadge.classList.remove('completed');
      }
      
      if (healthComplete) {
        healthBadge.textContent = 'Completed ✓';
        healthBadge.classList.add('completed');
      } else {
        healthBadge.textContent = 'Step 2 of 2';
        healthBadge.classList.remove('completed');
      }
    }
    
    // Add listeners to all form fields
    [...personalFields, ...healthFields].forEach(field => {
      field.addEventListener('input', updateProgress);
      field.addEventListener('change', updateProgress);
    });
    
    // Initial progress check
    updateProgress();
  }
  
  function setupTemperatureMonitoring() {
    tempInput.addEventListener('input', function() {
      const temp = parseFloat(this.value);
      
      if (isNaN(temp)) {
        tempIndicator.className = 'temperature-indicator';
        tempStatus.textContent = 'Enter temperature';
        return;
      }
      
      let status, className;
      
      if (temp < 36.0) {
        status = 'Low';
        className = 'temperature-indicator low';
      } else if (temp >= 36.0 && temp <= 37.5) {
        status = 'Normal';
        className = 'temperature-indicator normal';
      } else if (temp > 37.5 && temp <= 38.5) {
        status = 'Elevated';
        className = 'temperature-indicator elevated';
      } else {
        status = 'High';
        className = 'temperature-indicator high';
      }
      
      tempIndicator.className = className;
      tempStatus.textContent = status;
    });
  }
  
  function setupFormSubmission() {
    form.addEventListener('submit', function(e) {
      console.log('Form submission triggered');
      
      // Temporarily disable client-side validation to test database insertion
      // Remove the preventDefault() and validation logic
      
      console.log('Form validation bypassed for testing - submitting to server');
      
      // Show loading state
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
      
      const icon = submitBtn.querySelector('i');
      const text = submitBtn.querySelector('span');
      
      icon.className = 'fa-solid fa-spinner fa-spin';
      text.textContent = 'Submitting...';
      
      // Final progress update
      document.querySelectorAll('.progress-step').forEach(step => {
        step.classList.add('completed');
      });
      
      // Allow form to submit normally
      return true;
    });
  }
  
  // Add smooth scrolling for better UX
  const sections = document.querySelectorAll('.form-section');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
      }
    });
  }, { threshold: 0.3 });
  
  sections.forEach(section => {
    observer.observe(section);
  });
  
  // Auto-save to localStorage for better UX
  const formData = {};
  const inputs = form.querySelectorAll('input, select');
  
  // Load saved data
  inputs.forEach(input => {
    const saved = localStorage.getItem(`healthForm_${input.name}`);
    if (saved && input.type !== 'radio') {
      input.value = saved;
    } else if (saved && input.type === 'radio' && input.value === saved) {
      input.checked = true;
    }
  });
  
  // Save data on change
  inputs.forEach(input => {
    input.addEventListener('change', function() {
      if (this.type === 'radio') {
        localStorage.setItem(`healthForm_${this.name}`, this.value);
      } else {
        localStorage.setItem(`healthForm_${this.name}`, this.value);
      }
    });
  });
  
  // Clear saved data on successful submission
  form.addEventListener('submit', function() {
    setTimeout(() => {
      inputs.forEach(input => {
        localStorage.removeItem(`healthForm_${input.name}`);
      });
    }, 1000);
  });
  
  // Fix for gender select icon positioning
  function setupGenderSelectFix() {
    const genderSelect = document.getElementById('InputGender');
    const genderLabel = genderSelect?.closest('.form-group')?.querySelector('label[for="InputGender"]');
    
    if (genderSelect && genderLabel) {
      function updateGenderIcon() {
        const icon = genderLabel.querySelector('i');
        if (icon) {
          // Reset any problematic styles
          icon.style.transform = 'none';
          icon.style.position = 'relative';
          icon.style.display = 'inline-block';
          icon.style.verticalAlign = 'middle';
          
          // Ensure proper color
          if (genderSelect.value && genderSelect.value !== '') {
            genderLabel.classList.add('selected');
          } else {
            genderLabel.classList.remove('selected');
          }
        }
      }
      
      // Update icon on various events
      genderSelect.addEventListener('change', updateGenderIcon);
      genderSelect.addEventListener('input', updateGenderIcon);
      
      // Initial update
      setTimeout(updateGenderIcon, 100);
    }
  }
  
  // Initialize gender select fix
  setupGenderSelectFix();
  
  // General fix for all form field icons
  function fixAllFormIcons() {
    const allLabels = form.querySelectorAll('.form-label');
    allLabels.forEach(label => {
      const icon = label.querySelector('i');
      if (icon) {
        // Reset any problematic styles
        icon.style.transform = 'none';
        icon.style.position = 'relative';
        icon.style.display = 'inline-block';
        icon.style.verticalAlign = 'middle';
      }
    });
  }
  
  // Apply fixes on page load and after any form changes
  fixAllFormIcons();
  form.addEventListener('change', fixAllFormIcons);
});
</script>

</body>
</html>
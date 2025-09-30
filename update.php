<?php
// Include the database connection
include './includes/dbconn.inc.php';

// Include security functions for CSRF and validation (use include_once to prevent redeclaration)
include_once './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

// Check if user is logged in (update should require authentication)
if (!isValidSession()) {
    header('Location: signin.php');
    exit();
}

// Get user ID from the URL and validate
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
  header('Location: dashboard_admin.php');
  exit();
}

$id = (int)$_GET['id'];

// Fetch existing user data using prepared statement
$fetch_sql = "SELECT * FROM records WHERE id = ?";
$fetch_stmt = mysqli_prepare($conn, $fetch_sql);

if (!$fetch_stmt) {
  logSecurityEvent('Prepared statement failed in update.php: ' . mysqli_error($conn));
  die('Database preparation error');
}

mysqli_stmt_bind_param($fetch_stmt, "i", $id);
mysqli_stmt_execute($fetch_stmt);
$fetch_result = mysqli_stmt_get_result($fetch_stmt);

// Check if user exists
if ($fetch_result && mysqli_num_rows($fetch_result) > 0) {
  $user_data = mysqli_fetch_assoc($fetch_result);
  $email = $user_data['email'];
  $full_name = $user_data['full_name'];
  $gender = $user_data['gender'];
  $age = $user_data['age'];
  $temp = $user_data['temp'];
  $diagnosed = $user_data['diagnosed'];
  $encounter = $user_data['encountered'];
  $vaccinated = $user_data['vaccinated'];
  $nationality = $user_data['nationality'];
} else {
  mysqli_stmt_close($fetch_stmt);
  header('Location: dashboard_admin.php');
  exit();
}

mysqli_stmt_close($fetch_stmt);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    logSecurityEvent('CSRF token validation failed in update.php for record ID: ' . $id);
    $error_message = 'Security validation failed. Please try again.';
  } else {

  // Retrieve and sanitize form data
  $email = sanitizeInput($_POST['email']);
  $full_name = sanitizeInput($_POST['full_name']);
  $gender = sanitizeInput($_POST['gender']);
  $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);
  $temp = filter_var($_POST['temp'], FILTER_VALIDATE_FLOAT);
  $diagnosed = sanitizeInput($_POST['diagnosed']);
  $encounter = sanitizeInput($_POST['encounter']);
  $vaccinated = sanitizeInput($_POST['vaccinated']);
  $nationality = sanitizeInput($_POST['nationality']);

  // Validate required fields
  if (empty($email) || empty($full_name) || empty($gender) || $age === false || $temp === false || 
      empty($diagnosed) || empty($encounter) || empty($vaccinated) || empty($nationality)) {
    $error_message = 'Please fill in all required fields.';
  } 
  // Validate email format
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Please enter a valid email address.';
  }
  // Validate enum values
  elseif (!in_array($diagnosed, ['Yes', 'No']) || !in_array($encounter, ['Yes', 'No']) || !in_array($vaccinated, ['Yes', 'No'])) {
    $error_message = 'Invalid selection values. Please select Yes or No for health questions.';
  }
  // Validate age and temperature ranges
  elseif ($age < 0 || $age > 150) {
    $error_message = 'Please enter a valid age between 0 and 150.';
  }
  elseif ($temp < 30.0 || $temp > 50.0) {
    $error_message = 'Please enter a realistic temperature between 30°C and 50°C.';
  } else {

    // Convert to uppercase for database ENUM compatibility
    $diagnosed = strtoupper($diagnosed);
    $encounter = strtoupper($encounter);
    $vaccinated = strtoupper($vaccinated);
    
    // Update data using prepared statement
    $update_sql = "UPDATE records SET email=?, full_name=?, gender=?, age=?, temp=?, diagnosed=?, encountered=?, vaccinated=?, nationality=? WHERE id=?";
    $update_stmt = mysqli_prepare($conn, $update_sql);

    if ($update_stmt) {
      mysqli_stmt_bind_param($update_stmt, "sssidssssi", $email, $full_name, $gender, $age, $temp, $diagnosed, $encounter, $vaccinated, $nationality, $id);
      
      if (mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        $success_message = 'Health record updated successfully!';
        // Redirect after successful update
        header('Location: dashboard_admin.php?updated=1');
        exit();
      } else {
        mysqli_stmt_close($update_stmt);
        logSecurityEvent('Database error in update.php: ' . mysqli_error($conn));
        $error_message = 'Database update error. Please try again.';
      }
    } else {
      logSecurityEvent('Prepared statement failed in update.php: ' . mysqli_error($conn));
      $error_message = 'Database preparation error. Please try again.';
    }
  }
  } // End CSRF validation else block
}

// Include the header for consistent navigation after form processing
include './header.php';
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
        <span>Update</span>
      </div>
    </div>

    <div class="form-header">
      <div class="form-icon">
        <i class="fa-solid fa-pen-to-square"></i>
      </div>
      <h1>Update Health Declaration</h1>
      <p class="form-subtitle">Update the health record information - All fields are required</p>
      
      <!-- Record Info Display -->
      <div class="record-info">
        <span class="record-badge">
          <i class="fa-solid fa-id-badge"></i>
          Record ID: <?php echo htmlspecialchars($id); ?>
        </span>
      </div>
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
                   value="<?php echo htmlspecialchars($email); ?>">
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
                   value="<?php echo htmlspecialchars($full_name); ?>">
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
              <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo ($gender === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="InputAge" class="form-label">
              <i class="fa-solid fa-calendar"></i>
              Age *
            </label>
            <input type="number" class="form-control" id="InputAge" name="age" 
                   placeholder="Enter your age" min="0" max="150" required
                   value="<?php echo htmlspecialchars($age); ?>">
            <div class="field-validation" id="age-validation"></div>
          </div>

          <div class="form-group">
            <label for="InputNationality" class="form-label">
              <i class="fa-solid fa-globe"></i>
              Nationality *
            </label>
            <input type="text" class="form-control" id="InputNationality" name="nationality" 
                   placeholder="Enter your nationality" required
                   value="<?php echo htmlspecialchars($nationality); ?>">
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
                   value="<?php echo htmlspecialchars($temp); ?>">
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
                       <?php echo (strtoupper($diagnosed) === 'YES') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="diagnosed" value="No" required
                       <?php echo (strtoupper($diagnosed) === 'NO') ? 'checked' : ''; ?>>
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
                       <?php echo (strtoupper($encounter) === 'YES') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="encounter" value="No" required
                       <?php echo (strtoupper($encounter) === 'NO') ? 'checked' : ''; ?>>
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
                       <?php echo (strtoupper($vaccinated) === 'YES') ? 'checked' : ''; ?>>
                <span class="radio-custom"></span>
                <span class="radio-text">Yes</span>
              </label>
              <label class="radio-option">
                <input type="radio" name="vaccinated" value="No" required
                       <?php echo (strtoupper($vaccinated) === 'NO') ? 'checked' : ''; ?>>
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
          <i class="fa-solid fa-save"></i>
          <span>Update Health Declaration</span>
        </button>
        <a href="dashboard_admin.php" class="btn btn-secondary">
          <i class="fa-solid fa-arrow-left"></i>
          <span>Cancel</span>
        </a>
      </div>
    </form>
  </div>
</main>

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
  
  // Initial temperature status check (since we have pre-filled data)
  if (tempInput.value) {
    updateTemperatureStatus(parseFloat(tempInput.value));
  }
  
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
      updateTemperatureStatus(temp);
    });
  }
  
  function updateTemperatureStatus(temp) {
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
  }
  
  function setupFormSubmission() {
    form.addEventListener('submit', function(e) {
      console.log('Update form submission triggered');
      
      // Add a hidden input to ensure submit value is sent even if button is disabled
      const hiddenSubmit = document.createElement('input');
      hiddenSubmit.type = 'hidden';
      hiddenSubmit.name = 'submit';
      hiddenSubmit.value = 'submit';
      form.appendChild(hiddenSubmit);
      
      // Show loading state
      submitBtn.classList.add('loading');
      // Don't disable the button immediately to ensure its value is submitted
      setTimeout(() => {
        submitBtn.disabled = true;
      }, 100);
      
      const icon = submitBtn.querySelector('i');
      const text = submitBtn.querySelector('span');
      
      icon.className = 'fa-solid fa-spinner fa-spin';
      text.textContent = 'Updating...';
      
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
});
</script>

</body>
</html>
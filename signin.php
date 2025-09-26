<?php
// Include security functions and initialize session
include_once './includes/security.inc.php';
initializeSecureSession();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}

// Handle error messages with better UX
$error_message = '';
$error_type = '';
if (isset($_GET['error'])) {
    $error_type = $_GET['error'];
    switch ($error_type) {
        case 'emptyfields':
            $error_message = 'Please fill in all required fields.';
            break;
        case 'sqlerror':
            $error_message = 'A system error occurred. Please try again later.';
            break;
        case 'wrongpwd':
            $error_message = 'Invalid username/email or password.';
            break;
        case 'nousers':
            $error_message = 'Invalid username/email or password.';
            break;
        case 'csrftoken':
            $error_message = 'Security token expired. Please try again.';
            break;
        case 'ratelimit':
            $error_message = 'Too many login attempts. Please wait before trying again.';
            break;
        case 'sessionexpired':
            $error_message = 'Your session has expired. Please sign in again.';
            break;
        case 'wronglogin':
            $error_message = 'Invalid username/email or password.';
            break;
        default:
            $error_message = 'An error occurred. Please try again.';
    }
}

// Handle success messages
$success_message = '';
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $success_message = 'Account created successfully! Please sign in with your credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Sign in to COVID-19 Health Declaration System" />
  <link rel="stylesheet" href="./styles/signin.css" />
  <title>Sign In - COVID-19 Health Declaration</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <!-- Icons -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
</head>

<body>
  <main>
    <div id="wrapper">
      <div id="left">
        <div id="signin">
          <div class="logo">
            <i class="fa-solid fa-virus"></i>
            <div class="logo-text">
              <h2>Welcome Back</h2>
              <p>Sign in to your account</p>
            </div>
          </div>
          
          <!-- Success Message -->
          <?php if ($success_message): ?>
            <div class="alert alert-success animate-slide-down">
              <i class="fa-solid fa-check-circle"></i>
              <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
          <?php endif; ?>
          
          <!-- Error Message -->
          <?php if ($error_message): ?>
            <div class="alert alert-error animate-shake">
              <i class="fa-solid fa-exclamation-triangle"></i>
              <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
          <?php endif; ?>
          
          <form action="./includes/login.inc.php" method="post" id="signinForm" novalidate>
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
              <label for="mailuid">Username or Email</label>
              <input type="text" 
                     class="text-input" 
                     id="mailuid"
                     name="mailuid" 
                     placeholder="Enter your username or email"
                     value="<?php echo isset($_GET['uid']) ? htmlspecialchars($_GET['uid']) : ''; ?>"
                     required />
              <div class="field-validation" id="mailuid-validation"></div>
            </div>
            
            <div class="form-group">
              <label for="pwd">Password</label>
              <div class="password-input-wrapper">
                <input type="password" 
                       class="text-input" 
                       id="pwd"
                       name="pwd" 
                       placeholder="Enter your password"
                       required />
                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
              <div class="field-validation" id="pwd-validation"></div>
            </div>
            
            <button type="submit" class="primary-btn" name="login-submit" id="submitBtn">
              <i class="fa-solid fa-sign-in-alt"></i>
              <span>Sign In</span>
            </button>
          </form>
          
          <div class="links">
            <a href="#" class="forgot-password">Forgot Password?</a>
          </div>
          
          <div class="divider">
            <hr class="bar" />
            <span>or</span>
            <hr class="bar" />
          </div>
          
          <a href="./signup.php" class="secondary-btn">
            <i class="fa-solid fa-user-plus"></i>
            Create New Account
          </a>
          
          <div class="back-to-home">
            <a href="./index.php">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Home
            </a>
          </div>
        </div>
        <footer id="main-footer">
          <p>&copy; 2025 COVID-19 Health Declaration System</p>
          <div class="footer-links">
            <a href="#">Terms of Use</a>
            <span>|</span>
            <a href="#">Privacy Policy</a>
          </div>
        </footer>
      </div>
      <div id="right">
        <div id="showcase">
          <div class="showcase-content">
            <div class="showcase-badge">
              <i class="fa-solid fa-shield-check"></i>
              Secure Health Management
            </div>
            <h1 class="showcase-text">
              <strong>COVID-19</strong>
              <span>Health Declaration System</span>
            </h1>
            <p class="showcase-subtitle">
              Track your health status securely and efficiently with our comprehensive health declaration platform.
            </p>
            <div class="showcase-features">
              <div class="feature">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Easy Health Tracking</span>
              </div>
              <div class="feature">
                <i class="fa-solid fa-chart-line"></i>
                <span>Health Analytics</span>
              </div>
              <div class="feature">
                <i class="fa-solid fa-lock"></i>
                <span>Secure & Private</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('signinForm');
      const submitBtn = document.getElementById('submitBtn');
      const passwordToggle = document.getElementById('passwordToggle');
      const passwordInput = document.getElementById('pwd');
      const maiLuidInput = document.getElementById('mailuid');
      
      // Password visibility toggle
      passwordToggle.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        if (type === 'text') {
          icon.className = 'fa-solid fa-eye-slash';
          this.setAttribute('aria-label', 'Hide password');
        } else {
          icon.className = 'fa-solid fa-eye';
          this.setAttribute('aria-label', 'Show password');
        }
      });
      
      // Form validation
      function validateField(field) {
        const value = field.value.trim();
        const validationDiv = document.getElementById(field.id + '-validation');
        
        if (!validationDiv) return true;
        
        let isValid = true;
        let message = '';
        
        if (field.id === 'mailuid') {
          if (!value) {
            isValid = false;
            message = 'Username or email is required';
          } else if (value.length < 2) {
            isValid = false;
            message = 'Please enter a valid username or email';
          } else {
            message = 'Looks good ✓';
          }
        } else if (field.id === 'pwd') {
          if (!value) {
            isValid = false;
            message = 'Password is required';
          } else if (value.length < 3) {
            isValid = false;
            message = 'Password is too short';
          } else {
            message = 'Password entered ✓';
          }
        }
        
        // Update field appearance and validation message
        field.classList.toggle('valid', isValid);
        field.classList.toggle('invalid', !isValid);
        
        validationDiv.textContent = message;
        validationDiv.className = `field-validation ${isValid ? 'valid' : 'invalid'}`;
        
        return isValid;
      }
      
      // Real-time validation
      [maiLuidInput, passwordInput].forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
          // Clear validation on input
          field.classList.remove('valid', 'invalid');
          const validationDiv = document.getElementById(field.id + '-validation');
          if (validationDiv) {
            validationDiv.textContent = '';
            validationDiv.className = 'field-validation';
          }
        });
      });
      
      // Form submission
      form.addEventListener('submit', function(e) {
        const maiLuidValid = validateField(maiLuidInput);
        const passwordValid = validateField(passwordInput);
        
        // Ensure the submit button name is included in form data
        if (!form.querySelector('input[name="login-submit"]')) {
          const hiddenSubmit = document.createElement('input');
          hiddenSubmit.type = 'hidden';
          hiddenSubmit.name = 'login-submit';
          hiddenSubmit.value = '1';
          form.appendChild(hiddenSubmit);
        }
        
        if (!maiLuidValid || !passwordValid) {
          e.preventDefault();
          
          // Shake invalid fields
          [maiLuidInput, passwordInput].forEach(field => {
            if (field.classList.contains('invalid')) {
              field.classList.add('animate-shake');
              setTimeout(() => field.classList.remove('animate-shake'), 500);
            }
          });
          
          return;
        }
        
        // Show loading state
        setTimeout(() => {
          submitBtn.disabled = true;
          submitBtn.classList.add('loading');
          
          const icon = submitBtn.querySelector('i');
          const text = submitBtn.querySelector('span');
          
          icon.className = 'fa-solid fa-spinner fa-spin';
          text.textContent = 'Signing In...';
        }, 100);
        
        // Allow form to submit
        return true;
      });
      
      // Auto-dismiss alerts
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.classList.add('fade-out');
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });
    });
  </script>
</body>
</html>
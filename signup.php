<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - COVID Health Declaration</title>
  <link rel="stylesheet" href="./styles/signup.css" />
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
  <?php
  include_once './includes/security.inc.php';

  $errors = [];
  $success = false;
  $form_data = ['username' => '', 'email' => ''];

  // Process URL parameters
  if (isset($_GET['error']) || isset($_GET['signup'])) {
    switch ($_GET['error'] ?? '') {
      case 'emptyfields':
        $errors[] = 'Please fill in all required fields.';
        break;
      case 'invaliduidemail':
        $errors[] = 'Please enter a valid username and email address.';
        break;
      case 'invaliduid':
        $errors[] = 'Username must be at least 3 characters and contain only letters, numbers, and underscores.';
        break;
      case 'invalidmail':
        $errors[] = 'Please enter a valid email address.';
        break;
      case 'passwordCheck':
        $errors[] = 'Passwords do not match. Please try again.';
        break;
      case 'usertaken':
        $errors[] = 'Username is already taken. Please choose another.';
        break;
      case 'sqlerror':
        $errors[] = 'Registration failed due to a system error. Please try again.';
        break;
    }
    
    if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
      $success = true;
    }
    
    // Preserve form data if available
    if (isset($_GET['username'])) $form_data['username'] = htmlspecialchars($_GET['username']);
    if (isset($_GET['email'])) $form_data['email'] = htmlspecialchars($_GET['email']);
  }
  ?>

  <main class="signup-container">
    <div class="signup-wrapper">
      <div class="signup-header">
        <div class="logo">
          <i class="fa-solid fa-virus"></i>
          <span>COVID Health</span>
        </div>
        <h1>Create Account</h1>
        <p class="subtitle">Join our health declaration system to help keep everyone safe</p>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success" role="alert" aria-live="polite">
          <i class="fa-solid fa-circle-check"></i>
          <div>
            <strong>Account created successfully!</strong>
            <p>You can now <a href="signin.php">sign in</a> to access your dashboard.</p>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <div>
            <strong>Registration failed:</strong>
            <?php foreach ($errors as $error): ?>
              <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <form action="./includes/signup.inc.php" method="post" class="signup-form" id="signupForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-user input-icon"></i>
            <input 
              type="text" 
              id="username" 
              name="username" 
              value="<?php echo $form_data['username']; ?>"
              placeholder="Choose a unique username"
              autocomplete="username"
              required
              minlength="3"
              pattern="[a-zA-Z0-9_]+"
              aria-describedby="username-validation"
            />
          </div>
          <div id="username-validation" class="field-validation" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input 
              type="email" 
              id="email" 
              name="email" 
              value="<?php echo $form_data['email']; ?>"
              placeholder="your@email.com"
              autocomplete="email"
              required
              aria-describedby="email-validation"
            />
          </div>
          <div id="email-validation" class="field-validation" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock input-icon"></i>
            <input 
              type="password" 
              id="password" 
              name="password" 
              placeholder="Create a strong password"
              autocomplete="new-password"
              required
              minlength="6"
              aria-describedby="password-validation password-help"
            />
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <div id="password-help" class="field-help">At least 6 characters</div>
          <div id="password-validation" class="field-validation" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="password_repeat">Confirm Password</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock input-icon"></i>
            <input 
              type="password" 
              id="password_repeat" 
              name="password_repeat" 
              placeholder="Confirm your password"
              autocomplete="new-password"
              required
              minlength="6"
              aria-describedby="password-repeat-validation"
            />
            <button type="button" class="password-toggle" id="passwordRepeatToggle" aria-label="Show password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <div id="password-repeat-validation" class="field-validation" aria-live="polite"></div>
        </div>

        <button type="submit" name="signup-submit" class="submit-btn" id="submitBtn">
          <i class="fa-solid fa-user-plus"></i>
          <span>Create Account</span>
        </button>
      </form>
      <div class="signup-footer">
        <div class="divider">
          <span>Already registered?</span>
        </div>
        <a href="./signin.php" class="secondary-link">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          Sign in to your account
        </a>
        
        <div class="features-preview">
          <h3>Why join us?</h3>
          <div class="features-grid">
            <div class="feature-item">
              <i class="fa-solid fa-shield-halved"></i>
              <span>Secure health tracking</span>
            </div>
            <div class="feature-item">
              <i class="fa-solid fa-clock"></i>
              <span>Quick daily declarations</span>
            </div>
            <div class="feature-item">
              <i class="fa-solid fa-chart-line"></i>
              <span>Personal health insights</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('signupForm');
      const submitBtn = document.getElementById('submitBtn');
      const passwordToggle = document.getElementById('passwordToggle');
      const passwordRepeatToggle = document.getElementById('passwordRepeatToggle');
      const passwordInput = document.getElementById('password');
      const passwordRepeatInput = document.getElementById('password_repeat');
      const usernameInput = document.getElementById('username');
      const emailInput = document.getElementById('email');
      
      // Password visibility toggles
      [passwordToggle, passwordRepeatToggle].forEach((toggle, index) => {
        const input = index === 0 ? passwordInput : passwordRepeatInput;
        
        toggle.addEventListener('click', function() {
          const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
          input.setAttribute('type', type);
          
          const icon = this.querySelector('i');
          if (type === 'text') {
            icon.className = 'fa-solid fa-eye-slash';
            this.setAttribute('aria-label', 'Hide password');
          } else {
            icon.className = 'fa-solid fa-eye';
            this.setAttribute('aria-label', 'Show password');
          }
        });
      });
      
      // Form validation
      function validateField(field) {
        const value = field.value.trim();
        const validationDiv = document.getElementById(field.id + '-validation');
        
        if (!validationDiv) return true;
        
        let isValid = true;
        let message = '';
        
        switch (field.id) {
          case 'username':
            if (!value) {
              isValid = false;
              message = 'Username is required';
            } else if (value.length < 3) {
              isValid = false;
              message = 'Username must be at least 3 characters';
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
              isValid = false;
              message = 'Username can only contain letters, numbers, and underscores';
            } else {
              message = 'Username looks good ✓';
            }
            break;
            
          case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!value) {
              isValid = false;
              message = 'Email address is required';
            } else if (!emailRegex.test(value)) {
              isValid = false;
              message = 'Please enter a valid email address';
            } else {
              message = 'Email looks good ✓';
            }
            break;
            
          case 'password':
            if (!value) {
              isValid = false;
              message = 'Password is required';
            } else if (value.length < 6) {
              isValid = false;
              message = 'Password must be at least 6 characters';
            } else {
              message = 'Password is strong ✓';
            }
            
            // Also validate password repeat if it has a value
            if (passwordRepeatInput.value) {
              validateField(passwordRepeatInput);
            }
            break;
            
          case 'password_repeat':
            if (!value) {
              isValid = false;
              message = 'Please confirm your password';
            } else if (value !== passwordInput.value) {
              isValid = false;
              message = 'Passwords do not match';
            } else {
              message = 'Passwords match ✓';
            }
            break;
        }
        
        // Update field appearance and validation message
        field.classList.toggle('valid', isValid);
        field.classList.toggle('invalid', !isValid);
        
        validationDiv.textContent = message;
        validationDiv.className = `field-validation ${isValid ? 'valid' : 'invalid'}`;
        
        return isValid;
      }
      
      // Real-time validation
      [usernameInput, emailInput, passwordInput, passwordRepeatInput].forEach(field => {
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
        const usernameValid = validateField(usernameInput);
        const emailValid = validateField(emailInput);
        const passwordValid = validateField(passwordInput);
        const passwordRepeatValid = validateField(passwordRepeatInput);
        
        if (!usernameValid || !emailValid || !passwordValid || !passwordRepeatValid) {
          e.preventDefault();
          
          // Shake invalid fields
          [usernameInput, emailInput, passwordInput, passwordRepeatInput].forEach(field => {
            if (field.classList.contains('invalid')) {
              field.classList.add('animate-shake');
              setTimeout(() => field.classList.remove('animate-shake'), 500);
            }
          });
          
          return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        
        const icon = submitBtn.querySelector('i');
        const text = submitBtn.querySelector('span');
        
        icon.className = 'fa-solid fa-spinner fa-spin';
        text.textContent = 'Creating Account...';
        
        // Allow form to submit
        return true;
      });
      
      // Auto-dismiss alerts
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.classList.add('fade-out');
          setTimeout(() => alert.remove(), 300);
        }, 6000);
      });
    });
  </script>
</body>

</html>
<?php
// Include the database connection
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

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
if (isset($_POST['submit'])) {
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    logSecurityEvent('CSRF token validation failed in update.php for record ID: ' . $id);
    die('CSRF token validation failed');
  }

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
    die('All fields are required and must be valid');
  }

  // Validate email format
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Invalid email format');
  }

  // Validate enum values
  if (!in_array($diagnosed, ['Yes', 'No']) || !in_array($encounter, ['Yes', 'No']) || !in_array($vaccinated, ['Yes', 'No'])) {
    die('Invalid selection values');
  }

  // Validate age and temperature ranges
  if ($age < 0 || $age > 150) {
    die('Invalid age range');
  }
  if ($temp < 30.0 || $temp > 50.0) {
    die('Invalid temperature range');
  }

  // Update data using prepared statement
  $update_sql = "UPDATE records SET email=?, full_name=?, gender=?, age=?, temp=?, diagnosed=?, encountered=?, vaccinated=?, nationality=? WHERE id=?";
  $update_stmt = mysqli_prepare($conn, $update_sql);

  if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, "sssidssssi", $email, $full_name, $gender, $age, $temp, $diagnosed, $encounter, $vaccinated, $nationality, $id);
    
    if (mysqli_stmt_execute($update_stmt)) {
      mysqli_stmt_close($update_stmt);
      header('Location: dashboard_admin.php');
      exit();
    } else {
      mysqli_stmt_close($update_stmt);
      logSecurityEvent('Database error in update.php: ' . mysqli_error($conn));
      die('Database update error');
    }
  } else {
    logSecurityEvent('Prepared statement failed in update.php: ' . mysqli_error($conn));
    die('Database preparation error');
  }
}

// Fetch existing user data
$fetch_query = "SELECT * FROM records WHERE id = '$id'";
$fetch_result = mysqli_query($conn, $fetch_query);

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
  echo 'User not found.';
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="./styles/update.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous" defer></script>
  <!-- Icon -->
  <script src="https://kit.fontawesome.com/444873800b.js" crossorigin="anonymous"></script>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Favico -->
  <link rel="icon" type="image/x-icon" href='./assets/images/virus-solid.svg'>
</head>

<body>

  <main class="wrapper-form">
    <h2 class="update-title"><i class="fa-regular fa-pen-to-square"></i>Update</h2>
    <form method="post" action="">
      <!-- CSRF Protection -->
      <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
      
      <div class="mb-3">
        <label for="InputEmail" class="form-label">Email address</label>
        <input type="email" class="form-control" id="InputEmail" name="email" aria-describedby="emailHelp" value="<?php echo sanitizeOutput($email); ?>" required>
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
      </div>
      <div class="mb-3">
        <label for="InputFullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="InputFullName" name="full_name" value="<?php echo $full_name ?>">
      </div>
      <div class="mb-3">
        <label for="InputGender" class="form-label">Gender</label>
        <select class="form-select" id="InputGender" name="gender">
          <option value="Male" <?php if ($gender === 'Male') echo 'selected' ?>>Male</option>
          <option value="Female" <?php if ($gender === 'Female') echo 'selected' ?>>Female</option>
          <option value="Other" <?php if ($gender === 'Other') echo 'selected' ?>>Other</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="InputAge" class="form-label">Age</label>
        <input type="number" class="form-control" id="InputAge" name="age" value="<?php echo $age ?>">
      </div>
      <div class="mb-3">
        <label for="InputTemp" class="form-label">Body Temperature (°C)</label>
        <input type="number" step="0.01" class="form-control" id="InputTemp" name="temp" value="<?php echo $temp ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Diagnosed</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedYes" value="Yes" <?php if ($diagnosed === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="DiagnosedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="diagnosed" id="DiagnosedNo" value="No" <?php if ($diagnosed === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="DiagnosedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">COVID-19 Encounter</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterYes" value="Yes" <?php if ($encounter === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="EncounterYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="encounter" id="EncounterNo" value="No" <?php if ($encounter === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="EncounterNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Vaccinated</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedYes" value="Yes" <?php if ($vaccinated === 'YES') echo 'checked' ?>>
          <label class="form-check-label" for="VaccinatedYes">
            Yes
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="vaccinated" id="VaccinatedNo" value="No" <?php if ($vaccinated === 'NO') echo 'checked' ?>>
          <label class="form-check-label" for="VaccinatedNo">
            No
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label for="InputNationality" class="form-label">Nationality</label>
        <input type="text" class="form-control" id="InputNationality" name="nationality" value="<?php echo $nationality ?>">
      </div>
      <button type="submit" class="btn btn-primary" name="submit">Submit</button>
    </form>
  </main>

</body>

</html>
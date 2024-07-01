<?php
// Include the database connection
include './includes/dbconn.inc.php';

// Fetch data from the database
$query = "SELECT * FROM records";
$result = mysqli_query($conn, $query);

// Counters for various categories
$encounterYesCount = 0;
$vaccinatedYesCount = 0;
$feverCount = 0;
$adultCount = 0;
$minorCount = 0;
$foreignerCount = 0;

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    if ($row['encountered'] == 'YES') {
      $encounterYesCount++;
    }

    if ($row['vaccinated'] == 'YES') {
      $vaccinatedYesCount++;
    }

    if ($row['temp'] > 38.00) {
      $feverCount++;
    }

    if ($row['age'] >= 18) {
      $adultCount++;
    } else {
      $minorCount++;
    }

    if ($row['nationality'] != 'Philippines') {
      $foreignerCount++;
    }
  }
}
?>
<?php include "./header.php"; ?>

<body>
  <main>
    <div class="wrapper">
      <div class="status status-grid">
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $encounterYesCount; ?></p>
          <p class="status-grid-text">
            COVID-19 ENCOUNTER
          </p>
        </div>
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $vaccinatedYesCount; ?></p>
          <p class="status-grid-text">
            VACCINATED
          </p>
        </div>
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $feverCount; ?></p>
          <p class="status-grid-text">
            FEVER
          </p>
        </div>
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $adultCount; ?></p>
          <p class="status-grid-text">
            ADULT
          </p>
        </div>
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $minorCount; ?></p>
          <p class="status-grid-text">
            MINOR
          </p>
        </div>
        <div class="status-grid-item">
          <p class="status-grid-count"><?php echo $foreignerCount; ?></p>
          <p class="status-grid-text">
            FOREIGNER
          </p>
        </div>
      </div>
      <!-- <h1 class="">Dashboard</h1> -->
      <table>
        <!-- <caption>
          Dashboard
        </caption> -->
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
            <th>Operations</th>
        </thead>

        <tbody>
          <?php

          $query = "SELECT * FROM records";
          $result = mysqli_query($conn, $query);

          if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
              echo '<tr>';
              echo '<td data-cell="email">' . $row['email'] . '</td>';
              echo '<td data-cell="name">' . $row['full_name'] . '</td>';
              echo '<td data-cell="gender">' . $row['gender'] . '</td>';
              echo '<td data-cell="age">' . $row['age'] . '</td>';
              echo '<td data-cell="temperature">' . $row['temp'] . '</td>';
              echo '<td data-cell="diagnosed">' . $row['diagnosed'] . '</td>';
              echo '<td data-cell="encountered">' . $row['encountered'] . '</td>';
              echo '<td data-cell="vaccinated">' . $row['vaccinated'] . '</td>';
              echo '<td data-cell="nationality">' . $row['nationality'] . '</td>';
              echo '<td data-cell="operation">';
              echo '<a href="./add.php"><i class="fa-solid fa-user-plus"></i></a>';
              echo '<a href="./update.php?id=' . $row['id'] . '"><i class="fa-regular fa-pen-to-square"></i></a>';
              echo '<a href="./delete.php?id=' . $row['id'] . '"><i class="fa-regular fa-trash-can"></i></a>';
              echo '</td>';
              echo '</tr>';
            }
          }
          ?>

        </tbody>
      </table>
    </div>
  </main>
</body>

</html>
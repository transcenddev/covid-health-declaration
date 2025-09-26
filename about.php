<?php
include "./header.php";
?>

<main>
  <section class="about-hero section">
    <div class="about-container">
      <div class="about-header">
        <div class="hero-badge">
          <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
          <span>Health Screening Information</span>
        </div>
        <h1>About Health Screening</h1>
        <p class="about-subtitle">Simple daily health checks required for facility access.</p>
      </div>
    </div>
  </section>

  <section class="about-content section">
    <div class="about-container">
      <div class="about-grid">
        <div class="about-card highlight-card">
          <div class="card-icon">
            <i class="fa-solid fa-clipboard-check"></i>
          </div>
          <h3>What This Is</h3>
          <p>A quick daily health screening form that takes under 2 minutes to complete. Required before entering the facility to help keep everyone safe.</p>
          <div class="card-highlight">
            <i class="fa-solid fa-clock"></i>
            <span>Under 2 minutes</span>
          </div>
        </div>

        <div class="about-card">
          <div class="card-icon">
            <i class="fa-solid fa-shield-heart"></i>
          </div>
          <h3>Your Privacy</h3>
          <p>Your health information is encrypted and kept private. Data is only used for health screening purposes and is not shared with unauthorized parties.</p>
          <div class="card-highlight">
            <i class="fa-solid fa-lock"></i>
            <span>Fully encrypted</span>
          </div>
        </div>

        <div class="about-card">
          <div class="card-icon">
            <i class="fa-solid fa-bolt"></i>
          </div>
          <h3>Quick & Simple</h3>
          <p>Answer a few health questions, record your temperature, and receive immediate clearance. No complicated setup or account required.</p>
          <div class="card-highlight">
            <i class="fa-solid fa-user-slash"></i>
            <span>No account needed</span>
          </div>
        </div>
      </div>

      <div class="about-contact">
        <div class="info-header">
          <i class="fa-solid fa-info-circle"></i>
          <h2>Important Information</h2>
        </div>
        
        <div class="info-cards">
          <div class="info-card">
            <div class="info-icon">
              <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="info-content">
              <h4>Daily Requirement</h4>
              <p>This health screening is required for all facility access. Complete your screening daily before arrival.</p>
            </div>
          </div>
          
          <div class="info-card">
            <div class="info-icon">
              <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div class="info-content">
              <h4>Health Concerns</h4>
              <p>If you have health concerns or symptoms, please consult with healthcare professionals and follow facility health protocols.</p>
            </div>
          </div>
          
          <div class="info-card">
            <div class="info-icon">
              <i class="fa-solid fa-database"></i>
            </div>
            <div class="info-content">
              <h4>Data Storage</h4>
              <p>Your health data is temporarily stored for contact tracing purposes and is automatically removed according to health department guidelines.</p>
            </div>
          </div>
        </div>
        
        <div class="cta-section">
          <div class="cta-content">
            <h3>Ready to complete your health check?</h3>
            <p>Start your daily screening now - it only takes 2 minutes</p>
            <a href="add.php" class="btn btn-primary btn-large">
              <i class="fa-solid fa-clipboard-check"></i>
              Start Health Check
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<footer>
  &copy; 2025 Health Declaration System
</footer>

</body>
</html>
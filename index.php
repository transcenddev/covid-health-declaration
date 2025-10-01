 <?php
  include "./header.php";
  ?>

 <main>
   <!-- Minimal Hero Section -->
   <section class="hero-section section">
     <div class="container">
       <div class="hero-content">
         <div class="hero-badge">
           <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
           Official Health Declaration
         </div>
         <h1>COVID-19 Health Check</h1>
         <p class="hero-subtitle">Required for facility access • Under 2 minutes • Secure & Private</p>
         
         <div class="hero-cta">
           <a href="./add.php" class="btn btn-hero btn-primary">
             <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
             Start Health Check
           </a>
         </div>
       </div>
     </div>
   </section>
 </main>

 <!-- Floating Action Button for Mobile -->
 <div class="fab-container">
   <?php if (!isset($_SESSION['userId'])): ?>
     <a href="./add.php" class="fab" aria-label="Start Health Check">
       <i class="fa-solid fa-plus" aria-hidden="true"></i>
       <span class="fab-text">Start Health Check</span>
     </a>
   <?php else: ?>
     <a href="./add.php" class="fab" aria-label="New Health Declaration">
       <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
       <span class="fab-text">New Check</span>
     </a>
   <?php endif; ?>
 </div>

 <!-- <footer class="main-footer">
   <div class="container">
     <div class="footer-bottom">
       <p>&copy; 2025 Health Declaration System</p>
     </div>
   </div>
 </footer> -->
 </body>

 </html>
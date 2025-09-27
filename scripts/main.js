// Enhanced Navigation Functionality
class NavigationManager {
  constructor() {
    this.navbar = document.getElementById("navbar");
    this.mobileMenu = document.getElementById("mobileMenu");
    this.navToggle = document.getElementById("navToggle");
    this.userMenuBtn = document.getElementById("userMenuBtn");
    this.userDropdown = document.getElementById("userDropdown");

    this.init();
  }

  init() {
    this.setupScrollEffect();
    this.setupMobileMenu();
    this.setupUserDropdown();
    this.setupKeyboardNavigation();
    this.setupClickOutside();
  }

  setupScrollEffect() {
    let lastScrollY = window.scrollY;
    let ticking = false;

    const updateNavbar = () => {
      const currentScrollY = window.scrollY;
      const scrollDirection = currentScrollY > lastScrollY ? "down" : "up";
      const scrollProgress = Math.min(currentScrollY / 100, 1); // Normalize to 0-1

      // Dynamic transparency based on scroll position
      if (currentScrollY > 10) {
        this.navbar.classList.add("scrolled");

        // Add scroll direction class for additional styling
        this.navbar.classList.toggle("scroll-down", scrollDirection === "down");
        this.navbar.classList.toggle("scroll-up", scrollDirection === "up");

        // Dynamic blur and opacity based on scroll progress
        const blurAmount = Math.max(10, Math.min(25, 10 + scrollProgress * 15));
        const opacity = Math.max(
          0.85,
          Math.min(0.95, 0.1 + scrollProgress * 0.85)
        );

        this.navbar.style.backdropFilter = `blur(${blurAmount}px)`;
        this.navbar.style.webkitBackdropFilter = `blur(${blurAmount}px)`;
        this.navbar.style.setProperty("--dynamic-bg-opacity", opacity);
      } else {
        this.navbar.classList.remove("scrolled", "scroll-down", "scroll-up");
        this.navbar.style.backdropFilter = "blur(10px)";
        this.navbar.style.webkitBackdropFilter = "blur(10px)";
        this.navbar.style.setProperty("--dynamic-bg-opacity", 0.1);
      }

      lastScrollY = currentScrollY;
      ticking = false;
    };

    const requestTick = () => {
      if (!ticking) {
        requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    };

    window.addEventListener("scroll", requestTick, { passive: true });

    // Initial call
    updateNavbar();
  }

  setupMobileMenu() {
    if (!this.navToggle || !this.mobileMenu) return;

    this.navToggle.addEventListener("click", () => {
      this.toggleMobileMenu();
    });

    // Close mobile menu when clicking on a link
    const mobileLinks = this.mobileMenu.querySelectorAll(".nav-mobile-link");
    mobileLinks.forEach((link) => {
      link.addEventListener("click", () => {
        this.closeMobileMenu();
      });
    });

    // Handle escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.mobileMenu.classList.contains("show")) {
        this.closeMobileMenu();
      }
    });
  }

  setupUserDropdown() {
    if (!this.userMenuBtn || !this.userDropdown) return;

    this.userMenuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      this.toggleUserDropdown();
    });

    // Handle escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.userDropdown.classList.contains("show")) {
        this.closeUserDropdown();
      }
    });
  }

  setupKeyboardNavigation() {
    // Handle tab navigation for dropdown
    if (this.userDropdown) {
      const dropdownLinks =
        this.userDropdown.querySelectorAll(".nav-dropdown-link");

      dropdownLinks.forEach((link, index) => {
        link.addEventListener("keydown", (e) => {
          if (e.key === "ArrowDown") {
            e.preventDefault();
            const nextIndex = (index + 1) % dropdownLinks.length;
            dropdownLinks[nextIndex].focus();
          } else if (e.key === "ArrowUp") {
            e.preventDefault();
            const prevIndex =
              index === 0 ? dropdownLinks.length - 1 : index - 1;
            dropdownLinks[prevIndex].focus();
          }
        });
      });
    }
  }

  setupClickOutside() {
    document.addEventListener("click", (e) => {
      // Close user dropdown if clicking outside
      if (
        this.userDropdown &&
        !this.userMenuBtn.contains(e.target) &&
        !this.userDropdown.contains(e.target)
      ) {
        this.closeUserDropdown();
      }

      // Close mobile menu if clicking outside
      if (
        this.mobileMenu &&
        this.mobileMenu.classList.contains("show") &&
        !this.mobileMenu.contains(e.target) &&
        !this.navToggle.contains(e.target)
      ) {
        this.closeMobileMenu();
      }
    });
  }

  toggleMobileMenu() {
    const isOpen = this.mobileMenu.classList.contains("show");

    if (isOpen) {
      this.closeMobileMenu();
    } else {
      this.openMobileMenu();
    }
  }

  openMobileMenu() {
    this.mobileMenu.classList.add("show");
    this.navToggle.classList.add("active");
    this.navToggle.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";

    // Focus first menu item
    const firstLink = this.mobileMenu.querySelector(".nav-mobile-link");
    if (firstLink) {
      setTimeout(() => firstLink.focus(), 100);
    }
  }

  closeMobileMenu() {
    this.mobileMenu.classList.remove("show");
    this.navToggle.classList.remove("active");
    this.navToggle.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }

  toggleUserDropdown() {
    const isOpen = this.userDropdown.classList.contains("show");

    if (isOpen) {
      this.closeUserDropdown();
    } else {
      this.openUserDropdown();
    }
  }

  openUserDropdown() {
    this.userDropdown.classList.add("show");
    this.userMenuBtn.classList.add("active");
    this.userMenuBtn.setAttribute("aria-expanded", "true");

    // Focus first dropdown item
    const firstItem = this.userDropdown.querySelector(".nav-dropdown-link");
    if (firstItem) {
      setTimeout(() => firstItem.focus(), 100);
    }
  }

  closeUserDropdown() {
    this.userDropdown.classList.remove("show");
    this.userMenuBtn.classList.remove("active");
    this.userMenuBtn.setAttribute("aria-expanded", "false");
  }
}

// Dashboard Search Functionality
class DashboardSearchManager {
  constructor() {
    this.searchInput = document.getElementById("tableSearch");
    this.clearButton = document.getElementById("searchClear");
    this.searchResults = document.getElementById("resultsCounter");
    this.dataTable = document.querySelector(".data-table tbody");
    this.emptyResults = document.querySelector(".empty-search-results");

    console.log("Search elements found:", {
      searchInput: !!this.searchInput,
      clearButton: !!this.clearButton,
      searchResults: !!this.searchResults,
      dataTable: !!this.dataTable,
      emptyResults: !!this.emptyResults
    });

    this.allRows = [];
    this.filteredRows = [];
    this.isSearching = false;

    this.init();
  }

  init() {
    if (!this.searchInput) {
      console.log("Search input not found!");
      return;
    }

    console.log("Initializing search functionality...");
    this.cacheTableRows();
    this.setupEventListeners();
    this.updateResultsCount();
    console.log("Search functionality initialized with", this.allRows.length, "rows");
  }

  cacheTableRows() {
    // Use the correct table selector
    const tableBody = document.querySelector(".table-wrapper tbody");
    if (tableBody) {
      this.allRows = Array.from(tableBody.querySelectorAll("tr"));
      this.filteredRows = [...this.allRows];
    }
  }

  setupEventListeners() {
    // Real-time search as user types
    this.searchInput.addEventListener("input", (e) => {
      console.log("Input event triggered:", e.target.value);
      this.handleSearch(e.target.value);
    });

    // Clear search functionality
    if (this.clearButton) {
      this.clearButton.addEventListener("click", () => {
        console.log("Clear button clicked");
        this.clearSearch();
      });
    } else {
      console.warn("Clear button not found!");
    }

    // Handle enter key for search
    this.searchInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        this.searchInput.blur();
      }

      if (e.key === "Escape") {
        this.clearSearch();
      }
    });

    console.log("Event listeners set up successfully");
  }

  handleSearch(searchTerm) {
    const trimmedTerm = searchTerm.trim().toLowerCase();
    console.log("Searching for:", trimmedTerm);

    // Show/hide clear button
    if (this.clearButton) {
      this.clearButton.classList.toggle("show", trimmedTerm.length > 0);
    }

    if (trimmedTerm === "") {
      this.showAllRows();
      return;
    }

    this.isSearching = true;
    this.filterRows(trimmedTerm);
    this.updateResultsCount();
  }

  filterRows(searchTerm) {
    this.filteredRows = [];
    console.log("Filtering", this.allRows.length, "rows for term:", searchTerm);

    this.allRows.forEach((row) => {
      const email = this.getTextContent(row.cells[0]); // Email column
      const name = this.getTextContent(row.cells[1]); // Name column
      const nationality = this.getTextContent(row.cells[8]); // Nationality column

      const searchableText = `${email} ${name} ${nationality}`.toLowerCase();

      console.log("Row data:", { email, name, nationality, searchableText });

      if (searchableText.includes(searchTerm)) {
        row.style.display = "";
        this.filteredRows.push(row);
        console.log("Row matched!");
      } else {
        row.style.display = "none";
      }
    });

    console.log("Filtered results:", this.filteredRows.length, "out of", this.allRows.length);

    // Show empty state if no results
    if (this.emptyResults) {
      this.emptyResults.style.display =
        this.filteredRows.length === 0 ? "block" : "none";
    }
  }

  getTextContent(cell) {
    return cell ? cell.textContent.trim() : "";
  }

  showAllRows() {
    this.isSearching = false;
    this.filteredRows = [...this.allRows];

    this.allRows.forEach((row) => {
      row.style.display = "";
    });

    if (this.emptyResults) {
      this.emptyResults.style.display = "none";
    }

    this.updateResultsCount();
  }

  clearSearch() {
    this.searchInput.value = "";
    if (this.clearButton) {
      this.clearButton.classList.remove("show");
    }
    this.showAllRows();
    this.searchInput.focus();
  }

  updateResultsCount() {
    if (!this.searchResults) return;

    const totalRecords = this.allRows.length;
    const visibleRecords = this.filteredRows.length;

    if (this.isSearching && visibleRecords !== totalRecords) {
      this.searchResults.textContent = `(${visibleRecords} of ${totalRecords} records)`;
    } else {
      this.searchResults.textContent = `(${totalRecords} records)`;
    }
  }

  // Method to refresh search after data updates
  refresh() {
    this.cacheTableRows();
    if (this.searchInput.value.trim()) {
      this.handleSearch(this.searchInput.value);
    } else {
      this.updateResultsCount();
    }
  }
}

// Page Loading Indicator
class LoadingManager {
  constructor() {
    this.setupPageTransitions();
  }

  setupPageTransitions() {
    // Add loading class to navbar when navigating
    const navLinks = document.querySelectorAll(".nav-link, .nav-mobile-link");

    navLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        // Don't add loading for external links or same page
        if (
          link.target === "_blank" ||
          link.href === window.location.href ||
          link.getAttribute("href").startsWith("#")
        ) {
          return;
        }

        const navbar = document.getElementById("navbar");
        if (navbar) {
          navbar.classList.add("nav-loading");
        }
      });
    });
  }
}

// Active Page Detection
class ActivePageManager {
  constructor() {
    this.setActiveLinks();
  }

  setActiveLinks() {
    const currentPage =
      window.location.pathname.split("/").pop() || "index.php";
    const navLinks = document.querySelectorAll(".nav-link, .nav-mobile-link");

    navLinks.forEach((link) => {
      const linkPage = link.getAttribute("href");
      if (linkPage && linkPage.includes(currentPage)) {
        link.classList.add("active");
      }
    });
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new NavigationManager();
  new LoadingManager();
  new ActivePageManager();
  
  // Initialize search functionality if on dashboard page
  if (document.getElementById("tableSearch")) {
    const searchManager = new DashboardSearchManager();
    
    // Make search manager globally available for potential refresh needs
    window.dashboardSearch = searchManager;
  }
});

// Responsive table functionality (keep existing)
function ResponsiveCellHeaders(elmID) {
  try {
    var THarray = [];
    var table = document.getElementById(elmID);
    if (!table) return;

    var ths = table.getElementsByTagName("th");
    for (var i = 0; i < ths.length; i++) {
      var headingText = ths[i].innerHTML;
      THarray.push(headingText);
    }
    var styleElm = document.createElement("style"),
      styleSheet;
    document.head.appendChild(styleElm);
    styleSheet = styleElm.sheet;
    for (var i = 0; i < THarray.length; i++) {
      styleSheet.insertRule(
        "#" +
          elmID +
          " td:nth-child(" +
          (i + 1) +
          ')::before {content:"' +
          THarray[i] +
          ': ";}',
        styleSheet.cssRules.length
      );
    }
  } catch (e) {
    console.log("ResponsiveCellHeaders(): " + e);
  }
}

// Accessibility helper for tables
function AddTableARIA() {
  try {
    var allTables = document.querySelectorAll("table");
    for (var i = 0; i < allTables.length; i++) {
      allTables[i].setAttribute("role", "table");
    }
    var allRowGroups = document.querySelectorAll("thead, tbody, tfoot");
    for (var i = 0; i < allRowGroups.length; i++) {
      allRowGroups[i].setAttribute("role", "rowgroup");
    }
    var allRows = document.querySelectorAll("tr");
    for (var i = 0; i < allRows.length; i++) {
      allRows[i].setAttribute("role", "row");
    }
    var allCells = document.querySelectorAll("td");
    for (var i = 0; i < allCells.length; i++) {
      allCells[i].setAttribute("role", "cell");
    }
    var allHeaders = document.querySelectorAll("th");
    for (var i = 0; i < allHeaders.length; i++) {
      allHeaders[i].setAttribute("role", "columnheader");
    }
    var allRowHeaders = document.querySelectorAll("th[scope=row]");
    for (var i = 0; i < allRowHeaders.length; i++) {
      allRowHeaders[i].setAttribute("role", "rowheader");
    }
  } catch (e) {
    console.log("AddTableARIA(): " + e);
  }
}

// Active Page Detection
class ActivePageManager {
  constructor() {
    this.setActiveLinks();
  }

  setActiveLinks() {
    const currentPage =
      window.location.pathname.split("/").pop() || "index.php";
    const navLinks = document.querySelectorAll(".nav-link, .nav-mobile-link");

    navLinks.forEach((link) => {
      const linkPage = link.getAttribute("href");
      if (linkPage && linkPage.includes(currentPage)) {
        link.classList.add("active");
      }
    });
  }
}

// Initialize everything when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Initialize navigation
  const navManager = new NavigationManager();

  // Initialize search functionality if on dashboard page
  if (document.getElementById("tableSearch")) {
    const searchManager = new DashboardSearchManager();

    // Make search manager globally available for potential refresh needs
    window.dashboardSearch = searchManager;
  }

  // Initialize loading manager
  const loadingManager = new LoadingManager();

  // Initialize active page manager
  const activePageManager = new ActivePageManager();

  // Initialize table enhancements
  ResponsiveCellHeaders("Books");
  AddTableARIA();

  // Initialize table scroll detection for dashboard
  const tableContainer = document.querySelector(
    ".table-container .table-wrapper"
  );
  if (tableContainer) {
    const container = tableContainer.parentElement;

    function checkScroll() {
      const hasScroll = tableContainer.scrollWidth > tableContainer.clientWidth;
      container.classList.toggle("has-scroll", hasScroll);
    }

    // Check on load and resize
    checkScroll();
    window.addEventListener("resize", checkScroll);

    // Update scroll indicator on scroll
    tableContainer.addEventListener("scroll", () => {
      const scrolled = tableContainer.scrollLeft > 0;
      container.classList.toggle("is-scrolled", scrolled);
    });
  }
});

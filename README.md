# COVID-19 Health Declaration System

A comprehensive web application for managing COVID-19 health records with advanced analytics, filtering capabilities, and full accessibility compliance.

## 🎯 Overview

This system provides a modern, accessible dashboard for tracking COVID-19 health declarations with real-time trend analysis, interactive filtering, and comprehensive data visualization. Built with PHP, MySQL, and modern web standards.

## ✨ Key Features

### 📊 Enhanced Dashboard
- **Interactive KPI Tiles** with trend indicators (▲▼―)
- **Real-time Analytics** comparing current vs. previous periods
- **Dynamic Filtering** by time ranges (Today, 7 days, 30 days, All time)
- **Quick Filter Chips** for instant data filtering
- **Advanced Search** with term highlighting

### 🔍 Filtering & Analytics
- **KPI-based Filters**: Total Records, COVID Encounters, Vaccinated, High Temperature, Adults, International Visitors
- **Quick Filters**: Vaccinated, Encountered, Fever (≥37.5°C), Today's Records
- **Time Range Analysis**: Comparative trends with percentage changes
- **Combination Filtering**: Multiple filters can be applied simultaneously

### 📈 Data Visualization
- **Trend Arrows**: Visual indicators for data changes (▲ increase, ▼ decrease, ― stable)
- **Percentage Changes**: Precise metrics comparing periods
- **Color-coded Status**: Temperature levels, vaccination status, health indicators
- **Real-time Updates**: Live data refresh with loading states

### 📱 Table Management
- **Density Toggle**: Comfortable/Compact table views
- **CSV Export**: Download filtered data with current filters applied
- **Advanced Search**: Real-time filtering with highlighted results
- **Empty State Handling**: Contextual messages for no results

## ♿ Accessibility Features (WCAG 2.1 AA Compliant)

### 🎯 Comprehensive Screen Reader Support
- **Detailed ARIA Labels**: Complete metric descriptions with values, trends, and actions
- **Hidden Descriptions**: Contextual information for complex interactions
- **Live Regions**: Dynamic content announcements
- **Semantic Structure**: Proper landmarks, headings, and navigation

### ⌨️ Full Keyboard Navigation
- **Tab Order**: Logical progression through all interactive elements
- **Enter/Space Activation**: Standard activation keys for all buttons and links
- **Arrow Key Navigation**: Enhanced navigation within filter groups
- **Skip Links**: Quick access to main content
- **Focus Management**: Clear visual focus indicators

### 🎨 Visual Accessibility
- **High Contrast**: WCAG AA compliant color contrast ratios
- **Focus Indicators**: Visible 2px outlines with enhanced focus-visible states
- **Color Independence**: Information conveyed through multiple means (color + icons + text)
- **Touch Targets**: Minimum 44px touch targets for mobile accessibility
- **Responsive Design**: Full accessibility across all screen sizes

### 🔊 Enhanced User Feedback
- **Loading Announcements**: Screen reader feedback for dynamic actions
- **Filter Status**: Clear indication of active filters and their effects
- **Error States**: Accessible error messages and recovery options
- **Success Confirmations**: Confirmation of user actions

## 🛠️ Technical Stack

### Backend
- **PHP 8.2+**: Modern PHP with security best practices
- **MySQL/MariaDB**: Robust data storage with prepared statements
- **Session Management**: Secure authentication and state management
- **CSRF Protection**: Cross-site request forgery prevention

### Frontend
- **Vanilla JavaScript**: No frameworks, modern ES6+ features
- **CSS Grid/Flexbox**: Responsive layout system
- **CSS Custom Properties**: Consistent theming and dark mode
- **Progressive Enhancement**: Works without JavaScript

### Architecture
- **MVC Pattern**: Clean separation of concerns
- **Security-First**: Input validation, output sanitization, prepared statements
- **Performance Optimized**: Efficient queries and minimal dependencies
- **Mobile-First**: Responsive design principles

## 🚀 Getting Started

### Prerequisites
- XAMPP (Apache, MySQL, PHP 8.2+)
- Modern web browser with JavaScript enabled
- Git for version control

### Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/transcenddev/covid-health-declaration.git
   cd covid-health-declaration
   ```

2. **Database Setup**
   ```bash
   # Import main database structure
   mysql -u root -p < database/covid19recordsdb.sql
   
   # Add created_at column for trend analysis
   mysql -u root -p covid19recordsdb < database/add_created_at_column.sql
   ```

3. **XAMPP Configuration**
   - Start Apache and MySQL services
   - Place project in `C:\xampp\htdocs\covid-health-declaration\`
   - Access: `http://localhost/covid-health-declaration/`

### Default Login Credentials
- **Username**: Admin
- **Password**: Admin

## 📊 Database Schema

### Users Table
- `id_users`: Primary key
- `uid_users`: Username
- `email_users`: Email address
- `pwd_users`: Bcrypt hashed password

### Records Table
- `id`: Primary key
- `email`: Contact email
- `full_name`: Individual's name
- `gender`: Gender identity
- `age`: Age in years
- `temp`: Body temperature (DECIMAL 5,2)
- `diagnosed`: COVID diagnosis status (YES/NO)
- `encountered`: COVID exposure status (YES/NO)
- `vaccinated`: Vaccination status (YES/NO)
- `nationality`: Country of origin
- `created_at`: Timestamp for trend analysis

## 🎛️ Usage Guide

### Dashboard Navigation
1. **Time Range Selection**: Choose your analysis period (Today, 7 days, 30 days, All time)
2. **KPI Filtering**: Click any KPI tile to filter by that metric
3. **Quick Filters**: Use filter chips for instant common filters
4. **Search & Export**: Search records and export filtered data

### Accessibility Features
- **Keyboard Users**: Use Tab to navigate, Enter/Space to activate
- **Screen Readers**: Full ARIA support with detailed announcements
- **High Contrast**: System respects user contrast preferences
- **Mobile**: Touch-friendly interface with proper target sizes

### Data Management
- **Add Records**: Use the "Add New Record" button
- **Edit/Delete**: Use action buttons in the table
- **Export Data**: Click "Export CSV" to download current filtered data
- **Search**: Use the search box for real-time filtering

## 🔒 Security Features

- **Input Validation**: Server-side validation for all inputs
- **Prepared Statements**: SQL injection prevention
- **CSRF Protection**: Cross-site request forgery tokens
- **Session Security**: Secure session management
- **Output Sanitization**: XSS prevention
- **Security Logging**: Comprehensive audit trail

## 📱 Browser Support

- ✅ **Chrome 90+**
- ✅ **Firefox 88+**
- ✅ **Safari 14+**
- ✅ **Edge 90+**
- ✅ **Mobile Browsers** (iOS Safari, Chrome Mobile)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m "Add amazing feature"`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Standards
- Follow PSR-12 coding standards for PHP
- Use semantic HTML5 elements
- Maintain WCAG 2.1 AA compliance
- Write accessible JavaScript
- Test with screen readers

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Check existing documentation
- Review accessibility guidelines

## 🏆 Accessibility Certification

This application meets or exceeds:
- **WCAG 2.1 AA** compliance
- **Section 508** standards
- **ADA** digital accessibility requirements
- **EN 301 549** European accessibility standards

---

**Built with ❤️ for accessibility and inclusivity**

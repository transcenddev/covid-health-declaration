# COVID-19 Health Declaration System

> Modern web application for managing COVID-19 health records with analytics dashboard

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)

## ✨ Features

- 📊 **Interactive Dashboard** - Real-time analytics with filtering
- 🔒 **Secure Authentication** - CSRF protection & input validation
- 📱 **Mobile Responsive** - Works on all devices
- ♿ **Accessibility First** - WCAG 2.1 AA compliant
- 📈 **Data Export** - CSV download functionality
- 🎨 **Modern UI** - Glass-morphism design

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/transcenddev/covid-health-declaration.git

# Move to XAMPP directory
mv covid-health-declaration C:/xampp/htdocs/

# Setup database
mysql -u root -p < database/covid19recordsdb.sql
mysql -u root -p covid19recordsdb < database/add_created_at_column.sql
```

**Demo Login:**

- Username: `Admin`
- Password: `Admin`

**URL:** `http://localhost/covid-health-declaration/`

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+, MySQL 8.0+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Server:** Apache (XAMPP)
- **Security:** Prepared statements, CSRF tokens, bcrypt hashing

## 📱 Screenshots

_Dashboard with real-time analytics and filtering_

## 🤝 Contributing

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🐛 Issues

Found a bug? [Create an issue](https://github.com/transcenddev/covid-health-declaration/issues)

---

⭐ **Star this repo** if you find it helpful!

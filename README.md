# ParkSmart Parking Management System

A modern web-based parking management system built with PHP and MySQL that enables users to book parking slots online and administrators to efficiently manage parking operations.

![Project Status](https://img.shields.io/badge/status-active-success.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🚗 Features

### For Users
- **🔐 User Authentication** - Secure registration and login system
- **🅿️ Parking Slot Booking** - View and book available parking slots
- **💳 Online Payment** - Integrated payment system with different rates for vehicle types
- **📱 Booking Management** - Edit, cancel, and view booking history
- **🌤️ Weather Information** - Real-time weather data for parking area
- **🗺️ Navigation & Directions** - GPS-based route guidance with Google Maps
- **⭐ Reviews & Feedback** - Submit and view parking service reviews
- **📞 Contact Support** - WhatsApp and Email quick contact
- **📊 Access Logs** - Track all booking activities

### For Administrators
- **📈 Dashboard** - Overview of parking statistics
- **🚗 Entry Management** - Record vehicle entries
- **🚪 Exit Management** - Process exits and calculate charges
- **📝 Reviews Monitoring** - View and filter user reviews
- **💰 Payment Tracking** - Monitor all transactions

## 🎯 Vehicle Types & Pricing

| Vehicle Type | Icon | Rate per Hour |
|-------------|------|---------------|
| Car         | 🚗   | Rs. 100       |
| Van         | 🚐   | Rs. 150       |
| Jeep        | 🚙   | Rs. 120       |
| Lorry       | 🚛   | Rs. 200       |
| Three Wheel | 🛺   | Rs. 50        |
| Bus         | 🚌   | Rs. 250       |
| Bike        | 🏍️   | Rs. 30        |

## 🛠️ Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **APIs:** OpenWeatherMap API, Google Maps API
- **Server:** Apache (XAMPP)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Modern web browser
- Internet connection (for weather and maps)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/Imalka4Dilshan/PARKSMART-Parking-System.git
cd PARKSMART-Parking-System
```

### 2. Database Setup
```bash
# Start XAMPP MySQL server
# Create database
mysql -u root -p
CREATE DATABASE parking_db;
USE parking_db;

# Import database tables
source database/user_system.sql;
source database/access_logs.sql;
```

### 3. Configure Database Connection
Edit `config/db.php` with your database credentials:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_db";
```

### 4. Setup Parking Slots
```bash
php database/setup_slots.php
```

### 5. Access the Application
- **User Portal:** `http://localhost/parksmart_12-29/user/login.php`
- **Admin Portal:** `http://localhost/parksmart_12-29/admin/dashboard.php`

## 📁 Project Structure

```
parksmart_12-29/
├── admin/              # Admin panel pages
├── user/               # User portal pages
├── auth/               # Authentication files
├── config/             # Configuration files
├── database/           # Database SQL files
├── includes/           # Shared components
└── PROJECT_REPORT_SIMPLE.txt
```

## 💡 Usage

### For Users:
1. Register a new account or login
2. Browse available parking slots
3. Select vehicle type and slot
4. Choose date, time, and duration
5. Complete payment
6. Manage bookings from dashboard

### For Admin:
1. Login with admin credentials
2. Record vehicle entries/exits
3. Monitor bookings and payments
4. View user reviews and feedback
5. Generate reports

## 🌐 API Integrations

- **OpenWeatherMap API** - Real-time weather data
- **Google Maps API** - Navigation and directions
- **WhatsApp Web** - Quick customer support
- **Gmail API** - Email support integration

## 📞 Contact

- **WhatsApp:** +94 704305875
- **Email:** imalkadilshan1233@gmail.com

## 📝 Features Highlights

✅ Responsive design for all devices  
✅ Real-time slot availability updates  
✅ Automatic price calculation  
✅ Weather-based parking recommendations  
✅ GPS navigation support  
✅ Secure payment processing  
✅ Booking modification and cancellation  
✅ Access logging and tracking  
✅ Admin review monitoring with filters  
✅ Professional contact methods  

## 🔐 Security Features

- Password protection
- Session management
- SQL injection prevention
- Secure database queries
- User authentication & authorization

## 🚧 Future Enhancements

- [ ] Mobile application
- [ ] QR code entry/exit
- [ ] SMS notifications
- [ ] Monthly parking passes
- [ ] Multi-location support
- [ ] Advanced analytics dashboard
- [ ] Automatic number plate recognition
- [ ] Multi-language support

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Developer

**Imalka Dilshan**

---

⭐ If you like this project, please give it a star on GitHub!

# 🚆 Railway Reservation System

A complete train reservation and booking management system built with **PHP, MySQL, HTML, CSS, and JavaScript**. Features separate admin and user dashboards with full CRUD operations.

🔗 **Live Demo:** [https://railway-reservation-system-rho7.onrender.com/index.php](https://railway-reservation-system-rho7.onrender.com/index.php)
---

## 🛠️ Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | PHP 7.4+                          |
| Database   | MySQL (hosted on **Aiven.io**)    |
| Frontend   | HTML, CSS, JavaScript                     |

---

## 📁 Project Structure

```
Railway_Reservation_System/
├── index.php               # Login page (entry point)
├── signup.php              # User registration
├── logout.php              # Session logout
│
├── admin_dashboard.php     # Admin home panel
├── admin_trains.php        # Train management (Add/Edit/Delete)
├── admin_stations.php      # Station management
├── admin_bookings.php      # View all bookings
├── admin_status.php        # Train status management
├── admin_users.php         # User account management
│
├── user_dashboard.php      # User home panel
├── search.php              # Train search
├── booking.php             # Ticket booking
├── bookings.php            # User booking history
├── profile.php             # User profile management
│
├── schema.sql              # Database schema with sample data
├── style.css               # Global styles
├── script.js               # Client-side JavaScript
├── rail.gif                # Background animation
│
├── BCNF_analysis.md        # Database normalization analysis
├── queries.md              # Sample SQL queries
├── process.md              # Development process notes
├── php.md                  # PHP implementation notes
│
├── document/               # Project documentation
└── screenshot/             # Application screenshots
```

---

## ✨ Features

### 👨‍💼 Admin Panel
- Manage trains — Add, Edit, Delete (`admin_trains.php`)
- Manage stations (`admin_stations.php`)
- Manage train status per date/class (`admin_status.php`)
- View all bookings across users (`admin_bookings.php`)
- Manage registered users (`admin_users.php`)

### 👤 User Dashboard
- Search available trains by route/date (`search.php`)
- Book tickets with seat availability check (`booking.php`)
- View booking history with status (`bookings.php`)
- Manage personal profile (`profile.php`)
- Ticket status: **CNF** (Confirmed) / **WTL** (Waiting List) / **RJD** (Rejected)

### 🔐 Authentication
- Secure password hashing with **bcrypt**
- Session-based authentication
- Role-based access control (Admin / User)
- Login and registration with validation

---

## 🗄️ Database Schema

### Tables

| Table       | Description                                               |
|-------------|-----------------------------------------------------------|
| `USERS`     | User accounts with roles (admin/user)                     |
| `STATION`   | Train stations                                            |
| `TRAIN`     | Train information and routes                              |
| `PASSENGER` | Passenger details linked to users                         |
| `TSTATUS`   | Per-train, per-date, per-class availability and fare      |
| `TICKET`    | Booking records                                           |

All tables are in **BCNF (Boyce-Codd Normal Form)** with proper foreign key relationships. See [`BCNF_analysis.md`](./BCNF_analysis.md) for full normalization details.

---

## 🔑 Demo Credentials

### Admin Account
| Field    | Value              |
|----------|--------------------|
| Email    | `admin@gmail.com`  |
| Password | `admin123`         |
| Role     | Full system access |

### User Account
| Field    | Value             |
|----------|-------------------|
| Email    | `user@gmail.com`  |
| Password | `user123`         |
| Role     | Book trains, manage passengers |

---

## 🚀 Local Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL / MariaDB server
- Apache / Nginx (or built-in PHP server)
- Modern web browser

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/JAYASURYA-KK/Railway_Reservation_System.git
   cd Railway_Reservation_System
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < schema.sql
   ```

3. **Configure the database connection**

   Update the DB credentials in every PHP file that connects to MySQL (e.g., `index.php`, `signup.php`, `admin_dashboard.php`, `user_dashboard.php`, etc.):

   ```php
   $servername = "localhost";
   $username   = "root";
   $password   = "your_password";
   $dbname     = "train_booking";
   ```

4. **Start the server**
   ```bash
   # Using PHP's built-in server
   php -S localhost:8000

   # OR place files in XAMPP htdocs:
   # htdocs/Railway_Reservation_System/
   ```

5. **Open in browser**
   ```
   http://localhost:8000/index.php
   ```

---

## ☁️ Deployment (Render + Aiven)

This project is deployed using:

- **[Render](https://render.com)** — PHP web service hosting
- **[Aiven](https://aiven.io)** — Managed MySQL cloud database

---

## 📋 Booking Status Codes

| Code  | Meaning                              |
|-------|--------------------------------------|
| `CNF` | Confirmed — seat reserved            |
| `WTL` | Waiting List — seat not available    |
| `RJD` | Rejected — booking could not proceed |

---

## 🎨 UI Highlights

- Railway-themed design with responsive layout
- Color-coded booking status badges
- Modal forms for admin operations
- Mobile-friendly with flexbox/grid layouts

**Color Scheme:**
- Primary Blue: `#0066cc`
- Dark Blue: `#004999`
- Success Green: `#4CAF50`
- Danger Red: `#f44336`

---

## 🔮 Future Enhancements

- Payment gateway integration
- Email / SMS booking notifications
- Ticket cancellation and refund processing
- Real-time seat availability updates
- Advanced admin analytics dashboard
- Train schedule management

---

## 📄 License

This project is open source and available for educational and commercial use.

---

**GitHub:** [JAYASURYA-KK/Railway_Reservation_System](https://github.com/JAYASURYA-KK/Railway_Reservation_System)  
**Live Demo:** [railway-reservation-system-rho7.onrender.com](https://railway-reservation-system-rho7.onrender.com/index.php)  
**Version:** 1.0  
**Author:** JAYASURYA-KK

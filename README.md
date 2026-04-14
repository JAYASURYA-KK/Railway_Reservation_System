# Train Booking System

A complete train reservation and booking management system built with PHP, MySQL, HTML, CSS, and JavaScript. Features separate admin and user dashboards with full CRUD operations.

## Project Structure

```
Directory structure:
└── jayasurya-kk-railway_reservation_system/
    ├── README.md
    ├── admin_bookings.php
    ├── admin_dashboard.php
    ├── admin_stations.php
    ├── admin_status.php
    ├── admin_trains.php
    ├── BCNF_analysis.md
    ├── booking.php
    ├── bookings.php
    ├── index.php
    ├── logout.php
    ├── profile.php
    ├── schema.sql
    ├── script.js
    ├── search.php
    ├── signup.php
    ├── style.css
    ├── user_dashboard.php
    └── document/
        └── BCNF_analysis.md

```

## Features

### Admin Panel (`admin_dashboard.php`)
- Login with demo credentials: `admin@gmail.com` / `admin123`
- Manage trains (Add, Edit, Delete)
- View all bookings and passengers
- Manage user accounts
- Train details: Number, Name, Route, Seats, Fare

### User Dashboard (`user_dashboard.php`)
- Login with demo credentials: `user@gmail.com` / `user123`
- Manage passengers (Add, Edit, Delete)
- Search and book trains
- View booking history with status
- Personal profile management
- Ticket status: CNF (Confirmed) / WTL (Waiting List) / RJD (Rejected)

### Authentication
- Secure password hashing with bcrypt
- Session-based authentication
- Role-based access control (Admin/User)
- Login/Signup with validation

## Database Schema

### Tables
1. **USERS** - User accounts with roles
2. **STATION** - Train stations
3. **TRAIN** - Train information and routes
4. **PASSENGER** - Passenger details
5. **TSTATUS** - Per-train, per-date, per-class availability and fare (replaces TRAIN_STATUS)
6. **TICKET** - Booking records

All tables are in BCNF (Boyce-Codd Normal Form) with proper foreign key relationships.

## Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB server
- Apache/Nginx web server
- Modern web browser

### Installation Steps

1. **Create Database**
   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Update Database Connection**
   Edit the following files and update connection details:
   - `index.php` (line 6-9)
   - `signup.php` (line 6-9)
   - `admin_dashboard.php` (line 6-9)
   - `user_dashboard.php` (line 6-9)

   ```php
   $servername = "localhost";
   $username = "root";
   $password = "your_password";
   $dbname = "train_booking";
   ```

3. **Place Files in Web Root**
   - Copy all project files to your web server directory
   - For XAMPP: `htdocs/train-booking/`
   - For Linux: `/var/www/html/train-booking/`

4. **Start Web Server**
   ```bash
   # XAMPP - Start from XAMPP Control Panel
   # OR for local PHP server
   php -S localhost:8000
   ```

5. **Access Application**
   - Open browser and go to: `http://localhost/train-booking/index.php`

## Demo Credentials

### Admin Account
- Email: `admin@gmail.com`
- Password: `admin123`
- Role: Full system access

### User Account
- Email: `user@gmail.com`
- Password: `user123`
- Role: Can book trains and manage passengers

## File Details

### index.php (Login Page)
- User authentication
- Session validation
- Auto-redirect based on role
- Demo credentials display

### signup.php (Registration)
- New user registration
- Email validation
- Password strength check (min 6 characters)
- Duplicate email prevention

### admin_dashboard.php (Admin Panel)
- Train management with modal forms
- View all bookings
- Manage user accounts
- Sidebar navigation
- Success/error message alerts

### user_dashboard.php (User Dashboard)
- Passenger CRUD operations
- Train search and booking
- Booking history with status
- Profile information
- Section switching via JavaScript

### schema.sql
- Database and table creation
- Sample data insertion
- 8 sample trains
- 3 sample passengers
- Pre-configured admin/user accounts

### style.css
- Railway theme styling
- Responsive design
- Color-coded status badges
- Professional navigation bar
- Form and table styling

### script.js
- Section navigation
- Modal management
- Form validation
- Alert auto-hiding
- Passenger/Train editing functions

## Security Features

- Password hashing with bcrypt
- SQL injection prevention (mysqli_real_escape_string)
- XSS prevention (htmlspecialchars)
- Session-based authentication
- Role-based access control
- Prepared statements ready (can be enhanced)

## User Operations

### Admin CRUD Operations
1. **Add Train** - Enter train details and create
2. **Edit Train** - Modify existing train information
3. **Delete Train** - Remove trains from system
4. **View Bookings** - See all passenger bookings
5. **View Users** - List all registered users

### User CRUD Operations
1. **Add Passenger** - Register new passenger
2. **Edit Passenger** - Update passenger details
3. **Delete Passenger** - Remove passenger from list
4. **Book Ticket** - Reserve seat on a train
5. **View Bookings** - Check booking status

## Booking Status Codes

- **CNF** (Confirmed) - Seat available and confirmed
- **WTL** (Waiting List) - Seat not available, added to queue
- **RJD** (Rejected) - Booking rejected

## API Endpoints (Forms)

All operations use POST method with form submissions:
- Login: `POST index.php`
- Registration: `POST signup.php`
- Add Train: `POST admin_dashboard.php`
- Add Passenger: `POST user_dashboard.php`
- Book Ticket: `POST user_dashboard.php`

## Responsive Design

- Mobile-first approach
- Flexbox layouts
- Grid-based forms
- Touch-friendly buttons
- Optimized for tablets and phones

## Color Scheme

- Primary Blue: `#0066cc`
- Dark Blue: `#004999`
- Success Green: `#4CAF50`
- Danger Red: `#f44336`
- Warning Yellow: `#fff3cd`
- Light Gray: `#f5f5f5`

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Future Enhancements

- Payment gateway integration
- Email notifications
- SMS alerts
- Advanced analytics
- Train schedule management
- Real-time seat availability
- Ticket cancellation system
- Refund processing

## Troubleshooting

### Database Connection Error
- Check MySQL service is running
- Verify username and password
- Ensure `train_booking` database exists

### Login Failed
- Check email/password format
- Verify user exists in database
- Clear browser cookies and try again

### File Not Found (404)
- Ensure all files are in correct directory
- Check file permissions
- Verify PHP is processing PHP files

## Support

For issues or questions, check the code comments or review the database schema for reference implementation details.

## License

This project is open source and available for educational and commercial use.

---

**Created**: April 2024
**Version**: 1.0
**Author**: Train Booking System Team

# PHP Code Documentation — Railway Reservation System

## Overview

This is a PHP/MySQL web application for train ticket booking. It follows a **procedural PHP** architecture (no frameworks) with:
- **MySQLi** for database access (object-oriented connection, procedural query execution)
- **Session-based authentication** (`$_SESSION` for user_id, role, email)
- **Inline CSS** per page (plus shared `style.css`)
- **BCNF-normalized schema** defined in `schema.sql`

All files connect to the same database (`train_booking`) on `localhost` with user `root` and an empty password (XAMPP default).

---

## 1. `index.php` — Login Page

### Purpose
The application entry point. Displays a login form and handles authentication. Redirects already-logged-in users to their respective dashboard.

### Key Logic

1. **Session check & redirect**
   ```php
   if ($is_logged_in) {
       if ($user_role == 'admin') header("Location: admin_dashboard.php");
       else header("Location: user_dashboard.php");
   }
   ```
   If the session already has a `user_id`, the user is forwarded to the correct dashboard without seeing the login form.

2. **Hardcoded admin bypass**
   ```php
   if ($email === 'admin@gmail.com' && $password === 'admin123') {
       $_SESSION['user_id'] = 0;
       $_SESSION['role']    = 'admin';
   }
   ```
   Admin login is checked against a hardcoded credential pair *before* querying the database. `user_id` is set to `0` (not a real DB row), which means the admin session is not tied to a USERS record.

3. **Regular user login**
   ```php
   $query = "SELECT * FROM USERS WHERE Email = '$email'";
   // ... password_verify($password, $user['Password'])
   ```
   Looks up the email in the USERS table, then verifies the bcrypt hash. On success, stores `UserID`, `Role`, and `Email` in the session.

4. **Security notes**
   - Uses `mysqli_real_escape_string()` on email (prevents basic SQL injection on that field).
   - Uses `password_verify()` for safe hash comparison (timing-attack resistant).
   - Password field is NOT escaped before the verify call (correct — it goes straight to `password_verify`).
   - The admin bypass is a plain-text credential check — a potential security weakness.
   - Setting `user_id = 0` causes a **bug** on pages like `profile.php`: queries such as `SELECT * FROM USERS WHERE UserID = 0` return no rows, so the admin has no profile data to display or edit.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM USERS WHERE Email = '$email'` | Look up user by email for login |

### UI Features
- Animated slide-up login card over a darkened background image
- Error message display with red left border
- Demo credentials box
- Link to `signup.php`

---

## 2. `signup.php` — Registration Page

### Purpose
New user account creation form. Validates input, checks for duplicate emails, hashes the password, and inserts a new user with role `'user'`.

### Key Logic

1. **Validation chain**
   ```php
   if (empty($full_name) || empty($email) || ...) $signup_error = "All fields are required!";
   elseif (strlen($password) < 6)                 $signup_error = "Password must be at least 6 characters!";
   elseif ($password !== $confirm_password)        $signup_error = "Passwords do not match!";
   ```
   Server-side validation runs before any DB interaction.

2. **Duplicate email check**
   ```php
   $check_query = "SELECT Email FROM USERS WHERE Email = '$email'";
   ```
   Returns an error if the email already exists in USERS.

3. **Password hashing & insert**
   ```php
   $hashed_password = password_hash($password, PASSWORD_BCRYPT);
   $insert_query = "INSERT INTO USERS (Name, Email, Phone, Password, Role)
                    VALUES ('$full_name', '$email', '$phone', '$hashed_password', 'user')";
   ```
   Uses `PASSWORD_BCRYPT` (default cost of 10). Role is always `'user'` — there is no way to sign up as admin.

4. **Post-success redirect**
   ```php
   header("Refresh: 2; url=index.php");
   ```
   Shows a success message for 2 seconds, then sends the user to the login page.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT Email FROM USERS WHERE Email = '$email'` | Duplicate email check |
| `INSERT INTO USERS (Name, Email, Phone, Password, Role) VALUES (...)` | Create new user |

---

## 3. `logout.php` — Session Destruction

### Purpose
Terminates the active session and redirects to the login page.

### Code
```php
session_start();
session_destroy();
header("Location: index.php");
exit();
```
Simple and complete. Called via a POST form from the sidebar logout button on both dashboards.

---

## 4. `user_dashboard.php` — User Home Page

### Purpose
The main user interface. Contains a **sidebar** with navigation links and multiple **content sections** (passengers, search trains, bookings, profile). Only one section is visible at a time, toggled via JavaScript.

### Key Logic — Passenger CRUD

1. **Add passenger**
   ```php
   INSERT INTO PASSENGER (UserID, PassengerName, Age, Gender)
   VALUES ($user_id, '$passenger_name', $age, '$gender')
   ```
   Associates the passenger with the logged-in user via `UserID`.

2. **Edit passenger**
   ```php
   UPDATE PASSENGER SET PassengerName='$passenger_name', Age=$age, Gender='$gender'
   WHERE PassengerID=$passenger_id
   ```
   Note: There is no ownership check — any logged-in user could potentially edit another user's passenger if they knew the PassengerID (no `AND UserID=$user_id` guard).

3. **Delete passenger**
   ```php
   DELETE FROM PASSENGER WHERE PassengerID=$passenger_id
   ```
   Same ownership-gap issue as edit.

4. **Book ticket** (also handled here, duplicate of `booking.php` logic)
   ```php
   // Count confirmed tickets
   SELECT COUNT(*) as booked FROM TICKET WHERE TrainID = $train_id AND Status = 'CNF'
   // For each passenger:
   if ($booked_count < $train['TotalSeats']) { $status = 'CNF'; $fare = $train['Fare']; }
   else { $status = 'WTL'; $fare = 0; }
   INSERT INTO TICKET (UserID, TrainID, PassengerID, Status, BookingDate, Fare)
   VALUES ($user_id, $train_id, $pid, '$status', '$booking_date', $fare)
   ```
   Seat availability is checked by counting confirmed tickets vs. `TotalSeats`. If seats remain, status is `'CNF'` (confirmed); otherwise `'WTL'` (waitlisted) with fare = 0.

### Key Logic — Train Search

```php
$trains_query = "SELECT * FROM TRAIN";
if (count($filters) > 0) $trains_query .= ' WHERE ' . implode(' AND ', $filters);
```
Dynamically builds a WHERE clause from optional `source`/`destination` GET parameters.

### Key Logic — Bookings Display

```php
SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation,
       tr.ToStation, t.Status, t.BookingDate, t.Fare
FROM TICKET t
JOIN PASSENGER p ON t.PassengerID = p.PassengerID
JOIN TRAIN tr ON t.TrainID = tr.TrainID
WHERE t.UserID = $user_id
ORDER BY t.BookingDate DESC
```
Three-table join to display the user's booking history with passenger and train details.

### UI Features
- Fixed sidebar with nav links
- Edit passenger modal (JavaScript `editPassenger()` pre-fills the form)
- Book train modal (JavaScript `bookTrain()` pre-fills the form)
- Multi-select passenger list for booking
- Auto-hiding success/error messages (3-second fade)

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT DISTINCT StationName FROM STATION ORDER BY StationName` | Populate station dropdowns |
| `SELECT * FROM USERS WHERE UserID = $user_id` | Fetch user profile data |
| `INSERT INTO PASSENGER (...)` | Add a passenger |
| `UPDATE PASSENGER SET ... WHERE PassengerID=$id` | Edit a passenger |
| `DELETE FROM PASSENGER WHERE PassengerID=$id` | Delete a passenger |
| `SELECT * FROM TRAIN [WHERE ...]` | Search trains by source/destination |
| `SELECT COUNT(*) as booked FROM TICKET WHERE TrainID=$id AND Status='CNF'` | Count confirmed bookings |
| `INSERT INTO TICKET (...)` | Create a ticket |
| `3-table JOIN for booking history` | Display user's bookings |

---

## 5. `search.php` — Standalone Train Search Page

### Purpose
A dedicated search page (linked from the user sidebar). Shows dropdowns for source/destination stations and a results table with a "Book" link.

### Key Logic

```php
$stations_result = mysqli_query($conn, "SELECT DISTINCT StationName FROM STATION ORDER BY StationName");
// Build dynamic WHERE clause from GET params
$trains_query = "SELECT * FROM TRAIN";
if (count($filters) > 0) $trains_query .= ' WHERE ' . implode(' AND ', $filters);
$trains_query .= ' ORDER BY TrainNumber';
```

The results table includes TrainNumber, TrainName, From, To, Departure, Arrival, Fare, and a "Book" link to `booking.php?train_id=X`.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT DISTINCT StationName FROM STATION ORDER BY StationName` | Station dropdown |
| `SELECT * FROM TRAIN [WHERE filters] ORDER BY TrainNumber` | Search trains |

---

## 6. `booking.php` — Standalone Booking Page

### Purpose
Dedicated ticket booking page (reached from `search.php` or `user_dashboard.php`). Shows train info and lets the user select multiple passengers.

### Key Logic

1. **Fetch train info**
   ```php
   SELECT * FROM TRAIN WHERE TrainID = $train_id
   ```

2. **Fetch user's passengers**
   ```php
   SELECT * FROM PASSENGER WHERE UserID = $user_id
   ```

3. **Booking with availability check** (same logic as `user_dashboard.php`)
   ```php
   // Count confirmed tickets for the train
   SELECT COUNT(*) as booked FROM TICKET WHERE TrainID = $train_id AND Status = 'CNF'
   // Insert tickets with CNF or WTL status
   INSERT INTO TICKET (UserID, TrainID, PassengerID, Status, BookingDate, Fare) VALUES (...)
   ```

4. **Multi-passenger booking**
   The form uses `name="passenger_id[]"` with `multiple` attribute, so `$_POST['passenger_id']` is an array. Each passenger gets their own TICKET row. The `$booked_count` increments per passenger, so if 3 seats remain and 5 passengers are selected, the first 3 get CNF and the last 2 get WTL.

5. **Note on duplicate logic**
   The booking logic is duplicated between `booking.php` and `user_dashboard.php`. The modal booking in the dashboard uses the same INSERT query pattern.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM TRAIN WHERE TrainID = $train_id` | Get train details |
| `SELECT * FROM PASSENGER WHERE UserID = $user_id` | Get user's passengers |
| `SELECT COUNT(*) as booked FROM TICKET WHERE TrainID = $id AND Status = 'CNF'` | Availability check |
| `INSERT INTO TICKET (...)` | Create ticket |

---

## 7. `bookings.php` — User Booking History

### Purpose
Dedicated page showing all bookings for the logged-in user with a detailed table.

### Key Logic

```php
SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation,
       tr.ToStation, tr.DepartureTime, tr.ArrivalTime, t.Status, t.BookingDate, t.Fare
FROM TICKET t
JOIN PASSENGER p ON t.PassengerID = p.PassengerID
JOIN TRAIN tr ON t.TrainID = tr.TrainID
WHERE t.UserID = $user_id
ORDER BY t.BookingDate DESC
```

Three-table JOIN: TICKET → PASSENGER (for name) + TICKET → TRAIN (for route info). Shows departure/arrival times and status badges (color-coded: green for CNF, amber for WTL, red for RJD).

### Queries Used
| Query | Purpose |
|-------|---------|
| 3-table JOIN (TICKET + PASSENGER + TRAIN) | Fetch user's booking history |

---

## 8. `profile.php` — User Profile Editor

### Purpose
Allows the logged-in user to update their Name, Phone, and optionally change their Password.

### Key Logic

1. **Fetch current profile**
   ```php
   SELECT * FROM USERS WHERE UserID = $user_id
   ```

2. **Update with optional password**
   ```php
   if ($password !== '') {
       $hash = password_hash($password, PASSWORD_DEFAULT);
       $q = "UPDATE USERS SET Name='$name', Phone='$phone', Password='$hash' WHERE UserID=$user_id";
   } else {
       $q = "UPDATE USERS SET Name='$name', Phone='$phone' WHERE UserID=$user_id";
   }
   ```
   If the password field is left blank, only Name and Phone are updated. If filled, the new password is bcrypt-hashed before saving.

3. **Email is read-only**
   The email input has the `readonly` attribute — users cannot change their email.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM USERS WHERE UserID = $user_id` | Fetch profile |
| `UPDATE USERS SET Name=..., Phone=... [Password=...] WHERE UserID=$user_id` | Update profile |

---

## 9. `admin_dashboard.php` — Admin Home Page

### Purpose
The admin's primary interface. Shows a sidebar with links to sub-pages and a main content area for **Train CRUD** (add, edit, delete, list). Also handles Station CRUD inline.

### Key Logic — Train CRUD

1. **Add train**
   ```php
   INSERT INTO TRAIN (TrainNumber, TrainName, FromStation, ToStation, TotalSeats, Fare,
                      DepartureTime, ArrivalTime)
   VALUES ('$train_number', '$train_name', '$from_station', '$to_station', $total_seats,
           $fare, $departure_time, $arrival_time)
   ```
   Station names are selected from a dropdown populated from the STATION table. Departure/arrival times may be NULL.

2. **Edit train**
   ```php
   UPDATE TRAIN SET TrainName='$train_name', TotalSeats=$total_seats, Fare=$fare,
                    DepartureTime=$departure_time, ArrivalTime=$arrival_time
   WHERE TrainID=$train_id
   ```
   TrainNumber and stations are NOT editable (not included in the edit form).

3. **Delete train**
   ```php
   DELETE FROM TRAIN WHERE TrainID=$train_id
   ```
   Cascading deletes propagate to TICKET rows (FK `ON DELETE CASCADE`).

4. **Station CRUD** (also handled here, duplicate of `admin_stations.php`)
   ```php
   INSERT INTO STATION (StationName, City, State) VALUES (...)
   UPDATE STATION SET StationName='$name', City='$city', State='$state' WHERE StationID=$id
   DELETE FROM STATION WHERE StationID=$id
   ```

### UI Features
- Fixed admin sidebar with links to sub-pages
- Add Train form with 8 fields in a grid
- Edit Train modal (JavaScript `openEdit()` pre-fills the form)
- Delete button with `confirm()` dialog
- Success/error message display

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM TRAIN` | List all trains |
| `SELECT DISTINCT StationName FROM STATION ORDER BY StationName` | Station dropdown |
| `SELECT * FROM STATION ORDER BY StationName` | Station management list |
| `INSERT INTO TRAIN (...)` | Add train |
| `UPDATE TRAIN SET ... WHERE TrainID=$id` | Edit train |
| `DELETE FROM TRAIN WHERE TrainID=$id` | Delete train |
| `INSERT INTO STATION (...)` | Add station |
| `UPDATE STATION SET ... WHERE StationID=$id` | Edit station |
| `DELETE FROM STATION WHERE StationID=$id` | Delete station |

---

## 10. `admin_stations.php` — Station Management Page

### Purpose
Dedicated page for managing stations (add, edit, delete). Separated from the main admin dashboard for cleaner organization.

### Key Logic

Identical CRUD to the station handling in `admin_dashboard.php`:
- **Add**: validates station name is non-empty, then INSERTs.
- **Edit**: validates ID and name, then UPDATEs.
- **Delete**: validates ID > 0, then DELETEs.

Uses a JavaScript modal (`openEdit()`/`closeEdit()`) for the edit form.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM STATION ORDER BY StationName` | List stations |
| `INSERT INTO STATION (StationName, City, State) VALUES (...)` | Add station |
| `UPDATE STATION SET StationName='$name', City='$city', State='$state' WHERE StationID=$id` | Edit station |
| `DELETE FROM STATION WHERE StationID=$id` | Delete station |

---

## 11. `admin_trains.php` — Train Management Page

### Purpose
Dedicated page for managing trains (add, edit, delete). Similar to the train section in `admin_dashboard.php` but as a standalone page.

### Key Logic

1. **Schema migration check**
   ```php
   mysqli_query($conn, "ALTER TABLE TRAIN ADD COLUMN IF NOT EXISTS ArrivalTime TIME NULL,
                        ADD COLUMN IF NOT EXISTS DepartureTime TIME NULL");
   ```
   Ensures the DepartureTime/ArrivalTime columns exist (backward compatibility for older schemas).

2. **CRUD operations** — same pattern as `admin_dashboard.php`:
   - INSERT with 8 fields (TrainNumber, TrainName, FromStation, ToStation, TotalSeats, Fare, DepartureTime, ArrivalTime)
   - UPDATE for TrainName, TotalSeats, Fare, DepartureTime, ArrivalTime
   - DELETE by TrainID

### Queries Used
| Query | Purpose |
|-------|---------|
| `ALTER TABLE TRAIN ADD COLUMN IF NOT EXISTS ...` | Schema migration |
| `SELECT DISTINCT StationName FROM STATION ORDER BY StationName` | Station dropdown |
| `SELECT * FROM TRAIN ORDER BY TrainNumber` | List trains |
| `INSERT INTO TRAIN (...)` | Add train |
| `UPDATE TRAIN SET ... WHERE TrainID=$id` | Edit train |
| `DELETE FROM TRAIN WHERE TrainID=$id` | Delete train |

---

## 12. `admin_status.php` — Train Status Update Page

### Purpose
Allows admins to set the status (CNF/WTL/RJD) of all tickets for a given train.

### Key Logic

1. **Bulk status update**
   ```php
   $train_id = (int)$_POST['train_id'];
   $status = mysqli_real_escape_string($conn, $_POST['status']);
   if ($train_id>0 && in_array($status, ['CNF','WTL','RJD'])) {
       $up = "UPDATE TICKET SET Status='$status' WHERE TrainID=$train_id";
   }
   ```
   Sets ALL tickets for the selected train to the chosen status. No per-ticket granularity.

2. **Status history display**
   ```php
   SELECT tr.TrainNumber, t.Status, MAX(t.CreatedAt) AS UpdatedAt
   FROM TICKET t JOIN TRAIN tr ON t.TrainID = tr.TrainID
   GROUP BY tr.TrainNumber, t.Status
   ORDER BY UpdatedAt DESC LIMIT 50
   ```
   Shows the most recent status update per train/status combination (derived from ticket timestamps).

### Important Notes
- There is no separate "train status" table in the current normalized schema — the TSTATUS table exists but is not used by this page. Status is managed entirely through the TICKET table.
- The admin sets a blanket status for ALL tickets on a train, not individual tickets.

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT TrainID, TrainNumber, TrainName FROM TRAIN ORDER BY TrainNumber` | Train dropdown |
| `UPDATE TICKET SET Status='$status' WHERE TrainID=$train_id` | Bulk status update |
| `SELECT tr.TrainNumber, t.Status, MAX(t.CreatedAt) FROM TICKET t JOIN TRAIN tr ... GROUP BY ...` | Status history |

---

## 13. `admin_bookings.php` — All Bookings Viewer

### Purpose
Read-only view of ALL bookings across all users. Admin can see ticket details, passenger info, and which user made the booking.

### Key Logic

```php
SELECT t.TicketID, p.PassengerName, tr.TrainNumber, tr.TrainName, tr.FromStation,
       tr.ToStation, tr.DepartureTime, tr.ArrivalTime, t.Status, t.BookingDate,
       u.Email as UserEmail
FROM TICKET t
JOIN PASSENGER p ON t.PassengerID = p.PassengerID
JOIN TRAIN tr ON t.TrainID = tr.TrainID
JOIN USERS u ON t.UserID = u.UserID
ORDER BY t.BookingDate DESC
```
Four-table JOIN: TICKET → PASSENGER + TRAIN + USERS. Includes user email to identify who made the booking.

### Queries Used
| Query | Purpose |
|-------|---------|
| 4-table JOIN (TICKET + PASSENGER + TRAIN + USERS) | Fetch all bookings for admin |

---

## 14. `admin_users.php` — User Management Page

### Purpose
Allows admins to view all users, change their role (user ↔ admin), and delete users.

### Key Logic

1. **Role update**
   ```php
   $role = ($_POST['role'] === 'admin') ? 'admin' : 'user';
   mysqli_query($conn, "UPDATE USERS SET Role='$role' WHERE UserID=$uid");
   ```
   Whitelist validation ensures only `'admin'` or `'user'` can be set.

2. **User deletion**
   ```php
   DELETE FROM USERS WHERE UserID=$uid
   ```
   Cascading deletes propagate to PASSENGER and TICKET tables (FK `ON DELETE CASCADE`).

### Queries Used
| Query | Purpose |
|-------|---------|
| `SELECT * FROM USERS ORDER BY UserID DESC` | List all users |
| `UPDATE USERS SET Role='$role' WHERE UserID=$uid` | Change user role |
| `DELETE FROM USERS WHERE UserID=$uid` | Delete user (cascades) |

---

## Common Patterns Across All Files

### Database Connection
Every PHP file repeats this block:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "train_booking";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
```
There is no shared connection include file — each page opens its own connection.

### Authentication Guard
User pages check:
```php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php'); exit();
}
```
Admin pages check:
```php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php'); exit();
}
```

### Input Sanitization
- String inputs: `mysqli_real_escape_string($conn, $input)` — prevents SQL injection
- Integer inputs: `(int)$input` — cast to int
- Float inputs: `(float)$input` — cast to float
- Output: `htmlspecialchars($value)` — prevents XSS in displayed data

### Password Security
- **Hashing**: `password_hash($password, PASSWORD_BCRYPT)` or `PASSWORD_DEFAULT`
- **Verification**: `password_verify($password, $hash)`
- Both are the recommended PHP password hashing API.

### Error Handling
- Success/error messages stored in `$success_msg` / `$error_msg`
- Displayed as styled alert divs at the top of the page
- No error logging or exception handling — errors are shown directly to the user

### Reusable Result Sets with `mysqli_data_seek()`
Several pages (search.php, user_dashboard.php, admin_dashboard.php, admin_trains.php) iterate over station results multiple times to populate different dropdowns. They use `mysqli_data_seek($result, 0)` to rewind the result pointer back to the start before each loop. This avoids running the same SELECT query twice.

### Architecture Notes
- **No MVC separation**: Each PHP file mixes database logic, business logic, and HTML/CSS/JS
- **No prepared statements**: All queries use string interpolation with `mysqli_real_escape_string()`
- **No CSRF protection**: Forms have no token verification
- **No rate limiting**: Login and signup forms have no brute-force protection
- **Duplicate code**: Booking logic is duplicated between `booking.php` and `user_dashboard.php`; Station CRUD is duplicated between `admin_dashboard.php` and `admin_stations.php`; Train CRUD is duplicated between `admin_dashboard.php` and `admin_trains.php`

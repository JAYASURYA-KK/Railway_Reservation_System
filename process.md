# Database Process Flow & Table Connections — Railway Reservation System

## 1. Entity-Relationship Overview

The system uses **6 tables** in a BCNF-normalized schema. Below is the logical dependency graph:

```
STATION ←──── TRAIN ────→ TICKET ←──── PASSENGER ←──── USERS
                    │                                           │
                    │         ┌─────────────────────────────────┘
                    └────→ TSTATUS                        (UserID)
```

**Data flows from independent entities toward dependent ones:**
1. **STATION** — standalone (no FK dependencies)
2. **USERS** — standalone (no FK dependencies)
3. **TRAIN** — depends on STATION (FromStation, ToStation reference StationName)
4. **PASSENGER** — depends on USERS (UserID FK)
5. **TICKET** — depends on TRAIN, USERS, and PASSENGER (three FKs)
6. **TSTATUS** — depends on TRAIN (TrainID FK)

---

## 2. Table Definitions & Key Relationships

### 2.1 STATION
```
StationID  INT  PK  AUTO_INCREMENT
StationName  VARCHAR(100)  UNIQUE NOT NULL
City  VARCHAR(100)  NOT NULL
State  VARCHAR(100)  NOT NULL
```
- **Referenced by**: TRAIN.FromStation, TRAIN.ToStation (via StationName, not StationID)
- **Purpose**: Master list of railway stations. The `StationName` column is used as a foreign key target by the TRAIN table (string-based FK, not integer ID).

### 2.2 USERS
```
UserID  INT  PK  AUTO_INCREMENT
Name  VARCHAR(100)  NOT NULL
Email  VARCHAR(100)  UNIQUE NOT NULL
Phone  VARCHAR(15)  NOT NULL
Password  VARCHAR(255)  NOT NULL
Role  ENUM('admin','user')  DEFAULT 'user'
CreatedAt  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
```
- **Referenced by**: PASSENGER.UserID, TICKET.UserID (both ON DELETE CASCADE)
- **Purpose**: Stores all system users (both regular users and admins). The Role field controls access levels.
- **Cascade behavior**: Deleting a user removes all their passengers and tickets.

### 2.3 TRAIN
```
TrainID  INT  PK  AUTO_INCREMENT
TrainNumber  VARCHAR(20)  UNIQUE NOT NULL
TrainName  VARCHAR(100)  NOT NULL
FromStation  VARCHAR(100)  NOT NULL  → STATION.StationName
ToStation  VARCHAR(100)  NOT NULL  → STATION.StationName
TotalSeats  INT  NOT NULL
Fare  DECIMAL(10,2)  NOT NULL
DepartureTime  TIME  NULL
ArrivalTime  TIME  NULL
CreatedAt  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
```
- **FKs**: FromStation → STATION.StationName, ToStation → STATION.StationName
- **Referenced by**: TICKET.TrainID, TSTATUS.TrainID (both ON DELETE CASCADE)
- **Purpose**: Each train has a fixed route (source → destination), capacity, and base fare. DepartureTime/ArrivalTime were added later (nullable for backward compatibility).
- **Cascade behavior**: Deleting a train removes all its tickets and status records.

### 2.4 PASSENGER
```
PassengerID  INT  PK  AUTO_INCREMENT
UserID  INT  NOT NULL  → USERS.UserID  ON DELETE CASCADE
PassengerName  VARCHAR(100)  NOT NULL
Age  INT  NOT NULL
Gender  ENUM('Male','Female','Other')  NOT NULL
CreatedAt  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
```
- **FK**: UserID → USERS.UserID (CASCADE)
- **Referenced by**: TICKET.PassengerID (ON DELETE CASCADE)
- **Purpose**: Each user can register multiple passengers (family members, etc.). A passenger is owned by exactly one user.
- **Cascade behavior**: Deleting a user cascades to their passengers; deleting a passenger cascades to their tickets.

### 2.5 TICKET
```
TicketID  INT  PK  AUTO_INCREMENT
Pnr  VARCHAR(50)  UNIQUE NULL
UserID  INT  NOT NULL  → USERS.UserID  ON DELETE CASCADE
TrainID  INT  NOT NULL  → TRAIN.TrainID  ON DELETE CASCADE
PassengerID  INT  NOT NULL  → PASSENGER.PassengerID  ON DELETE CASCADE
Status  ENUM('CNF','WTL','RJD')  DEFAULT 'CNF'
BookingDate  DATE  NOT NULL
Fare  DECIMAL(10,2)  NOT NULL
CreatedAt  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP
```
- **FKs**: UserID → USERS, TrainID → TRAIN, PassengerID → PASSENGER (all CASCADE)
- **Purpose**: The central booking record. Each row represents one passenger on one train, booked by one user. Status tracks the ticket state:
  - **CNF** = Confirmed (seat allocated, fare charged)
  - **WTL** = Waitlisted (no seat yet, fare = 0)
  - **RJD** = Rejected/Cancelled
- **Pnr field**: Intended for PNR number generation but currently unused (NULL in most inserts)

### 2.6 TSTATUS
```
TrainID  INT  NOT NULL  → TRAIN.TrainID  ON DELETE CASCADE
SDate  DATE  NOT NULL
Class  VARCHAR(20)  NOT NULL
TotalSeats  INT  NOT NULL
TotalFare  DECIMAL(10,2)  NOT NULL
PK: (TrainID, SDate, Class)
```
- **FK**: TrainID → TRAIN.TrainID (CASCADE)
- **Purpose**: Per-train, per-date, per-class seat availability and fare overrides. **Currently NOT used by any PHP page** — it exists in the schema but the application derives availability from counting TICKET rows with Status='CNF' vs. TRAIN.TotalSeats.

---

## 3. Foreign Key Connection Map

```
┌──────────┐
│ STATION  │ No FKs (root table)
└────┬─────┘
     │ (StationName referenced by TRAIN)
     ▼
┌──────────┐        ┌──────────┐
│  TRAIN   │        │  USERS   │ No FKs (root table)
└──┬───┬───┘        └────┬─────┘
   │   │                  │ (UserID referenced by PASSENGER & TICKET)
   │   │                  │
   │   │    ┌─────────────┤
   │   │    ▼             │
   │   │ ┌──────────┐    │
   │   │ │PASSENGER │    │
   │   │ └────┬─────┘    │
   │   │      │          │
   │   │      │(PassengerID referenced by TICKET)
   │   │      │          │
   │   ▼      ▼          ▼
   │   ┌──────────┐
   │   │  TICKET  │ ← Central table (3 FKs)
   │   └──────────┘
   │
   ▼
┌──────────┐
│ TSTATUS  │ (1 FK: TrainID → TRAIN)
└──────────┘
```

### FK Summary Table

| Child Table | FK Column | Parent Table | Parent Column | On Delete |
|-------------|-----------|-------------|---------------|-----------|
| TRAIN | FromStation | STATION | StationName | default (RESTRICT) |
| TRAIN | ToStation | STATION | StationName | default (RESTRICT) |
| PASSENGER | UserID | USERS | UserID | CASCADE |
| TICKET | UserID | USERS | UserID | CASCADE |
| TICKET | TrainID | TRAIN | TrainID | CASCADE |
| TICKET | PassengerID | PASSENGER | PassengerID | CASCADE |
| TSTATUS | TrainID | TRAIN | TrainID | CASCADE |

---

## 4. Cascade Delete Chain

When a row is deleted, cascading deletes propagate through the FK chain:

```
Delete USER (UserID=X)
  ├── Delete all PASSENGER rows WHERE UserID=X
  │     └── Delete all TICKET rows WHERE PassengerID= those passengers
  └── Delete all TICKET rows WHERE UserID=X

Delete TRAIN (TrainID=Y)
  ├── Delete all TICKET rows WHERE TrainID=Y
  └── Delete all TSTATUS rows WHERE TrainID=Y

Delete PASSENGER (PassengerID=Z)
  └── Delete all TICKET rows WHERE PassengerID=Z
```

**Important**: Deleting a USER triggers a double-cascade on TICKET — once through PASSENGER and once directly. MySQL handles this correctly (rows are deleted once, not duplicated).

---

## 5. Index Strategy

The schema creates the following indexes for query performance:

| Index Name | Table | Column(s) | Purpose |
|------------|-------|-----------|---------|
| idx_user_email | USERS | Email | Fast login lookup |
| idx_passenger_userid | PASSENGER | UserID | Fetch user's passengers |
| idx_ticket_userid | TICKET | UserID | Fetch user's bookings |
| idx_ticket_trainid | TICKET | TrainID | Count bookings per train |
| idx_train_stations | TRAIN | FromStation, ToStation | Train search by route |

These indexes directly support the most frequent queries in the application:
- **Login**: `SELECT * FROM USERS WHERE Email = ?` → uses idx_user_email
- **Passenger list**: `SELECT * FROM PASSENGER WHERE UserID = ?` → uses idx_passenger_userid
- **Booking history**: `SELECT ... FROM TICKET WHERE UserID = ?` → uses idx_ticket_userid
- **Availability check**: `SELECT COUNT(*) FROM TICKET WHERE TrainID = ? AND Status = 'CNF'` → uses idx_ticket_trainid
- **Route search**: `SELECT * FROM TRAIN WHERE FromStation = ? AND ToStation = ?` → uses idx_train_stations

---

## 6. Complete Process Flows

### 6.1 User Registration Flow

```
Browser                  PHP (signup.php)                    MySQL
  │                         │                                  │
  │── POST (name,email, ──→│                                  │
  │    phone,password)      │                                  │
  │                         │── SELECT Email FROM USERS ─────→│
  │                         │←── result (empty) ─────────────│
  │                         │                                  │
  │                         │── password_hash($pw, BCRYPT) ──→│ (PHP-side)
  │                         │                                  │
  │                         │── INSERT INTO USERS (...) ─────→│
  │                         │←── OK ─────────────────────────│
  │                         │                                  │
  │←── "Account created" ──│                                  │
  │    + Redirect to login  │                                  │
```

**Tables touched**: USERS (1 INSERT)

---

### 6.2 Login Flow

```
Browser                  PHP (index.php)                     MySQL
  │                         │                                  │
  │── POST (email,password)─→│                                 │
  │                         │                                  │
  │                         │── [Hardcoded admin check] ─────→│ (no DB query)
  │                         │    if admin@gmail.com/admin123   │
  │                         │    → session = admin, redirect   │
  │                         │                                  │
  │                         │── SELECT * FROM USERS ─────────→│
  │                         │    WHERE Email = '$email'        │
  │                         │←── user row ───────────────────│
  │                         │                                  │
  │                         │── password_verify($pw, $hash) ──→│ (PHP-side)
  │                         │                                  │
  │                         │── $_SESSION['user_id'] = ...     │
  │                         │── $_SESSION['role'] = ...        │
  │                         │                                  │
  │←── Redirect to          │                                  │
  │    admin_dashboard.php  │                                  │
  │    or user_dashboard.php│                                  │
```

**Tables touched**: USERS (1 SELECT)

---

### 6.3 Train Search Flow

```
Browser                  PHP (search.php)                    MySQL
  │                         │                                  │
  │── GET ?source=X&dest=Y─→│                                 │
  │                         │                                  │
  │                         │── SELECT DISTINCT StationName ─→│
  │                         │    FROM STATION ORDER BY ...     │
  │                         │←── station list ───────────────│
  │                         │                                  │
  │                         │── SELECT * FROM TRAIN ─────────→│
  │                         │    WHERE FromStation='X'        │
  │                         │    AND ToStation='Y'             │
  │                         │←── train rows ─────────────────│
  │                         │                                  │
  │←── HTML table with ────│                                  │
  │    trains + Book links  │                                  │
```

**Tables touched**: STATION (1 SELECT), TRAIN (1 SELECT)

---

### 6.4 Ticket Booking Flow (Most Complex)

```
Browser                  PHP (booking.php)                   MySQL
  │                         │                                  │
  │── GET ?train_id=3 ────→│                                  │
  │                         │── SELECT * FROM TRAIN ─────────→│
  │                         │    WHERE TrainID = 3             │
  │                         │←── train info ─────────────────│
  │                         │                                  │
  │                         │── SELECT * FROM PASSENGER ────→│
  │                         │    WHERE UserID = $uid           │
  │                         │←── passenger list ────────────│
  │                         │                                  │
  │←── Booking form with ──│                                  │
  │    passenger dropdown   │                                  │
  │                         │                                  │
  │── POST (passenger_ids)─→│                                  │
  │                         │── SELECT * FROM TRAIN ─────────→│ (again, for fare)
  │                         │←── train row ─────────────────│
  │                         │                                  │
  │                         │── SELECT COUNT(*) FROM TICKET ─→│
  │                         │    WHERE TrainID=3 AND Status='CNF'
  │                         │←── booked_count ──────────────│
  │                         │                                  │
  │                         │  FOR EACH passenger_id:          │
  │                         │    if booked_count < TotalSeats: │
  │                         │      status = 'CNF', fare = Fare │
  │                         │      booked_count++              │
  │                         │    else:                          │
  │                         │      status = 'WTL', fare = 0    │
  │                         │                                  │
  │                         │── INSERT INTO TICKET (...) ────→│
  │                         │←── OK ─────────────────────────│
  │                         │                                  │
  │←── "Tickets: PID 1(CNF),│                                  │
  │     PID 2(WTL)"         │                                  │
```

**Tables touched**: TRAIN (2 SELECTs), PASSENGER (1 SELECT), TICKET (1 SELECT + N INSERTs)

**Availability Algorithm**:
1. Count existing confirmed tickets for the train: `COUNT(*) WHERE TrainID=X AND Status='CNF'`
2. Compare against `TRAIN.TotalSeats`
3. For each selected passenger:
   - If seats remain → Status='CNF', Fare=train's fare, increment count
   - If no seats → Status='WTL', Fare=0
4. Insert one TICKET row per passenger

**Note**: This is NOT transactional — concurrent bookings could overbook. There is no `LOCK TABLES` or `START TRANSACTION` around the check-then-insert sequence.

**Entry points**: This booking flow is accessible from two pages — `booking.php` (standalone) and `user_dashboard.php` (modal). Both execute identical logic.

---

### 6.5 Admin Status Update Flow

```
Browser                  PHP (admin_status.php)              MySQL
  │                         │                                  │
  │── POST (train_id, ────→│                                  │
  │    status='RJD')        │                                  │
  │                         │── UPDATE TICKET SET Status='RJD'│
  │                         │    WHERE TrainID=$train_id       │
  │                         │←── rows affected ──────────────│
  │                         │                                  │
  │←── "Status set and ────│                                  │
  │    bookings updated"    │                                  │
```

**Tables touched**: TICKET (1 UPDATE — bulk update on all tickets for the train)

**Important**: This updates ALL tickets for the train to the same status. Individual ticket status changes are not supported in the current UI.

---

### 6.6 User Deletion Flow (Cascade Demonstration)

```
Admin                   PHP (admin_users.php)               MySQL
  │                         │                                  │
  │── POST delete_user ───→│                                  │
  │    (user_id=2)          │── DELETE FROM USERS ──────────→│
  │                         │    WHERE UserID=2                │
  │                         │                                  │
  │                         │   [MySQL cascades automatically]│
  │                         │   ├── DELETE FROM PASSENGER     │
  │                         │   │   WHERE UserID=2            │
  │                         │   ├── DELETE FROM TICKET        │
  │                         │   │   WHERE UserID=2            │
  │                         │   └── DELETE FROM TICKET        │
  │                         │       WHERE PassengerID IN      │
  │                         │       (deleted passenger IDs)   │
  │                         │←── OK ─────────────────────────│
```

**Tables touched**: USERS (1 DELETE), PASSENGER (auto-cascade), TICKET (auto-cascade from both paths)

---

## 7. Data Lifecycle Summary

### What Happens When Each Entity Is Created/Deleted

| Operation | Direct Table | Cascade Effects |
|-----------|-------------|-----------------|
| Add Station | STATION (INSERT) | None |
| Delete Station | STATION (DELETE) | Trains referencing this station become invalid (FK violation, no CASCADE on TRAIN→STATION) |
| Add User | USERS (INSERT) | None |
| Delete User | USERS (DELETE) | All PASSENGER rows + all TICKET rows for this user are deleted |
| Add Train | TRAIN (INSERT) | None |
| Delete Train | TRAIN (DELETE) | All TICKET rows + all TSTATUS rows for this train are deleted |
| Add Passenger | PASSENGER (INSERT) | None |
| Delete Passenger | PASSENGER (DELETE) | All TICKET rows for this passenger are deleted |
| Create Ticket | TICKET (INSERT) | None (leaf table) |
| Add TSTATUS | TSTATUS (INSERT) | None (leaf table) |

### Critical Gap: Station Deletion

The FK from TRAIN to STATION uses StationName (not StationID) and does **NOT** have an `ON DELETE` clause specified. InnoDB's default behavior is **RESTRICT** — so deleting a station that is referenced by any train will **fail with a foreign key constraint error**. The station will NOT be deleted, and no orphaned references will be created.

The current code does not check for train references before attempting to delete a station, meaning the user will see a raw MySQL error message instead of a friendly warning.

---

## 8. Session State & Data Access Matrix

### Session Variables
```php
$_SESSION['user_id']  // UserID from USERS table (or 0 for hardcoded admin)
$_SESSION['role']     // 'admin' or 'user'
$_SESSION['email']    // User's email address
```

### Who Accesses What

| Page | Role | Tables Read | Tables Written |
|------|------|-------------|----------------|
| index.php | anyone | USERS | — |
| signup.php | anyone | USERS | USERS |
| user_dashboard.php | user | USERS, STATION, TRAIN, PASSENGER, TICKET | PASSENGER, TICKET |
| search.php | user | STATION, TRAIN | — |
| booking.php | user | TRAIN, PASSENGER, TICKET | TICKET |
| bookings.php | user | TICKET, PASSENGER, TRAIN | — |
| profile.php | user | USERS | USERS |
| admin_dashboard.php | admin | TRAIN, STATION | TRAIN, STATION |
| admin_stations.php | admin | STATION | STATION |
| admin_trains.php | admin | STATION, TRAIN | TRAIN |
| admin_status.php | admin | TRAIN, TICKET | TICKET |
| admin_bookings.php | admin | TICKET, PASSENGER, TRAIN, USERS | — |
| admin_users.php | admin | USERS | USERS |

---

## 9. Unused Schema Elements

### TSTATUS Table
Defined in `schema.sql` with sample data, but **no PHP page reads from or writes to TSTATUS**. The availability check in `booking.php` and `user_dashboard.php` uses:
```sql
SELECT COUNT(*) FROM TICKET WHERE TrainID = $train_id AND Status = 'CNF'
```
compared against `TRAIN.TotalSeats`, completely bypassing TSTATUS.

### PNR Field
The `Pnr` column in TICKET is defined as `UNIQUE NULL`, but no INSERT statement in any PHP file generates a PNR value. All tickets are created with `Pnr = NULL`.

### Admin User in DB
The schema inserts an admin user with a dummy bcrypt hash, but the login page uses a hardcoded check (`admin@gmail.com` / `admin123`) instead of looking up this DB row.

---

## 10. Query Frequency Analysis

### Most Frequently Executed Queries (by typical usage)

| Rank | Query | Pages | Trigger |
|------|-------|-------|---------|
| 1 | `SELECT * FROM USERS WHERE Email = ?` | index.php | Every login |
| 2 | `SELECT DISTINCT StationName FROM STATION` | search, user_dash, admin_dash, admin_trains | Every page load with dropdowns |
| 3 | `SELECT * FROM TRAIN [WHERE ...]` | search, user_dash, booking, admin pages | Every train search/display |
| 4 | `SELECT * FROM PASSENGER WHERE UserID = ?` | user_dash, booking | Every booking attempt |
| 5 | `SELECT COUNT(*) FROM TICKET WHERE TrainID=? AND Status='CNF'` | user_dash, booking | Every booking attempt |
| 6 | `INSERT INTO TICKET (...)` | user_dash, booking | Every booking confirmation |
| 7 | 3-table JOIN (TICKET+PASSENGER+TRAIN) | bookings, user_dash | Every bookings view |
| 8 | `SELECT * FROM TRAIN` | admin_dash, admin_trains | Every admin page load |

### Writes by Table

| Table | INSERT | UPDATE | DELETE | Pages |
|-------|--------|--------|--------|-------|
| USERS | signup.php | profile.php, admin_users.php | admin_users.php |
| STATION | admin_stations, admin_dash | admin_stations, admin_dash | admin_stations, admin_dash |
| TRAIN | admin_trains, admin_dash | admin_trains, admin_dash | admin_trains, admin_dash |
| PASSENGER | user_dashboard | user_dashboard | user_dashboard |
| TICKET | user_dashboard, booking | admin_status (bulk) | (cascade only) |
| TSTATUS | (none) | (none) | (none) |

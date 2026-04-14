# BCNF Analysis and Final Schemas

Date: 2026-04-08

Purpose: provide a concise, corrected BCNF analysis for the project relations, confirm keys and functional dependencies (FDs), and deliver final CREATE TABLE schemas. The goal is to ensure each relation satisfies Boyce–Codd Normal Form: for every non-trivial FD X -> A, X must be a superkey.

Summary conclusion
- The relations `USERS`, `TICKET`, `PASSENGER`, `TRAIN`, and `STATION` (as described below) each have a candidate key equal to the left-hand side of the declared FD and thus conform to BCNF.
- The `TSTATUS` (train status / schedule) relation is treated as an independent relation with composite primary key (TrainNo, SDate, Class). It does not embed attributes from other relations other than a reference to the train identifier (optional FK). This design meets BCNF.

=================================================================
Table: USERS

FDs
- {UserID} -> {Name, Email, City, State, Pincode, Gender, Phone, Address, Password, Role}

Key
- {UserID}

BCNF Check
- Every FD has a left-hand side that is the key (`UserID`), so relation is in BCNF.

Notes
- Email is declared UNIQUE in the DB (index). If you ever have FD Email -> other attributes, Email would be a candidate key and BCNF still holds.

Final schema (recommended)
```
CREATE TABLE USERS (
	UserID INT PRIMARY KEY AUTO_INCREMENT,
	Name VARCHAR(100) NOT NULL,
	Email VARCHAR(100) UNIQUE NOT NULL,
	Phone VARCHAR(15) NOT NULL,
	Address TEXT NOT NULL,
	Password VARCHAR(255) NOT NULL,
	Role ENUM('admin','user') DEFAULT 'user',
	CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

=================================================================
Table: TICKET

FDs
- {Pnr} -> {doj, Class, Status, Book_date, Train_no, User_id, pid, Fare}

Key
- {Pnr}

BCNF Check
- Left-hand side of FD is the key (`Pnr`), so relation is in BCNF.

Notes
- Implementation in this project uses `TicketID` (auto-increment) or `Pnr` as a unique ticket identifier. Keep it unique.

Final schema (recommended)
```
CREATE TABLE TICKET (
	TicketID INT PRIMARY KEY AUTO_INCREMENT,
	Pnr VARCHAR(50) UNIQUE,
	UserID INT NOT NULL,
	TrainID INT NOT NULL,
	PassengerID INT NOT NULL,
	Status ENUM('CNF','WTL','RJD') DEFAULT 'CNF',
	BookingDate DATE NOT NULL,
	Fare DECIMAL(10,2) NOT NULL,
	CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (UserID) REFERENCES USERS(UserID) ON DELETE CASCADE,
	FOREIGN KEY (TrainID) REFERENCES TRAIN(TrainID) ON DELETE CASCADE,
	FOREIGN KEY (PassengerID) REFERENCES PASSENGER(PassengerID) ON DELETE CASCADE
);
```

=================================================================
Table: PASSENGER

FDs
- {pid} -> {name, gender, age}

Key
- {pid}

BCNF Check
- Left-hand side is the key, so relation is in BCNF.

Final schema (recommended)
```
CREATE TABLE PASSENGER (
	PassengerID INT PRIMARY KEY AUTO_INCREMENT,
	UserID INT NOT NULL,
	PassengerName VARCHAR(100) NOT NULL,
	Age INT NOT NULL,
	Gender ENUM('Male','Female','Other') NOT NULL,
	CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (UserID) REFERENCES USERS(UserID) ON DELETE CASCADE
);
```

=================================================================
Table: TRAIN

FDs
- {train_no} -> {train_name, source_id, dest_id, arr_time, dep_time, total_seats, fare}

Key
- {train_no}

BCNF Check
- Left-hand side is the key, therefore train relation is in BCNF.

Notes
- We added `ArrivalTime` and `DepartureTime` (TIME) for scheduling; these depend only on `train_no` and so conform to BCNF.

Final schema (recommended)
```
CREATE TABLE TRAIN (
	TrainID INT PRIMARY KEY AUTO_INCREMENT,
	TrainNumber VARCHAR(20) UNIQUE NOT NULL,
	TrainName VARCHAR(100) NOT NULL,
	FromStation VARCHAR(100) NOT NULL,
	ToStation VARCHAR(100) NOT NULL,
	TotalSeats INT NOT NULL,
	Fare DECIMAL(10,2) NOT NULL,
	DepartureTime TIME NULL,
	ArrivalTime TIME NULL,
	CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (FromStation) REFERENCES STATION(StationName),
	FOREIGN KEY (ToStation) REFERENCES STATION(StationName)
);
```

=================================================================
Table: STATION

FDs
- {station_id} -> {station_name, city, state}

Key
- {station_id}

BCNF Check
- Left-hand side is the key, BCNF holds.

Final schema (recommended)
```
CREATE TABLE STATION (
	StationID INT PRIMARY KEY AUTO_INCREMENT,
	StationName VARCHAR(100) NOT NULL UNIQUE,
	City VARCHAR(100) NOT NULL,
	State VARCHAR(100) NOT NULL
);
```

=================================================================
Table: TSTATUS (train status / daily schedule / class availability)

Requirement and FD given by user
- FD: {train_no, sdate, class} -> { total_seat, total_fare }
- Key: {train_no, sdate, class}

Design decisions and BCNF
- The FD left-hand side is the composite key (train_no, sdate, class). Because the determinant is the key, the relation is in BCNF.
- TSTATUS should be a separate relation that captures per-train, per-date, per-class availability/fare. It must not duplicate non-key attributes from other tables. It may optionally reference `TRAIN(TrainNumber)` via FK to help integrity, but the functional dependency and key reside entirely in TSTATUS.

Final schema (recommended)
```
CREATE TABLE TSTATUS (
	TrainNumber VARCHAR(20) NOT NULL,
	SDate DATE NOT NULL,
	Class VARCHAR(20) NOT NULL,
	TotalSeats INT NOT NULL,
	TotalFare DECIMAL(10,2) NOT NULL,
	PRIMARY KEY (TrainNumber, SDate, Class),
	-- optional foreign key:
	FOREIGN KEY (TrainNumber) REFERENCES TRAIN(TrainNumber) ON DELETE CASCADE
);
```

Notes about the `TSTATUS` relation
- Keep `TrainNumber` values consistent (same format) as `TRAIN.TrainNumber` if you use the optional FK. Using `TrainID` (INT) instead of `TrainNumber` is more normalized and efficient; in that case define `TrainID INT` and FK to `TRAIN(TrainID)` and keep the primary key `(TrainID, SDate, Class)`.
- Do not store attributes from other relations in `TSTATUS` unless they are functionally dependent on the TSTATUS key.

=================================================================
Final used tables (short list)
- USERS
- STATION
- TRAIN
- PASSENGER
- TICKET
- TSTATUS

Closing note
All the relations above obey BCNF because every declared FD has a determinant that is a key (or superkey). `TSTATUS` is designed as a focused relation capturing schedule/availability by (train, date, class) and is intentionally independent except for an optional referential integrity link to the `TRAIN` table.

If you want, I can:
- generate and run a migration SQL file to add `DepartureTime` and `ArrivalTime` to the live `TRAIN` table (safe ALTER statements),
- convert `TSTATUS` to use `TrainID` instead of `TrainNumber` and update queries accordingly.

---
Generated by project assistant on 2026-04-08

-- Train Booking System Database Schema (BCNF Normalized)

CREATE DATABASE IF NOT EXISTS train_booking;
USE train_booking;

-- Drop only the six project tables if they exist (safe reset)
DROP TABLE IF EXISTS TSTATUS;
DROP TABLE IF EXISTS TICKET;
DROP TABLE IF EXISTS PASSENGER;
DROP TABLE IF EXISTS TRAIN;
DROP TABLE IF EXISTS STATION;
DROP TABLE IF EXISTS USERS;

-- USERS Table
CREATE TABLE USERS (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Role ENUM('admin', 'user') DEFAULT 'user',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- STATION Table
CREATE TABLE STATION (
    StationID INT PRIMARY KEY AUTO_INCREMENT,
    StationName VARCHAR(100) NOT NULL UNIQUE,
    City VARCHAR(100) NOT NULL,
    State VARCHAR(100) NOT NULL
);

-- TRAIN Table (includes arrival/departure times)
CREATE TABLE TRAIN (
    TrainID INT PRIMARY KEY AUTO_INCREMENT,
    TrainNumber VARCHAR(20) UNIQUE NOT NULL,
    TrainName VARCHAR(100) NOT NULL,
    FromStation VARCHAR(100) NOT NULL,
    ToStation VARCHAR(100) NOT NULL,
    TotalSeats INT NOT NULL,
    Fare DECIMAL(10, 2) NOT NULL,
    DepartureTime TIME NULL,
    ArrivalTime TIME NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FromStation) REFERENCES STATION(StationName),
    FOREIGN KEY (ToStation) REFERENCES STATION(StationName)
);

-- PASSENGER Table
CREATE TABLE PASSENGER (
    PassengerID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    PassengerName VARCHAR(100) NOT NULL,
    Age INT NOT NULL,
    Gender ENUM('Male', 'Female', 'Other') NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES USERS(UserID) ON DELETE CASCADE
);

-- TICKET Table
CREATE TABLE TICKET (
    TicketID INT PRIMARY KEY AUTO_INCREMENT,
    Pnr VARCHAR(50) UNIQUE NULL,
    UserID INT NOT NULL,
    TrainID INT NOT NULL,
    PassengerID INT NOT NULL,
    Status ENUM('CNF', 'WTL', 'RJD') DEFAULT 'CNF',
    BookingDate DATE NOT NULL,
    Fare DECIMAL(10, 2) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES USERS(UserID) ON DELETE CASCADE,
    FOREIGN KEY (TrainID) REFERENCES TRAIN(TrainID) ON DELETE CASCADE,
    FOREIGN KEY (PassengerID) REFERENCES PASSENGER(PassengerID) ON DELETE CASCADE
);

-- TSTATUS Table (per-train, per-date, per-class availability/fare)
CREATE TABLE TSTATUS (
    TrainID INT NOT NULL,
    SDate DATE NOT NULL,
    Class VARCHAR(20) NOT NULL,
    TotalSeats INT NOT NULL,
    TotalFare DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (TrainID, SDate, Class),
    FOREIGN KEY (TrainID) REFERENCES TRAIN(TrainID) ON DELETE CASCADE
);

-- Insert Sample Stations
INSERT INTO STATION (StationName, City, State) VALUES
('Mumbai Central', 'Mumbai', 'Maharashtra'),
('Delhi Junction', 'Delhi', 'Delhi'),
('Bangalore City', 'Bangalore', 'Karnataka'),
('Kolkata Central', 'Kolkata', 'West Bengal'),
('Chennai Central', 'Chennai', 'Tamil Nadu'),
('Hyderabad Deccan', 'Hyderabad', 'Telangana'),
('Pune Junction', 'Pune', 'Maharashtra'),
('Ahmedabad Junction', 'Ahmedabad', 'Gujarat');

-- Insert Admin User (admin123)
INSERT INTO USERS (Name, Email, Phone, Password, Role) VALUES
('Admin User', 'admin@gmail.com', '9999999999', '$2y$10$s6z4RwMDqJsIIWfWdD3dIey0v9VrPQB5R8p6q7m8n9o0p1q2r3s4', 'admin');

-- Insert Sample User (user123)
INSERT INTO USERS (Name, Email, Phone, Password, Role) VALUES
('John Doe', 'user@gmail.com', '9876543210', '$2y$10$n0p1q2r3s4t5u6v7w8x9y0z1a2b3c4d5e6f7g8h9i0j1k2l3m4n5', 'user');

-- Insert Sample Trains
INSERT INTO TRAIN (TrainNumber, TrainName, FromStation, ToStation, TotalSeats, Fare, DepartureTime, ArrivalTime) VALUES
('TR001', 'Rajdhani Express', 'Mumbai Central', 'Delhi Junction', 150, 2500.00, '08:00:00', '20:00:00'),
('TR002', 'Shatabdi Express', 'Delhi Junction', 'Mumbai Central', 150, 2500.00, '09:00:00', '21:00:00'),
('TR003', 'Bangalore Express', 'Mumbai Central', 'Bangalore City', 200, 1800.00, '07:30:00', '19:00:00');

-- Insert Sample Passengers
INSERT INTO PASSENGER (UserID, PassengerName, Age, Gender) VALUES
(2, 'Alice Johnson', 28, 'Female'),
(2, 'Bob Smith', 32, 'Male');

-- Insert Sample Tickets (Bookings)
INSERT INTO TICKET (Pnr, UserID, TrainID, PassengerID, Status, BookingDate, Fare) VALUES
('PNR001', 2, 1, 1, 'CNF', '2024-04-01', 2500.00),
('PNR002', 2, 3, 2, 'CNF', '2024-04-02', 1800.00);

-- Insert Sample TSTATUS rows
INSERT INTO TSTATUS (TrainID, SDate, Class, TotalSeats, TotalFare) VALUES
(1, '2026-04-08', 'AC', 50, 2500.00),
(1, '2026-04-08', 'Sleeper', 100, 1200.00);

-- Create Indexes for better performance
CREATE INDEX idx_user_email ON USERS(Email);
CREATE INDEX idx_passenger_userid ON PASSENGER(UserID);
CREATE INDEX idx_ticket_userid ON TICKET(UserID);
CREATE INDEX idx_ticket_trainid ON TICKET(TrainID);
CREATE INDEX idx_train_stations ON TRAIN(FromStation, ToStation);

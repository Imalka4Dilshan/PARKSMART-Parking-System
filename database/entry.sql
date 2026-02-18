-- Create parking_slots table
CREATE TABLE parking_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slot_number VARCHAR(10),
  vehicle_type VARCHAR(20),
  is_occupied TINYINT(1) DEFAULT 0
);

-- Create vehicles table
CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_number VARCHAR(20),
  vehicle_type VARCHAR(20),
  slot_number VARCHAR(10),
  entry_time DATETIME DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE vehicles ADD entry_time1 DATETIME DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE vehicles
ADD COLUMN exit_time DATETIME NULL,
ADD COLUMN fee DECIMAL(10,2) DEFAULT 0;

CREATE TABLE exited_vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_number VARCHAR(20) NOT NULL,
  vehicle_type VARCHAR(20),
  slot_number VARCHAR(10) NOT NULL,
  entry_time DATETIME NOT NULL,
  exit_time DATETIME NOT NULL,
  fee DECIMAL(10,2) NOT NULL
);
ALTER TABLE exited_vehicles ADD feee INT(11) NOT NULL DEFAULT 0;
////
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 50 Car Slots (A1 - A50)
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('A1', 'car', 0),
('A2', 'car', 0),
('A3', 'car', 0),
('A4', 'car', 0),
('A5', 'car', 0),
('A6', 'car', 0),
('A7', 'car', 0),
('A8', 'car', 0),
('A9', 'car', 0),
('A10', 'car', 0),
('A11', 'car', 0),
('A12', 'car', 0),
('A13', 'car', 0),
('A14', 'car', 0),
('A15', 'car', 0),
('A16', 'car', 0),
('A17', 'car', 0),
('A18', 'car', 0),
('A19', 'car', 0),
('A20', 'car', 0),
('A21', 'car', 0),
('A22', 'car', 0),
('A23', 'car', 0),
('A24', 'car', 0),
('A25', 'car', 0),
('A26', 'car', 0),
('A27', 'car', 0),
('A28', 'car', 0),
('A29', 'car', 0),
('A30', 'car', 0),
('A31', 'car', 0),
('A32', 'car', 0),
('A33', 'car', 0),
('A34', 'car', 0),
('A35', 'car', 0),
('A36', 'car', 0),
('A37', 'car', 0),
('A38', 'car', 0),
('A39', 'car', 0),
('A40', 'car', 0),
('A41', 'car', 0),
('A42', 'car', 0),
('A43', 'car', 0),
('A44', 'car', 0),
('A45', 'car', 0),
('A46', 'car', 0),
('A47', 'car', 0),
('A48', 'car', 0),
('A49', 'car', 0),
('A50', 'car', 0);

-- 30 Bike Slots (B1 - B30)
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('B1', 'bike', 0),
('B2', 'bike', 0),
('B3', 'bike', 0),
('B4', 'bike', 0),
('B5', 'bike', 0),
('B6', 'bike', 0),
('B7', 'bike', 0),
('B8', 'bike', 0),
('B9', 'bike', 0),
('B10', 'bike', 0),
('B11', 'bike', 0),
('B12', 'bike', 0),
('B13', 'bike', 0),
('B14', 'bike', 0),
('B15', 'bike', 0),
('B16', 'bike', 0),
('B17', 'bike', 0),
('B18', 'bike', 0),
('B19', 'bike', 0),
('B20', 'bike', 0),
('B21', 'bike', 0),
('B22', 'bike', 0),
('B23', 'bike', 0),
('B24', 'bike', 0),
('B25', 'bike', 0),
('B26', 'bike', 0),
('B27', 'bike', 0),
('B28', 'bike', 0),
('B29', 'bike', 0),
('B30', 'bike', 0);

-- 20 Van Slots (C1 - C20)
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('C1', 'van', 0),
('C2', 'van', 0),
('C3', 'van', 0),
('C4', 'van', 0),
('C5', 'van', 0),
('C6', 'van', 0),
('C7', 'van', 0),
('C8', 'van', 0),
('C9', 'van', 0),
('C10', 'van', 0),
('C11', 'van', 0),
('C12', 'van', 0),
('C13', 'van', 0),
('C14', 'van', 0),
('C15', 'van', 0),
('C16', 'van', 0),
('C17', 'van', 0),
('C18', 'van', 0),
('C19', 'van', 0),
('C20', 'van', 0);


CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_number VARCHAR(20),
  vehicle_type VARCHAR(20),
  slot_number VARCHAR(10),
  booking_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) DEFAULT 'pending'
);








-- Update parking slots to include new vehicle types
-- First, clear existing slots
TRUNCATE TABLE parking_slots;

-- Add 50 Car slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('C-01', 'car', 0), ('C-02', 'car', 0), ('C-03', 'car', 0), ('C-04', 'car', 0), ('C-05', 'car', 0),
('C-06', 'car', 0), ('C-07', 'car', 0), ('C-08', 'car', 0), ('C-09', 'car', 0), ('C-10', 'car', 0),
('C-11', 'car', 0), ('C-12', 'car', 0), ('C-13', 'car', 0), ('C-14', 'car', 0), ('C-15', 'car', 0),
('C-16', 'car', 0), ('C-17', 'car', 0), ('C-18', 'car', 0), ('C-19', 'car', 0), ('C-20', 'car', 0),
('C-21', 'car', 0), ('C-22', 'car', 0), ('C-23', 'car', 0), ('C-24', 'car', 0), ('C-25', 'car', 0),
('C-26', 'car', 0), ('C-27', 'car', 0), ('C-28', 'car', 0), ('C-29', 'car', 0), ('C-30', 'car', 0),
('C-31', 'car', 0), ('C-32', 'car', 0), ('C-33', 'car', 0), ('C-34', 'car', 0), ('C-35', 'car', 0),
('C-36', 'car', 0), ('C-37', 'car', 0), ('C-38', 'car', 0), ('C-39', 'car', 0), ('C-40', 'car', 0),
('C-41', 'car', 0), ('C-42', 'car', 0), ('C-43', 'car', 0), ('C-44', 'car', 0), ('C-45', 'car', 0),
('C-46', 'car', 0), ('C-47', 'car', 0), ('C-48', 'car', 0), ('C-49', 'car', 0), ('C-50', 'car', 0);

-- Add 50 Van slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('V-01', 'van', 0), ('V-02', 'van', 0), ('V-03', 'van', 0), ('V-04', 'van', 0), ('V-05', 'van', 0),
('V-06', 'van', 0), ('V-07', 'van', 0), ('V-08', 'van', 0), ('V-09', 'van', 0), ('V-10', 'van', 0),
('V-11', 'van', 0), ('V-12', 'van', 0), ('V-13', 'van', 0), ('V-14', 'van', 0), ('V-15', 'van', 0),
('V-16', 'van', 0), ('V-17', 'van', 0), ('V-18', 'van', 0), ('V-19', 'van', 0), ('V-20', 'van', 0),
('V-21', 'van', 0), ('V-22', 'van', 0), ('V-23', 'van', 0), ('V-24', 'van', 0), ('V-25', 'van', 0),
('V-26', 'van', 0), ('V-27', 'van', 0), ('V-28', 'van', 0), ('V-29', 'van', 0), ('V-30', 'van', 0),
('V-31', 'van', 0), ('V-32', 'van', 0), ('V-33', 'van', 0), ('V-34', 'van', 0), ('V-35', 'van', 0),
('V-36', 'van', 0), ('V-37', 'van', 0), ('V-38', 'van', 0), ('V-39', 'van', 0), ('V-40', 'van', 0),
('V-41', 'van', 0), ('V-42', 'van', 0), ('V-43', 'van', 0), ('V-44', 'van', 0), ('V-45', 'van', 0),
('V-46', 'van', 0), ('V-47', 'van', 0), ('V-48', 'van', 0), ('V-49', 'van', 0), ('V-50', 'van', 0);

-- Add 50 Jeep slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('J-01', 'jeep', 0), ('J-02', 'jeep', 0), ('J-03', 'jeep', 0), ('J-04', 'jeep', 0), ('J-05', 'jeep', 0),
('J-06', 'jeep', 0), ('J-07', 'jeep', 0), ('J-08', 'jeep', 0), ('J-09', 'jeep', 0), ('J-10', 'jeep', 0),
('J-11', 'jeep', 0), ('J-12', 'jeep', 0), ('J-13', 'jeep', 0), ('J-14', 'jeep', 0), ('J-15', 'jeep', 0),
('J-16', 'jeep', 0), ('J-17', 'jeep', 0), ('J-18', 'jeep', 0), ('J-19', 'jeep', 0), ('J-20', 'jeep', 0),
('J-21', 'jeep', 0), ('J-22', 'jeep', 0), ('J-23', 'jeep', 0), ('J-24', 'jeep', 0), ('J-25', 'jeep', 0),
('J-26', 'jeep', 0), ('J-27', 'jeep', 0), ('J-28', 'jeep', 0), ('J-29', 'jeep', 0), ('J-30', 'jeep', 0),
('J-31', 'jeep', 0), ('J-32', 'jeep', 0), ('J-33', 'jeep', 0), ('J-34', 'jeep', 0), ('J-35', 'jeep', 0),
('J-36', 'jeep', 0), ('J-37', 'jeep', 0), ('J-38', 'jeep', 0), ('J-39', 'jeep', 0), ('J-40', 'jeep', 0),
('J-41', 'jeep', 0), ('J-42', 'jeep', 0), ('J-43', 'jeep', 0), ('J-44', 'jeep', 0), ('J-45', 'jeep', 0),
('J-46', 'jeep', 0), ('J-47', 'jeep', 0), ('J-48', 'jeep', 0), ('J-49', 'jeep', 0), ('J-50', 'jeep', 0);

-- Add 50 Lorry slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('L-01', 'lorry', 0), ('L-02', 'lorry', 0), ('L-03', 'lorry', 0), ('L-04', 'lorry', 0), ('L-05', 'lorry', 0),
('L-06', 'lorry', 0), ('L-07', 'lorry', 0), ('L-08', 'lorry', 0), ('L-09', 'lorry', 0), ('L-10', 'lorry', 0),
('L-11', 'lorry', 0), ('L-12', 'lorry', 0), ('L-13', 'lorry', 0), ('L-14', 'lorry', 0), ('L-15', 'lorry', 0),
('L-16', 'lorry', 0), ('L-17', 'lorry', 0), ('L-18', 'lorry', 0), ('L-19', 'lorry', 0), ('L-20', 'lorry', 0),
('L-21', 'lorry', 0), ('L-22', 'lorry', 0), ('L-23', 'lorry', 0), ('L-24', 'lorry', 0), ('L-25', 'lorry', 0),
('L-26', 'lorry', 0), ('L-27', 'lorry', 0), ('L-28', 'lorry', 0), ('L-29', 'lorry', 0), ('L-30', 'lorry', 0),
('L-31', 'lorry', 0), ('L-32', 'lorry', 0), ('L-33', 'lorry', 0), ('L-34', 'lorry', 0), ('L-35', 'lorry', 0),
('L-36', 'lorry', 0), ('L-37', 'lorry', 0), ('L-38', 'lorry', 0), ('L-39', 'lorry', 0), ('L-40', 'lorry', 0),
('L-41', 'lorry', 0), ('L-42', 'lorry', 0), ('L-43', 'lorry', 0), ('L-44', 'lorry', 0), ('L-45', 'lorry', 0),
('L-46', 'lorry', 0), ('L-47', 'lorry', 0), ('L-48', 'lorry', 0), ('L-49', 'lorry', 0), ('L-50', 'lorry', 0);

-- Add 50 Three Wheel slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('T-01', 'threewheel', 0), ('T-02', 'threewheel', 0), ('T-03', 'threewheel', 0), ('T-04', 'threewheel', 0), ('T-05', 'threewheel', 0),
('T-06', 'threewheel', 0), ('T-07', 'threewheel', 0), ('T-08', 'threewheel', 0), ('T-09', 'threewheel', 0), ('T-10', 'threewheel', 0),
('T-11', 'threewheel', 0), ('T-12', 'threewheel', 0), ('T-13', 'threewheel', 0), ('T-14', 'threewheel', 0), ('T-15', 'threewheel', 0),
('T-16', 'threewheel', 0), ('T-17', 'threewheel', 0), ('T-18', 'threewheel', 0), ('T-19', 'threewheel', 0), ('T-20', 'threewheel', 0),
('T-21', 'threewheel', 0), ('T-22', 'threewheel', 0), ('T-23', 'threewheel', 0), ('T-24', 'threewheel', 0), ('T-25', 'threewheel', 0),
('T-26', 'threewheel', 0), ('T-27', 'threewheel', 0), ('T-28', 'threewheel', 0), ('T-29', 'threewheel', 0), ('T-30', 'threewheel', 0),
('T-31', 'threewheel', 0), ('T-32', 'threewheel', 0), ('T-33', 'threewheel', 0), ('T-34', 'threewheel', 0), ('T-35', 'threewheel', 0),
('T-36', 'threewheel', 0), ('T-37', 'threewheel', 0), ('T-38', 'threewheel', 0), ('T-39', 'threewheel', 0), ('T-40', 'threewheel', 0),
('T-41', 'threewheel', 0), ('T-42', 'threewheel', 0), ('T-43', 'threewheel', 0), ('T-44', 'threewheel', 0), ('T-45', 'threewheel', 0),
('T-46', 'threewheel', 0), ('T-47', 'threewheel', 0), ('T-48', 'threewheel', 0), ('T-49', 'threewheel', 0), ('T-50', 'threewheel', 0);

-- Add 50 Bus slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('B-01', 'bus', 0), ('B-02', 'bus', 0), ('B-03', 'bus', 0), ('B-04', 'bus', 0), ('B-05', 'bus', 0),
('B-06', 'bus', 0), ('B-07', 'bus', 0), ('B-08', 'bus', 0), ('B-09', 'bus', 0), ('B-10', 'bus', 0),
('B-11', 'bus', 0), ('B-12', 'bus', 0), ('B-13', 'bus', 0), ('B-14', 'bus', 0), ('B-15', 'bus', 0),
('B-16', 'bus', 0), ('B-17', 'bus', 0), ('B-18', 'bus', 0), ('B-19', 'bus', 0), ('B-20', 'bus', 0),
('B-21', 'bus', 0), ('B-22', 'bus', 0), ('B-23', 'bus', 0), ('B-24', 'bus', 0), ('B-25', 'bus', 0),
('B-26', 'bus', 0), ('B-27', 'bus', 0), ('B-28', 'bus', 0), ('B-29', 'bus', 0), ('B-30', 'bus', 0),
('B-31', 'bus', 0), ('B-32', 'bus', 0), ('B-33', 'bus', 0), ('B-34', 'bus', 0), ('B-35', 'bus', 0),
('B-36', 'bus', 0), ('B-37', 'bus', 0), ('B-38', 'bus', 0), ('B-39', 'bus', 0), ('B-40', 'bus', 0),
('B-41', 'bus', 0), ('B-42', 'bus', 0), ('B-43', 'bus', 0), ('B-44', 'bus', 0), ('B-45', 'bus', 0),
('B-46', 'bus', 0), ('B-47', 'bus', 0), ('B-48', 'bus', 0), ('B-49', 'bus', 0), ('B-50', 'bus', 0);

-- Add 50 Bike slots
INSERT INTO parking_slots (slot_number, vehicle_type, is_occupied) VALUES
('BK-01', 'bike', 0), ('BK-02', 'bike', 0), ('BK-03', 'bike', 0), ('BK-04', 'bike', 0), ('BK-05', 'bike', 0),
('BK-06', 'bike', 0), ('BK-07', 'bike', 0), ('BK-08', 'bike', 0), ('BK-09', 'bike', 0), ('BK-10', 'bike', 0),
('BK-11', 'bike', 0), ('BK-12', 'bike', 0), ('BK-13', 'bike', 0), ('BK-14', 'bike', 0), ('BK-15', 'bike', 0),
('BK-16', 'bike', 0), ('BK-17', 'bike', 0), ('BK-18', 'bike', 0), ('BK-19', 'bike', 0), ('BK-20', 'bike', 0),
('BK-21', 'bike', 0), ('BK-22', 'bike', 0), ('BK-23', 'bike', 0), ('BK-24', 'bike', 0), ('BK-25', 'bike', 0),
('BK-26', 'bike', 0), ('BK-27', 'bike', 0), ('BK-28', 'bike', 0), ('BK-29', 'bike', 0), ('BK-30', 'bike', 0),
('BK-31', 'bike', 0), ('BK-32', 'bike', 0), ('BK-33', 'bike', 0), ('BK-34', 'bike', 0), ('BK-35', 'bike', 0),
('BK-36', 'bike', 0), ('BK-37', 'bike', 0), ('BK-38', 'bike', 0), ('BK-39', 'bike', 0), ('BK-40', 'bike', 0),
('BK-41', 'bike', 0), ('BK-42', 'bike', 0), ('BK-43', 'bike', 0), ('BK-44', 'bike', 0), ('BK-45', 'bike', 0),
('BK-46', 'bike', 0), ('BK-47', 'bike', 0), ('BK-48', 'bike', 0), ('BK-49', 'bike', 0), ('BK-50', 'bike', 0);

-- MotoTrack Database Schema
-- Bike Tracker Web Application

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bikes Table
CREATE TABLE IF NOT EXISTS bikes (
    bike_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bike_name VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    registration_number VARCHAR(20) UNIQUE,
    engine_capacity INT,
    purchase_date DATE,
    purchase_price DECIMAL(10, 2),
    current_odometer INT DEFAULT 0,
    fuel_tank_capacity DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Trips Table
CREATE TABLE IF NOT EXISTS trips (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    bike_id INT NOT NULL,
    user_id INT NOT NULL,
    trip_date DATE NOT NULL,
    start_odometer INT NOT NULL,
    end_odometer INT NOT NULL,
    distance INT GENERATED ALWAYS AS (end_odometer - start_odometer) STORED,
    start_location VARCHAR(200),
    end_location VARCHAR(200),
    trip_purpose ENUM('Commute', 'Leisure', 'Long Ride', 'Delivery', 'Other') DEFAULT 'Other',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bike_id) REFERENCES bikes(bike_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Fuel Logs Table
CREATE TABLE IF NOT EXISTS fuel_logs (
    fuel_id INT AUTO_INCREMENT PRIMARY KEY,
    bike_id INT NOT NULL,
    user_id INT NOT NULL,
    fill_date DATE NOT NULL,
    odometer_reading INT NOT NULL,
    fuel_quantity DECIMAL(6, 2) NOT NULL,
    fuel_cost DECIMAL(8, 2) NOT NULL,
    price_per_liter DECIMAL(6, 2) NOT NULL,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric') DEFAULT 'Petrol',
    is_full_tank BOOLEAN DEFAULT TRUE,
    fuel_station VARCHAR(200),
    mileage DECIMAL(6, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bike_id) REFERENCES bikes(bike_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Service Records Table
CREATE TABLE IF NOT EXISTS service_records (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    bike_id INT NOT NULL,
    user_id INT NOT NULL,
    service_date DATE NOT NULL,
    odometer_reading INT NOT NULL,
    service_type ENUM('Regular Service', 'Oil Change', 'Tire Replacement', 'Brake Service', 'Chain Maintenance', 'Battery Replacement', 'General Repair', 'Other') NOT NULL,
    service_center VARCHAR(200),
    service_cost DECIMAL(10, 2) NOT NULL,
    parts_replaced TEXT,
    next_service_km INT,
    next_service_date DATE,
    description TEXT,
    invoice_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bike_id) REFERENCES bikes(bike_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Reminders Table
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    bike_id INT NOT NULL,
    user_id INT NOT NULL,
    reminder_type ENUM('Service', 'Insurance', 'Pollution Check', 'Registration Renewal', 'Tire Change', 'Chain Lubrication', 'Custom') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    due_odometer INT,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_date DATE,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bike_id) REFERENCES bikes(bike_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Expenses Table (General bike-related expenses)
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    bike_id INT NOT NULL,
    user_id INT NOT NULL,
    expense_date DATE NOT NULL,
    expense_category ENUM('Insurance', 'Tax', 'Parking', 'Toll', 'Accessories', 'Cleaning', 'Other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bike_id) REFERENCES bikes(bike_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_trips_bike_date ON trips(bike_id, trip_date);
CREATE INDEX idx_fuel_bike_date ON fuel_logs(bike_id, fill_date);
CREATE INDEX idx_service_bike_date ON service_records(bike_id, service_date);
CREATE INDEX idx_reminders_due ON reminders(due_date, is_completed);
CREATE INDEX idx_expenses_bike_date ON expenses(bike_id, expense_date);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, phone) 
VALUES ('admin', 'admin@mototrack.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', '1234567890');

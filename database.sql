CREATE DATABASE IF NOT EXISTS roulez_tn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roulez_tn;
 
-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    cin VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
 
-- Table: machines
CREATE TABLE IF NOT EXISTS machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    type ENUM('car','bike','motorcycle','scooter') NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    description TEXT,
    photo VARCHAR(255),
    price_per_day DECIMAL(10,2) NOT NULL,
    available_from DATE NOT NULL,
    available_to DATE NOT NULL,
    city VARCHAR(100) NOT NULL,
    status ENUM('available','rented','unavailable') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
 
-- Table: bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    renter_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    platform_fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    card_last4 CHAR(4) NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'confirmed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
);
 
-- Sample data
INSERT INTO users (full_name, email, phone, password_hash, cin) VALUES
('Ahmed Ben Salah', 'ahmed@example.com', '+216 22 111 222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08123456'),
('Fatma Gharbi', 'fatma@example.com', '+216 55 333 444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09876543');
 
INSERT INTO machines (owner_id, type, brand, model, year, description, photo, price_per_day, available_from, available_to, city, status) VALUES
(1, 'car', 'Volkswagen', 'Golf 7', 2020, 'Voiture en excellent état, climatisation, GPS intégré. Non-fumeur.', NULL, 85.00, '2025-05-01', '2025-06-15', 'Tunis', 'available'),
(1, 'motorcycle', 'Yamaha', 'MT-07', 2019, 'Moto sportive, entretenue régulièrement. Casque fourni.', NULL, 60.00, '2025-05-10', '2025-07-01', 'Sousse', 'available'),
(2, 'scooter', 'Honda', 'PCX 125', 2022, 'Scooter économique idéal pour la ville. Parfait état.', NULL, 35.00, '2025-05-01', '2025-05-31', 'Sfax', 'available'),
(2, 'bike', 'Trek', 'FX3', 2021, 'Vélo hybride léger, freins à disque. Idéal pour balades.', NULL, 20.00, '2025-06-01', '2025-08-31', 'Hammamet', 'available'),
(1, 'car', 'Renault', 'Clio 5', 2021, 'Petite citadine économique, faible consommation, facile à garer.', NULL, 70.00, '2025-05-15', '2025-06-30', 'Bizerte', 'available'),
(2, 'motorcycle', 'Honda', 'CB500F', 2020, 'Moto polyvalente, confortable pour longs trajets.', NULL, 55.00, '2025-05-20', '2025-07-15', 'Monastir', 'available');

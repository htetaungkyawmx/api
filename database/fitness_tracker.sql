CREATE DATABASE IF NOT EXISTS fitness_tracker;
USE fitness_tracker;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    gender VARCHAR(10),
    daily_goal INT DEFAULT 10000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activities table
CREATE TABLE activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    duration INT NOT NULL COMMENT 'in minutes',
    distance DECIMAL(8,2) DEFAULT 0.00 COMMENT 'in km',
    calories INT NOT NULL,
    note TEXT,
    date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, date)
);

-- Weightlifting specific table
CREATE TABLE weightlifting_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_id INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    sets INT NOT NULL,
    reps INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL COMMENT 'in kg',
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Insert test user (password: password123)
INSERT INTO users (name, email, password, age, weight, height, gender, daily_goal) 
VALUES 
('John', 'john@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, 75.5, 180.0, 'Male', 10000),
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert sample activities
INSERT INTO activities (user_id, type, duration, distance, calories, note, date) VALUES
(1, 'Running', 45, 5.2, 320, 'Morning run', NOW()),
(1, 'Cycling', 60, 15.5, 450, 'Evening cycling', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Walking', 30, 2.5, 150, 'Park walk', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Swimming', 45, 1.0, 400, 'Pool session', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'Gym', 60, 0, 350, 'Strength training', DATE_SUB(NOW(), INTERVAL 4 DAY));
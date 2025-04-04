
-- Database initialization script for ClubNest

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS clubnest;

-- Use the database
USE clubnest;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'club_leader', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clubs table
CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    meeting_schedule VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Club members table (junction table for users and clubs)
CREATE TABLE IF NOT EXISTS club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    club_id INT NOT NULL,
    role ENUM('member', 'leader') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (user_id, club_id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample users
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@university.edu', 'admin123', 'admin'),
('clubleader', 'leader@university.edu', 'leader123', 'club_leader'),
('student', 'student@university.edu', 'student123', 'student');

-- Insert sample clubs
INSERT INTO clubs (name, description, category, meeting_schedule) VALUES
('Computer Science Club', 'A club for computer science enthusiasts', 'Academic', 'Tuesdays at 5 PM'),
('Chess Club', 'Learn and play chess with other students', 'Recreation', 'Fridays at 3 PM'),
('Environmental Club', 'Working together for a sustainable campus', 'Social', 'Wednesdays at 6 PM');

-- Assign club leaders
INSERT INTO club_members (user_id, club_id, role) VALUES
(2, 1, 'leader'), -- clubleader for Computer Science Club
(2, 2, 'leader'), -- clubleader for Chess Club
(3, 3, 'member'); -- student is a member of Environmental Club

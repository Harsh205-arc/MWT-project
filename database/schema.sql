-- RoomateHub Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS roomatehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE roomatehub;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_color VARCHAR(7) DEFAULT '#7c3aed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0
);

-- Houses table
CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    invite_code VARCHAR(8) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- House members (links users to houses)
CREATE TABLE IF NOT EXISTS house_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_membership (house_id, user_id),
    FOREIGN KEY (house_id) REFERENCES houses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Chores table
CREATE TABLE IF NOT EXISTS chores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    assigned_to INT,
    due_date DATE,
    status ENUM('pending', 'in_progress', 'complete') DEFAULT 'pending',
    recurrence ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
    is_archived TINYINT(1) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_by INT NOT NULL,
    split_type ENUM('equal', 'custom', 'percentage') DEFAULT 'equal',
    category VARCHAR(50) DEFAULT 'general',
    expense_date DATE NOT NULL,
    is_archived TINYINT(1) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id),
    FOREIGN KEY (paid_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expense splits — individual share per person per expense
CREATE TABLE IF NOT EXISTS expense_splits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    user_id INT NOT NULL,
    amount_owed DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) DEFAULT NULL,
    is_settled TINYINT(1) DEFAULT 0,
    settled_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_split (expense_id, user_id),
    FOREIGN KEY (expense_id) REFERENCES expenses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

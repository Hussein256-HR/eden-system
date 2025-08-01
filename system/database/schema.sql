-- Eden Miracle Church Management System Database Schema
-- Created: 2025

-- Create database
CREATE DATABASE IF NOT EXISTS eden_church_db;
USE eden_church_db;

-- Users table for login system
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'staff', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Ministers table
CREATE TABLE ministers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    surname VARCHAR(50) NOT NULL,
    other_names VARCHAR(100),
    gender ENUM('Male', 'Female') NOT NULL,
    dob DATE NOT NULL,
    age INT,
    po_box VARCHAR(50),
    education ENUM('Primary', 'Secondary', 'Diploma', 'Degree', 'Postgraduate'),
    profession VARCHAR(100),
    occupation VARCHAR(100),
    tel_office VARCHAR(20),
    tel_neighbor VARCHAR(20),
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    national_id VARCHAR(20),
    tribe VARCHAR(50),
    district VARCHAR(50),
    village VARCHAR(50),
    employment_address TEXT,
    year_joined INT,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    spouse_name VARCHAR(100),
    children TEXT,
    former_church VARCHAR(100),
    saved_date DATE,
    joined_date DATE,
    tithe ENUM('Yes', 'No'),
    generation VARCHAR(50),
    calling VARCHAR(100),
    service_period VARCHAR(50),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Generations/Members table
CREATE TABLE generations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    surname VARCHAR(50) NOT NULL,
    other_names VARCHAR(100),
    gender ENUM('Male', 'Female') NOT NULL,
    dob DATE NOT NULL,
    age INT,
    po_box VARCHAR(50),
    education ENUM('Primary', 'Secondary', 'Diploma', 'Degree', 'Postgraduate'),
    profession VARCHAR(100),
    occupation VARCHAR(100),
    employment_year INT,
    tel_office VARCHAR(20),
    tel_neighbor VARCHAR(20),
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    national_id VARCHAR(20),
    tribe VARCHAR(50),
    district VARCHAR(50),
    village VARCHAR(50),
    parish VARCHAR(50),
    employment_address TEXT,
    church_join_year INT,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    spouse_name VARCHAR(100),
    children TEXT,
    former_church VARCHAR(100),
    saved_date DATE,
    joined_date DATE,
    generation VARCHAR(50),
    ministry_area VARCHAR(100),
    assisted_period VARCHAR(50),
    updation_date DATE,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Children department
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    parent_name VARCHAR(100) NOT NULL,
    parent_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Choir department
CREATE TABLE choir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_name VARCHAR(100) NOT NULL,
    voice_type ENUM('Soprano', 'Alto', 'Tenor', 'Bass') NOT NULL,
    phone VARCHAR(20),
    join_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Ushering department
CREATE TABLE ushering (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usher_name VARCHAR(100) NOT NULL,
    team ENUM('Team A', 'Team B', 'Team C'),
    phone VARCHAR(20),
    join_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Security department
CREATE TABLE security (
    id INT PRIMARY KEY AUTO_INCREMENT,
    security_name VARCHAR(100) NOT NULL,
    shift ENUM('Morning', 'Afternoon', 'Evening', 'Night'),
    phone VARCHAR(20),
    join_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Generic department members table for other departments
CREATE TABLE department_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    join_date DATE,
    next_of_kin VARCHAR(100),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Service coordination reports
CREATE TABLE service_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    preacher_name VARCHAR(100) NOT NULL,
    coordinator_name VARCHAR(100) NOT NULL,
    sessions TEXT, -- JSON array of selected sessions
    sermon_start TIME,
    sermon_end TIME,
    prayer_start TIME,
    prayer_end TIME,
    pastors TEXT, -- List of pastors in attendance
    sermon_clarity INT DEFAULT 0,
    time_management INT DEFAULT 0,
    confidence INT DEFAULT 0,
    engagement INT DEFAULT 0,
    conduct INT DEFAULT 0,
    relevance INT DEFAULT 0,
    scripture_use INT DEFAULT 0,
    testimonies INT DEFAULT 0,
    attendance INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Church projects
CREATE TABLE church_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(100) NOT NULL,
    project_type ENUM('buying-land', 'construction', 'renovation') NOT NULL,
    description TEXT,
    budget DECIMAL(15,2),
    start_date DATE,
    end_date DATE,
    status ENUM('planning', 'in-progress', 'completed', 'on-hold') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity logs for dashboard
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@edenchurch.com', 'admin');

-- Insert sample data for testing
INSERT INTO children (child_name, age, gender, parent_name, parent_phone) VALUES
('John Doe', 8, 'Male', 'Jane Doe', '0701234567'),
('Mary Smith', 10, 'Female', 'Peter Smith', '0709876543'),
('David Wilson', 6, 'Male', 'Sarah Wilson', '0756789012');

INSERT INTO choir (member_name, voice_type, phone, join_date) VALUES
('Alice Johnson', 'Soprano', '0701111111', '2023-01-15'),
('Bob Brown', 'Bass', '0702222222', '2023-02-20'),
('Carol White', 'Alto', '0703333333', '2023-03-10');

INSERT INTO ushering (usher_name, team, phone, join_date) VALUES
('Michael Green', 'Team A', '0704444444', '2023-01-01'),
('Susan Black', 'Team B', '0705555555', '2023-01-15'),
('Robert Gray', 'Team C', '0706666666', '2023-02-01');

INSERT INTO security (security_name, shift, phone, join_date) VALUES
('James Security', 'Morning', '0707777777', '2023-01-01'),
('Paul Guard', 'Evening', '0708888888', '2023-01-10'),
('Peter Watch', 'Night', '0709999999', '2023-01-20');

-- Insert sample department members
INSERT INTO department_members (department, name, email, phone, join_date, marital_status) VALUES
('pastoral', 'Pastor John', 'pastor.john@church.com', '0701000001', '2023-01-01', 'Married'),
('administration', 'Admin Mary', 'admin.mary@church.com', '0701000002', '2023-01-01', 'Single'),
('church-elders', 'Elder Peter', 'elder.peter@church.com', '0701000003', '2023-01-01', 'Married'),
('worship-team', 'Worship Leader', 'worship@church.com', '0701000004', '2023-01-01', 'Single'),
('intercession', 'Prayer Warrior', 'prayer@church.com', '0701000005', '2023-01-01', 'Married');

-- Insert sample activity logs
INSERT INTO activity_logs (activity_type, description, user_id) VALUES
('member_added', '5 new members joined Eden Miracle Church', 1),
('service_report', 'Sunday service report submitted by Pastor James', 1),
('children_added', 'Added 3 new children to the Sunday School program', 1),
('choir_practice', 'Weekly choir practice completed successfully', 1);
-- Create database
CREATE DATABASE IF NOT EXISTS recruit_portal;
USE recruit_portal;

-- Create companies table
CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('full-time', 'part-time', 'contract', 'internship') NOT NULL,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    benefits TEXT,
    deadline DATE NOT NULL,
    status ENUM('active', 'closed', 'expired') DEFAULT 'active',
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create screening questions table
CREATE TABLE screening_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    question TEXT NOT NULL,
    type ENUM('short_answer', 'multiple_choice', 'yes_no') NOT NULL,
    options JSON,
    required BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Create applications table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    resume_filename VARCHAR(255),
    cover_letter TEXT,
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    referral_source VARCHAR(100),
    additional_info TEXT,
    status ENUM('new', 'reviewed', 'interview', 'rejected', 'hired') DEFAULT 'new',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Create screening answers table
CREATE TABLE screening_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES screening_questions(id) ON DELETE CASCADE
);

-- Create system settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

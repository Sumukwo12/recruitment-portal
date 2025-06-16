-- Create database
CREATE DATABASE IF NOT EXISTS recruit_portal;
USE recruit_portal;

-- Users table for admin authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    job_type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Screening questions table
CREATE TABLE screening_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    question TEXT NOT NULL,
    question_type ENUM('short_answer', 'multiple_choice', 'yes_no') NOT NULL,
    options JSON NULL, -- For multiple choice questions
    required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    resume_path VARCHAR(255),
    cover_letter TEXT,
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    status ENUM('Pending', 'Reviewed', 'Shortlisted', 'Rejected') DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Application answers table for screening questions
CREATE TABLE application_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES screening_questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_by INT NOT NULL,
    sent_at DATETIME NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (sent_by) REFERENCES users(id)
);


-- Insert default admin user (password: admin123)
-- First delete any existing admin user
DELETE FROM users WHERE username = 'admin' OR email = 'admin@recruitportal.com';

-- Insert with properly hashed password
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@recruitportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample jobs
INSERT INTO jobs (title, company, location, job_type, salary_min, salary_max, description, requirements, deadline) VALUES 
('Senior Software Developer', 'TechCorp Inc.', 'New York, NY', 'Full-time', 80000.00, 120000.00, 
'We are looking for a Senior Software Developer to join our dynamic team. You will be responsible for developing high-quality software solutions and mentoring junior developers.',
'- 5+ years of experience in software development\n- Proficiency in PHP, JavaScript, and MySQL\n- Experience with modern frameworks\n- Strong problem-solving skills',
DATE_ADD(CURDATE(), INTERVAL 30 DAY)),

('Marketing Manager', 'Creative Solutions', 'Los Angeles, CA', 'Full-time', 60000.00, 85000.00,
'Join our marketing team as a Marketing Manager. You will lead marketing campaigns and drive brand awareness.',
'- Bachelor\'s degree in Marketing or related field\n- 3+ years of marketing experience\n- Strong analytical skills\n- Experience with digital marketing',
DATE_ADD(CURDATE(), INTERVAL 25 DAY)),

('Frontend Developer Intern', 'StartupXYZ', 'Remote', 'Internship', 2000.00, 3000.00,
'Great opportunity for students to gain hands-on experience in frontend development.',
'- Currently pursuing Computer Science degree\n- Knowledge of HTML, CSS, JavaScript\n- Familiarity with React or Vue.js\n- Strong willingness to learn',
DATE_ADD(CURDATE(), INTERVAL 15 DAY));

-- Insert sample screening questions
INSERT INTO screening_questions (job_id, question, question_type, required) VALUES 
(1, 'How many years of PHP development experience do you have?', 'short_answer', TRUE),
(1, 'Are you comfortable working with legacy code?', 'yes_no', TRUE),
(1, 'Which of the following frameworks have you worked with?', 'multiple_choice', TRUE),
(2, 'Describe your experience with digital marketing campaigns.', 'short_answer', TRUE),
(2, 'Do you have experience with Google Analytics?', 'yes_no', TRUE),
(3, 'What motivates you to pursue a career in frontend development?', 'short_answer', TRUE);

-- Update multiple choice options
UPDATE screening_questions 
SET options = JSON_ARRAY('Laravel', 'CodeIgniter', 'Symfony', 'None of the above')
WHERE id = 3;



-- Create screening questions table if it doesn't exist
CREATE TABLE IF NOT EXISTS screening_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    question TEXT NOT NULL,
    type ENUM('short_answer', 'long_answer', 'yes_no', 'multiple_choice') NOT NULL DEFAULT 'short_answer',
    options JSON NULL,
    required BOOLEAN DEFAULT FALSE,
    help_text TEXT NULL,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_order (job_id, order_index)
);

-- Create application answers table for storing screening question responses
CREATE TABLE IF NOT EXISTS application_screening_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES screening_questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application_question (application_id, question_id),
    INDEX idx_application_id (application_id),
    INDEX idx_question_id (question_id)
);

-- Add help_text column to screening_questions if it doesn't exist
ALTER TABLE screening_questions 
ADD COLUMN IF NOT EXISTS help_text TEXT NULL AFTER required;

-- Update jobs table to include screening questions count
ALTER TABLE jobs 
ADD COLUMN IF NOT EXISTS screening_questions_count INT DEFAULT 0 AFTER benefits;

-- Create trigger to update screening questions count
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_screening_count_insert
    AFTER INSERT ON screening_questions
    FOR EACH ROW
BEGIN
    UPDATE jobs 
    SET screening_questions_count = (
        SELECT COUNT(*) FROM screening_questions WHERE job_id = NEW.job_id
    ) 
    WHERE id = NEW.job_id;
END//

CREATE TRIGGER IF NOT EXISTS update_screening_count_delete
    AFTER DELETE ON screening_questions
    FOR EACH ROW
BEGIN
    UPDATE jobs 
    SET screening_questions_count = (
        SELECT COUNT(*) FROM screening_questions WHERE job_id = OLD.job_id
    ) 
    WHERE id = OLD.job_id;
END//
DELIMITER ;

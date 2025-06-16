-- Add email logs table to track sent emails
CREATE TABLE IF NOT EXISTS email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_by INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for better performance
CREATE INDEX idx_email_logs_application ON email_logs(application_id);
CREATE INDEX idx_email_logs_sent_by ON email_logs(sent_by);
CREATE INDEX idx_email_logs_sent_at ON email_logs(sent_at);

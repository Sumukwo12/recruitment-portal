<?php
require_once __DIR__ . '/../config/database.php';

class Settings {
    private $conn;
    private $table_name = "settings";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->createSettingsTable();
        $this->initializeDefaultSettings();
    }

    private function createSettingsTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->conn->exec($query);
    }

    private function initializeDefaultSettings() {
        $defaultSettings = [
            'job_board_visible' => '1',
            'email_notifications' => '1',
            'notification_email' => 'admin@techcorp.com',
            'cv_display_sections' => json_encode([
                'personal_info', 'experience', 'education', 'skills', 
                'portfolio', 'references', 'cover_letter', 'screening_answers'
            ])
        ];

        foreach ($defaultSettings as $key => $value) {
            $this->createSettingIfNotExists($key, $value);
        }
    }

    private function createSettingIfNotExists($key, $value) {
        $query = "INSERT IGNORE INTO " . $this->table_name . " (setting_key, setting_value) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$key, $value]);
    }

    public function getSetting($key, $default = null) {
        $query = "SELECT setting_value FROM " . $this->table_name . " WHERE setting_key = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    }

    public function updateSetting($key, $value) {
        $query = "INSERT INTO " . $this->table_name . " (setting_key, setting_value) 
                  VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$key, $value]);
    }

    public function getAllSettings() {
        $query = "SELECT setting_key, setting_value FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
}
?>

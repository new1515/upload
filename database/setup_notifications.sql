-- Run this SQL in phpMyAdmin to create SMS/MoMo tables

-- SMS Settings Table
CREATE TABLE IF NOT EXISTS sms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) DEFAULT 'twilio',
    twilio_sid VARCHAR(100) DEFAULT '',
    twilio_token VARCHAR(100) DEFAULT '',
    twilio_phone VARCHAR(20) DEFAULT '',
    africastalking_api_key VARCHAR(100) DEFAULT '',
    africastalking_username VARCHAR(100) DEFAULT 'sandbox',
    momo_provider VARCHAR(50) DEFAULT 'mtn',
    mtn_momo_subscription_key VARCHAR(200) DEFAULT '',
    mtn_momo_oauth_token TEXT DEFAULT '',
    mtn_momo_oauth_expires TIMESTAMP NULL,
    mtn_momo_primary_key VARCHAR(200) DEFAULT '',
    mtn_momo_user_id VARCHAR(100) DEFAULT '',
    sms_enabled TINYINT(1) DEFAULT 0,
    momo_enabled TINYINT(1) DEFAULT 0,
    default_country_code VARCHAR(10) DEFAULT '+233',
    cost_per_sms DECIMAL(10,4) DEFAULT 0.05,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- SMS Logs Table
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    recipient_name VARCHAR(100) DEFAULT '',
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'announcement',
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    provider_response TEXT,
    cost DECIMAL(10,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MoMo Logs Table
CREATE TABLE IF NOT EXISTS momo_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    recipient_name VARCHAR(100) DEFAULT '',
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'announcement',
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    provider_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Daily Attendance Table
CREATE TABLE IF NOT EXISTS daily_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    marked_by INT,
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily (student_id, attendance_date)
);

-- Insert default settings
INSERT IGNORE INTO sms_settings (id) VALUES (1);

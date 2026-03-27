-- SMS System Tables
-- Run this SQL to create the SMS tables

-- SMS Settings Table
CREATE TABLE IF NOT EXISTS sms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) DEFAULT 'twilio',
    twilio_sid VARCHAR(100) DEFAULT '',
    twilio_token VARCHAR(100) DEFAULT '',
    twilio_phone VARCHAR(20) DEFAULT '',
    africastalking_api_key VARCHAR(100) DEFAULT '',
    africastalking_username VARCHAR(100) DEFAULT 'sandbox',
    sms_enabled TINYINT(1) DEFAULT 0,
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

-- Insert default settings if not exists
INSERT IGNORE INTO sms_settings (id) VALUES (1);

-- Make sure parents have phone numbers
-- ALTER TABLE parents ADD COLUMN IF NOT EXISTS phone VARCHAR(20);

-- Make sure teachers have phone numbers
-- ALTER TABLE teachers ADD COLUMN IF NOT EXISTS phone VARCHAR(20);

-- Make sure students have phone numbers
-- ALTER TABLE students ADD COLUMN IF NOT EXISTS phone VARCHAR(20);

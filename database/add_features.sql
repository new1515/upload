-- Run this to add Settings, Lessons, and Chatbot tables

USE school_php_ai_db;

-- School Settings table
CREATE TABLE IF NOT EXISTS school_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO school_settings (setting_key, setting_value) VALUES
('school_name', 'Ghana Basic School'),
('school_tagline', 'Excellence in Education'),
('school_address', 'Accra, Ghana'),
('school_phone', '+233 XX XXX XXXX'),
('school_email', 'info@school.edu.gh'),
('school_logo', '');

-- Lessons/Materials table
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    class_id INT,
    subject_id INT,
    file_name VARCHAR(255),
    file_type VARCHAR(50),
    file_path VARCHAR(500),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- Insert sample lessons
INSERT INTO lessons (title, description, class_id, subject_id, file_type) VALUES
('Mathematics Basics - Numbers', 'Introduction to numbers and counting for Basic 1', 1, 2, 'pdf'),
('English Grammar - Verbs', 'Understanding verbs and their usage', 1, 1, 'pdf'),
('Science - Living Things', 'Learn about plants and animals', 1, 3, 'pdf'),
('Introduction to Addition', 'Simple addition exercises for Basic 2', 3, 2, 'pdf'),
('Reading Comprehension', 'English reading and understanding', 3, 1, 'pdf');

-- Chatbot Conversations table
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    user_message TEXT,
    bot_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

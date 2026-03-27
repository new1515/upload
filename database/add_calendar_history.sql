-- Run this SQL to add Calendar and History tables to your existing database

USE school_php_ai_db;

-- Academic Calendar table
CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('term', 'holiday', 'exam', 'event', 'meeting', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert academic calendar data
INSERT INTO academic_calendar (title, description, event_type, start_date, end_date) VALUES
('First Term Opening', 'Schools reopen for First Term', 'term', '2026-01-05', '2026-01-07'),
('Staff General Meeting', 'All staff meeting for term planning', 'meeting', '2026-01-08', '2026-01-08'),
('First Term Mid-Term Exams', 'Mid-term examinations for all classes', 'exam', '2026-02-15', '2026-02-25'),
('Parent-Teacher Meeting', 'PTM for progress discussion', 'meeting', '2026-02-28', '2026-02-28'),
('First Term End Exams', 'End of term examinations', 'exam', '2026-03-20', '2026-04-05'),
('First Term Holiday', 'Holiday after First Term', 'holiday', '2026-04-06', '2026-04-19'),
('Second Term Opening', 'Schools reopen for Second Term', 'term', '2026-04-20', '2026-04-22'),
('Sports Day', 'Annual sports competition', 'event', '2026-05-01', '2026-05-01'),
('Second Term Mid-Term Exams', 'Mid-term examinations', 'exam', '2026-05-20', '2026-05-30'),
('Independence Day Celebration', 'National day celebrations', 'event', '2026-06-01', '2026-06-01'),
('Second Term End Exams', 'End of term examinations', 'exam', '2026-07-10', '2026-07-25'),
('Second Term Holiday', 'Holiday after Second Term', 'holiday', '2026-07-26', '2026-08-09'),
('Third Term Opening', 'Schools reopen for Third Term', 'term', '2026-08-10', '2026-08-12'),
('Third Term Mid-Term Exams', 'Mid-term examinations', 'exam', '2026-09-15', '2026-09-25'),
('Cultural Day', 'Annual cultural festival', 'event', '2026-10-10', '2026-10-10'),
('Third Term End Exams', 'Final examinations', 'exam', '2026-11-15', '2026-11-30'),
('Third Term Holiday', 'Holiday after Third Term', 'holiday', '2026-12-01', '2026-12-31');

-- Activity History table
CREATE TABLE IF NOT EXISTS activity_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    record_type VARCHAR(50),
    record_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample history
INSERT INTO activity_history (user_id, username, action_type, description, record_type) VALUES
(1, 'admin', 'login', 'Admin logged in successfully', 'auth'),
(1, 'admin', 'create', 'System initialized', 'system');

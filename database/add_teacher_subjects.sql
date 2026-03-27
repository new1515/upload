-- Run this SQL to add multiple subjects support for teachers
-- Use this if you already have an existing database

USE school_php_ai_db;

-- Create teacher subjects table (allows multiple subjects per teacher)
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);

-- Copy existing subject assignments from teachers table
INSERT INTO teacher_subjects (teacher_id, subject_id)
SELECT id, subject_id FROM teachers WHERE subject_id IS NOT NULL;

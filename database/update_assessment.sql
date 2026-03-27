-- Run this to update database with new class structure and assessment system

USE school_php_ai_db;

-- Drop old results table and create new assessment table
DROP TABLE IF EXISTS student_assessments;

-- Student Assessments table (tracks all scores per student per subject per class)
CREATE TABLE IF NOT EXISTS student_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    term VARCHAR(20) NOT NULL DEFAULT 'Term 1',
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    test1 DECIMAL(5,2) DEFAULT NULL,
    test2 DECIMAL(5,2) DEFAULT NULL,
    test3 DECIMAL(5,2) DEFAULT NULL,
    project DECIMAL(5,2) DEFAULT NULL,
    class_assessment DECIMAL(5,2) DEFAULT NULL,
    exam DECIMAL(5,2) DEFAULT NULL,
    total_ca DECIMAL(5,2) GENERATED ALWAYS AS (COALESCE(test1,0) + COALESCE(test2,0) + COALESCE(test3,0) + COALESCE(project,0) + COALESCE(class_assessment,0)) STORED,
    total EXTRACT DECIMAL(5,2) GENERATED ALWAYS AS (COALESCE(test1,0) + COALESCE(test2,0) + COALESCE(test3,0) + COALESCE(project,0) + COALESCE(class_assessment,0) + COALESCE(exam,0)) STORED,
    grade VARCHAR(2) DEFAULT NULL,
    remarks VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    UNIQUE KEY unique_assessment (student_id, class_id, subject_id, term, academic_year)
);

-- Update classes table with new Ghana school structure
TRUNCATE TABLE classes;

INSERT INTO classes (class_name, section, level) VALUES
('Basic 1', 'A', 'primary'),
('Basic 1', 'B', 'primary'),
('Basic 2', 'A', 'primary'),
('Basic 2', 'B', 'primary'),
('Basic 3', 'A', 'primary'),
('Basic 3', 'B', 'primary'),
('Basic 4', 'A', 'primary'),
('Basic 4', 'B', 'primary'),
('Basic 5', 'A', 'primary'),
('Basic 5', 'B', 'primary'),
('Basic 6', 'A', 'primary'),
('Basic 6', 'B', 'primary'),
('JHS 1', 'A', 'jhs'),
('JHS 1', 'B', 'jhs'),
('JHS 2', 'A', 'jhs'),
('JHS 2', 'B', 'jhs'),
('JHS 3', 'A', 'jhs'),
('JHS 3', 'B', 'jhs');

-- Update subjects for Ghana curriculum
TRUNCATE TABLE subjects;

INSERT INTO subjects (subject_name, category, level) VALUES
-- Core Subjects
('English', 'core', 'all'),
('Mathematics', 'core', 'all'),
('Science', 'core', 'primary'),
('Integrated Science', 'core', 'jhs'),
('Social Studies', 'core', 'all'),
('Citizenship Education', 'core', 'jhs'),
('Religious and Moral Education', 'core', 'all'),
-- Primary School Subjects
('Creative Arts', 'elective', 'primary'),
('Our World Our People', 'elective', 'primary'),
('Computing', 'elective', 'primary'),
-- JHS Subjects
('Agriculture', 'elective', 'jhs'),
('Home Economics', 'elective', 'jhs'),
('Visual Arts', 'elective', 'jhs'),
('Basic Design and Technology', 'elective', 'jhs'),
('French', 'elective', 'jhs'),
('Arabic', 'elective', 'jhs');

-- Terms table
DROP TABLE IF EXISTS terms;
CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    term_name VARCHAR(20) NOT NULL,
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 0,
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025-2026'
);

INSERT INTO terms (term_name, start_date, end_date, is_active, academic_year) VALUES
('Term 1', '2026-01-05', '2026-04-03', 1, '2025-2026'),
('Term 2', '2026-04-20', '2026-07-24', 0, '2025-2026'),
('Term 3', '2026-08-10', '2026-11-27', 0, '2025-2026');

-- Class Subjects (which subjects are offered in which class)
DROP TABLE IF EXISTS class_subjects;
CREATE TABLE IF NOT EXISTS class_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    UNIQUE KEY unique_class_subject (class_id, subject_id)
);

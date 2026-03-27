-- Complete School Management System Database
-- Includes: Nursery, KG, Primary (Basic 1-6), JHS (1-3)

CREATE DATABASE IF NOT EXISTS school_php_ai_db;
USE school_php_ai_db;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'superadmin') DEFAULT 'admin',
    verified TINYINT(1) DEFAULT 1,
    reset_token VARCHAR(255),
    reset_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO admins (username, password, email, role) VALUES
('admin', '21232f297a57a5a743894a0e4a801fc3', 'admin@school.edu', 'admin'),
('superadmin', 'e10adc3949ba59abbe56e057f20f883e', 'superadmin@school.edu', 'superadmin');

-- Teachers table (MUST be before classes)
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_username (username)
);
INSERT INTO teachers (name, username, password, email, phone) VALUES
('Aba Mensah', 'aba.mensah', '25d55ad283aa400af464c76d713c07ad', 'aba.m@school.edu', '055-0201'),
('Kofi Agyeman', 'kofi.agyeman', '25d55ad283aa400af464c76d713c07ad', 'kofi.a@school.edu', '055-0202'),
('Akua Nyame', 'akua.nyame', '25d55ad283aa400af464c76d713c07ad', 'akua.n@school.edu', '055-0203'),
('Yaw Oppong', 'yaw.oppong', '25d55ad283aa400af464c76d713c07ad', 'yaw.o@school.edu', '055-0204'),
('Akosua Kumi', 'akosua.kumi', '25d55ad283aa400af464c76d713c07ad', 'akosua.k@school.edu', '055-0205'),
('Kwame Asiedu', 'kwame.asiedu', '25d55ad283aa400af464c76d713c07ad', 'kwame.as@school.edu', '055-0206'),
('Efua Owusu', 'efua.owusu', '25d55ad283aa400af464c76d713c07ad', 'efua.o@school.edu', '055-0207'),
('Kojo Afful', 'kojo.afful', '25d55ad283aa400af464c76d713c07ad', 'kojo.af@school.edu', '055-0208');

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    category ENUM('core', 'elective') DEFAULT 'core',
    level ENUM('nursery', 'kg', 'primary', 'jhs', 'all') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO subjects (subject_name, category, level) VALUES
-- Nursery Subjects
('Pre-Writing', 'core', 'nursery'),
('Pre-Reading', 'core', 'nursery'),
('Numeracy', 'core', 'nursery'),
('RME', 'core', 'nursery'),
('Art & Craft', 'elective', 'nursery'),
-- KG Subjects
('Writing', 'core', 'kg'),
('Reading', 'core', 'kg'),
('Numbers', 'core', 'kg'),
('RME', 'core', 'kg'),
('Art & Craft', 'elective', 'kg'),
-- Primary Subjects
('English', 'core', 'primary'),
('Mathematics', 'core', 'primary'),
('Science', 'core', 'primary'),
('Social Studies', 'core', 'primary'),
('RME', 'core', 'primary'),
('Creative Arts', 'elective', 'primary'),
('Our World Our People', 'elective', 'primary'),
('Computing', 'elective', 'primary'),
-- JHS Subjects
('English', 'core', 'jhs'),
('Mathematics', 'core', 'jhs'),
('Integrated Science', 'core', 'jhs'),
('Social Studies', 'core', 'jhs'),
('Citizenship Education', 'core', 'jhs'),
('RME', 'core', 'jhs'),
('Agriculture', 'elective', 'jhs'),
('Home Economics', 'elective', 'jhs'),
('French', 'elective', 'jhs'),
('Arabic', 'elective', 'jhs');

-- Classes table (after teachers)
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    level ENUM('nursery', 'kg', 'primary', 'jhs') DEFAULT 'primary',
    class_teacher_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);
INSERT INTO classes (class_name, section, level, class_teacher_id) VALUES
-- Nursery
('Nursery 1', 'A', 'nursery', 1), ('Nursery 1', 'B', 'nursery', 2),
('Nursery 2', 'A', 'nursery', 3), ('Nursery 2', 'B', 'nursery', 4),
-- Kindergarten
('KG 1', 'A', 'kg', 1), ('KG 1', 'B', 'kg', 2),
('KG 2', 'A', 'kg', 3), ('KG 2', 'B', 'kg', 4),
-- Primary School
('Basic 1', 'A', 'primary', 5), ('Basic 1', 'B', 'primary', 6),
('Basic 2', 'A', 'primary', 7), ('Basic 2', 'B', 'primary', 8),
('Basic 3', 'A', 'primary', 1), ('Basic 3', 'B', 'primary', 2),
('Basic 4', 'A', 'primary', 3), ('Basic 4', 'B', 'primary', 4),
('Basic 5', 'A', 'primary', 5), ('Basic 5', 'B', 'primary', 6),
('Basic 6', 'A', 'primary', 7), ('Basic 6', 'B', 'primary', 8),
-- Junior High School
('JHS 1', 'A', 'jhs', 1), ('JHS 1', 'B', 'jhs', 2),
('JHS 2', 'A', 'jhs', 3), ('JHS 2', 'B', 'jhs', 4),
('JHS 3', 'A', 'jhs', 5), ('JHS 3', 'B', 'jhs', 6);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    class_id INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    INDEX idx_class_id (class_id),
    INDEX idx_name (name),
    INDEX idx_username (username)
);
INSERT INTO students (name, username, password, class_id, gender, email, phone) VALUES
-- Nursery students
('Abena Kwaku', 'abena.kwaku', '25d55ad283aa400af464c76d713c07ad', 1, 'Female', 'abena@email.com', '055-0001'),
('Kwesi Manu', 'kwesi.manu', '25d55ad283aa400af464c76d713c07ad', 1, 'Male', 'kwesi@email.com', '055-0002'),
('Akua Serwaa', 'akua.serwaa', '25d55ad283aa400af464c76d713c07ad', 2, 'Female', 'akua.s@email.com', '055-0003'),
('Yaw Osei', 'yaw.osei', '25d55ad283aa400af464c76d713c07ad', 3, 'Male', 'yaw@email.com', '055-0004'),
-- KG students
('Ama Darko', 'ama.darko', '25d55ad283aa400af464c76d713c07ad', 5, 'Female', 'ama.d@email.com', '055-0005'),
('Kofi Mensah', 'kofi.mensah', '25d55ad283aa400af464c76d713c07ad', 5, 'Male', 'kofi.m@email.com', '055-0006'),
-- Primary students
('John Smith', 'john.smith', '25d55ad283aa400af464c76d713c07ad', 9, 'Male', 'john@email.com', '055-0007'),
('Emily Johnson', 'emily.johnson', '25d55ad283aa400af464c76d713c07ad', 9, 'Female', 'emily.j@email.com', '055-0008'),
('Michael Brown', 'michael.brown', '25d55ad283aa400af464c76d713c07ad', 11, 'Male', 'michael.b@email.com', '055-0009'),
('Sarah Davis', 'sarah.davis', '25d55ad283aa400af464c76d713c07ad', 11, 'Female', 'sarah.d@email.com', '055-0010'),
-- JHS students
('James Wilson', 'james.wilson', '25d55ad283aa400af464c76d713c07ad', 21, 'Male', 'james.w@email.com', '055-0011'),
('Emma Martinez', 'emma.martinez', '25d55ad283aa400af464c76d713c07ad', 21, 'Female', 'emma.m@email.com', '055-0012');

-- Teacher Subjects
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);
INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES
(1, 1), (1, 6), (1, 11), (1, 20),
(2, 2), (2, 7), (2, 12), (2, 21),
(3, 3), (3, 8), (3, 13), (3, 22),
(4, 4), (4, 9), (4, 14), (4, 23),
(5, 5), (5, 10), (5, 15), (5, 24),
(6, 16), (6, 25),
(7, 17), (7, 26),
(8, 18), (8, 27);

-- Teacher Class Assignments
CREATE TABLE IF NOT EXISTS teacher_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_class (teacher_id, class_id)
);
INSERT INTO teacher_classes (teacher_id, class_id) VALUES
(1, 1), (1, 5), (1, 9),
(2, 2), (2, 6), (2, 10),
(3, 3), (3, 7), (3, 11),
(4, 4), (4, 8), (4, 12);

-- Student Assessments
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
    grade VARCHAR(2) DEFAULT NULL,
    remarks VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    INDEX idx_student_id (student_id),
    INDEX idx_class_id (class_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_term_year (term, academic_year)
);

-- Sample Student Assessments (Results)
INSERT INTO student_assessments (student_id, class_id, subject_id, term, academic_year, test1, test2, test3, project, class_assessment, exam, grade, remarks) VALUES
(1, 1, 1, 'Term 1', '2025-2026', 8.5, 9.0, 8.0, 9.5, 8.0, 45.0, 'A', 'Excellent'),
(1, 1, 2, 'Term 1', '2025-2026', 7.0, 8.5, 8.0, 8.0, 7.5, 42.0, 'B', 'Very Good'),
(1, 1, 3, 'Term 1', '2025-2026', 9.0, 8.5, 9.0, 9.0, 8.5, 46.0, 'A', 'Excellent'),
(2, 1, 1, 'Term 1', '2025-2026', 8.0, 7.5, 8.5, 8.0, 7.0, 40.0, 'B', 'Very Good'),
(2, 1, 2, 'Term 1', '2025-2026', 7.5, 8.0, 7.0, 7.5, 8.0, 38.0, 'C', 'Good'),
(3, 2, 1, 'Term 1', '2025-2026', 9.0, 9.5, 9.0, 9.0, 9.5, 48.0, 'A', 'Excellent'),
(3, 2, 2, 'Term 1', '2025-2026', 8.5, 9.0, 8.0, 8.5, 8.0, 44.0, 'A', 'Excellent'),
(4, 3, 1, 'Term 1', '2025-2026', 7.0, 6.5, 7.5, 7.0, 6.0, 35.0, 'D', 'Pass'),
(4, 3, 2, 'Term 1', '2025-2026', 6.5, 7.0, 6.0, 6.5, 5.5, 32.0, 'E', 'Below Pass'),
(5, 3, 1, 'Term 1', '2025-2026', 8.0, 8.5, 9.0, 8.0, 8.5, 43.0, 'A', 'Excellent');

-- School Settings
CREATE TABLE IF NOT EXISTS school_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO school_settings (setting_key, setting_value) VALUES
('school_name', 'Ghana Basic School'),
('school_tagline', 'Excellence in Education'),
('school_address', 'Accra, Ghana'),
('school_phone', '+233 XX XXX XXXX'),
('school_email', 'info@school.edu.gh'),
('school_logo', ''),
('headmaster_name', 'Mr. Emmanuel Kofi Asante');

-- Parents table
CREATE TABLE IF NOT EXISTS parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    student_id INT,
    relationship ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'guardian',
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    INDEX idx_student_id (student_id),
    INDEX idx_name (name),
    INDEX idx_username (username)
);
INSERT INTO parents (name, username, password, email, phone, student_id, relationship) VALUES
('Mr. Kwaku Darko', 'kwaku.darko', '25d55ad283aa400af464c76d713c07ad', 'kwaku.d@email.com', '055-0101', 1, 'father'),
('Mrs. Ama Serwaa', 'ama.serwaa', '25d55ad283aa400af464c76d713c07ad', 'ama.s@email.com', '055-0102', 3, 'mother'),
('Mr. Kofi Mensah', 'kofi.pmensah', '25d55ad283aa400af464c76d713c07ad', 'kofi.p@email.com', '055-0103', 6, 'father'),
('Mrs. Grace Wilson', 'grace.wilson', '25d55ad283aa400af464c76d713c07ad', 'grace.w@email.com', '055-0104', 11, 'mother');

-- Student Attendance
CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    term VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    days_school_opened INT DEFAULT 0,
    days_present INT DEFAULT 0,
    days_absent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    UNIQUE KEY unique_attendance (student_id, class_id, term, academic_year)
);

-- GES Timetable
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
);

-- Academic Calendar
CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('term', 'holiday', 'exam', 'event', 'meeting', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lessons
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

-- GES Lesson Plans
CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(200),
    term VARCHAR(20) NOT NULL DEFAULT 'Term 1',
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    week VARCHAR(50) NOT NULL,
    topic VARCHAR(200),
    sub_topic VARCHAR(200),
    objectives TEXT,
    activities TEXT,
    materials TEXT,
    references VARCHAR(200),
    duration VARCHAR(50),
    evaluation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
);

-- GES Test Books
CREATE TABLE IF NOT EXISTS test_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    test_type VARCHAR(50) NOT NULL DEFAULT 'Class Test',
    test_date DATE NOT NULL,
    total_marks INT DEFAULT 20,
    topic VARCHAR(200),
    scores_data TEXT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
);

-- Insert sample lesson plans
INSERT INTO lesson_plans (teacher_id, class_id, subject_id, title, week, topic, objectives, activities, evaluation) VALUES
(1, 1, 1, 'Introduction to Numbers', 'Week 1', 'Counting 1-10', 'By end of lesson, students will be able to:\n1. Count numbers 1-10\n2. Write numbers 1-10', '1. Introduction (5 min)\n2. Demonstration (15 min)\n3. Group practice (15 min)\n4. Individual work (5 min)', 'Oral counting, Workbook exercise');

-- GES Test Books
CREATE TABLE IF NOT EXISTS test_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    term VARCHAR(20) NOT NULL DEFAULT 'Term 1',
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    test_type VARCHAR(50) NOT NULL DEFAULT 'Class Test',
    test_number INT NOT NULL DEFAULT 1,
    duration VARCHAR(20) DEFAULT '30 minutes',
    total_marks INT DEFAULT 10,
    instructions TEXT,
    questions_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    UNIQUE KEY unique_test (class_id, subject_id, term, academic_year, test_number)
);

-- Activity History
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

-- Chatbot Messages
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    user_message TEXT,
    bot_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GES Lesson Plans
CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    class_level ENUM('nursery', 'kg', 'primary', 'jhs') NOT NULL,
    term VARCHAR(20) NOT NULL,
    week_number INT DEFAULT 1,
    topic VARCHAR(200) NOT NULL,
    sub_topic VARCHAR(200),
    objectives TEXT,
    content TEXT,
    teaching_methods TEXT,
    resources TEXT,
    homework TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (created_by) REFERENCES teachers(id)
);

-- GES Test Books (Assessment Banks)
CREATE TABLE IF NOT EXISTS test_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    class_level ENUM('nursery', 'kg', 'primary', 'jhs') NOT NULL,
    term VARCHAR(20) NOT NULL,
    assessment_type ENUM('test1', 'test2', 'test3', 'project', 'exam') NOT NULL,
    title VARCHAR(200) NOT NULL,
    questions TEXT NOT NULL,
    answers TEXT,
    marks_per_question VARCHAR(50) DEFAULT '1',
    total_marks INT DEFAULT 10,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (created_by) REFERENCES teachers(id)
);

-- Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM('all', 'parents', 'teachers', 'students') DEFAULT 'all',
    priority ENUM('normal', 'high') DEFAULT 'normal',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id)
);
INSERT INTO announcements (title, content, target_audience, priority, status, created_by) VALUES
('Welcome to New Academic Year 2025-2026', 'Dear Parents, Teachers, and Students, we are excited to welcome you to the new academic year. Let us work together for excellence in education.', 'all', 'high', 'active', 1),
('Parent-Teacher Meeting Scheduled', 'A parent-teacher meeting has been scheduled for the last Saturday of this month. All parents are encouraged to attend.', 'parents', 'normal', 'active', 1),
('Staff Development Workshop', 'All teachers are requested to attend the professional development workshop scheduled for next Friday.', 'teachers', 'normal', 'active', 2),
('Examination Schedule Released', 'Students, please check the academic calendar for examination dates and prepare accordingly.', 'students', 'high', 'active', 1);

-- GES Lesson Plans
CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject_id INT,
    class_level ENUM('nursery', 'kg', 'primary', 'jhs') NOT NULL,
    class_id INT,
    term VARCHAR(20),
    week_number INT,
    duration_minutes INT DEFAULT 40,
    objectives TEXT,
    content TEXT,
    teaching_methods TEXT,
    materials TEXT,
    activities TEXT,
    assessment TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- GES Test Books
CREATE TABLE IF NOT EXISTS test_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject_id INT,
    class_id INT,
    class_level ENUM('nursery', 'kg', 'primary', 'jhs') NOT NULL,
    term VARCHAR(20),
    academic_year VARCHAR(20),
    test_type ENUM('weekly', 'monthly', 'midterm', 'endterm', 'exam') NOT NULL,
    total_marks INT DEFAULT 100,
    duration_minutes INT DEFAULT 60,
    instructions TEXT,
    questions TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Sample GES Lesson Plans
INSERT INTO lesson_plans (title, subject_id, class_level, term, week_number, duration_minutes, objectives, content, teaching_methods, materials, notes) VALUES
('Introduction to Numbers 1-10', 1, 'nursery', 'Term 1', 1, 30, 'Students will count numbers 1-10 and recognize their shapes', 'Counting objects, number recognition, simple addition within 10', 'Demonstration, counting exercises, games', 'Number cards, counting blocks, whiteboard', 'Use colorful materials to attract attention'),
('Reading Readiness - Letter Sounds', 1, 'nursery', 'Term 1', 2, 30, 'Students will identify and produce initial letter sounds', 'Phonics activities, listening games, letter tracing', 'Phonics cards, audio materials, worksheets', 'Make learning fun with songs'),
('Basic Addition and Subtraction', 2, 'kg', 'Term 1', 1, 40, 'Students will add and subtract numbers within 20', 'Visual aids, manipulatives, practice exercises', 'Counting objects, number lines, worksheets', 'Use real-life examples like fruits'),
('English Grammar - Nouns and Verbs', 1, 'primary', 'Term 1', 3, 40, 'Students will identify and use nouns and verbs correctly', 'Interactive lesson, group work, sentence building', 'Flashcards, worksheets, whiteboard', 'Use examples from everyday life'),
('Introduction to Fractions', 2, 'primary', 'Term 1', 5, 40, 'Students will understand basic fractions (half, quarter)', 'Hands-on activities, visual models, guided practice', 'Fraction circles, paper folding, worksheets', 'Use paper folding technique'),
('Science - Plants and Their Parts', 3, 'primary', 'Term 1', 4, 40, 'Students will identify parts of a plant and their functions', 'Observation, experiment, discussion', 'Live plant, diagrams, magnifying glass', 'Bring actual plant specimen'),
('Social Studies - Our Community', 4, 'primary', 'Term 1', 2, 40, 'Students will describe their local community', 'Storytelling, map work, role play', 'Maps, pictures, textbooks', 'Encourage students to share experiences'),
('Mathematical Calculations', 2, 'jhs', 'Term 1', 1, 40, 'Students will solve algebraic equations', 'Direct instruction, problem solving, group work', 'Textbook, chalkboard, worksheets', 'Start with simple equations'),
('English Language - Reading Comprehension', 1, 'jhs', 'Term 1', 2, 40, 'Students will read and understand passages', 'Guided reading, comprehension questions', 'Reading materials, worksheets', 'Choose age-appropriate passages'),
('Natural Science - Life Processes', 3, 'jhs', 'Term 1', 3, 40, 'Students will understand life processes in plants and animals', 'Practical experiments, observation, discussion', 'Microscope, specimens, diagrams', 'Safety first during experiments');

-- Sample GES Test Books
INSERT INTO test_books (title, subject_id, class_id, class_level, term, test_type, total_marks, duration_minutes, instructions, questions) VALUES
('English Weekly Test 1', 1, 1, 'nursery', 'Term 1', 'weekly', 20, 30, 'Write your name. Circle the correct answer.', 'Section A: Match pictures to words (5 marks)\nSection B: Circle the correct letter sound (5 marks)\nSection C: Write the missing letter (10 marks)'),
('Mathematics Monthly Test', 2, 5, 'primary', 'Term 1', 'monthly', 50, 60, 'Show all your workings. Write neatly.', 'Section A: Addition and Subtraction (20 marks)\nSection B: Multiplication tables (15 marks)\nSection C: Word problems (15 marks)'),
('Science End of Term Exam', 3, 9, 'primary', 'Term 1', 'endterm', 100, 120, 'Answer all questions. Show workings where necessary.', 'Part 1: Multiple choice (30 marks)\nPart 2: Short answers (40 marks)\nPart 3: Essay/Long answer (30 marks)'),
('JHS Mathematics Exam', 2, 15, 'jhs', 'Term 1', 'exam', 100, 150, 'Attempt all questions. Scientific calculators allowed.', 'Section A: Algebra (25 marks)\nSection B: Geometry (25 marks)\nSection C: Statistics (25 marks)\nSection D: Problem Solving (25 marks)'),
('English Language JHS Exam', 1, 15, 'jhs', 'Term 1', 'exam', 100, 150, 'Read all questions carefully. Write in your own words.', 'Comprehension (30 marks)\nGrammar (25 marks)\nEssay Writing (25 marks)\nSummary (20 marks)');

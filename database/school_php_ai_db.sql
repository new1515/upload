-- School Management System Database
-- Ghana Basic School

CREATE DATABASE IF NOT EXISTS school_php_ai_db;
USE school_php_ai_db;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'admin',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (username, password, email, role) VALUES
('admin', '21232f297a57a5a743894a0e4a801fc3', 'admin@school.edu', 'admin'),
('superadmin', 'e10adc3949ba59abbe56e057f20f883e', 'superadmin@school.edu', 'superadmin');

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO teachers (name, username, password, email, phone) VALUES
('Aba Mensah', 'aba.mensah', '25d55ad283aa400af464c76d713c07ad', 'aba.m@school.edu', '055-0201'),
('Kofi Agyeman', 'kofi.agyeman', '25d55ad283aa400af464c76d713c07ad', 'kofi.a@school.edu', '055-0202'),
('Akua Nyame', 'akua.nyame', '25d55ad283aa400af464c76d713c07ad', 'akua.n@school.edu', '055-0203'),
('Yaw Oppong', 'yaw.oppong', '25d55ad283aa400af464c76d713c07ad', 'yaw.o@school.edu', '055-0204'),
('Akosua Kumi', 'akosua.kumi', '25d55ad283aa400af464c76d713c07ad', 'akosua.k@school.edu', '055-0205'),
('Kwame Asiedu', 'kwame.asiedu', '25d55ad283aa400af464c76d713c07ad', 'kwame.as@school.edu', '055-0206');

-- Subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    category VARCHAR(20) DEFAULT 'core',
    level VARCHAR(20) DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO subjects (subject_name, category, level) VALUES
('English Language', 'core', 'all'),
('Mathematics', 'core', 'all'),
('Science', 'core', 'primary'),
('Social Studies', 'core', 'all'),
('Religious and Moral Education', 'core', 'all'),
('Creative Arts', 'core', 'primary'),
('Our World Our People', 'core', 'primary'),
('Integrated Science', 'core', 'jhs'),
('Citizenship Education', 'core', 'jhs'),
('Agriculture', 'elective', 'jhs'),
('Home Economics', 'elective', 'jhs'),
('French', 'elective', 'jhs');

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    level VARCHAR(20) DEFAULT 'primary',
    class_teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO classes (class_name, section, level, class_teacher_id) VALUES
('Nursery 1', 'A', 'nursery', 1),
('Nursery 1', 'B', 'nursery', 2),
('Nursery 2', 'A', 'nursery', 3),
('Nursery 2', 'B', 'nursery', 4),
('KG 1', 'A', 'kg', 1),
('KG 1', 'B', 'kg', 2),
('KG 2', 'A', 'kg', 3),
('KG 2', 'B', 'kg', 4),
('Basic 1', 'A', 'primary', 1),
('Basic 1', 'B', 'primary', 2),
('Basic 2', 'A', 'primary', 3),
('Basic 2', 'B', 'primary', 4),
('Basic 3', 'A', 'primary', 5),
('Basic 3', 'B', 'primary', 6),
('Basic 4', 'A', 'primary', 1),
('Basic 4', 'B', 'primary', 2),
('Basic 5', 'A', 'primary', 3),
('Basic 5', 'B', 'primary', 4),
('Basic 6', 'A', 'primary', 5),
('Basic 6', 'B', 'primary', 6),
('JHS 1', 'A', 'jhs', 1),
('JHS 1', 'B', 'jhs', 2),
('JHS 2', 'A', 'jhs', 3),
('JHS 2', 'B', 'jhs', 4),
('JHS 3', 'A', 'jhs', 5),
('JHS 3', 'B', 'jhs', 6);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    class_id INT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    photo VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO students (name, username, password, class_id, gender, email, phone) VALUES
('Kwame Asante', 'kwame.asante', '25d55ad283aa400af464c76d713c07ad', 9, 'Male', 'kwame@email.com', '055-0101'),
('Akua Serwaa', 'akua.serwaa', '25d55ad283aa400af464c76d713c07ad', 9, 'Female', 'akua@email.com', '055-0102'),
('Kofi Mensah', 'kofi.mensah', '25d55ad283aa400af464c76d713c07ad', 9, 'Male', 'kofi@email.com', '055-0103'),
('Ama Darko', 'ama.darko', '25d55ad283aa400af464c76d713c07ad', 10, 'Female', 'ama@email.com', '055-0104'),
('Yaw Osei', 'yaw.osei', '25d55ad283aa400af464c76d713c07ad', 10, 'Male', 'yaw@email.com', '055-0105'),
('Kojo Afful', 'kojo.afful', '25d55ad283aa400af464c76d713c07ad', 11, 'Male', 'kojo@email.com', '055-0107'),
('Adjoa Serwaa', 'adjoa.serwaa', '25d55ad283aa400af464c76d713c07ad', 21, 'Female', 'adjoa@email.com', '055-0111');

-- Parents table
CREATE TABLE parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    student_id INT,
    relationship VARCHAR(20) DEFAULT 'guardian',
    status ENUM('active', 'inactive') DEFAULT 'active',
    verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO parents (name, username, password, email, phone, student_id, relationship) VALUES
('Mr. Asante', 'mr.asante', '25d55ad283aa400af464c76d713c07ad', 'asante@email.com', '055-1001', 1, 'father'),
('Mrs. Serwaa', 'mrs.serwaa', '25d55ad283aa400af464c76d713c07ad', 'serwaa@email.com', '055-1002', 2, 'mother'),
('Mr. Mensah', 'mr.mensah', '25d55ad283aa400af464c76d713c07ad', 'mensah@email.com', '055-1003', 3, 'father');

-- Student Assessments
CREATE TABLE student_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    term VARCHAR(20) DEFAULT 'Term 1',
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    test1 DECIMAL(5,2) DEFAULT 0,
    test2 DECIMAL(5,2) DEFAULT 0,
    test3 DECIMAL(5,2) DEFAULT 0,
    project DECIMAL(5,2) DEFAULT 0,
    class_assessment DECIMAL(5,2) DEFAULT 0,
    exam DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(2),
    remarks VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO student_assessments (student_id, class_id, subject_id, term, academic_year, test1, test2, test3, project, class_assessment, exam, grade, remarks) VALUES
(1, 9, 1, 'Term 1', '2025-2026', 8.5, 9.0, 8.0, 9.5, 8.0, 45.0, 'A', 'Excellent'),
(1, 9, 2, 'Term 1', '2025-2026', 7.0, 8.5, 8.0, 8.0, 7.5, 42.0, 'B', 'Very Good'),
(2, 9, 1, 'Term 1', '2025-2026', 8.0, 7.5, 8.5, 8.0, 7.0, 40.0, 'B', 'Very Good'),
(3, 9, 1, 'Term 1', '2025-2026', 9.0, 9.5, 9.0, 9.0, 9.5, 48.0, 'A', 'Excellent');

-- Student Attendance
CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    term VARCHAR(20),
    academic_year VARCHAR(20),
    days_school_opened INT DEFAULT 0,
    days_present INT DEFAULT 0,
    days_absent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Timetable
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_audience VARCHAR(20) DEFAULT 'all',
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(20) DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO announcements (title, content, target_audience, priority) VALUES
('Welcome Back!', 'We welcome all students and staff to the new academic year.', 'all', 'high'),
('Parent-Teacher Meeting', 'A parent-teacher meeting is scheduled for next Friday at 2:00 PM.', 'parents', 'medium'),
('Examination Schedule', 'End of term examinations will begin on the 15th of next month.', 'students', 'high');

-- Academic Calendar
CREATE TABLE academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO academic_calendar (title, description, event_type, start_date, end_date) VALUES
('Term 1 Opening', 'First day of Term 1', 'term', '2025-01-06', '2025-01-06'),
('Mid-Term Break', 'Mid-term break for all students', 'holiday', '2025-02-20', '2025-02-24'),
('End of Term Exams', 'End of Term 1 examinations', 'exam', '2025-03-10', '2025-03-21');

-- School Settings
CREATE TABLE school_settings (
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
('headmaster_name', 'Mr. Emmanuel Kofi Asante'),
('headmaster_title', 'Headmaster'),
('class_teacher_title', 'Class Teacher'),
('school_start_time', '07:30'),
('period_duration', '40'),
('break1_start', '09:30'),
('break1_end', '10:00'),
('break2_start', '12:00'),
('break2_end', '12:40'),
('school_end_time', '14:30');

-- Teacher Classes
CREATE TABLE teacher_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO teacher_classes (teacher_id, class_id) VALUES
(1, 9), (1, 15), (1, 21),
(2, 10), (2, 16), (2, 22),
(3, 11), (3, 17), (3, 23),
(4, 12), (4, 18), (4, 24);

-- Teacher Subjects
CREATE TABLE teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES
(1, 1), (1, 2), (1, 5), (1, 6),
(2, 1), (2, 3), (2, 5), (2, 7),
(3, 2), (3, 4), (3, 6), (3, 8),
(4, 9), (4, 10), (4, 11), (4, 12);

-- Lessons
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    file_name VARCHAR(255),
    file_type VARCHAR(50),
    file_path VARCHAR(255),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lesson Plans
CREATE TABLE lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(200),
    term VARCHAR(20) DEFAULT 'Term 1',
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    week VARCHAR(50) NOT NULL,
    topic VARCHAR(200),
    sub_topic VARCHAR(200),
    objectives TEXT,
    activities TEXT,
    materials TEXT,
    duration VARCHAR(50),
    evaluation TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test Books
CREATE TABLE test_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    test_type VARCHAR(50) DEFAULT 'Class Test',
    test_date DATE NOT NULL,
    total_marks INT DEFAULT 20,
    topic VARCHAR(200),
    scores_data TEXT,
    remarks TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chatbot Q&A
CREATE TABLE chatbot_qa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keywords TEXT NOT NULL,
    question VARCHAR(255),
    answer TEXT NOT NULL,
    category VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Q&A
INSERT INTO chatbot_qa (keywords, question, answer, category) VALUES
('hello,hi,hey', 'Greeting', 'Hello! Welcome to [SCHOOL_NAME]! How can I help you today?', 'general'),
('how are you,how do you do', 'Status', 'I am doing great, thank you for asking! How can I assist you today?', 'general'),
('admission,apply,register,enroll', 'Admissions', 'To apply for admission:\n1. Visit the school with birth certificate\n2. Provide previous school records (if applicable)\n3. Complete admission form\n4. Pay registration fee\n\nContact us for more details!', 'admission'),
('fees,school fees,tuition,payment,cost', 'Fees', 'School fees vary by class level:\n- Nursery & KG: GH¢ 400-500 per term\n- Primary: GH¢ 500-700 per term\n- JHS: GH¢ 600-800 per term\n\nContact administration for detailed fee structure.', 'fees'),
('contact,phone,email,reach,call', 'Contact', 'Contact [SCHOOL_NAME]:\n- Phone: [SCHOOL_PHONE]\n- Email: [SCHOOL_EMAIL]\n- Address: [SCHOOL_ADDRESS]\n\nVisit us during school hours!', 'contact'),
('hours,time,open,close,schedule', 'Hours', 'School Hours:\n- Classes: 7:45 AM - 2:30 PM\n- Office: 7:30 AM - 4:00 PM\n- Days: Monday to Friday\n\nExtracurricular activities may extend beyond.', 'general'),
('location,address,where,located,find', 'Location', '[SCHOOL_NAME] is located in [SCHOOL_ADDRESS].\n\nVisit us for a campus tour during school hours!', 'contact'),
('uniform,dress,clothes,attire', 'Uniform', 'School Uniform Requirements:\n- Navy blue polo shirt with school emblem\n- Navy blue trousers/skirt\n- White shirts for Fridays\n- PE uniform for sports days\n\nContact office for complete uniform list and purchasing details.', 'general'),
('principal,headmaster,headmistress,leader', 'Leadership', 'Please contact the school administration office for information about school leadership.', 'contact'),
('teacher,teachers,staff,instructor', 'Staff', 'We have qualified and experienced teachers for all subjects!\n\nEach class has dedicated subject teachers committed to providing quality education.', 'general'),
('subject,subjects,course,lesson', 'Subjects', 'Core Subjects:\n- English Language\n- Mathematics\n- Science\n- Social Studies\n- RME (Religious & Moral Education)\n\nElective subjects vary by class level.', 'academic'),
('homework,assignment,task,work', 'Homework', 'Homework is assigned regularly to reinforce learning.\n\nCheck with your class teacher for specific assignments.\nParents can monitor homework through the parent portal.', 'academic'),
('exam,examination,test,quiz', 'Examinations', 'Examination Schedule:\n- Continuous Assessment: Ongoing\n- Mid-Term Tests: As scheduled\n- End-of-Term Exams: End of each term\n\nResults are shared with parents via report cards.', 'academic'),
('result,results,grade,grades,report', 'Results', 'Student results are available through:\n1. Report Cards (end of each term)\n2. Parent Portal (online access)\n3. School Office (hard copies)\n\nContact class teacher for specific inquiries.', 'academic'),
('transport,bus,van,carriage', 'Transportation', 'Transportation services may be available.\n\nContact the school office to inquire about bus routes and transportation fees.', 'general'),
('food,lunch,cafeteria,canteen,meal', 'Food Services', 'Students can bring lunch from home or purchase from the school canteen.\n\nHealthy eating is encouraged!', 'general'),
('thanks,thank you,appreciate', 'Thanks', 'You are welcome!\n\nIs there anything else I can help you with?', 'general'),
('bye,goodbye,see you,take care', 'Farewell', 'Goodbye! Have a wonderful day!\n\nFeel free to return if you have more questions.', 'general'),
('help,assist,support,guide', 'Help', 'I can help you with information about:\n• Admissions & Registration\n• School Fees\n• Contact Details\n• School Hours\n• Uniform Requirements\n• Subjects Offered\n• Results & Examinations\n• Transportation\n• Food Services\n\nJust ask!', 'general');

-- Activity History
CREATE TABLE activity_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    username VARCHAR(50),
    record_type VARCHAR(50),
    record_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chatbot Messages
CREATE TABLE chatbot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    user_message TEXT,
    bot_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notes
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    category VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fee Categories
CREATE TABLE fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_mandatory TINYINT(1) DEFAULT 1,
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Class Fees (fee amounts per class)
CREATE TABLE class_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    class_level VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    term VARCHAR(20),
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES fee_categories(id)
);

-- Student Fee Payments
CREATE TABLE student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    due_date DATE,
    paid_date DATE,
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    term VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (category_id) REFERENCES fee_categories(id)
);

-- Fee Payment Records
CREATE TABLE fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50),
    reference_number VARCHAR(50),
    received_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id)
);

-- Insert Sample Fee Categories
INSERT INTO fee_categories (name, description, is_mandatory) VALUES
('Tuition Fee', 'Basic tuition charges for the academic year', 1),
('Registration Fee', 'One-time registration fee per academic year', 1),
('Examination Fee', 'Fees for examinations and tests', 1),
('Sports Fee', 'Fees for sports and physical education activities', 0),
('Library Fee', 'Fees for library services and book rental', 0),
('Computer Fee', 'Fees for computer lab and IT services', 0),
(' PTA Fee', 'Parent-Teacher Association dues', 1),
('Activity Fee', 'Fees for school activities and events', 0);

-- Insert Sample Class Fees
INSERT INTO class_fees (category_id, class_level, amount, academic_year) VALUES
(1, 'nursery', 300.00, '2025-2026'),
(1, 'kg', 350.00, '2025-2026'),
(1, 'primary', 400.00, '2025-2026'),
(1, 'jhs', 500.00, '2025-2026'),
(2, 'nursery', 50.00, '2025-2026'),
(2, 'kg', 50.00, '2025-2026'),
(2, 'primary', 75.00, '2025-2026'),
(2, 'jhs', 100.00, '2025-2026'),
(3, 'nursery', 40.00, '2025-2026'),
(3, 'kg', 40.00, '2025-2026'),
(3, 'primary', 60.00, '2025-2026'),
(3, 'jhs', 80.00, '2025-2026'),
(4, 'nursery', 30.00, '2025-2026'),
(4, 'kg', 30.00, '2025-2026'),
(4, 'primary', 40.00, '2025-2026'),
(4, 'jhs', 50.00, '2025-2026'),
(5, 'nursery', 25.00, '2025-2026'),
(5, 'kg', 25.00, '2025-2026'),
(5, 'primary', 35.00, '2025-2026'),
(5, 'jhs', 45.00, '2025-2026'),
(6, 'nursery', 50.00, '2025-2026'),
(6, 'kg', 50.00, '2025-2026'),
(6, 'primary', 75.00, '2025-2026'),
(6, 'jhs', 100.00, '2025-2026'),
(7, 'nursery', 30.00, '2025-2026'),
(7, 'kg', 30.00, '2025-2026'),
(7, 'primary', 40.00, '2025-2026'),
(7, 'jhs', 50.00, '2025-2026'),
(8, 'nursery', 35.00, '2025-2026'),
(8, 'kg', 35.00, '2025-2026'),
(8, 'primary', 45.00, '2025-2026'),
(8, 'jhs', 60.00, '2025-2026');

-- Insert Sample Student Fees
INSERT INTO student_fees (student_id, category_id, amount, academic_year, payment_status) VALUES
(1, 1, 300.00, '2025-2026', 'unpaid'),
(1, 2, 50.00, '2025-2026', 'unpaid'),
(1, 3, 40.00, '2025-2026', 'unpaid'),
(2, 1, 300.00, '2025-2026', 'unpaid'),
(2, 2, 50.00, '2025-2026', 'unpaid'),
(3, 1, 300.00, '2025-2026', 'unpaid');

-- Insert Sample Lesson Plans
INSERT INTO lesson_plans (teacher_id, class_id, subject_id, title, week, topic, objectives, activities, materials, evaluation) VALUES
(1, 9, 1, 'Introduction to Numbers', 'Week 1', 'Counting 1-10', 'Count numbers 1-10, Write numbers correctly', 'Intro (5min), Demo (15min), Practice (15min), Individual (5min)', 'Number charts, Counters, Chalkboard', 'Oral counting, Workbook exercise'),
(1, 9, 2, 'Addition Basics', 'Week 2', 'Adding single digit numbers', 'Add two single digit numbers, Solve addition problems', 'Review (5min), Introduce + (10min), Demo (15min), Practice (10min)', 'Counters, Number cards, Workbook', 'Complete worksheet'),
(1, 9, 1, 'Reading Simple Words', 'Week 3', 'Three-letter words', 'Read CVC words, Spell simple words', 'Review sounds (5min), Blend (15min), Read list (10min), Game (10min)', 'Word cards, Flashcards, Reading chart', 'Read 5 words, Write 3 words'),
(1, 9, 2, 'Shapes and Patterns', 'Week 4', 'Basic 2D shapes', 'Identify circle, square, triangle, rectangle', 'Show flashcards (5min), Describe (10min), Hunt (15min), Draw (10min)', 'Shape flashcards, Real objects, Paper', 'Identify shapes, Draw shapes'),
(1, 9, 5, 'Our School', 'Week 1', 'Parts of the school', 'Name parts of school, Know classroom location', 'Walk (10min), Discuss (10min), Label diagram (15min), Share (5min)', 'School diagram, Crayons, Chart paper', 'Draw and label classroom');

-- Insert Sample Notes
INSERT INTO notes (title, content, category, created_by) VALUES
('English Grammar Rules', 'PARTS OF SPEECH:\n\n1. NOUN - Person, place, thing, or idea\n2. VERB - Action word\n3. ADJECTIVE - Describes a noun\n4. ADVERB - Describes a verb\n5. PRONOUN - Takes the place of a noun\n\nSENTENCE: Subject + Verb + Object', 'English', 1),
('Mathematics Formulas', 'ADDITION: a + b = c\nSUBTRACTION: a - b = c\nMULTIPLICATION: a x b = c\nDIVISION: a / b = c\n\nBODMAS: Brackets, Orders, Division, Multiplication, Addition, Subtraction', 'Mathematics', 1),
('Science Topics', 'SCIENTIFIC METHOD:\n1. OBSERVE\n2. QUESTION\n3. HYPOTHESIZE\n4. EXPERIMENT\n5. ANALYZE\n6. CONCLUDE\n\nLIVING THINGS: Need food, water, air. Grow and change.', 'Science', 1),
('Social Studies Notes', 'MAP READING:\n1. Compass Rose - Shows N, S, E, W\n2. Map Key - Explains symbols\n3. Scale - Shows distance\n\nGHANA: Located in West Africa', 'Social Studies', 1),
('RME Key Points', 'TEN COMMANDMENTS:\n1. No other gods\n2. No idols\n3. Respect my name\n4. Keep Sabbath holy\n5. Honor parents\n6. Do not kill\n7. No adultery\n8. Do not steal\n9. No false witness\n10. No coveting', 'RME', 1);

-- Update lesson_plans table
DROP TABLE IF EXISTS lesson_plans;

CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    term VARCHAR(20) NOT NULL DEFAULT 'Term 1',
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    week INT NOT NULL DEFAULT 1,
    topic VARCHAR(200) NOT NULL,
    sub_topic VARCHAR(200),
    objectives TEXT,
    content TEXT,
    activities TEXT,
    resources TEXT,
    assessment TEXT,
    reflection TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    UNIQUE KEY unique_plan (class_id, subject_id, term, academic_year, week)
);

-- Update test_books table
DROP TABLE IF EXISTS test_books;

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

-- Update lessons table to include uploaded_by
ALTER TABLE lessons ADD COLUMN uploaded_by INT AFTER file_path;

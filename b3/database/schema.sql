-- ساختار دیتابیس برای سیستم فریلنسری

-- جدول کاربران
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    user_type ENUM('freelancer', 'employer') NOT NULL,
    company_name VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول کلاس‌های شغلی
CREATE TABLE JobClasses (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) UNIQUE NOT NULL
);

-- جدول زیرکلاس‌های شغلی
CREATE TABLE JobSubClasses (
    subclass_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subclass_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (class_id) REFERENCES JobClasses(class_id) ON DELETE CASCADE
);

-- جدول مهارت‌های کارجو
CREATE TABLE FreelancerSkills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    subclass_id INT,
    proficiency_level ENUM('beginner', 'intermediate', 'expert') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES JobClasses(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subclass_id) REFERENCES JobSubClasses(subclass_id) ON DELETE SET NULL
);

-- جدول آگهی‌ها
CREATE TABLE Jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    budget_type ENUM('range', 'negotiable') NOT NULL,
    budget_min DECIMAL(10,2),
    budget_max DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- جدول مهارت‌های مورد نیاز آگهی
CREATE TABLE JobRequiredSkills (
    job_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    class_id INT NOT NULL,
    subclass_id INT,
    proficiency_level ENUM('beginner', 'intermediate', 'expert') NOT NULL,
    FOREIGN KEY (job_id) REFERENCES Jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES JobClasses(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subclass_id) REFERENCES JobSubClasses(subclass_id) ON DELETE SET NULL
);

-- ایجاد ایندکس‌ها برای بهینه‌سازی
CREATE INDEX idx_user_id ON Users(user_id);
CREATE INDEX idx_job_id ON Jobs(job_id);
CREATE INDEX idx_class_id ON JobSubClasses(class_id);
CREATE INDEX idx_freelancer_skills_user ON FreelancerSkills(user_id);
CREATE INDEX idx_job_required_skills_job ON JobRequiredSkills(job_id);

-- درج داده‌های نمونه
INSERT INTO JobClasses (class_name) VALUES 
('برنامه‌نویسی'), 
('طراحی گرافیک'),
('نویسندگی'),
('ترجمه'),
('بازاریابی دیجیتال');

INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(1, 'توسعه وب'),
(1, 'توسعه اپلیکیشن'),
(1, 'توسعه بازی'),
(2, 'طراحی لوگو'),
(2, 'طراحی رابط کاربری'),
(2, 'طراحی بسته‌بندی'),
(3, 'نویسندگی محتوا'),
(3, 'نویسندگی فنی'),
(4, 'ترجمه انگلیسی'),
(4, 'ترجمه فرانسوی'),
(5, 'بازاریابی شبکه‌های اجتماعی'),
(5, 'سئو'); 
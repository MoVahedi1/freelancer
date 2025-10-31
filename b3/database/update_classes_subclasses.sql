-- بروزرسانی کلاس‌های شغلی و زیرکلاس‌ها
-- حذف تمام داده‌های موجود و درج داده‌های جدید

-- غیرفعال کردن بررسی کلیدهای خارجی موقتاً
SET FOREIGN_KEY_CHECKS = 0;

-- حذف تمام داده‌های مرتبط به ترتیب صحیح
-- ابتدا جداول وابسته را حذف می‌کنیم
DELETE FROM JobRequiredSkills;
DELETE FROM FreelancerSkills;
DELETE FROM JobSubClasses;
DELETE FROM JobClasses;

-- بازنشانی AUTO_INCREMENT
ALTER TABLE JobClasses AUTO_INCREMENT = 1;
ALTER TABLE JobSubClasses AUTO_INCREMENT = 1;

-- فعال کردن مجدد بررسی کلیدهای خارجی
SET FOREIGN_KEY_CHECKS = 1;

-- درج کلاس‌های جدید
INSERT INTO JobClasses (class_name) VALUES 
('برنامه‌نویسی و توسعه نرم‌افزار (Software Development)'),
('هوش مصنوعی و یادگیری ماشین (AI & Machine Learning)'),
('علم داده و تحلیل داده (Data Science & Analytics)'),
('امنیت اطلاعات و شبکه (Cybersecurity & Networking)'),
('پایگاه داده و مدیریت داده (Database Administration)'),
('توسعه وب (Web Development) فرانت'),
('توسعه وب (Web Development) بک اند'),
('توسعه موبایل (Mobile Development)'),
('مهندسی نرم‌افزار و DevOps'),
('رایانش ابری و فناوری‌های Cloud'),
('فناوری‌های بلاکچین و رمزارزها'),
('مدیریت پروژه فناوری اطلاعات (IT Project Management)'),
('تست نرم‌افزار و QA (Software Testing & QA)');

-- درج زیرکلاس‌ها
-- برای کلاس برنامه‌نویسی و توسعه نرم‌افزار
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(1, 'Python'),
(1, 'Java'),
(1, 'C'),
(1, 'C++'),
(1, 'C#'),
(1, 'JavaScript'),
(1, 'Kotlin'),
(1, 'Swift'),
(1, 'Go'),
(1, 'Rust'),
(1, 'Ruby'),
(1, 'PHP'),
(1, 'TypeScript'),
(1, 'Django'),
(1, 'Flask'),
(1, 'Spring'),
(1, '.NET'),
(1, 'React'),
(1, 'Angular'),
(1, 'Vue.js'),
(1, 'Node.js');

-- برای کلاس هوش مصنوعی و یادگیری ماشین
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(2, 'یادگیری ماشین (Machine Learning)'),
(2, 'یادگیری عمیق (Deep Learning)'),
(2, 'پردازش زبان طبیعی (NLP)'),
(2, 'بینایی ماشین (Computer Vision)');

-- برای کلاس علم داده و تحلیل داده
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(3, 'تحلیل داده (Data Analysis)'),
(3, 'مهندسی داده (Data Engineering)'),
(3, 'مصورسازی داده (Data Visualization)'),
(3, 'SQL'),
(3, 'NoSQL (MongoDB, Cassandra)'),
(3, 'Pandas'),
(3, 'NumPy'),
(3, 'Tableau'),
(3, 'Power BI'),
(3, 'Apache Spark');

-- برای کلاس امنیت اطلاعات و شبکه
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(4, 'تست نفوذ (Penetration Testing)'),
(4, 'امنیت شبکه (Network Security)'),
(4, 'رمزنگاری (Cryptography)'),
(4, 'Wireshark'),
(4, 'Metasploit'),
(4, 'Burp Suite'),
(4, 'Nmap'),
(4, 'Kali Linux');

-- برای کلاس پایگاه داده و مدیریت داده
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(5, 'MySQL'),
(5, 'PostgreSQL'),
(5, 'Oracle'),
(5, 'Microsoft SQL Server'),
(5, 'MongoDB'),
(5, 'Redis');

-- برای کلاس توسعه وب فرانت
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(6, 'HTML/CSS'),
(6, 'JavaScript'),
(6, 'React'),
(6, 'Angular'),
(6, 'Vue.js');

-- برای کلاس توسعه وب بک اند
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(7, 'Node.js'),
(7, 'Django'),
(7, 'Flask'),
(7, 'Laravel'),
(7, 'Ruby on Rails');

-- برای کلاس توسعه موبایل
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(8, 'Android (Kotlin/Java)'),
(8, 'iOS (Swift)'),
(8, 'Flutter'),
(8, 'React Native');

-- برای کلاس DevOps و رایانش ابری
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(9, 'Docker'),
(9, 'Kubernetes'),
(9, 'CI/CD (Jenkins, GitLab CI)'),
(9, 'AWS'),
(9, 'Azure'),
(9, 'Google Cloud');

-- برای کلاس رایانش ابری و فناوری‌های Cloud (اگر جداگانه باشد)
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(10, 'AWS'),
(10, 'Azure'),
(10, 'Google Cloud'),
(10, 'Docker'),
(10, 'Kubernetes');

-- برای کلاس فناوری بلاکچین و رمزارزها
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(11, 'توسعه‌دهنده بلاکچین (Blockchain Developer)'),
(11, 'مهندس قراردادهای هوشمند (Smart Contract Engineer)'),
(11, 'تحلیلگر رمزارزها (Cryptocurrency Analyst)'),
(11, 'متخصص امنیت بلاکچین (Blockchain Security Specialist)'),
(11, 'معمار بلاکچین (Blockchain Architect)'),
(11, 'مشاور بلاکچین (Blockchain Consultant)'),
(11, 'توسعه‌دهنده برنامه‌های غیرمتمرکز (DApp Developer)'),
(11, 'مدیر محصول بلاکچین (Blockchain Product Manager)'),
(11, 'تحلیلگر داده‌های بلاکچین (Blockchain Data Analyst)'),
(11, 'متخصص توکن‌سازی (Tokenization Specialist)');

-- برای کلاس مدیریت پروژه فناوری اطلاعات
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(12, 'مدیر پروژه فناوری اطلاعات (IT Project Manager)'),
(12, 'هماهنگ‌کننده پروژه (Project Coordinator)'),
(12, 'مدیر برنامه (Program Manager)'),
(12, 'مدیر پورتفولیو پروژه (Portfolio Manager)'),
(12, 'مدیر پروژه اسکرام (Scrum Master)'),
(12, 'مدیر پروژه چابک (Agile Project Manager)'),
(12, 'تحلیلگر کسب‌وکار فناوری اطلاعات (IT Business Analyst)'),
(12, 'مدیر تغییر فناوری اطلاعات (IT Change Manager)'),
(12, 'مدیر ریسک پروژه (Project Risk Manager)'),
(12, 'متخصص زمان‌بندی پروژه (Project Scheduler)');

-- برای کلاس تست نرم‌افزار و QA
INSERT INTO JobSubClasses (class_id, subclass_name) VALUES 
(13, 'مهندس تست نرم‌افزار (Software Test Engineer)'),
(13, 'متخصص تضمین کیفیت (QA Engineer)'),
(13, 'تست‌کننده خودکار (Automation Tester)'),
(13, 'تست‌کننده دستی (Manual Tester)'),
(13, 'تحلیلگر کیفیت نرم‌افزار (Software Quality Analyst)'),
(13, 'متخصص تست عملکرد (Performance Test Engineer)'),
(13, 'متخصص تست امنیت (Security Test Engineer)'),
(13, 'مهندس تست قابلیت استفاده (Usability Test Engineer)'),
(13, 'سرپرست تیم QA (QA Lead)'),
(13, 'متخصص تست DevOps (DevOps Test Engineer)');

-- نمایش تعداد رکوردهای درج شده
SELECT 'Classes inserted:' as Info, COUNT(*) as Count FROM JobClasses;
SELECT 'Subclasses inserted:' as Info, COUNT(*) as Count FROM JobSubClasses;

-- نمایش کلاس‌ها و تعداد زیرکلاس‌های هر کدام
SELECT 
    jc.class_id,
    jc.class_name,
    COUNT(jsc.subclass_id) as subclass_count
FROM JobClasses jc
LEFT JOIN JobSubClasses jsc ON jc.class_id = jsc.class_id
GROUP BY jc.class_id, jc.class_name
ORDER BY jc.class_id;

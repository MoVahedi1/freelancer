<?php
require_once __DIR__ . '/../backend/config/database.php';

try {
    // اتصال به دیتابیس
    $database = new Database();
    $db = $database->getConnection();
    
    echo "اتصال به دیتابیس برقرار شد.\n";
    
    // غیرفعال کردن بررسی کلیدهای خارجی
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // حذف تمام داده‌های مرتبط به ترتیب صحیح
    // ابتدا جداول وابسته را حذف می‌کنیم
    $db->exec("DELETE FROM JobRequiredSkills");
    $db->exec("DELETE FROM FreelancerSkills");
    $db->exec("DELETE FROM JobSubClasses");
    $db->exec("DELETE FROM JobClasses");
    
    // بازنشانی AUTO_INCREMENT
    $db->exec("ALTER TABLE JobClasses AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE JobSubClasses AUTO_INCREMENT = 1");
    
    // فعال کردن مجدد بررسی کلیدهای خارجی
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "داده‌های قدیمی حذف شدند.\n";
    
    // درج کلاس‌های جدید
    $classes = [
        'برنامه‌نویسی و توسعه نرم‌افزار (Software Development)',
        'هوش مصنوعی و یادگیری ماشین (AI & Machine Learning)',
        'علم داده و تحلیل داده (Data Science & Analytics)',
        'امنیت اطلاعات و شبکه (Cybersecurity & Networking)',
        'پایگاه داده و مدیریت داده (Database Administration)',
        'توسعه وب (Web Development) فرانت',
        'توسعه وب (Web Development) بک اند',
        'توسعه موبایل (Mobile Development)',
        'مهندسی نرم‌افزار و DevOps',
        'رایانش ابری و فناوری‌های Cloud',
        'فناوری‌های بلاکچین و رمزارزها',
        'مدیریت پروژه فناوری اطلاعات (IT Project Management)',
        'تست نرم‌افزار و QA (Software Testing & QA)'
    ];
    
    $stmt = $db->prepare("INSERT INTO JobClasses (class_name) VALUES (?)");
    foreach ($classes as $class) {
        $stmt->execute([$class]);
    }
    
    echo "کلاس‌های جدید درج شدند.\n";
    
    // درج زیرکلاس‌ها
    $subclasses = [
        // برای کلاس برنامه‌نویسی و توسعه نرم‌افزار (class_id = 1)
        [1, 'Python'],
        [1, 'Java'],
        [1, 'C'],
        [1, 'C++'],
        [1, 'C#'],
        [1, 'JavaScript'],
        [1, 'Kotlin'],
        [1, 'Swift'],
        [1, 'Go'],
        [1, 'Rust'],
        [1, 'Ruby'],
        [1, 'PHP'],
        [1, 'TypeScript'],
        [1, 'Django'],
        [1, 'Flask'],
        [1, 'Spring'],
        [1, '.NET'],
        [1, 'React'],
        [1, 'Angular'],
        [1, 'Vue.js'],
        [1, 'Node.js'],
        
        // برای کلاس هوش مصنوعی و یادگیری ماشین (class_id = 2)
        [2, 'یادگیری ماشین (Machine Learning)'],
        [2, 'یادگیری عمیق (Deep Learning)'],
        [2, 'پردازش زبان طبیعی (NLP)'],
        [2, 'بینایی ماشین (Computer Vision)'],
        
        // برای کلاس علم داده و تحلیل داده (class_id = 3)
        [3, 'تحلیل داده (Data Analysis)'],
        [3, 'مهندسی داده (Data Engineering)'],
        [3, 'مصورسازی داده (Data Visualization)'],
        [3, 'SQL'],
        [3, 'NoSQL (MongoDB, Cassandra)'],
        [3, 'Pandas'],
        [3, 'NumPy'],
        [3, 'Tableau'],
        [3, 'Power BI'],
        [3, 'Apache Spark'],
        
        // برای کلاس امنیت اطلاعات و شبکه (class_id = 4)
        [4, 'تست نفوذ (Penetration Testing)'],
        [4, 'امنیت شبکه (Network Security)'],
        [4, 'رمزنگاری (Cryptography)'],
        [4, 'Wireshark'],
        [4, 'Metasploit'],
        [4, 'Burp Suite'],
        [4, 'Nmap'],
        [4, 'Kali Linux'],
        
        // برای کلاس پایگاه داده و مدیریت داده (class_id = 5)
        [5, 'MySQL'],
        [5, 'PostgreSQL'],
        [5, 'Oracle'],
        [5, 'Microsoft SQL Server'],
        [5, 'MongoDB'],
        [5, 'Redis'],
        
        // برای کلاس توسعه وب فرانت (class_id = 6)
        [6, 'HTML/CSS'],
        [6, 'JavaScript'],
        [6, 'React'],
        [6, 'Angular'],
        [6, 'Vue.js'],
        
        // برای کلاس توسعه وب بک اند (class_id = 7)
        [7, 'Node.js'],
        [7, 'Django'],
        [7, 'Flask'],
        [7, 'Laravel'],
        [7, 'Ruby on Rails'],
        
        // برای کلاس توسعه موبایل (class_id = 8)
        [8, 'Android (Kotlin/Java)'],
        [8, 'iOS (Swift)'],
        [8, 'Flutter'],
        [8, 'React Native'],
        
        // برای کلاس DevOps و رایانش ابری (class_id = 9)
        [9, 'Docker'],
        [9, 'Kubernetes'],
        [9, 'CI/CD (Jenkins, GitLab CI)'],
        [9, 'AWS'],
        [9, 'Azure'],
        [9, 'Google Cloud'],
        
        // برای کلاس رایانش ابری و فناوری‌های Cloud (class_id = 10)
        [10, 'AWS'],
        [10, 'Azure'],
        [10, 'Google Cloud'],
        [10, 'Docker'],
        [10, 'Kubernetes'],
        
        // برای کلاس فناوری بلاکچین و رمزارزها (class_id = 11)
        [11, 'توسعه‌دهنده بلاکچین (Blockchain Developer)'],
        [11, 'مهندس قراردادهای هوشمند (Smart Contract Engineer)'],
        [11, 'تحلیلگر رمزارزها (Cryptocurrency Analyst)'],
        [11, 'متخصص امنیت بلاکچین (Blockchain Security Specialist)'],
        [11, 'معمار بلاکچین (Blockchain Architect)'],
        [11, 'مشاور بلاکچین (Blockchain Consultant)'],
        [11, 'توسعه‌دهنده برنامه‌های غیرمتمرکز (DApp Developer)'],
        [11, 'مدیر محصول بلاکچین (Blockchain Product Manager)'],
        [11, 'تحلیلگر داده‌های بلاکچین (Blockchain Data Analyst)'],
        [11, 'متخصص توکن‌سازی (Tokenization Specialist)'],
        
        // برای کلاس مدیریت پروژه فناوری اطلاعات (class_id = 12)
        [12, 'مدیر پروژه فناوری اطلاعات (IT Project Manager)'],
        [12, 'هماهنگ‌کننده پروژه (Project Coordinator)'],
        [12, 'مدیر برنامه (Program Manager)'],
        [12, 'مدیر پورتفولیو پروژه (Portfolio Manager)'],
        [12, 'مدیر پروژه اسکرام (Scrum Master)'],
        [12, 'مدیر پروژه چابک (Agile Project Manager)'],
        [12, 'تحلیلگر کسب‌وکار فناوری اطلاعات (IT Business Analyst)'],
        [12, 'مدیر تغییر فناوری اطلاعات (IT Change Manager)'],
        [12, 'مدیر ریسک پروژه (Project Risk Manager)'],
        [12, 'متخصص زمان‌بندی پروژه (Project Scheduler)'],
        
        // برای کلاس تست نرم‌افزار و QA (class_id = 13)
        [13, 'مهندس تست نرم‌افزار (Software Test Engineer)'],
        [13, 'متخصص تضمین کیفیت (QA Engineer)'],
        [13, 'تست‌کننده خودکار (Automation Tester)'],
        [13, 'تست‌کننده دستی (Manual Tester)'],
        [13, 'تحلیلگر کیفیت نرم‌افزار (Software Quality Analyst)'],
        [13, 'متخصص تست عملکرد (Performance Test Engineer)'],
        [13, 'متخصص تست امنیت (Security Test Engineer)'],
        [13, 'مهندس تست قابلیت استفاده (Usability Test Engineer)'],
        [13, 'سرپرست تیم QA (QA Lead)'],
        [13, 'متخصص تست DevOps (DevOps Test Engineer)']
    ];
    
    $stmt = $db->prepare("INSERT INTO JobSubClasses (class_id, subclass_name) VALUES (?, ?)");
    foreach ($subclasses as $subclass) {
        $stmt->execute([$subclass[0], $subclass[1]]);
    }
    
    echo "زیرکلاس‌های جدید درج شدند.\n";
    
    // نمایش آمار
    $stmt = $db->query("SELECT COUNT(*) as count FROM JobClasses");
    $classCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM JobSubClasses");
    $subclassCount = $stmt->fetch()['count'];
    
    echo "\n=== آمار نهایی ===\n";
    echo "تعداد کلاس‌های درج شده: $classCount\n";
    echo "تعداد زیرکلاس‌های درج شده: $subclassCount\n";
    
    // نمایش کلاس‌ها و تعداد زیرکلاس‌های هر کدام
    echo "\n=== جزئیات کلاس‌ها ===\n";
    $stmt = $db->query("
        SELECT 
            jc.class_id,
            jc.class_name,
            COUNT(jsc.subclass_id) as subclass_count
        FROM JobClasses jc
        LEFT JOIN JobSubClasses jsc ON jc.class_id = jsc.class_id
        GROUP BY jc.class_id, jc.class_name
        ORDER BY jc.class_id
    ");
    
    while ($row = $stmt->fetch()) {
        echo "کلاس {$row['class_id']}: {$row['class_name']} ({$row['subclass_count']} زیرکلاس)\n";
    }
    
    echo "\nبروزرسانی دیتابیس با موفقیت انجام شد!\n";
    
} catch (Exception $e) {
    echo "خطا در بروزرسانی دیتابیس: " . $e->getMessage() . "\n";
}
?>

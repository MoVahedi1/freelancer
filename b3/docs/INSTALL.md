# راهنمای نصب و راه‌اندازی پلتفرم فریلنسری

## پیش‌نیازها

### نرم‌افزارهای مورد نیاز:
- **PHP 7.4 یا بالاتر**
- **MySQL 5.7 یا بالاتر**
- **Apache/Nginx** (اختیاری - می‌توان از سرور داخلی PHP استفاده کرد)
- **مرورگر وب** (Chrome, Firefox, Safari, Edge)

### بررسی نصب PHP:
```bash
php --version
```

### بررسی نصب MySQL:
```bash
mysql --version
```

## مراحل نصب

### 1. راه‌اندازی دیتابیس

#### الف) ایجاد دیتابیس:
```sql
CREATE DATABASE b3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### ب) اجرای فایل schema.sql:
```bash
mysql -u root -p b3 < database/schema.sql
```

یا از طریق phpMyAdmin:
1. وارد phpMyAdmin شوید
2. دیتابیس `b3` را انتخاب کنید
3. فایل `database/schema.sql` را import کنید

### 2. تنظیم اتصال دیتابیس

فایل `backend/config/database.php` را ویرایش کنید:

```php
private $host = "localhost";        // آدرس سرور MySQL
private $db_name = "b3";           // نام دیتابیس
private $username = "root";         // نام کاربری MySQL
private $password = "your_password"; // رمز عبور MySQL
```

### 3. راه‌اندازی سرور

#### روش 1: سرور داخلی PHP (توصیه شده برای توسعه)
```bash
# در پوشه اصلی پروژه
php -S localhost:8000
```

#### روش 2: Apache
1. فایل‌های پروژه را در پوشه `htdocs` کپی کنید
2. آدرس `http://localhost/b3` را باز کنید

#### روش 3: Nginx
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/b3;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## تست سیستم

### 1. تست اتصال دیتابیس
فایل `test_api.html` را در مرورگر باز کنید:
```
http://localhost:8000/test_api.html
```

### 2. تست صفحات HTML
```
http://localhost:8000/pages/index.html
```

### 3. تست API ها
```bash
# تست ثبت‌نام
curl -X POST http://localhost:8000/backend/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456","first_name":"علی","last_name":"احمدی","user_type":"freelancer"}'

# تست ورود
curl -X POST http://localhost:8000/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'
```

## جریان کاربری کامل

### برای کارجو (Freelancer):

1. **ثبت‌نام:**
   - به `pages/register.html` بروید
   - نوع کاربر را "کارجو" انتخاب کنید
   - اطلاعات را تکمیل کنید
   - ثبت‌نام کنید

2. **ورود:**
   - به `pages/login.html` بروید
   - با ایمیل و رمز عبور وارد شوید
   - به صفحه مهارت‌ها هدایت می‌شوید

3. **ثبت مهارت‌ها:**
   - در `pages/freelancer-skills.html`
   - مهارت‌های خود را اضافه کنید
   - کلاس و زیرکلاس شغلی انتخاب کنید
   - سطح تسلط را تعیین کنید

4. **مشاهده آگهی‌ها:**
   - به `pages/jobs.html` بروید
   - آگهی‌های موجود را مشاهده کنید

5. **مدیریت حساب:**
   - به `pages/dashboard.html` بروید
   - مهارت‌ها و اطلاعات خود را مدیریت کنید

### برای کارفرما (Employer):

1. **ثبت‌نام:**
   - به `pages/register.html` بروید
   - نوع کاربر را "کارفرما" انتخاب کنید
   - اطلاعات شرکت را وارد کنید
   - ثبت‌نام کنید

2. **ورود:**
   - به `pages/login.html` بروید
   - با ایمیل و رمز عبور وارد شوید
   - به صفحه ثبت آگهی هدایت می‌شوید

3. **ثبت آگهی:**
   - در `pages/post-job.html`
   - عنوان و توضیحات آگهی را وارد کنید
   - بودجه را تعیین کنید
   - مهارت‌های مورد نیاز را اضافه کنید

4. **مشاهده آگهی‌ها:**
   - به `pages/jobs.html` بروید
   - آگهی‌های موجود را مشاهده کنید

5. **مدیریت حساب:**
   - به `pages/dashboard.html` بروید
   - آگهی‌های خود را مدیریت کنید

## عیب‌یابی

### مشکل در اتصال دیتابیس:
```bash
# بررسی وضعیت MySQL
sudo systemctl status mysql

# راه‌اندازی مجدد MySQL
sudo systemctl restart mysql

# بررسی اتصال
mysql -u root -p
```

### مشکل در اجرای PHP:
```bash
# بررسی نصب PHP
php --version

# بررسی ماژول‌های PHP
php -m | grep pdo
php -m | grep mysql
```

### مشکل در CORS:
فایل `backend/.htaccess` را بررسی کنید:
```apache
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
```

### مشکل در احراز هویت:
1. localStorage را پاک کنید
2. مجدداً وارد شوید
3. توکن JWT را بررسی کنید

### مشکل در نمایش داده‌ها:
1. کنسول مرورگر را بررسی کنید
2. Network tab را چک کنید
3. خطاهای PHP را بررسی کنید

## تنظیمات پیشرفته

### تنظیمات امنیتی:

1. **تغییر رمز عبور دیتابیس:**
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

2. **ایجاد کاربر جدید برای دیتابیس:**
```sql
CREATE USER 'freelance_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON b3.* TO 'freelance_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **تنظیمات PHP برای امنیت:**
```ini
; در php.ini
display_errors = Off
log_errors = On
error_log = /path/to/error.log
max_execution_time = 30
memory_limit = 128M
```

### تنظیمات بهینه‌سازی:

1. **فعال‌سازی کش MySQL:**
```sql
SET GLOBAL query_cache_size = 67108864;
SET GLOBAL query_cache_type = 1;
```

2. **ایجاد ایندکس‌های اضافی:**
```sql
CREATE INDEX idx_jobs_created_at ON Jobs(created_at);
CREATE INDEX idx_users_email ON Users(email);
```

## پشتیبان‌گیری و بازیابی

### پشتیبان‌گیری از دیتابیس:
```bash
mysqldump -u root -p b3 > backup_$(date +%Y%m%d_%H%M%S).sql
```

### بازیابی دیتابیس:
```bash
mysql -u root -p b3 < backup_file.sql
```

## به‌روزرسانی

### به‌روزرسانی کد:
```bash
# پشتیبان‌گیری
mysqldump -u root -p b3 > backup.sql

# به‌روزرسانی فایل‌ها
git pull origin main

# اجرای مجدد schema.sql برای تغییرات جدید
mysql -u root -p b3 < database/schema.sql
```

## پشتیبانی

برای گزارش مشکلات یا درخواست ویژگی‌های جدید:
1. مشکل را مستند کنید
2. خطاها را کپی کنید
3. مراحل تولید مشکل را توضیح دهید
4. اطلاعات سیستم را ارائه دهید

## مجوز

این پروژه تحت مجوز MIT منتشر شده است. 
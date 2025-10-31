# راهنمای عیب‌یابی مشکل "خطا در ارتباط با سرور"

## مراحل تشخیص و حل مشکل:

### 1. بررسی سرور PHP

#### الف) اطمینان از اجرای سرور:
```bash
# بررسی وضعیت سرور
php -S localhost:8000

# یا در ترمینال جدید
curl http://localhost:8000/test_db_connection.php
```

#### ب) تست اتصال دیتابیس:
```bash
# باز کردن در مرورگر
http://localhost:8000/test_db_connection.php
```

### 2. بررسی تنظیمات دیتابیس

#### الف) بررسی فایل `backend/config/database.php`:
```php
private $host = "localhost";        // آدرس سرور MySQL
private $db_name = "b3";           // نام دیتابیس
private $username = "root";         // نام کاربری MySQL
private $password = "";             // رمز عبور MySQL
```

#### ب) تست اتصال MySQL:
```bash
# اتصال مستقیم به MySQL
mysql -u root -p

# بررسی وجود دیتابیس
SHOW DATABASES;

# انتخاب دیتابیس
USE b3;

# بررسی جداول
SHOW TABLES;
```

### 3. بررسی فایل‌های PHP

#### الف) بررسی خطاهای PHP:
```bash
# بررسی خطاهای PHP
php -l backend/api/auth/register.php
php -l backend/models/User.php
php -l backend/config/database.php
```

#### ب) تست فایل register.php:
```bash
# تست مستقیم
curl -X POST http://localhost:8000/backend/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456","first_name":"علی","last_name":"احمدی","user_type":"freelancer"}'
```

### 4. بررسی تنظیمات CORS

#### الف) بررسی فایل `.htaccess`:
```apache
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
```

#### ب) بررسی header های PHP:
```php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
```

### 5. بررسی کنسول مرورگر

#### الف) باز کردن Developer Tools:
- F12 یا Ctrl+Shift+I
- تب Network
- تب Console

#### ب) بررسی خطاها:
```javascript
// در کنسول مرورگر
fetch('/backend/api/auth/register.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'test@example.com',
    password: '123456',
    first_name: 'علی',
    last_name: 'احمدی',
    user_type: 'freelancer'
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### 6. مشکلات رایج و راه‌حل‌ها:

#### مشکل 1: خطای CORS
```
Access to fetch at 'http://localhost:8000/backend/api/auth/register.php' from origin 'http://localhost:3000' has been blocked by CORS policy
```

**راه‌حل:**
```apache
# در .htaccess
Header always set Access-Control-Allow-Origin "*"
```

#### مشکل 2: خطای اتصال دیتابیس
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```

**راه‌حل:**
```php
// در database.php
private $password = "your_actual_password";
```

#### مشکل 3: خطای جدول وجود ندارد
```
SQLSTATE[42S02]: Base table or view 'b3.Users' doesn't exist
```

**راه‌حل:**
```bash
# اجرای schema.sql
mysql -u root -p b3 < database/schema.sql
```

#### مشکل 4: خطای PDO
```
Fatal error: Uncaught PDOException
```

**راه‌حل:**
```bash
# نصب PDO MySQL
sudo apt-get install php-mysql
# یا
sudo yum install php-mysql
```

### 7. تست‌های تشخیصی:

#### الف) تست اتصال دیتابیس:
```bash
http://localhost:8000/test_db_connection.php
```

#### ب) تست API ثبت‌نام:
```bash
http://localhost:8000/debug_register.php
```

#### ج) تست کامل:
```bash
http://localhost:8000/test_connection.html
```

### 8. بررسی فایل‌های لاگ:

#### الف) لاگ‌های PHP:
```bash
# در Linux
tail -f /var/log/apache2/error.log

# در Windows
# بررسی فایل error.log در پوشه Apache
```

#### ب) لاگ‌های MySQL:
```bash
# در Linux
tail -f /var/log/mysql/error.log

# در Windows
# بررسی فایل error.log در پوشه MySQL
```

### 9. تنظیمات پیشرفته:

#### الف) فعال کردن error reporting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### ب) بررسی تنظیمات PHP:
```bash
php -i | grep -i mysql
php -i | grep -i pdo
```

#### ج) بررسی پورت‌ها:
```bash
# بررسی پورت 8000
netstat -an | grep 8000

# بررسی پورت MySQL
netstat -an | grep 3306
```

### 10. راه‌حل‌های سریع:

#### الف) راه‌اندازی مجدد:
```bash
# توقف سرور
Ctrl+C

# راه‌اندازی مجدد
php -S localhost:8000
```

#### ب) پاک کردن کش:
```bash
# پاک کردن کش مرورگر
Ctrl+Shift+R

# یا در Developer Tools
Network tab → Disable cache
```

#### ج) بررسی فایل‌ها:
```bash
# بررسی وجود فایل‌ها
ls -la backend/api/auth/register.php
ls -la backend/config/database.php
ls -la backend/models/User.php
```

### 11. تست نهایی:

پس از اعمال تغییرات، این تست‌ها را انجام دهید:

1. **تست اتصال دیتابیس:**
   ```
   http://localhost:8000/test_db_connection.php
   ```

2. **تست API ثبت‌نام:**
   ```
   http://localhost:8000/test_connection.html
   ```

3. **تست از طریق React:**
   ```
   http://localhost:3000
   ```

### نتیجه‌گیری:

اگر تمام مراحل بالا را انجام دادید و مشکل همچنان وجود دارد، لطفاً:

1. خطاهای دقیق را کپی کنید
2. خروجی فایل‌های تست را ارائه دهید
3. تنظیمات سیستم خود را ذکر کنید
4. نسخه PHP و MySQL را مشخص کنید 
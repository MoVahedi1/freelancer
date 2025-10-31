# راهنمای سریع حل مشکل "Failed to fetch"

## مشکل: خطای "Failed to fetch" در ساعت 7:36:53 PM

### علل احتمالی:

1. **سرور PHP اجرا نشده است**
2. **پورت 8000 در دسترس نیست**
3. **مشکل در تنظیمات CORS**
4. **فایروال یا آنتی‌ویروس**

## مراحل حل سریع:

### 1. بررسی سرور PHP

```bash
# در ترمینال، در پوشه پروژه
php -S localhost:8000
```

**خروجی صحیح:**
```
PHP 8.x.x Development Server (http://localhost:8000) started
```

### 2. تست سرور

```bash
# در مرورگر باز کنید
http://localhost:8000/check_server.php
```

**خروجی صحیح:**
```json
{
  "status": "success",
  "message": "سرور PHP در حال کار است"
}
```

### 3. تست کامل

```bash
# در مرورگر باز کنید
http://localhost:8000/simple_test.html
```

### 4. بررسی پورت‌ها

```bash
# در Windows
netstat -an | findstr :8000

# در Linux/Mac
netstat -an | grep :8000
```

### 5. بررسی فایروال

**Windows:**
- Windows Defender → Allow an app through firewall
- اضافه کردن PHP به لیست مجاز

**Linux:**
```bash
sudo ufw allow 8000
```

### 6. تست با curl

```bash
# تست سرور
curl http://localhost:8000/check_server.php

# تست API
curl -X POST http://localhost:8000/backend/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456","first_name":"علی","last_name":"احمدی","user_type":"freelancer"}'
```

## مشکلات رایج:

### مشکل 1: پورت 8000 در حال استفاده
```bash
# توقف پروسه‌های PHP
taskkill /f /im php.exe  # Windows
pkill php                 # Linux/Mac
```

### مشکل 2: خطای CORS
```javascript
// در کنسول مرورگر
fetch('http://localhost:8000/check_server.php')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

### مشکل 3: فایروال
- غیرفعال کردن موقت فایروال
- اضافه کردن localhost به لیست مجاز

## تست‌های تشخیصی:

### تست 1: سرور ساده
```bash
# فایل test.php ایجاد کنید
<?php echo "Hello World"; ?>

# اجرا
php -S localhost:8000
# باز کردن http://localhost:8000/test.php
```

### تست 2: اتصال شبکه
```bash
# تست ping
ping localhost

# تست telnet
telnet localhost 8000
```

### تست 3: بررسی فایل‌ها
```bash
# بررسی وجود فایل‌ها
ls -la backend/api/auth/register.php
ls -la backend/config/database.php
```

## راه‌حل‌های جایگزین:

### 1. تغییر پورت
```bash
# اجرای سرور روی پورت دیگر
php -S localhost:8080
```

### 2. استفاده از Apache/XAMPP
```bash
# کپی فایل‌ها به htdocs
# اجرای Apache
# دسترسی از طریق http://localhost/b3/
```

### 3. استفاده از Docker
```bash
# اجرای با Docker
docker-compose up -d
```

## بررسی نهایی:

پس از اعمال تغییرات:

1. **سرور PHP را راه‌اندازی کنید:**
   ```bash
   php -S localhost:8000
   ```

2. **تست کنید:**
   ```
   http://localhost:8000/simple_test.html
   ```

3. **کنسول مرورگر را بررسی کنید:**
   - F12 → Console
   - بررسی خطاها

4. **شبکه را بررسی کنید:**
   - F12 → Network
   - بررسی درخواست‌ها

## نتیجه‌گیری:

اگر مشکل همچنان وجود دارد:

1. **خطاهای دقیق را کپی کنید**
2. **خروجی دستورات را ارائه دهید**
3. **سیستم عامل و نسخه PHP را ذکر کنید**
4. **تنظیمات فایروال را بررسی کنید**

**نکته مهم:** اطمینان حاصل کنید که سرور PHP در حال اجرا است و پورت 8000 آزاد است. 
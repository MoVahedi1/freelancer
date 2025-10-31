# راهنمای Docker برای پلتفرم فریلنسری

## پیش‌نیازها

- **Docker** (نسخه 20.10 یا بالاتر)
- **Docker Compose** (نسخه 2.0 یا بالاتر)

### بررسی نصب Docker:
```bash
docker --version
docker-compose --version
```

## راه‌اندازی با Docker

### 1. راه‌اندازی کامل پروژه

```bash
# راه‌اندازی تمام سرویس‌ها
docker-compose up -d

# مشاهده لاگ‌ها
docker-compose logs -f

# توقف سرویس‌ها
docker-compose down
```

### 2. دسترسی به سرویس‌ها

- **پلتفرم فریلنسری**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080
  - نام کاربری: `freelance_user`
  - رمز عبور: `freelance123`

### 3. ساخت مجدد در صورت تغییرات

```bash
# ساخت مجدد image ها
docker-compose build

# راه‌اندازی مجدد
docker-compose up -d
```

## ساختار Docker

### سرویس‌های موجود:

1. **mysql**: دیتابیس MySQL 8.0
   - پورت: 3306
   - دیتابیس: b3
   - کاربر: freelance_user
   - رمز عبور: freelance123

2. **app**: سرور PHP با Apache
   - پورت: 8000
   - PHP 8.1
   - Apache با mod_rewrite

3. **phpmyadmin**: مدیریت دیتابیس
   - پورت: 8080
   - رابط گرافیکی برای MySQL

## مدیریت داده‌ها

### پشتیبان‌گیری از دیتابیس:
```bash
# پشتیبان‌گیری
docker exec freelance_mysql mysqldump -u freelance_user -pfreelance123 b3 > backup.sql

# بازیابی
docker exec -i freelance_mysql mysql -u freelance_user -pfreelance123 b3 < backup.sql
```

### مشاهده لاگ‌ها:
```bash
# لاگ‌های MySQL
docker-compose logs mysql

# لاگ‌های PHP
docker-compose logs app

# لاگ‌های phpMyAdmin
docker-compose logs phpmyadmin
```

## تنظیمات پیشرفته

### تغییر رمز عبور دیتابیس:

1. فایل `docker-compose.yml` را ویرایش کنید:
```yaml
environment:
  MYSQL_ROOT_PASSWORD: new_password
  MYSQL_PASSWORD: new_password
```

2. سرویس‌ها را مجدداً راه‌اندازی کنید:
```bash
docker-compose down
docker-compose up -d
```

### اضافه کردن سرویس Redis (اختیاری):

```yaml
# در docker-compose.yml اضافه کنید
redis:
  image: redis:7-alpine
  container_name: freelance_redis
  restart: unless-stopped
  ports:
    - "6379:6379"
  networks:
    - freelance_network
```

### تنظیمات محیطی:

فایل `.env` ایجاد کنید:
```env
MYSQL_ROOT_PASSWORD=your_secure_password
MYSQL_PASSWORD=your_secure_password
MYSQL_USER=freelance_user
MYSQL_DATABASE=b3
```

## عیب‌یابی

### مشکل در اتصال به دیتابیس:
```bash
# بررسی وضعیت MySQL
docker-compose ps mysql

# اتصال به MySQL
docker exec -it freelance_mysql mysql -u freelance_user -p

# بررسی لاگ‌ها
docker-compose logs mysql
```

### مشکل در سرور PHP:
```bash
# بررسی وضعیت PHP
docker-compose ps app

# اتصال به container
docker exec -it freelance_app bash

# بررسی لاگ‌ها
docker-compose logs app
```

### مشکل در پورت‌ها:
```bash
# بررسی پورت‌های استفاده شده
docker-compose ps

# آزاد کردن پورت‌ها
docker-compose down
```

## بهینه‌سازی

### تنظیمات MySQL:
```yaml
# در docker-compose.yml
mysql:
  command: --default-authentication-plugin=mysql_native_password
  environment:
    MYSQL_ROOT_PASSWORD: freelance123
    MYSQL_DATABASE: b3
    MYSQL_USER: freelance_user
    MYSQL_PASSWORD: freelance123
  volumes:
    - mysql_data:/var/lib/mysql
    - ./mysql.cnf:/etc/mysql/conf.d/mysql.cnf
```

### تنظیمات PHP:
```dockerfile
# در Dockerfile
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution-time.ini
RUN echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/upload.ini
RUN echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/upload.ini
```

## تولید (Production)

### تنظیمات امنیتی:

1. **تغییر رمزهای عبور پیش‌فرض**
2. **استفاده از SSL/TLS**
3. **محدود کردن دسترسی‌ها**

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  mysql:
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - internal_network

  app:
    build: .
    environment:
      - MYSQL_HOST=mysql
    networks:
      - internal_network
      - external_network

networks:
  internal_network:
    internal: true
  external_network:
    driver: bridge
```

### راه‌اندازی در تولید:
```bash
# استفاده از فایل تولید
docker-compose -f docker-compose.prod.yml up -d

# با متغیرهای محیطی
MYSQL_ROOT_PASSWORD=secure_password docker-compose -f docker-compose.prod.yml up -d
```

## پاک‌سازی

### حذف کامل:
```bash
# توقف و حذف container ها
docker-compose down

# حذف volume ها
docker-compose down -v

# حذف image ها
docker-compose down --rmi all
```

### پاک‌سازی Docker:
```bash
# حذف container های متوقف شده
docker container prune

# حذف image های استفاده نشده
docker image prune

# حذف volume های استفاده نشده
docker volume prune

# پاک‌سازی کامل
docker system prune -a
```

## نکات مهم

1. **داده‌ها**: volume `mysql_data` داده‌های دیتابیس را حفظ می‌کند
2. **پورت‌ها**: اطمینان حاصل کنید که پورت‌های 8000 و 8080 آزاد هستند
3. **حافظه**: حداقل 2GB RAM برای اجرای راحت توصیه می‌شود
4. **فضا**: حداقل 1GB فضای خالی برای image ها و volume ها

## پشتیبانی

برای مشکلات Docker:
1. لاگ‌ها را بررسی کنید: `docker-compose logs`
2. وضعیت container ها را چک کنید: `docker-compose ps`
3. به container ها متصل شوید: `docker exec -it container_name bash` 
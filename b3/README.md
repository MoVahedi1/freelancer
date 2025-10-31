# پروژه فریلنسینگ پلتفرم

## ساختار پروژه

```
b3/
├── backend/           # بک‌اند PHP
│   ├── api/          # API endpoints
│   ├── config/       # تنظیمات
│   └── models/       # مدل‌ها
├── frontend/          # فرانت‌اند React
│   ├── src/          # کدهای React
│   └── public/       # فایل‌های عمومی
├── database/          # دیتابیس
│   └── schema.sql    # ساختار دیتابیس
├── tools/            # ابزارها و تست‌ها
│   ├── test_*.php    # فایل‌های تست
│   ├── debug_*.php   # فایل‌های دیباگ
│   └── setup_*.php   # فایل‌های نصب
├── docs/             # مستندات
│   ├── README.md     # راهنمای اصلی
│   ├── INSTALL.md    # راهنمای نصب
│   └── *.md          # سایر مستندات
└── pages/            # صفحات HTML
```

## نصب و راه‌اندازی

### پیش‌نیازها
- PHP 7.4+
- MySQL 5.7+
- Node.js 14+
- Apache/Nginx

### مراحل نصب

1. **نصب دیتابیس:**
   ```bash
   php tools/setup_database.php
   ```

2. **تست اتصال:**
   ```bash
   php tools/test_project_connection.php
   ```

3. **اجرای فرانت‌اند:**
   ```bash
   cd frontend
   npm install
   npm start
   ```

4. **تست کامل:**
   ```bash
   # باز کردن در مرورگر
   http://project.php/b3.8/b3/tools/test_react_connection.html
   ```

## تست‌ها

### تست اتصال
- `tools/test_project_connection.php` - تست اتصال project.php
- `tools/test_react_connection.html` - تست React API
- `tools/final_test.html` - تست کامل

### دیباگ
- `tools/debug_register_json.php` - دیباگ JSON ثبت‌نام
- `tools/debug_json.php` - دیباگ JSON عمومی

## مستندات

- `docs/README.md` - راهنمای اصلی
- `docs/INSTALL.md` - راهنمای نصب
- `docs/TROUBLESHOOTING.md` - عیب‌یابی
- `docs/QUICK_FIX.md` - راه‌حل‌های سریع

## پشتیبانی

برای گزارش مشکلات یا سوالات، لطفاً از فایل‌های تست استفاده کنید.

---

**طراحی و توسعه: محمد واحدی** 
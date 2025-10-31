# راهنمای اتصال فرانت‌اند و بک‌اند

## اتصال کامل فرانت‌اند و بک‌اند

### ساختار اتصال:

```
Frontend (React) ←→ Backend (PHP) ←→ Database (MySQL)
```

### فایل‌های کلیدی اتصال:

#### 1. **`frontend/src/utils/api.js`** - تنظیمات axios
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// اضافه کردن توکن به درخواست‌ها
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// مدیریت خطاها
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

#### 2. **`backend/.htaccess`** - تنظیمات CORS
```apache
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
```

### جریان داده‌ها:

#### 1. **ثبت‌نام کاربر:**
```
Frontend (Register.js) 
  ↓ POST /backend/api/auth/register.php
Backend (register.php)
  ↓ INSERT INTO Users
Database (MySQL)
  ↓ Response with user_id
Frontend (Auto Login)
  ↓ POST /backend/api/auth/login.php
Backend (login.php)
  ↓ Verify credentials
  ↓ Generate JWT Token
Frontend (Store Token)
  ↓ localStorage.setItem('token', token)
  ↓ Navigate to appropriate page
```

#### 2. **ورود کاربر:**
```
Frontend (Login.js)
  ↓ POST /backend/api/auth/login.php
Backend (login.php)
  ↓ Verify email/password
  ↓ Generate JWT Token
Frontend (AuthContext)
  ↓ Store token and user data
  ↓ Navigate to dashboard
```

#### 3. **ثبت مهارت‌های کارجو:**
```
Frontend (FreelancerSkills.js)
  ↓ GET /backend/api/freelancer/job-classes.php
Backend (job-classes.php)
  ↓ SELECT FROM JobClasses
Database
  ↓ Return job classes
Frontend (Display classes)
  ↓ User selects skills
  ↓ POST /backend/api/freelancer/skills.php
Backend (skills.php)
  ↓ Verify JWT Token
  ↓ INSERT INTO FreelancerSkills
Database
  ↓ Success response
Frontend (Show success message)
```

#### 4. **ثبت آگهی کارفرما:**
```
Frontend (PostJob.js)
  ↓ GET /backend/api/freelancer/job-classes.php
Backend (job-classes.php)
  ↓ SELECT FROM JobClasses
Database
  ↓ Return job classes
Frontend (Display classes)
  ↓ User fills job form
  ↓ POST /backend/api/employer/jobs.php
Backend (jobs.php)
  ↓ Verify JWT Token
  ↓ INSERT INTO Jobs
  ↓ INSERT INTO JobRequiredSkills
Database
  ↓ Success response
Frontend (Show success message)
```

#### 5. **نمایش آگهی‌ها:**
```
Frontend (JobList.js)
  ↓ GET /backend/api/jobs.php?page=1&limit=10
Backend (jobs.php)
  ↓ SELECT FROM Jobs JOIN Users
  ↓ SELECT FROM JobRequiredSkills
Database
  ↓ Return jobs with skills
Frontend (Display jobs)
  ↓ Pagination (Load More)
```

### ویژگی‌های اتصال:

#### ✅ **احراز هویت خودکار:**
- توکن JWT در localStorage ذخیره می‌شود
- هر درخواست به صورت خودکار توکن را ارسال می‌کند
- در صورت انقضای توکن، کاربر به صفحه ورود هدایت می‌شود

#### ✅ **مدیریت خطا:**
- خطاهای شبکه نمایش داده می‌شوند
- خطاهای سرور با پیام مناسب نمایش داده می‌شوند
- خطاهای احراز هویت مدیریت می‌شوند

#### ✅ **بارگذاری و وضعیت:**
- نمایش وضعیت بارگذاری در فرم‌ها
- غیرفعال کردن دکمه‌ها در حین ارسال
- نمایش پیام‌های موفقیت/خطا

#### ✅ **مسیریابی خودکار:**
- پس از ثبت‌نام، ورود خودکار انجام می‌شود
- بر اساس نوع کاربر، مسیر مناسب انتخاب می‌شود
- محافظت از مسیرها بر اساس احراز هویت

### تست اتصال:

#### 1. **فایل تست:**
```bash
# باز کردن فایل تست
http://localhost:8000/test_connection.html
```

#### 2. **تست‌های موجود:**
- ✅ تست ثبت‌نام
- ✅ تست ورود
- ✅ تست دریافت کلاس‌های شغلی
- ✅ تست دریافت آگهی‌ها
- ✅ فرم تست ثبت‌نام

#### 3. **تست با curl:**
```bash
# تست ثبت‌نام
curl -X POST http://localhost:8000/backend/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456","first_name":"علی","last_name":"احمدی","user_type":"freelancer"}'

# تست ورود
curl -X POST http://localhost:8000/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'

# تست دریافت آگهی‌ها
curl http://localhost:8000/backend/api/jobs.php?page=1&limit=5
```

### تنظیمات محیطی:

#### 1. **متغیرهای محیطی React:**
```bash
# .env
REACT_APP_API_URL=http://localhost:8000
```

#### 2. **تنظیمات CORS:**
```php
// در فایل‌های PHP
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

#### 3. **تنظیمات Apache:**
```apache
# .htaccess
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
```

### عیب‌یابی:

#### 1. **مشکل CORS:**
```javascript
// بررسی در کنسول مرورگر
// خطای CORS در Network tab
```

**راه‌حل:**
- بررسی تنظیمات `.htaccess`
- اطمینان از فعال بودن mod_headers
- بررسی header های ارسالی

#### 2. **مشکل اتصال به سرور:**
```javascript
// خطای Network Error
```

**راه‌حل:**
- بررسی آدرس سرور در `api.js`
- اطمینان از اجرای سرور PHP
- بررسی پورت‌ها

#### 3. **مشکل احراز هویت:**
```javascript
// خطای 401 Unauthorized
```

**راه‌حل:**
- بررسی ذخیره توکن در localStorage
- بررسی ارسال توکن در header
- بررسی اعتبار توکن در سرور

#### 4. **مشکل در دیتابیس:**
```php
// خطای PDO Exception
```

**راه‌حل:**
- بررسی اتصال دیتابیس
- بررسی تنظیمات `database.php`
- بررسی وجود جداول

### بهینه‌سازی:

#### 1. **کش کردن داده‌ها:**
```javascript
// کش کردن کلاس‌های شغلی
const [jobClasses, setJobClasses] = useState([]);
useEffect(() => {
  if (jobClasses.length === 0) {
    fetchJobClasses();
  }
}, []);
```

#### 2. **بارگذاری تدریجی:**
```javascript
// صفحه‌بندی آگهی‌ها
const [page, setPage] = useState(1);
const [hasMore, setHasMore] = useState(true);
```

#### 3. **مدیریت وضعیت:**
```javascript
// وضعیت بارگذاری
const [loading, setLoading] = useState(false);
const [error, setError] = useState('');
const [success, setSuccess] = useState('');
```

### امنیت:

#### 1. **احراز هویت:**
- استفاده از JWT Token
- بررسی اعتبار توکن در هر درخواست
- حذف خودکار توکن نامعتبر

#### 2. **اعتبارسنجی داده‌ها:**
- اعتبارسنجی در فرانت‌اند
- اعتبارسنجی در بک‌اند
- استفاده از Prepared Statements

#### 3. **محافظت از مسیرها:**
- بررسی احراز هویت
- بررسی نوع کاربر
- هدایت به صفحه ورود

### نتیجه‌گیری:

اتصال فرانت‌اند و بک‌اند به صورت کامل پیاده‌سازی شده است. تمام عملیات‌ها از ثبت‌نام تا نمایش آگهی‌ها به درستی کار می‌کنند و داده‌ها در دیتابیس ذخیره می‌شوند.

**نکات مهم:**
1. ✅ تمام API ها تست شده‌اند
2. ✅ احراز هویت کامل پیاده‌سازی شده
3. ✅ مدیریت خطاها انجام شده
4. ✅ امنیت رعایت شده
5. ✅ بهینه‌سازی انجام شده 
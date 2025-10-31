# 📋 توضیح مفصل پیاده‌سازی بخش‌های مختلف پروژه کارپاچینو

## 🏗️ 1. معماری کلی پروژه

### ساختار MVC (Model-View-Controller)
```
📁 Project Structure
├── 📁 pages/           # Frontend (View Layer)
├── 📁 backend/         # Backend Logic (Controller + Model)
│   ├── 📁 api/         # API Endpoints
│   ├── 📁 config/      # Database Configuration
│   └── 📁 includes/    # Shared Functions
└── 📁 assets/          # Static Resources
```

## 🎨 2. Frontend Implementation (صفحات کاربری)

### 2.1 صفحه اصلی (index.html)

#### پیاده‌سازی تشخیص وضعیت کاربر:
```javascript
function checkUserStatus() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    if (token && user) {
        const userData = JSON.parse(user);
        // مخفی کردن دکمه‌های ثبت‌نام برای کاربران وارد شده
        if (guestHeroButtons) guestHeroButtons.classList.add('hidden');
        if (registerCtaBtn) registerCtaBtn.classList.add('hidden');
        
        // نمایش دکمه‌های مخصوص کارفرمایان
        if (userData.user_type === 'employer') {
            postJobSection.classList.remove('hidden');
            postJobHeroBtn.classList.remove('hidden');
            postJobCtaBtn.classList.remove('hidden');
        }
    } else {
        // نمایش دکمه‌های ثبت‌نام برای مهمان‌ها
        if (guestHeroButtons) guestHeroButtons.classList.remove('hidden');
        if (registerCtaBtn) registerCtaBtn.classList.remove('hidden');
    }
}
```

#### پیاده‌سازی آمار زنده:
```javascript
async function loadStats() {
    try {
        const response = await fetch('../backend/api/public/stats.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('freelancersCount').textContent = 
                `+${data.freelancers} فریلنسر فعال`;
            document.getElementById('completedProjectsCount').textContent = 
                `+${data.completed_projects} پروژه موفق`;
            document.getElementById('satisfactionRate').textContent = 
                `${data.satisfaction_rate}% رضایت کاربران`;
        }
    } catch (error) {
        setDefaultStats(); // استفاده از آمار پیش‌فرض در صورت خطا
    }
}
```

### 2.2 داشبورد مدیریت (admin-dashboard.html)

#### پیاده‌سازی سیستم جستجوی پیشرفته:

**الف) جستجوی کاربران:**
```javascript
// admin-dashboard-search-enhanced.js
class UserSearchSystem {
    constructor() {
        this.users = [];
        this.filteredUsers = [];
        this.currentPage = 1;
        this.itemsPerPage = 12;
        this.searchTimeout = null;
    }

    // جستجوی real-time با debouncing
    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 300); // تاخیر 300 میلی‌ثانیه
    }

    performSearch(query) {
        if (!query.trim()) {
            this.filteredUsers = [...this.users];
        } else {
            this.filteredUsers = this.users.filter(user => 
                user.first_name?.toLowerCase().includes(query.toLowerCase()) ||
                user.last_name?.toLowerCase().includes(query.toLowerCase()) ||
                user.email?.toLowerCase().includes(query.toLowerCase()) ||
                user.company_name?.toLowerCase().includes(query.toLowerCase())
            );
        }
        this.renderUsers();
    }
}
```

**ب) فیلترهای سریع:**
```javascript
// فیلتر بر اساس نوع کاربر
filterByUserType(userType) {
    if (userType === 'all') {
        this.filteredUsers = [...this.users];
    } else if (userType === 'recent') {
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        this.filteredUsers = this.users.filter(user => 
            new Date(user.created_at) >= sevenDaysAgo
        );
    } else {
        this.filteredUsers = this.users.filter(user => 
            user.user_type === userType
        );
    }
    this.currentPage = 1;
    this.renderUsers();
}
```

## 🔧 3. Backend Implementation (سرور)

### 3.1 API Authentication System

#### پیاده‌سازی JWT Authentication:
```php
// backend/includes/auth.php
function validateToken($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return [
            'valid' => true,
            'user_id' => $decoded->user_id,
            'user_type' => $decoded->user_type,
            'email' => $decoded->email
        ];
    } catch (Exception $e) {
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}

function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token required']);
        exit;
    }
    
    $token = $matches[1];
    $validation = validateToken($token);
    
    if (!$validation['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    return $validation;
}
```

### 3.2 Database Operations

#### پیاده‌سازی عملیات CRUD:
```php
// backend/api/admin/admin-data.php
function getAllUsers($db, $filters = []) {
    try {
        $query = "SELECT user_id, email, first_name, last_name, user_type, 
                         company_name, phone, created_at, updated_at 
                  FROM Users WHERE 1=1";
        
        $params = [];
        
        // اعمال فیلترها
        if (!empty($filters['user_type'])) {
            $query .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (first_name LIKE :search OR last_name LIKE :search 
                           OR email LIKE :search OR company_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطا در دریافت کاربران: ' . $e->getMessage()
        ];
    }
}
```

#### پیاده‌سازی Soft Delete:
```php
function deleteJob($db, $jobId) {
    try {
        // حذف نرم: فقط فیلدهای اصلی null می‌شوند
        $query = "UPDATE Jobs SET 
                    title = NULL,
                    description = NULL,
                    budget_min = NULL,
                    budget_max = NULL,
                    budget_type = NULL,
                    status = 'deleted',
                    updated_at = NOW()
                  WHERE job_id = :job_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "آگهی با موفقیت حذف شد"];
        }
    } catch (Exception $e) {
        return ["success" => false, "message" => "خطای سیستم: " . $e->getMessage()];
    }
}
```

## 🎯 4. User Experience Features

### 4.1 Real-time Search with Debouncing
```javascript
// تکنیک debouncing برای بهینه‌سازی جستجو
handleSearchInput(event) {
    const query = event.target.value;
    
    // لغو درخواست قبلی
    clearTimeout(this.searchTimeout);
    
    // تنظیم تاخیر 300 میلی‌ثانیه
    this.searchTimeout = setTimeout(() => {
        this.performSearch(query);
    }, 300);
}
```

### 4.2 Responsive Design Implementation
```css
/* طراحی ریسپانسیو با CSS Grid */
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

@media (max-width: 768px) {
    .user-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
```

## 🔒 5. Security Implementation

### 5.1 Input Sanitization
```php
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
```

### 5.2 SQL Injection Prevention
```php
// استفاده از Prepared Statements
$query = "SELECT * FROM Users WHERE email = :email AND password = :password";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
$stmt->execute();
```

## ⚡ 6. Performance Optimizations

### 6.1 Database Indexing
```sql
-- ایندکس‌گذاری برای بهبود عملکرد
CREATE INDEX idx_users_type ON Users(user_type);
CREATE INDEX idx_users_created ON Users(created_at);
CREATE INDEX idx_jobs_status ON Jobs(status);
CREATE INDEX idx_jobs_user ON Jobs(user_id);
```

### 6.2 Frontend Caching
```javascript
// کش کردن داده‌ها در localStorage
class DataCache {
    constructor(ttl = 300000) { // 5 دقیقه
        this.ttl = ttl;
    }
    
    set(key, data) {
        const item = {
            data: data,
            timestamp: Date.now()
        };
        localStorage.setItem(key, JSON.stringify(item));
    }
    
    get(key) {
        const item = localStorage.getItem(key);
        if (!item) return null;
        
        const parsed = JSON.parse(item);
        if (Date.now() - parsed.timestamp > this.ttl) {
            localStorage.removeItem(key);
            return null;
        }
        
        return parsed.data;
    }
}
```

## 📱 7. Mobile-First Design

### 7.1 Responsive Navigation
```css
/* منوی همبرگری برای موبایل */
.mobile-menu {
    display: none;
}

@media (max-width: 768px) {
    .desktop-menu {
        display: none;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .mobile-menu-toggle {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }
}
```

## 🎨 8. UI/UX Design Patterns

### 8.1 Card-Based Layout
```css
.card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}
```

## 🔄 9. Data Flow Architecture

### Frontend → Backend Flow:
```
1. User Action (Click/Type)
   ↓
2. JavaScript Event Handler
   ↓
3. Data Validation & Sanitization
   ↓
4. API Request with JWT Token
   ↓
5. Backend Authentication
   ↓
6. Database Operation
   ↓
7. JSON Response
   ↓
8. Frontend Update & UI Refresh
```

## 📊 10. Analytics and Monitoring

### 10.1 Performance Monitoring
```javascript
// اندازه‌گیری عملکرد
class PerformanceMonitor {
    static measureLoadTime() {
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);
            
            // ارسال به سرور analytics
            this.sendMetric('page_load_time', loadTime);
        });
    }
    
    static measureAPIResponse(endpoint, startTime) {
        const endTime = performance.now();
        const duration = endTime - startTime;
        
        console.log(`API ${endpoint} responded in ${duration.toFixed(2)}ms`);
        this.sendMetric('api_response_time', duration, { endpoint });
    }
}
```

---

این پیاده‌سازی شامل تمام جنبه‌های فنی پروژه از Frontend تا Backend، امنیت، عملکرد و تجربه کاربری است. هر بخش با جزئیات کامل و کدهای نمونه توضیح داده شده تا درک عمیقی از معماری و پیاده‌سازی پروژه فراهم شود.

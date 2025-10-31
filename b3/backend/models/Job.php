<?php
require_once __DIR__ . '/../config/database.php';

class Job {
    private $conn;
    private $table_name = "Jobs";

    public $job_id;
    public $user_id;
    public $title;
    public $description;
    public $budget_type;
    public $budget_min;
    public $budget_max;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ایجاد آگهی جدید
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, title, description, budget_type, budget_min, budget_max) 
                      VALUES (:user_id, :title, :description, :budget_type, :budget_min, :budget_max)";

            $stmt = $this->conn->prepare($query);

            // پاکسازی و اعتبارسنجی داده‌ها برای ذخیره در جدول Jobs
            $this->user_id = (int)$this->user_id; // آیدی کارفرما
            $this->title = htmlspecialchars(strip_tags(trim($this->title))); // عنوان آگهی
            $this->description = htmlspecialchars(strip_tags(trim($this->description))); // توضیحات آگهی
            $this->budget_type = htmlspecialchars(strip_tags($this->budget_type)); // نوع حقوق (توافقی یا بازه‌ای)
            
            // تنظیم بودجه بر اساس نوع
            if ($this->budget_type === 'range') {
                // برای نوع بازه‌ای: مینیمم و ماکسیمم
                $this->budget_min = $this->budget_min ? (float)$this->budget_min : null;
                $this->budget_max = $this->budget_max ? (float)$this->budget_max : null;
                
                // اعتبارسنجی بودجه
                if ($this->budget_min && $this->budget_max && $this->budget_min >= $this->budget_max) {
                    error_log("Invalid budget range: min={$this->budget_min}, max={$this->budget_max}");
                    return false;
                }
            } else {
                // برای نوع توافقی: null
                $this->budget_min = null;
                $this->budget_max = null;
            }

            // باند کردن پارامترها
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":budget_type", $this->budget_type);
            $stmt->bindParam(":budget_min", $this->budget_min);
            $stmt->bindParam(":budget_max", $this->budget_max);

            if($stmt->execute()) {
                $job_id = $this->conn->lastInsertId();
                error_log("Job created successfully with ID: {$job_id}, User: {$this->user_id}, Title: {$this->title}, Budget Type: {$this->budget_type}");
                return $job_id;
            }
            
            error_log("Failed to execute job creation query");
            return false;
        } catch (PDOException $e) {
            error_log("Database error in Job::create: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("General error in Job::create: " . $e->getMessage());
            return false;
        }
    }

    // دریافت تمام آگهی‌ها
    public function getAll($limit = 50, $offset = 0) {
        try {
            $query = "SELECT j.job_id, j.user_id, j.title, j.description, j.budget_type, 
                             j.budget_min, j.budget_max, j.created_at,
                             u.first_name, u.last_name, u.company_name
                      FROM " . $this->table_name . " j
                      JOIN Users u ON j.user_id = u.user_id
                      ORDER BY j.created_at DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in Job::getAll: " . $e->getMessage());
            return false;
        }
    }

    // دریافت آگهی بر اساس ID
    public function getById($job_id) {
        try {
            $query = "SELECT j.job_id, j.user_id, j.title, j.description, j.budget_type, 
                             j.budget_min, j.budget_max, j.created_at,
                             u.first_name, u.last_name, u.company_name
                      FROM " . $this->table_name . " j
                      JOIN Users u ON j.user_id = u.user_id
                      WHERE j.job_id = :job_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in Job::getById: " . $e->getMessage());
            return false;
        }
    }

    // دریافت آگهی‌های یک کاربر
    public function getByUserId($user_id) {
        try {
            $query = "SELECT job_id, title, description, budget_type, budget_min, budget_max, created_at
                      FROM " . $this->table_name . " 
                      WHERE user_id = :user_id
                      ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in Job::getByUserId: " . $e->getMessage());
            return false;
        }
    }

    // بررسی وجود آگهی
    public function jobExists($job_id) {
        try {
            $query = "SELECT job_id FROM " . $this->table_name . " WHERE job_id = :job_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in Job::jobExists: " . $e->getMessage());
            return false;
        }
    }

    // حذف آگهی
    public function delete($job_id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE job_id = :job_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->bindParam(":user_id", $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in Job::delete: " . $e->getMessage());
            return false;
        }
    }

    // به‌روزرسانی آگهی
    public function update($job_id, $user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET title = :title, description = :description, budget_type = :budget_type, 
                          budget_min = :budget_min, budget_max = :budget_max
                      WHERE job_id = :job_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->budget_type = htmlspecialchars(strip_tags($this->budget_type));
            $this->budget_min = $this->budget_min ? htmlspecialchars(strip_tags($this->budget_min)) : null;
            $this->budget_max = $this->budget_max ? htmlspecialchars(strip_tags($this->budget_max)) : null;

            // باند کردن پارامترها
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":budget_type", $this->budget_type);
            $stmt->bindParam(":budget_min", $this->budget_min);
            $stmt->bindParam(":budget_max", $this->budget_max);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->bindParam(":user_id", $user_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in Job::update: " . $e->getMessage());
            return false;
        }
    }
}
?> 
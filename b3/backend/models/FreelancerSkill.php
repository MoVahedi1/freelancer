<?php
require_once __DIR__ . '/../config/database.php';

class FreelancerSkill {
    private $conn;
    private $table_name = "FreelancerSkills";

    public $skill_id;
    public $user_id;
    public $class_id;
    public $subclass_id;
    public $proficiency_level;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // شمارش مهارت‌های کاربر
    public function countByUserId($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // بررسی محدودیت 10 مهارت
    public function canAddSkill($user_id) {
        $current_count = $this->countByUserId($user_id);
        return $current_count < 10;
    }

    // ثبت مهارت جدید
    public function create() {
        // بررسی محدودیت 10 مهارت
        if (!$this->canAddSkill($this->user_id)) {
            throw new Exception("شما نمی‌توانید بیش از 10 مهارت داشته باشید.");
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, class_id, subclass_id, proficiency_level) 
                  VALUES (:user_id, :class_id, :subclass_id, :proficiency_level)";

        $stmt = $this->conn->prepare($query);

        // پاکسازی داده‌ها
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->class_id = htmlspecialchars(strip_tags($this->class_id));
        $this->subclass_id = $this->subclass_id ? htmlspecialchars(strip_tags($this->subclass_id)) : null;
        $this->proficiency_level = htmlspecialchars(strip_tags($this->proficiency_level));

        // باند کردن پارامترها
        $stmt->bindValue(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(":class_id", $this->class_id, PDO::PARAM_INT);
        $stmt->bindValue(":subclass_id", $this->subclass_id, PDO::PARAM_INT);
        $stmt->bindValue(":proficiency_level", $this->proficiency_level, PDO::PARAM_STR);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // ثبت چندین مهارت برای یک کاربر
    public function createMultiple($user_id, $skills) {
        // بررسی محدودیت 10 مهارت
        if (count($skills) > 10) {
            throw new Exception("شما نمی‌توانید بیش از 10 مهارت داشته باشید.");
        }

        $this->conn->beginTransaction();
        
        try {
            // حذف مهارت‌های قبلی کاربر
            $delete_query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
                    $delete_stmt = $this->conn->prepare($delete_query);
        $delete_stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $delete_stmt->execute();

            // ثبت مهارت‌های جدید
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, class_id, subclass_id, proficiency_level) 
                      VALUES (:user_id, :class_id, :subclass_id, :proficiency_level)";

            $stmt = $this->conn->prepare($query);

            foreach($skills as $skill) {
                $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindValue(":class_id", $skill['class_id'], PDO::PARAM_INT);
                $stmt->bindValue(":subclass_id", $skill['subclass_id'] ?? null, PDO::PARAM_INT);
                $stmt->bindValue(":proficiency_level", $skill['proficiency_level'], PDO::PARAM_STR);
                
                if(!$stmt->execute()) {
                    throw new Exception("خطا در ثبت مهارت: " . implode(", ", $stmt->errorInfo()));
                }
            }

            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollback();
            error_log("FreelancerSkill createMultiple error: " . $e->getMessage());
            throw $e;
        }
    }

    // به‌روزرسانی مهارت
    public function update($skill_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET class_id = :class_id, subclass_id = :subclass_id, proficiency_level = :proficiency_level 
                  WHERE skill_id = :skill_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // پاکسازی داده‌ها
        $this->class_id = htmlspecialchars(strip_tags($this->class_id));
        $this->subclass_id = $this->subclass_id ? htmlspecialchars(strip_tags($this->subclass_id)) : null;
        $this->proficiency_level = htmlspecialchars(strip_tags($this->proficiency_level));

        // باند کردن پارامترها
        $stmt->bindValue(":class_id", $this->class_id, PDO::PARAM_INT);
        $stmt->bindValue(":subclass_id", $this->subclass_id, PDO::PARAM_INT);
        $stmt->bindValue(":proficiency_level", $this->proficiency_level, PDO::PARAM_STR);
        $stmt->bindValue(":skill_id", $skill_id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // دریافت مهارت‌های یک کاربر
    public function getByUserId($user_id) {
        $query = "SELECT fs.skill_id, fs.class_id, fs.subclass_id, fs.proficiency_level,
                         jc.class_name, jsc.subclass_name
                  FROM " . $this->table_name . " fs
                  JOIN JobClasses jc ON fs.class_id = jc.class_id
                  LEFT JOIN JobSubClasses jsc ON fs.subclass_id = jsc.subclass_id
                  WHERE fs.user_id = :user_id
                  ORDER BY jc.class_name, jsc.subclass_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // دریافت یک مهارت خاص
    public function getById($skill_id, $user_id) {
        $query = "SELECT fs.skill_id, fs.class_id, fs.subclass_id, fs.proficiency_level,
                         jc.class_name, jsc.subclass_name
                  FROM " . $this->table_name . " fs
                  JOIN JobClasses jc ON fs.class_id = jc.class_id
                  LEFT JOIN JobSubClasses jsc ON fs.subclass_id = jsc.subclass_id
                  WHERE fs.skill_id = :skill_id AND fs.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":skill_id", $skill_id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // حذف مهارت
    public function delete($skill_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE skill_id = :skill_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":skill_id", $skill_id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // بررسی تکراری بودن مهارت
    public function isDuplicate($user_id, $class_id, $subclass_id = null, $exclude_skill_id = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND class_id = :class_id";
        
        if ($subclass_id) {
            $query .= " AND subclass_id = :subclass_id";
        } else {
            $query .= " AND subclass_id IS NULL";
        }
        
        if ($exclude_skill_id) {
            $query .= " AND skill_id != :exclude_skill_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":class_id", $class_id, PDO::PARAM_INT);
        
        if ($subclass_id) {
            $stmt->bindValue(":subclass_id", $subclass_id, PDO::PARAM_INT);
        }
        
        if ($exclude_skill_id) {
            $stmt->bindValue(":exclude_skill_id", $exclude_skill_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
?> 
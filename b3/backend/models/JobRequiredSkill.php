<?php
require_once __DIR__ . '/../config/database.php';

class JobRequiredSkill {
    private $conn;
    private $table_name = "JobRequiredSkills";

    public $job_skill_id;
    public $job_id;
    public $class_id;
    public $subclass_id;
    public $proficiency_level;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ثبت مهارت مورد نیاز برای آگهی
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (job_id, class_id, subclass_id, proficiency_level) 
                      VALUES (:job_id, :class_id, :subclass_id, :proficiency_level)";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->job_id = htmlspecialchars(strip_tags($this->job_id));
            $this->class_id = htmlspecialchars(strip_tags($this->class_id));
            $this->subclass_id = $this->subclass_id ? htmlspecialchars(strip_tags($this->subclass_id)) : null;
            $this->proficiency_level = htmlspecialchars(strip_tags($this->proficiency_level));

            // باند کردن پارامترها
            $stmt->bindParam(":job_id", $this->job_id);
            $stmt->bindParam(":class_id", $this->class_id);
            $stmt->bindParam(":subclass_id", $this->subclass_id);
            $stmt->bindParam(":proficiency_level", $this->proficiency_level);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::create: " . $e->getMessage());
            return false;
        }
    }

    // ثبت چندین مهارت برای یک آگهی
    public function createMultiple($job_id, $skills) {
        try {
            if (empty($skills) || !is_array($skills)) {
                error_log("No skills provided or invalid format for job_id: {$job_id}");
                return true; // No skills to save is not an error
            }

            $this->conn->beginTransaction();
            
            // حذف مهارت‌های قبلی آگهی
            $delete_query = "DELETE FROM " . $this->table_name . " WHERE job_id = :job_id";
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
            $delete_stmt->execute();

            // ثبت مهارت‌های جدید در جدول JobRequiredSkills
            $query = "INSERT INTO " . $this->table_name . " 
                      (job_id, class_id, subclass_id, proficiency_level) 
                      VALUES (:job_id, :class_id, :subclass_id, :proficiency_level)";

            $stmt = $this->conn->prepare($query);
            $success_count = 0;

            foreach($skills as $skill) {
                // اعتبارسنجی داده‌های مهارت برای ذخیره در جدول JobRequiredSkills
                if (!isset($skill['class_id']) || !isset($skill['proficiency_level'])) {
                    error_log("Invalid skill data for job_id {$job_id}: " . json_encode($skill));
                    continue;
                }

                // تنظیم مقادیر برای ذخیره
                $class_id = (int)$skill['class_id']; // کلاس شغلی
                $subclass_id = isset($skill['subclass_id']) && $skill['subclass_id'] ? (int)$skill['subclass_id'] : null; // زیرکلاس شغلی
                $proficiency_level = htmlspecialchars(strip_tags($skill['proficiency_level'])); // سطح توانایی

                // بررسی سطح تسلط
                if (!in_array($proficiency_level, ['beginner', 'intermediate', 'expert'])) {
                    error_log("Invalid proficiency level for job_id {$job_id}: " . $proficiency_level);
                    continue;
                }

                // باند کردن پارامترها
                $stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT); // آیدی شغل
                $stmt->bindParam(":class_id", $class_id, PDO::PARAM_INT); // کلاس شغلی
                $stmt->bindParam(":subclass_id", $subclass_id, PDO::PARAM_INT); // زیرکلاس شغلی
                $stmt->bindParam(":proficiency_level", $proficiency_level); // سطح توانایی
                
                if ($stmt->execute()) {
                    $success_count++;
                    error_log("Skill saved for job_id {$job_id}: class_id={$class_id}, subclass_id=" . ($subclass_id ?? 'null') . ", level={$proficiency_level}");
                } else {
                    error_log("Failed to insert skill for job_id {$job_id}: " . json_encode($skill));
                }
            }

            $this->conn->commit();
            error_log("Successfully saved {$success_count} skills for job_id {$job_id}");
            return $success_count > 0 || empty($skills);
        } catch(Exception $e) {
            $this->conn->rollback();
            error_log("Database error in JobRequiredSkill::createMultiple for job_id {$job_id}: " . $e->getMessage());
            return false;
        }
    }

    // دریافت مهارت‌های مورد نیاز یک آگهی
    public function getByJobId($job_id) {
        try {
            $query = "SELECT jrs.job_skill_id, jrs.class_id, jrs.subclass_id, jrs.proficiency_level,
                             jc.class_name, jsc.subclass_name
                      FROM " . $this->table_name . " jrs
                      JOIN JobClasses jc ON jrs.class_id = jc.class_id
                      LEFT JOIN JobSubClasses jsc ON jrs.subclass_id = jsc.subclass_id
                      WHERE jrs.job_id = :job_id
                      ORDER BY jc.class_name, jsc.subclass_name";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::getByJobId: " . $e->getMessage());
            return false;
        }
    }

    // بررسی وجود مهارت مورد نیاز
    public function skillExists($job_id, $class_id, $subclass_id = null) {
        try {
            $query = "SELECT job_skill_id FROM " . $this->table_name . " 
                      WHERE job_id = :job_id AND class_id = :class_id";
            
            if ($subclass_id) {
                $query .= " AND subclass_id = :subclass_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            $stmt->bindParam(":class_id", $class_id);
            
            if ($subclass_id) {
                $stmt->bindParam(":subclass_id", $subclass_id);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::skillExists: " . $e->getMessage());
            return false;
        }
    }

    // حذف مهارت مورد نیاز
    public function delete($job_skill_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE job_skill_id = :job_skill_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_skill_id", $job_skill_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::delete: " . $e->getMessage());
            return false;
        }
    }

    // حذف تمام مهارت‌های یک آگهی
    public function deleteByJobId($job_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE job_id = :job_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::deleteByJobId: " . $e->getMessage());
            return false;
        }
    }

    // به‌روزرسانی مهارت مورد نیاز
    public function update($job_skill_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET class_id = :class_id, subclass_id = :subclass_id, 
                          proficiency_level = :proficiency_level
                      WHERE job_skill_id = :job_skill_id";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->class_id = htmlspecialchars(strip_tags($this->class_id));
            $this->subclass_id = $this->subclass_id ? htmlspecialchars(strip_tags($this->subclass_id)) : null;
            $this->proficiency_level = htmlspecialchars(strip_tags($this->proficiency_level));

            // باند کردن پارامترها
            $stmt->bindParam(":class_id", $this->class_id);
            $stmt->bindParam(":subclass_id", $this->subclass_id);
            $stmt->bindParam(":proficiency_level", $this->proficiency_level);
            $stmt->bindParam(":job_skill_id", $job_skill_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequiredSkill::update: " . $e->getMessage());
            return false;
        }
    }
}
?> 
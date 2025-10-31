<?php
require_once __DIR__ . '/../config/database.php';

class JobRequest {
    private $conn;
    private $table_name = "JobRequests";

    public $request_id;
    public $job_id;
    public $freelancer_id;
    public $message;
    public $proposed_price;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ایجاد درخواست جدید
    public function create() {
        try {
            // بررسی وجود درخواست قبلی
            if ($this->requestExists($this->job_id, $this->freelancer_id)) {
                return false; // درخواست قبلی وجود دارد
            }

            $query = "INSERT INTO " . $this->table_name . " 
                      (job_id, freelancer_id, message, proposed_price) 
                      VALUES (:job_id, :freelancer_id, :message, :proposed_price)";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->job_id = (int)$this->job_id;
            $this->freelancer_id = (int)$this->freelancer_id;
            $this->message = htmlspecialchars(strip_tags(trim($this->message)));
            $this->proposed_price = $this->proposed_price ? (float)$this->proposed_price : null;

            // باند کردن پارامترها
            $stmt->bindParam(":job_id", $this->job_id, PDO::PARAM_INT);
            $stmt->bindParam(":freelancer_id", $this->freelancer_id, PDO::PARAM_INT);
            $stmt->bindParam(":message", $this->message);
            $stmt->bindParam(":proposed_price", $this->proposed_price);

            if($stmt->execute()) {
                $request_id = $this->conn->lastInsertId();
                error_log("Job request created successfully with ID: {$request_id}");
                return $request_id;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::create: " . $e->getMessage());
            return false;
        }
    }

    // بررسی وجود درخواست قبلی
    public function requestExists($job_id, $freelancer_id) {
        try {
            $query = "SELECT request_id FROM " . $this->table_name . " 
                      WHERE job_id = :job_id AND freelancer_id = :freelancer_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
            $stmt->bindParam(":freelancer_id", $freelancer_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::requestExists: " . $e->getMessage());
            return false;
        }
    }

    // دریافت درخواست‌های یک آگهی
    public function getByJobId($job_id) {
        try {
            $query = "SELECT jr.*, u.first_name, u.last_name, u.email
                      FROM " . $this->table_name . " jr
                      JOIN Users u ON jr.freelancer_id = u.user_id
                      WHERE jr.job_id = :job_id
                      ORDER BY jr.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::getByJobId: " . $e->getMessage());
            return false;
        }
    }

    // دریافت درخواست‌های یک کارجو
    public function getByFreelancerId($freelancer_id) {
        try {
            $query = "SELECT jr.*, j.title as job_title, j.description as job_description,
                             u.first_name as employer_first_name, u.last_name as employer_last_name
                      FROM " . $this->table_name . " jr
                      JOIN Jobs j ON jr.job_id = j.job_id
                      JOIN Users u ON j.user_id = u.user_id
                      WHERE jr.freelancer_id = :freelancer_id
                      ORDER BY jr.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":freelancer_id", $freelancer_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::getByFreelancerId: " . $e->getMessage());
            return false;
        }
    }

    // به‌روزرسانی وضعیت درخواست
    public function updateStatus($request_id, $status, $employer_id) {
        try {
            // بررسی اینکه کارفرما مالک آگهی است
            $check_query = "SELECT jr.request_id FROM " . $this->table_name . " jr
                           JOIN Jobs j ON jr.job_id = j.job_id
                           WHERE jr.request_id = :request_id AND j.user_id = :employer_id";
            
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
            $check_stmt->bindParam(":employer_id", $employer_id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                return false; // کارفرما مالک آگهی نیست
            }

            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status, updated_at = CURRENT_TIMESTAMP
                      WHERE request_id = :request_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::updateStatus: " . $e->getMessage());
            return false;
        }
    }

    // دریافت درخواست بر اساس ID
    public function getById($request_id) {
        try {
            $query = "SELECT jr.*, j.title as job_title, j.user_id as employer_id,
                             u.first_name, u.last_name, u.email
                      FROM " . $this->table_name . " jr
                      JOIN Jobs j ON jr.job_id = j.job_id
                      JOIN Users u ON jr.freelancer_id = u.user_id
                      WHERE jr.request_id = :request_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::getById: " . $e->getMessage());
            return false;
        }
    }

    // حذف درخواست
    public function delete($request_id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE request_id = :request_id AND freelancer_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":request_id", $request_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in JobRequest::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>

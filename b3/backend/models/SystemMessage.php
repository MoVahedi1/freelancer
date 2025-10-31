<?php
require_once __DIR__ . '/../config/database.php';

class SystemMessage {
    private $conn;
    private $table_name = "SystemMessages";

    public $message_id;
    public $user_id;
    public $sender_id;
    public $message_type;
    public $title;
    public $message;
    public $related_request_id;
    public $related_job_id;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ایجاد پیام جدید
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, sender_id, message_type, title, message, related_request_id, related_job_id) 
                      VALUES (:user_id, :sender_id, :message_type, :title, :message, :related_request_id, :related_job_id)";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->user_id = (int)$this->user_id;
            $this->sender_id = $this->sender_id ? (int)$this->sender_id : null;
            $this->message_type = htmlspecialchars(strip_tags($this->message_type));
            $this->title = htmlspecialchars(strip_tags(trim($this->title)));
            $this->message = htmlspecialchars(strip_tags(trim($this->message)));
            $this->related_request_id = $this->related_request_id ? (int)$this->related_request_id : null;
            $this->related_job_id = $this->related_job_id ? (int)$this->related_job_id : null;

            // باند کردن پارامترها
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(":sender_id", $this->sender_id, PDO::PARAM_INT);
            $stmt->bindParam(":message_type", $this->message_type);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":message", $this->message);
            $stmt->bindParam(":related_request_id", $this->related_request_id, PDO::PARAM_INT);
            $stmt->bindParam(":related_job_id", $this->related_job_id, PDO::PARAM_INT);

            if($stmt->execute()) {
                $message_id = $this->conn->lastInsertId();
                error_log("System message created successfully with ID: {$message_id}");
                return $message_id;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::create: " . $e->getMessage());
            return false;
        }
    }

    // دریافت پیام‌های یک کاربر
    public function getByUserId($user_id, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT sm.*, u.first_name as sender_first_name, u.last_name as sender_last_name
                      FROM " . $this->table_name . " sm
                      LEFT JOIN Users u ON sm.sender_id = u.user_id
                      WHERE sm.user_id = :user_id
                      ORDER BY sm.created_at DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::getByUserId: " . $e->getMessage());
            return false;
        }
    }

    // شمارش پیام‌های خوانده نشده
    public function getUnreadCount($user_id) {
        try {
            $query = "SELECT COUNT(*) as unread_count FROM " . $this->table_name . " 
                      WHERE user_id = :user_id AND is_read = FALSE";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? (int)$result['unread_count'] : 0;
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::getUnreadCount: " . $e->getMessage());
            return 0;
        }
    }

    // علامت‌گذاری پیام به عنوان خوانده شده
    public function markAsRead($message_id, $user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET is_read = TRUE 
                      WHERE message_id = :message_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":message_id", $message_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::markAsRead: " . $e->getMessage());
            return false;
        }
    }

    // علامت‌گذاری همه پیام‌ها به عنوان خوانده شده
    public function markAllAsRead($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET is_read = TRUE 
                      WHERE user_id = :user_id AND is_read = FALSE";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::markAllAsRead: " . $e->getMessage());
            return false;
        }
    }

    // حذف پیام
    public function delete($message_id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE message_id = :message_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":message_id", $message_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in SystemMessage::delete: " . $e->getMessage());
            return false;
        }
    }

    // ایجاد پیام درخواست کاری
    public static function createJobRequestMessage($db, $employer_id, $freelancer_id, $job_id, $request_id, $job_title, $freelancer_name) {
        $message = new SystemMessage($db);
        $message->user_id = $employer_id;
        $message->sender_id = $freelancer_id;
        $message->message_type = 'job_request';
        $message->title = "درخواست جدید برای آگهی: {$job_title}";
        $message->message = "کارجوی {$freelancer_name} برای آگهی شما درخواست ارسال کرده است.";
        $message->related_request_id = $request_id;
        $message->related_job_id = $job_id;
        
        return $message->create();
    }

    // ایجاد پیام پاسخ به درخواست
    public static function createRequestResponseMessage($db, $freelancer_id, $employer_id, $job_id, $request_id, $job_title, $status) {
        $message = new SystemMessage($db);
        $message->user_id = $freelancer_id;
        $message->sender_id = $employer_id;
        $message->message_type = 'request_response';
        
        if ($status === 'accepted') {
            $message->title = "درخواست شما پذیرفته شد!";
            $message->message = "درخواست شما برای آگهی '{$job_title}' پذیرفته شده است. با کارفرما تماس بگیرید.";
        } else {
            $message->title = "درخواست شما رد شد";
            $message->message = "متأسفانه درخواست شما برای آگهی '{$job_title}' رد شده است.";
        }
        
        $message->related_request_id = $request_id;
        $message->related_job_id = $job_id;
        
        return $message->create();
    }

    // ایجاد پیام تکمیل پروژه
    public static function createProjectCompletionMessage($db, $employer_id, $freelancer_id, $request_id, $job_title) {
        $message = new SystemMessage($db);
        $message->user_id = $employer_id;
        $message->sender_id = $freelancer_id;
        $message->message_type = 'project_completion';
        $message->title = "پروژه تکمیل شد";
        $message->message = "پروژه '{$job_title}' توسط کارجو تکمیل شده و در انتظار تایید شماست.";
        $message->related_request_id = $request_id;
        
        return $message->create();
    }

    // ایجاد پیام تحویل پروژه
    public static function createProjectDeliveryMessage($db, $freelancer_id, $employer_id, $request_id, $job_title) {
        $message = new SystemMessage($db);
        $message->user_id = $freelancer_id;
        $message->sender_id = $employer_id;
        $message->message_type = 'project_delivery';
        $message->title = "پروژه تحویل داده شد";
        $message->message = "پروژه '{$job_title}' توسط کارفرما تایید شد و با موفقیت تحویل داده شد.";
        $message->related_request_id = $request_id;
        
        return $message->create();
    }

    // ایجاد پیام درخواست همکاری
    public static function createCollaborationRequestMessage($db, $freelancer_id, $employer_id, $collaboration_id, $employer_name) {
        $message = new SystemMessage($db);
        $message->user_id = $freelancer_id;
        $message->sender_id = $employer_id;
        $message->message_type = 'collaboration_request';
        $message->title = "درخواست همکاری جدید";
        $message->message = "کارفرما {$employer_name} برای شما درخواست همکاری ارسال کرده است.";
        $message->related_request_id = $collaboration_id;
        
        return $message->create();
    }

    // ایجاد پیام پاسخ به درخواست همکاری
    public static function createCollaborationResponseMessage($db, $employer_id, $freelancer_id, $collaboration_id, $freelancer_name, $status) {
        $message = new SystemMessage($db);
        $message->user_id = $employer_id;
        $message->sender_id = $freelancer_id;
        $message->message_type = 'collaboration_response';
        
        if ($status === 'accepted') {
            $message->title = "درخواست همکاری پذیرفته شد!";
            $message->message = "کارجو {$freelancer_name} درخواست همکاری شما را پذیرفته است.";
        } else {
            $message->title = "درخواست همکاری رد شد";
            $message->message = "متأسفانه کارجو {$freelancer_name} درخواست همکاری شما را رد کرده است.";
        }
        
        $message->related_request_id = $collaboration_id;
        
        return $message->create();
    }
}
?>

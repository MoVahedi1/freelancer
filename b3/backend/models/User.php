<?php
// Database connection will be provided by the calling script

class User {
    private $conn;
    private $table_name = "Users";

    public $user_id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $user_type;
    public $company_name;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ثبت‌نام کاربر جدید
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (email, password, first_name, last_name, user_type, company_name) 
                      VALUES (:email, :password, :first_name, :last_name, :user_type, :company_name)";

            $stmt = $this->conn->prepare($query);

            // پاکسازی داده‌ها
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->user_type = htmlspecialchars(strip_tags($this->user_type));
            $this->company_name = $this->company_name ? htmlspecialchars(strip_tags($this->company_name)) : null;

            // هش کردن رمز عبور
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            // باند کردن پارامترها
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":user_type", $this->user_type);
            $stmt->bindParam(":company_name", $this->company_name);

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in User::create: " . $e->getMessage());
            return false;
        }
    }

    // ورود کاربر
    public function login($email, $password) {
        try {
            $query = "SELECT user_id, email, password, first_name, last_name, user_type, company_name 
                      FROM " . $this->table_name . " WHERE email = :email";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                if(password_verify($password, $row['password'])) {
                    return $row;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in User::login: " . $e->getMessage());
            return false;
        }
    }

    // بررسی وجود ایمیل
    public function emailExists($email) {
        try {
            $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in User::emailExists: " . $e->getMessage());
            return false;
        }
    }

    // دریافت اطلاعات کاربر بر اساس ID
    public function getById($user_id) {
        try {
            $query = "SELECT user_id, email, first_name, last_name, user_type, company_name, created_at 
                      FROM " . $this->table_name . " WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in User::getById: " . $e->getMessage());
            return false;
        }
    }
}
?> 
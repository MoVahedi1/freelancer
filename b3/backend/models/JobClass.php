<?php
require_once __DIR__ . '/../config/database.php';

class JobClass {
    private $conn;
    private $table_name = "JobClasses";

    public $class_id;
    public $class_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    // دریافت تمام کلاس‌های شغلی
    public function getAll() {
        $query = "SELECT class_id, class_name FROM " . $this->table_name . " ORDER BY class_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // دریافت زیرکلاس‌های یک کلاس خاص
    public function getSubClasses($class_id) {
        $query = "SELECT subclass_id, subclass_name 
                  FROM JobSubClasses 
                  WHERE class_id = :class_id 
                  ORDER BY subclass_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":class_id", $class_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // دریافت کلاس و زیرکلاس‌های آن
    public function getClassWithSubClasses($class_id) {
        $query = "SELECT jc.class_id, jc.class_name, 
                         jsc.subclass_id, jsc.subclass_name
                  FROM " . $this->table_name . " jc
                  LEFT JOIN JobSubClasses jsc ON jc.class_id = jsc.class_id
                  WHERE jc.class_id = :class_id
                  ORDER BY jsc.subclass_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":class_id", $class_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?> 
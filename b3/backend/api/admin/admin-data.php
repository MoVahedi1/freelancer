<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

// بررسی احراز هویت ادمین
function checkAdminAuth() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(array("message" => "توکن احراز هویت مورد نیاز است."));
        exit();
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = json_decode(base64_decode($token), true);
    
    if (!$decoded || $decoded['user_type'] !== 'admin' || $decoded['exp'] < time()) {
        http_response_code(401);
        echo json_encode(array("message" => "دسترسی غیرمجاز."));
        exit();
    }
    
    return $decoded;
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// بررسی احراز هویت ادمین
checkAdminAuth();

switch($method) {
    case 'GET':
        handleGet($db, $action);
        break;
    case 'POST':
        handlePost($db, $action);
        break;
    case 'PUT':
        handlePut($db, $action);
        break;
    case 'DELETE':
        handleDelete($db, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "روش غیرمجاز"));
        break;
}

function handleGet($db, $action) {
    switch($action) {
        case 'users':
            getAllUsers($db);
            break;
        case 'jobs':
            getAllJobs($db);
            break;
        case 'requests':
            getAllRequests($db);
            break;
        case 'messages':
            getAllMessages($db);
            break;
        case 'projects':
            getAllProjects($db);
            break;
        case 'stats':
            getStats($db);
            break;
        case 'status':
            getSystemStatus($db);
            break;
        case 'null-ids':
            getNullIdsStats($db);
            break;
        case 'user':
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if ($id) {
                getUserById($db, $id);
            } else {
                getAllUsers($db);
            }
            break;
        case 'job':
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if ($id) {
                getJobById($db, $id);
            } else {
                getAllJobs($db);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handlePost($db, $action) {
    $data = json_decode(file_get_contents("php://input"));
    
    switch($action) {
        case 'user':
            createUser($db, $data);
            break;
        case 'job':
            createJob($db, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handlePut($db, $action) {
    $data = json_decode(file_get_contents("php://input"));
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    
    switch($action) {
        case 'user':
            updateUser($db, $id, $data);
            break;
        case 'job':
            updateJob($db, $id, $data);
            break;
        case 'project':
            updateProject($db, $id, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handleDelete($db, $action) {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    
    switch($action) {
        case 'user':
            deleteUser($db, $id);
            break;
        case 'job':
            deleteJob($db, $id);
            break;
        case 'project':
            deleteProject($db, $id);
            break;
        case 'request':
            deleteRequest($db, $id);
            break;
        case 'message':
            deleteMessage($db, $id);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

// توابع مدیریت کاربران
function getAllUsers($db) {
    try {
        $query = "SELECT user_id, email, first_name, last_name, user_type, company_name, created_at 
                  FROM Users 
                  WHERE email IS NOT NULL 
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $users = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }
        
        echo json_encode(array("users" => $users));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت کاربران: " . $e->getMessage()));
    }
}

function createUser($db, $data) {
    if (empty($data->email) || empty($data->password) || empty($data->first_name) || empty($data->last_name) || empty($data->user_type)) {
        http_response_code(400);
        echo json_encode(array("message" => "داده‌های ناقص"));
        return;
    }
    
    try {
        // First, try to find a null user record to reuse
        $findNullQuery = "SELECT user_id FROM Users WHERE email IS NULL LIMIT 1";
        $findStmt = $db->prepare($findNullQuery);
        $findStmt->execute();
        $nullUser = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nullUser) {
            // Reuse the null user ID
            $query = "UPDATE Users SET 
                      email = ?, 
                      password = ?, 
                      first_name = ?, 
                      last_name = ?, 
                      user_type = ?, 
                      company_name = ?, 
                      created_at = NOW() 
                      WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            
            if ($stmt->execute([$data->email, $hashed_password, $data->first_name, $data->last_name, $data->user_type, $data->company_name ?? null, $nullUser['user_id']])) {
                echo json_encode(array(
                    "message" => "کاربر با موفقیت ایجاد شد",
                    "reused_id" => $nullUser['user_id'],
                    "note" => "آیدی بازاستفاده شده: " . $nullUser['user_id']
                ));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "خطا در ایجاد کاربر"));
            }
        } else {
            // Create new user with new ID
            $query = "INSERT INTO Users (email, password, first_name, last_name, user_type, company_name) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            
            if ($stmt->execute([$data->email, $hashed_password, $data->first_name, $data->last_name, $data->user_type, $data->company_name ?? null])) {
                echo json_encode(array("message" => "کاربر با موفقیت ایجاد شد"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "خطا در ایجاد کاربر"));
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در ایجاد کاربر: " . $e->getMessage()));
    }
}

function updateUser($db, $id, $data) {
    try {
        $query = "UPDATE Users SET email = ?, first_name = ?, last_name = ?, user_type = ?, company_name = ? WHERE user_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$data->email, $data->first_name, $data->last_name, $data->user_type, $data->company_name ?? null, $id])) {
            echo json_encode(array("message" => "کاربر با موفقیت به‌روزرسانی شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در به‌روزرسانی کاربر"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در به‌روزرسانی کاربر: " . $e->getMessage()));
    }
}

function deleteUser($db, $id) {
    try {
        // شروع تراکنش برای حذف کامل
        $db->beginTransaction();
        
        // 1. حذف مهارت‌های فریلنسر
        $query1 = "DELETE FROM FreelancerSkills WHERE user_id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->execute([$id]);
        
        // 2. حذف درخواست‌های شغلی که توسط این کاربر ارسال شده
        $query2 = "DELETE FROM JobRequests WHERE freelancer_id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute([$id]);
        
        // 3. حذف آگهی‌های این کاربر (اگر کارفرما باشد)
        $query3 = "DELETE FROM Jobs WHERE user_id = ?";
        $stmt3 = $db->prepare($query3);
        $stmt3->execute([$id]);
        
        // 4. حذف پیام‌های سیستمی مرتبط با این کاربر
        $query4 = "DELETE FROM SystemMessages WHERE user_id = ?";
        $stmt4 = $db->prepare($query4);
        $stmt4->execute([$id]);
        
        // 5. حذف کاربر از جدول اصلی
        $query5 = "DELETE FROM Users WHERE user_id = ?";
        $stmt5 = $db->prepare($query5);
        
        if ($stmt5->execute([$id])) {
            $db->commit();
            echo json_encode(array(
                "message" => "کاربر و تمام اطلاعات مرتبط با آن با موفقیت حذف شد",
                "deleted_records" => [
                    "skills" => $stmt1->rowCount(),
                    "requests" => $stmt2->rowCount(), 
                    "jobs" => $stmt3->rowCount(),
                    "messages" => $stmt4->rowCount(),
                    "user" => $stmt5->rowCount()
                ]
            ));
        } else {
            $db->rollback();
            http_response_code(500);
            echo json_encode(array("message" => "خطا در حذف کاربر"));
        }
    } catch (PDOException $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(array("error" => "خطا در حذف کاربر: " . $e->getMessage()));
    }
}

// توابع مدیریت آگهی‌ها
function getAllJobs($db) {
    try {
        $query = "SELECT j.job_id, j.title, j.description, j.budget_type, j.budget_min, j.budget_max, j.created_at,
                         u.first_name, u.last_name, u.company_name
                  FROM Jobs j 
                  LEFT JOIN Users u ON j.user_id = u.user_id 
                  WHERE j.title IS NOT NULL 
                  ORDER BY j.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $jobs = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Format budget for display
            if ($row['budget_type'] === 'range') {
                $row['budget'] = $row['budget_min'] . ' - ' . $row['budget_max'] . ' تومان';
            } else {
                $row['budget'] = 'قابل مذاکره';
            }
            $jobs[] = $row;
        }
        
        echo json_encode(array("jobs" => $jobs));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت آگهی‌ها: " . $e->getMessage()));
    }
}

function createJob($db, $data) {
    if (empty($data->title) || empty($data->description) || empty($data->user_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "داده‌های ناقص"));
        return;
    }
    
    try {
        // First, try to find a null job record to reuse
        $findNullQuery = "SELECT job_id FROM Jobs WHERE title IS NULL LIMIT 1";
        $findStmt = $db->prepare($findNullQuery);
        $findStmt->execute();
        $nullJob = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nullJob) {
            // Reuse the null job ID
            $query = "UPDATE Jobs SET 
                      title = ?, 
                      description = ?, 
                      budget_type = ?, 
                      budget_min = ?, 
                      budget_max = ?, 
                      user_id = ?, 
                      created_at = NOW() 
                      WHERE job_id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$data->title, $data->description, $data->budget_type, $data->budget_min ?? null, $data->budget_max ?? null, $data->user_id, $nullJob['job_id']])) {
                echo json_encode(array(
                    "message" => "آگهی با موفقیت ایجاد شد",
                    "reused_id" => $nullJob['job_id'],
                    "note" => "آیدی بازاستفاده شده: " . $nullJob['job_id']
                ));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "خطا در ایجاد آگهی"));
            }
        } else {
            // Create new job with new ID
            $query = "INSERT INTO Jobs (title, description, budget_type, budget_min, budget_max, user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$data->title, $data->description, $data->budget_type, $data->budget_min ?? null, $data->budget_max ?? null, $data->user_id])) {
                echo json_encode(array("message" => "آگهی با موفقیت ایجاد شد"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "خطا در ایجاد آگهی"));
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در ایجاد آگهی: " . $e->getMessage()));
    }
}

function updateJob($db, $id, $data) {
    try {
        $query = "UPDATE Jobs SET title = ?, description = ?, budget_type = ?, budget_min = ?, budget_max = ? WHERE job_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$data->title, $data->description, $data->budget_type, $data->budget_min ?? null, $data->budget_max ?? null, $id])) {
            echo json_encode(array("message" => "آگهی با موفقیت به‌روزرسانی شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در به‌روزرسانی آگهی"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در به‌روزرسانی آگهی: " . $e->getMessage()));
    }
}

function deleteJob($db, $id) {
    try {
        // شروع تراکنش برای حذف کامل
        $db->beginTransaction();
        
        // 1. حذف درخواست‌های مرتبط با این آگهی
        $query1 = "DELETE FROM JobRequests WHERE job_id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->execute([$id]);
        
        // 2. حذف فایل‌های آپلود شده مرتبط با این آگهی (اگر جدولی وجود داشته باشد)
        // $query2 = "DELETE FROM JobFiles WHERE job_id = ?";
        // $stmt2 = $db->prepare($query2);
        // $stmt2->execute([$id]);
        
        // 3. حذف آگهی از جدول اصلی
        $query3 = "DELETE FROM Jobs WHERE job_id = ?";
        $stmt3 = $db->prepare($query3);
        
        if ($stmt3->execute([$id])) {
            $db->commit();
            echo json_encode(array(
                "message" => "آگهی و تمام اطلاعات مرتبط با آن با موفقیت حذف شد",
                "deleted_records" => [
                    "requests" => $stmt1->rowCount(),
                    "job" => $stmt3->rowCount()
                ]
            ));
        } else {
            $db->rollback();
            http_response_code(500);
            echo json_encode(array("message" => "خطا در حذف آگهی"));
        }
    } catch (PDOException $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(array("error" => "خطا در حذف آگهی: " . $e->getMessage()));
    }
}

// توابع مدیریت درخواست‌ها
function getAllRequests($db) {
    try {
        $query = "SELECT jr.request_id, jr.message, jr.proposed_price, jr.status, jr.created_at,
                         j.title as job_title,
                         u1.first_name as employer_first_name, u1.last_name as employer_last_name,
                         u2.first_name as freelancer_first_name, u2.last_name as freelancer_last_name
                  FROM JobRequests jr
                  LEFT JOIN Jobs j ON jr.job_id = j.job_id
                  LEFT JOIN Users u1 ON j.user_id = u1.user_id
                  LEFT JOIN Users u2 ON jr.freelancer_id = u2.user_id
                  ORDER BY jr.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $requests = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requests[] = $row;
        }
        
        echo json_encode(array("requests" => $requests));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت درخواست‌ها: " . $e->getMessage()));
    }
}

function deleteRequest($db, $id) {
    try {
        $query = "DELETE FROM JobRequests WHERE request_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id])) {
            echo json_encode(array("message" => "درخواست با موفقیت حذف شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در حذف درخواست"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در حذف درخواست: " . $e->getMessage()));
    }
}

// توابع مدیریت پروژه‌ها (درخواست‌های پذیرفته شده)
function getAllProjects($db) {
    try {
        $query = "SELECT jr.request_id, jr.message, jr.proposed_price, jr.status, jr.created_at,
                         j.title as job_title,
                         u1.first_name as employer_first_name, u1.last_name as employer_last_name,
                         u2.first_name as freelancer_first_name, u2.last_name as freelancer_last_name
                  FROM JobRequests jr
                  LEFT JOIN Jobs j ON jr.job_id = j.job_id
                  LEFT JOIN Users u1 ON j.user_id = u1.user_id
                  LEFT JOIN Users u2 ON jr.freelancer_id = u2.user_id
                  WHERE jr.status IN ('accepted', 'ongoing', 'completed', 'delivered')
                  ORDER BY jr.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $projects = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $projects[] = $row;
        }
        
        echo json_encode(array("projects" => $projects));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت پروژه‌ها: " . $e->getMessage()));
    }
}

function updateProject($db, $id, $data) {
    try {
        $query = "UPDATE JobRequests SET status = ? WHERE request_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$data->status, $id])) {
            echo json_encode(array("message" => "پروژه با موفقیت به‌روزرسانی شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در به‌روزرسانی پروژه"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در به‌روزرسانی پروژه: " . $e->getMessage()));
    }
}

function deleteProject($db, $id) {
    try {
        $query = "DELETE FROM JobRequests WHERE request_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id])) {
            echo json_encode(array("message" => "پروژه با موفقیت حذف شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در حذف پروژه"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در حذف پروژه: " . $e->getMessage()));
    }
}

// توابع مدیریت پیام‌ها
function getAllMessages($db) {
    try {
        $query = "SELECT sm.message_id, sm.title, sm.message, sm.message_type, sm.is_read, sm.created_at,
                         u1.first_name as sender_first_name, u1.last_name as sender_last_name,
                         u2.first_name as receiver_first_name, u2.last_name as receiver_last_name
                  FROM SystemMessages sm
                  LEFT JOIN Users u1 ON sm.sender_id = u1.user_id
                  LEFT JOIN Users u2 ON sm.user_id = u2.user_id
                  ORDER BY sm.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $messages = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }
        
        echo json_encode(array("messages" => $messages));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت پیام‌ها: " . $e->getMessage()));
    }
}

function deleteMessage($db, $id) {
    try {
        $query = "DELETE FROM SystemMessages WHERE message_id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id])) {
            echo json_encode(array("message" => "پیام با موفقیت حذف شد"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در حذف پیام"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در حذف پیام: " . $e->getMessage()));
    }
}

// توابع آمار
function getStats($db) {
    try {
        $stats = array();
        
        // تعداد کل کاربران
        $query = "SELECT COUNT(*) as total FROM Users WHERE email IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['users'] = $stmt->fetch()['total'];
        
        // تعداد کارفرمایان
        $query = "SELECT COUNT(*) as total FROM Users WHERE user_type = 'employer' AND email IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['employers'] = $stmt->fetch()['total'];
        
        // تعداد فریلنسرها
        $query = "SELECT COUNT(*) as total FROM Users WHERE user_type = 'freelancer' AND email IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['freelancers'] = $stmt->fetch()['total'];
        
        // تعداد آگهی‌ها
        $query = "SELECT COUNT(*) as total FROM Jobs WHERE title IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['jobs'] = $stmt->fetch()['total'];
        
        // تعداد درخواست‌ها
        $query = "SELECT COUNT(*) as total FROM JobRequests";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['requests'] = $stmt->fetch()['total'];
        
        // تعداد پروژه‌های فعال
        $query = "SELECT COUNT(*) as total FROM JobRequests WHERE status IN ('accepted', 'ongoing')";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['active_projects'] = $stmt->fetch()['total'];
        
        // تعداد پیام‌ها
        $query = "SELECT COUNT(*) as total FROM SystemMessages";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['messages'] = $stmt->fetch()['total'];
        
        echo json_encode($stats);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت آمار: " . $e->getMessage()));
    }
}

// وضعیت سیستم
function getSystemStatus($db) {
    try {
        $status = array(
            'database_connected' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'tables_status' => array()
        );
        
        // بررسی وجود جداول اصلی
        $tables = ['Users', 'Jobs', 'JobRequests', 'SystemMessages', 'JobClasses', 'JobSubClasses', 'FreelancerSkills'];
        foreach ($tables as $table) {
            try {
                $query = "SELECT COUNT(*) as count FROM {$table}";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch();
                $status['tables_status'][$table] = $result['count'];
            } catch (PDOException $e) {
                $status['tables_status'][$table] = 'not_exists';
            }
        }
        
        echo json_encode($status);
    } catch (PDOException $e) {
        http_response_code(200); // Still return 200 for status check
        echo json_encode(array(
            'database_connected' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ));
    }
}

// Get user by ID for editing
function getUserById($db, $id) {
    try {
        $query = "SELECT user_id, email, first_name, last_name, user_type, company_name, created_at 
                  FROM Users WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "کاربر یافت نشد"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت کاربر: " . $e->getMessage()));
    }
}

// Get job by ID for editing
function getJobById($db, $id) {
    try {
        $query = "SELECT j.job_id, j.title, j.description, j.budget_type, j.budget_min, j.budget_max, j.user_id, j.created_at,
                         u.first_name, u.last_name, u.company_name
                  FROM Jobs j 
                  LEFT JOIN Users u ON j.user_id = u.user_id 
                  WHERE j.job_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($job) {
            echo json_encode($job);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "آگهی یافت نشد"));
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت آگهی: " . $e->getMessage()));
    }
}

// توابع آمار برای شناسایی آیدی‌های بازاستفاده شده
function getNullIdsStats($db) {
    try {
        $stats = array(
            'null_user_ids' => array(
                'total' => 0,
                'last_used' => null
            ),
            'null_job_ids' => array(
                'total' => 0,
                'last_used' => null
            ),
            'null_request_ids' => array(
                'total' => 0,
                'last_used' => null
            ),
            'null_project_ids' => array(
                'total' => 0,
                'last_used' => null
            )
        );

        // بررسی آیدی‌های کاربر null
        $query = "SELECT user_id FROM Users WHERE email IS NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $nullUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stats['null_user_ids']['total'] = count($nullUserIds);
        if (!empty($nullUserIds)) {
            $stats['null_user_ids']['last_used'] = $nullUserIds[0]; // فقط آخرین آیدی را نمایش می‌دهد
        }

        // بررسی آیدی‌های آگهی null
        $query = "SELECT job_id FROM Jobs WHERE title IS NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $nullJobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stats['null_job_ids']['total'] = count($nullJobIds);
        if (!empty($nullJobIds)) {
            $stats['null_job_ids']['last_used'] = $nullJobIds[0]; // فقط آخرین آیدی را نمایش می‌دهد
        }

        // بررسی آیدی‌های درخواست null
        $query = "SELECT request_id FROM JobRequests WHERE message IS NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $nullRequestIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stats['null_request_ids']['total'] = count($nullRequestIds);
        if (!empty($nullRequestIds)) {
            $stats['null_request_ids']['last_used'] = $nullRequestIds[0]; // فقط آخرین آیدی را نمایش می‌دهد
        }

        // بررسی آیدی‌های پروژه null
        $query = "SELECT request_id FROM JobRequests WHERE status IS NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $nullProjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stats['null_project_ids']['total'] = count($nullProjectIds);
        if (!empty($nullProjectIds)) {
            $stats['null_project_ids']['last_used'] = $nullProjectIds[0]; // فقط آخرین آیدی را نمایش می‌دهد
        }

        echo json_encode($stats);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array("error" => "خطا در دریافت آمار آیدی‌های بازاستفاده شده: " . $e->getMessage()));
    }
}
?>

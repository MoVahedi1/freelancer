<?php
// API برای مدیریت چت پروژه‌ها
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// تابع برای استخراج اطلاعات کاربر از توکن
function getUserFromToken($token) {
    try {
        $decoded = json_decode(base64_decode($token), true);
        if($decoded && isset($decoded['user_id']) && $decoded['exp'] > time()) {
            return $decoded;
        }
        return false;
    } catch (Exception $e) {
        error_log("Token decode error: " . $e->getMessage());
        return false;
    }
}

// دریافت هدر Authorization
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if(strpos($auth_header, 'Bearer ') === 0) {
    $token = substr($auth_header, 7);
    $user_data = getUserFromToken($token);
    
    if(!$user_data) {
        http_response_code(401);
        echo json_encode(array("message" => "توکن نامعتبر است."));
        exit();
    }
} else {
    http_response_code(401);
    echo json_encode(array("message" => "توکن ارسال نشده است."));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
        exit();
    }

    if($method == 'GET') {
        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $action = isset($_GET['action']) ? $_GET['action'] : 'project';
        
        if (!$project_id) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پروژه الزامی است."));
            exit();
        }

        if ($action === 'project') {
            // دریافت اطلاعات پروژه
            $query = "SELECT 
                        jr.request_id,
                        jr.job_id,
                        jr.freelancer_id,
                        jr.proposed_price,
                        jr.status,
                        jr.updated_at as accepted_at,
                        j.title as job_title,
                        j.description as job_description,
                        j.user_id as employer_id,
                        uf.first_name as freelancer_first_name,
                        uf.last_name as freelancer_last_name,
                        CONCAT(uf.first_name, ' ', uf.last_name) as freelancer_name,
                        ue.first_name as employer_first_name,
                        ue.last_name as employer_last_name,
                        CONCAT(ue.first_name, ' ', ue.last_name) as employer_name
                      FROM JobRequests jr
                      JOIN Jobs j ON jr.job_id = j.job_id
                      JOIN Users uf ON jr.freelancer_id = uf.user_id
                      JOIN Users ue ON j.user_id = ue.user_id
                      WHERE jr.request_id = ? AND jr.status = 'accepted'
                      AND (jr.freelancer_id = ? OR j.user_id = ?)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $project_id);
            $stmt->bindParam(2, $user_data['user_id']);
            $stmt->bindParam(3, $user_data['user_id']);
            $stmt->execute();
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                http_response_code(404);
                echo json_encode(array("message" => "پروژه یافت نشد یا دسترسی ندارید."));
                exit();
            }
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "اطلاعات پروژه با موفقیت دریافت شد.",
                "project" => $project
            ));
            
        } elseif ($action === 'messages') {
            // دریافت پیام‌های چت پروژه
            $query = "SELECT 
                        pc.message_id,
                        pc.sender_id,
                        pc.content,
                        pc.message_type,
                        pc.file_url,
                        pc.file_name,
                        pc.created_at,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as sender_name
                      FROM ProjectChat pc
                      JOIN Users u ON pc.sender_id = u.user_id
                      WHERE pc.project_id = ?
                      ORDER BY pc.created_at ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $project_id);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "پیام‌ها با موفقیت دریافت شدند.",
                "messages" => $messages
            ));
        }
        
    } elseif($method == 'POST') {
        // ارسال پیام جدید
        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        if(empty($data->project_id) || empty($data->content)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پروژه و محتوای پیام الزامی است."));
            exit();
        }

        // بررسی دسترسی به پروژه
        $access_query = "SELECT jr.request_id 
                        FROM JobRequests jr
                        JOIN Jobs j ON jr.job_id = j.job_id
                        WHERE jr.request_id = ? AND jr.status = 'accepted'
                        AND (jr.freelancer_id = ? OR j.user_id = ?)";
        
        $access_stmt = $db->prepare($access_query);
        $access_stmt->bindParam(1, $data->project_id);
        $access_stmt->bindParam(2, $user_data['user_id']);
        $access_stmt->bindParam(3, $user_data['user_id']);
        $access_stmt->execute();
        
        if (!$access_stmt->fetch()) {
            http_response_code(403);
            echo json_encode(array("message" => "دسترسی به این پروژه ندارید."));
            exit();
        }

        // ایجاد پیام جدید
        $insert_query = "INSERT INTO ProjectChat (project_id, sender_id, content, message_type, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
        
        $insert_stmt = $db->prepare($insert_query);
        $message_type = isset($data->message_type) ? $data->message_type : 'text';
        
        $insert_stmt->bindParam(1, $data->project_id);
        $insert_stmt->bindParam(2, $user_data['user_id']);
        $insert_stmt->bindParam(3, $data->content);
        $insert_stmt->bindParam(4, $message_type);
        
        if ($insert_stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "پیام با موفقیت ارسال شد."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در ارسال پیام."));
        }
    }

} catch (Exception $e) {
    error_log("Project chat error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
}
?>

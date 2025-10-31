<?php
// API برای مدیریت فایل‌های پروژه
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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

    if($method == 'POST') {
        // آپلود فایل‌های پروژه
        $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        
        if (!$project_id) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پروژه الزامی است."));
            exit();
        }

        // بررسی دسترسی به پروژه
        $access_query = "SELECT jr.request_id 
                        FROM JobRequests jr
                        JOIN Jobs j ON jr.job_id = j.job_id
                        WHERE jr.request_id = ? AND jr.status = 'accepted'
                        AND (jr.freelancer_id = ? OR j.user_id = ?)";
        
        $access_stmt = $db->prepare($access_query);
        $access_stmt->bindParam(1, $project_id);
        $access_stmt->bindParam(2, $user_data['user_id']);
        $access_stmt->bindParam(3, $user_data['user_id']);
        $access_stmt->execute();
        
        if (!$access_stmt->fetch()) {
            http_response_code(403);
            echo json_encode(array("message" => "دسترسی به این پروژه ندارید."));
            exit();
        }

        if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
            http_response_code(400);
            echo json_encode(array("message" => "هیچ فایلی انتخاب نشده است."));
            exit();
        }

        // ایجاد پوشه آپلود در صورت عدم وجود
        $upload_dir = "../../uploads/projects/" . $project_id . "/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $uploaded_files = [];
        $files = $_FILES['files'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                
                // بررسی اندازه فایل (حداکثر 10MB)
                if ($file_size > 10 * 1024 * 1024) {
                    continue; // رد کردن فایل‌های بزرگ
                }
                
                // ایجاد نام یکتا برای فایل
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // ذخیره اطلاعات فایل در دیتابیس
                    $file_url = "uploads/projects/" . $project_id . "/" . $unique_name;
                    
                    // ایجاد پیام چت برای فایل
                    $chat_query = "INSERT INTO ProjectChat (project_id, sender_id, content, message_type, file_url, file_name, created_at) 
                                  VALUES (?, ?, ?, 'file', ?, ?, NOW())";
                    
                    $chat_stmt = $db->prepare($chat_query);
                    $content = "فایل ارسال شد: " . $file_name;
                    
                    $chat_stmt->bindParam(1, $project_id);
                    $chat_stmt->bindParam(2, $user_data['user_id']);
                    $chat_stmt->bindParam(3, $content);
                    $chat_stmt->bindParam(4, $file_url);
                    $chat_stmt->bindParam(5, $file_name);
                    
                    if ($chat_stmt->execute()) {
                        $uploaded_files[] = [
                            'name' => $file_name,
                            'url' => $file_url,
                            'size' => $file_size
                        ];
                    }
                }
            }
        }

        if (count($uploaded_files) > 0) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "فایل‌ها با موفقیت آپلود شدند.",
                "files" => $uploaded_files
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در آپلود فایل‌ها."));
        }
        
    } elseif($method == 'GET') {
        // دریافت فایل‌های پروژه
        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        
        if (!$project_id) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پروژه الزامی است."));
            exit();
        }

        $query = "SELECT 
                    pc.message_id,
                    pc.file_url,
                    pc.file_name,
                    pc.created_at,
                    u.first_name,
                    u.last_name,
                    CONCAT(u.first_name, ' ', u.last_name) as uploader_name
                  FROM ProjectChat pc
                  JOIN Users u ON pc.sender_id = u.user_id
                  WHERE pc.project_id = ? AND pc.message_type = 'file'
                  ORDER BY pc.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $project_id);
        $stmt->execute();
        
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "فایل‌ها با موفقیت دریافت شدند.",
            "files" => $files
        ));
    }

} catch (Exception $e) {
    error_log("Project files error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
}
?>

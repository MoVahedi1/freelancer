<?php
// API برای مدیریت پیام‌های سیستم
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS");
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

if($method == 'GET') {
    // دریافت پیام‌های کاربر
    try {
        require_once '../config/database.php';
        require_once '../models/SystemMessage.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $systemMessage = new SystemMessage($db);
        
        // پارامترهای صفحه‌بندی
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // دریافت پیام‌ها
        $messages = $systemMessage->getByUserId($user_data['user_id'], $limit, $offset);
        $unread_count = $systemMessage->getUnreadCount($user_data['user_id']);

        if($messages !== false) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "پیام‌ها با موفقیت دریافت شدند.",
                "data" => $messages,
                "count" => count($messages),
                "unread_count" => $unread_count
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "پیامی یافت نشد."));
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
    }

} elseif($method == 'PUT') {
    // علامت‌گذاری پیام به عنوان خوانده شده
    try {
        require_once '../config/database.php';
        require_once '../models/SystemMessage.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $systemMessage = new SystemMessage($db);

        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        if(isset($data->message_id)) {
            // علامت‌گذاری یک پیام خاص
            if($systemMessage->markAsRead($data->message_id, $user_data['user_id'])) {
                http_response_code(200);
                echo json_encode(array("message" => "پیام به عنوان خوانده شده علامت‌گذاری شد."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "خطا در علامت‌گذاری پیام."));
            }
        } elseif(isset($data->mark_all_read) && $data->mark_all_read === true) {
            // علامت‌گذاری همه پیام‌ها
            if($systemMessage->markAllAsRead($user_data['user_id'])) {
                http_response_code(200);
                echo json_encode(array("message" => "همه پیام‌ها به عنوان خوانده شده علامت‌گذاری شدند."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "خطا در علامت‌گذاری پیام‌ها."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "پارامترهای نامعتبر."));
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
    }

} elseif($method == 'DELETE') {
    // حذف پیام
    try {
        require_once '../config/database.php';
        require_once '../models/SystemMessage.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $systemMessage = new SystemMessage($db);

        $message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : null;

        if(!$message_id) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پیام الزامی است."));
            exit();
        }

        if($systemMessage->delete($message_id, $user_data['user_id'])) {
            http_response_code(200);
            echo json_encode(array("message" => "پیام با موفقیت حذف شد."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "خطا در حذف پیام."));
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
    }

} else {
    http_response_code(405);
    echo json_encode(array("message" => "متد غیرمجاز."));
}
?>

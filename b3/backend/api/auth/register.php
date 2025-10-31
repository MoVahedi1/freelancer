<?php
// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 0); // غیرفعال کردن نمایش خطاها در خروجی

// تنظیم header های مناسب
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "متد درخواست نامعتبر است"));
    exit();
}

try {
    // بررسی وجود فایل‌های مورد نیاز
    if (!file_exists('../../config/database.php')) {
        throw new Exception('فایل database.php یافت نشد');
    }
    
    if (!file_exists('../../models/User.php')) {
        throw new Exception('فایل User.php یافت نشد');
    }

    require_once '../../config/database.php';
    require_once '../../models/User.php';

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
        exit();
    }
    
    $user = new User($db);

    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->email) && !empty($data->password) && !empty($data->first_name) && 
       !empty($data->last_name) && !empty($data->user_type)) {
        
        // بررسی وجود ایمیل
        if($user->emailExists($data->email)) {
            http_response_code(400);
            echo json_encode(array("message" => "این ایمیل قبلاً ثبت شده است."));
            exit();
        }

        // تنظیم مقادیر
        $user->email = $data->email;
        $user->password = $data->password;
        $user->first_name = $data->first_name;
        $user->last_name = $data->last_name;
        $user->user_type = $data->user_type;
        $user->company_name = $data->company_name ?? null;

        // ایجاد کاربر
        if($user_id = $user->create()) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "کاربر با موفقیت ثبت شد.",
                "user_id" => $user_id,
                "user_type" => $user->user_type
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "خطا در ثبت کاربر."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array(
            "message" => "داده‌های ناقص ارسال شده است.",
            "received_data" => $data
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "message" => "خطا در سرور: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
}
?> 
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    
    // بررسی اینکه آیا کاربر ادمین است
    if($data->email === 'mohammadvahediorg@gmail.com' && $data->password === '12345678') {
        
        // تولید توکن برای ادمین
        $token = base64_encode(json_encode([
            'user_id' => 'admin',
            'email' => $data->email,
            'user_type' => 'admin',
            'exp' => time() + (60 * 60 * 24) // 24 ساعت
        ]));
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "ورود ادمین موفقیت‌آمیز بود.",
            "token" => $token,
            "user" => array(
                "user_id" => "admin",
                "email" => $data->email,
                "first_name" => "مدیر",
                "last_name" => "سیستم",
                "user_type" => "admin"
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "دسترسی مجاز نیست. فقط ادمین می‌تواند وارد شود."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "داده‌های ناقص ارسال شده است."));
}
?>

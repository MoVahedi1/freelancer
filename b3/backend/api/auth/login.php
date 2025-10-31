<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    
    // تلاش برای ورود
    $user_data = $user->login($data->email, $data->password);
    
    if($user_data) {
        // تولید توکن JWT ساده (در پروژه واقعی از کتابخانه JWT استفاده کنید)
        $token = base64_encode(json_encode([
            'user_id' => $user_data['user_id'],
            'email' => $user_data['email'],
            'user_type' => $user_data['user_type'],
            'exp' => time() + (60 * 60 * 24) // 24 ساعت
        ]));
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "ورود موفقیت‌آمیز بود.",
            "token" => $token,
            "user" => array(
                "user_id" => $user_data['user_id'],
                "email" => $user_data['email'],
                "first_name" => $user_data['first_name'],
                "last_name" => $user_data['last_name'],
                "user_type" => $user_data['user_type'],
                "company_name" => $user_data['company_name']
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "ایمیل یا رمز عبور اشتباه است."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "داده‌های ناقص ارسال شده است."));
}
?> 
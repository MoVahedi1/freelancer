<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->token)) {
    // تایید توکن ادمین
    $decoded = json_decode(base64_decode($data->token), true);
    
    if ($decoded && $decoded['user_type'] === 'admin' && $decoded['exp'] > time()) {
        // ذخیره توکن در session
        $_SESSION['admin_token'] = $data->token;
        $_SESSION['admin_user'] = $decoded;
        
        http_response_code(200);
        echo json_encode(array("message" => "Session created successfully"));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Invalid token"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Token required"));
}
?>

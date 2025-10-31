<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// تابع برای استخراج اطلاعات کاربر از توکن
function getUserFromToken($token) {
    $decoded = json_decode(base64_decode($token), true);
    if($decoded && isset($decoded['user_id']) && $decoded['exp'] > time()) {
        return $decoded;
    }
    return false;
}

// دریافت هدر Authorization با روش‌های مختلف
$auth_header = '';
$debug_info = [];

// روش اول: getallheaders
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $debug_info['getallheaders_available'] = true;
    $debug_info['all_headers'] = $headers;
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : 
                  (isset($headers['authorization']) ? $headers['authorization'] : '');
} else {
    $debug_info['getallheaders_available'] = false;
}

// روش دوم: $_SERVER
if (empty($auth_header)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        $debug_info['auth_source'] = 'HTTP_AUTHORIZATION';
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        $debug_info['auth_source'] = 'REDIRECT_HTTP_AUTHORIZATION';
    }
}

$debug_info['auth_header'] = $auth_header;
$debug_info['request_method'] = $_SERVER['REQUEST_METHOD'];

// بررسی و استخراج توکن
if(strpos($auth_header, 'Bearer ') === 0) {
    $token = substr($auth_header, 7);
    $debug_info['token_extracted'] = substr($token, 0, 20) . '...'; // نمایش بخشی از توکن
    
    $user_data = getUserFromToken($token);
    
    if($user_data) {
        $debug_info['token_valid'] = true;
        $debug_info['user_data'] = [
            'user_id' => $user_data['user_id'],
            'email' => $user_data['email'],
            'user_type' => $user_data['user_type'],
            'exp' => $user_data['exp'],
            'current_time' => time(),
            'token_expired' => $user_data['exp'] <= time()
        ];
        
        http_response_code(200);
        echo json_encode([
            "message" => "توکن معتبر است",
            "debug" => $debug_info
        ]);
    } else {
        $debug_info['token_valid'] = false;
        $debug_info['token_decode_attempt'] = json_decode(base64_decode($token), true);
        
        http_response_code(401);
        echo json_encode([
            "message" => "توکن نامعتبر است",
            "debug" => $debug_info
        ]);
    }
} else {
    $debug_info['bearer_found'] = false;
    
    http_response_code(401);
    echo json_encode([
        "message" => "توکن ارسال نشده است یا فرمت آن اشتباه است",
        "debug" => $debug_info
    ]);
}
?>

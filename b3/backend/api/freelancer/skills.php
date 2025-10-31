<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../../models/FreelancerSkill.php';
require_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();
$freelancerSkill = new FreelancerSkill($db);

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

// روش اول: getallheaders
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : 
                  (isset($headers['authorization']) ? $headers['authorization'] : '');
}

// روش دوم: $_SERVER
if (empty($auth_header)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
}

// بررسی و استخراج توکن
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
    echo json_encode(array("message" => "توکن ارسال نشده است. Header: " . $auth_header));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    // ثبت مهارت‌ها
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->skills) && is_array($data->skills)) {
        // اعتبارسنجی داده‌ها
        $valid_skills = [];
        foreach($data->skills as $skill) {
            if(isset($skill->class_id) && isset($skill->proficiency_level)) {
                // بررسی سطح تسلط
                if(in_array($skill->proficiency_level, ['beginner', 'intermediate', 'expert'])) {
                    $valid_skills[] = [
                        'class_id' => (int)$skill->class_id,
                        'subclass_id' => isset($skill->subclass_id) && $skill->subclass_id ? (int)$skill->subclass_id : null,
                        'proficiency_level' => $skill->proficiency_level
                    ];
                }
            }
        }
        
        if(empty($valid_skills)) {
            http_response_code(400);
            echo json_encode(array("message" => "هیچ مهارت معتبری یافت نشد."));
            exit();
        }
        
        try {
            if($freelancerSkill->createMultiple($user_data['user_id'], $valid_skills)) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "مهارت‌ها با موفقیت در دیتابیس ذخیره شدند.",
                    "count" => count($valid_skills)
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "خطا در ثبت مهارت‌ها در دیتابیس."));
            }
        } catch(Exception $e) {
            http_response_code(400);
            echo json_encode(array("message" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "داده‌های ناقص ارسال شده است."));
    }
} elseif($method == 'GET') {
    // دریافت مهارت‌های کاربر
    $skills = $freelancerSkill->getByUserId($user_data['user_id']);
    $skill_count = $freelancerSkill->countByUserId($user_data['user_id']);
    
    if($skills !== false) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "مهارت‌ها با موفقیت دریافت شدند.",
            "data" => $skills,
            "count" => $skill_count,
            "can_add_more" => $skill_count < 10
        ));
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "مهارت‌ای یافت نشد."));
    }
} elseif($method == 'PUT') {
    // به‌روزرسانی مهارت
    $data = json_decode(file_get_contents("php://input"));
    
    if(!isset($data->skill_id) || !isset($data->class_id) || !isset($data->proficiency_level)) {
        http_response_code(400);
        echo json_encode(array("message" => "داده‌های ناقص ارسال شده است."));
        exit();
    }
    
    // بررسی سطح تسلط
    if(!in_array($data->proficiency_level, ['beginner', 'intermediate', 'expert'])) {
        http_response_code(400);
        echo json_encode(array("message" => "سطح تسلط نامعتبر است."));
        exit();
    }
    
    // بررسی تکراری بودن مهارت
    $subclass_id = isset($data->subclass_id) && $data->subclass_id ? (int)$data->subclass_id : null;
    if($freelancerSkill->isDuplicate($user_data['user_id'], (int)$data->class_id, $subclass_id, (int)$data->skill_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "این مهارت قبلاً ثبت شده است."));
        exit();
    }
    
    $freelancerSkill->class_id = (int)$data->class_id;
    $freelancerSkill->subclass_id = $subclass_id;
    $freelancerSkill->proficiency_level = $data->proficiency_level;
    
    if($freelancerSkill->update((int)$data->skill_id, $user_data['user_id'])) {
        http_response_code(200);
        echo json_encode(array("message" => "مهارت با موفقیت به‌روزرسانی شد."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "خطا در به‌روزرسانی مهارت."));
    }
} elseif($method == 'DELETE') {
    // حذف مهارت
    $skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : null;
    
    if(!$skill_id) {
        http_response_code(400);
        echo json_encode(array("message" => "شناسه مهارت ارسال نشده است."));
        exit();
    }
    
    if($freelancerSkill->delete($skill_id, $user_data['user_id'])) {
        http_response_code(200);
        echo json_encode(array("message" => "مهارت با موفقیت حذف شد."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "خطا در حذف مهارت."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "متد غیرمجاز."));
}
?> 
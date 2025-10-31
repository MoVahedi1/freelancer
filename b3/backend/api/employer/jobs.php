<?php
// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 0); // غیرفعال کردن نمایش خطاها در خروجی

// تنظیم header های مناسب
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
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

if($method == 'POST') {
    // ثبت آگهی جدید
    try {
        // بررسی وجود فایل‌های مورد نیاز
        if (!file_exists('../../config/database.php')) {
            throw new Exception('فایل database.php یافت نشد');
        }
        
        if (!file_exists('../../models/Job.php')) {
            throw new Exception('فایل Job.php یافت نشد');
        }
        
        if (!file_exists('../../models/JobRequiredSkill.php')) {
            throw new Exception('فایل JobRequiredSkill.php یافت نشد');
        }

        require_once '../../config/database.php';
        require_once '../../models/Job.php';
        require_once '../../models/JobRequiredSkill.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $job = new Job($db);
        $jobRequiredSkill = new JobRequiredSkill($db);

        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        // بررسی داده‌های ورودی
        if(!$data) {
            http_response_code(400);
            echo json_encode(array(
                "message" => "داده‌های JSON نامعتبر است.",
                "received_input" => $input_data
            ));
            exit();
        }

        // اعتبارسنجی فیلدهای اجباری
        if(empty($data->title) || empty($data->description) || empty($data->budget_type)) {
            http_response_code(400);
            echo json_encode(array(
                "message" => "داده‌های ناقص ارسال شده است.",
                "required_fields" => ["title", "description", "budget_type"],
                "received_data" => $data
            ));
            exit();
        }
        
        // اعتبارسنجی نوع بودجه
        if (!in_array($data->budget_type, ['range', 'negotiable'])) {
            http_response_code(400);
            echo json_encode(array(
                "message" => "نوع بودجه نامعتبر است. باید 'range' یا 'negotiable' باشد.",
                "received_budget_type" => $data->budget_type
            ));
            exit();
        }
        
        // اعتبارسنجی بودجه برای نوع بازه‌ای
        if ($data->budget_type === 'range') {
            if (!isset($data->budget_min) || !isset($data->budget_max)) {
                http_response_code(400);
                echo json_encode(array(
                    "message" => "برای نوع بودجه بازه‌ای، حداقل و حداکثر بودجه الزامی است.",
                    "received_data" => $data
                ));
                exit();
            }
            
            if ($data->budget_min >= $data->budget_max) {
                http_response_code(400);
                echo json_encode(array(
                    "message" => "حداقل بودجه باید کمتر از حداکثر بودجه باشد.",
                    "budget_min" => $data->budget_min,
                    "budget_max" => $data->budget_max
                ));
                exit();
            }
        }
        
        // تنظیم مقادیر آگهی برای ذخیره در جدول Jobs
        $job->user_id = $user_data['user_id']; // آیدی کارفرما
        $job->title = trim($data->title); // عنوان آگهی
        $job->description = trim($data->description); // توضیحات آگهی
        $job->budget_type = $data->budget_type; // نوع حقوق (توافقی یا بازه‌ای)
        
        // تنظیم بودجه بر اساس نوع
        if ($data->budget_type === 'range') {
            $job->budget_min = (float)$data->budget_min; // مینیمم برای نوع بازه‌ای
            $job->budget_max = (float)$data->budget_max; // ماکسیمم برای نوع بازه‌ای
        } else {
            $job->budget_min = null; // برای نوع توافقی
            $job->budget_max = null; // برای نوع توافقی
        }
        
        // created_at به صورت خودکار توسط دیتابیس تنظیم می‌شود

        // ایجاد آگهی در جدول Jobs
        $job_id = $job->create();
        
        if($job_id) {
            $skills_saved = true;
            $skills_count = 0;
            $skills_message = "";
            
            // ذخیره مهارت‌های مورد نیاز در جدول JobRequiredSkills
            if(!empty($data->required_skills) && is_array($data->required_skills)) {
                $skills_count = count($data->required_skills);
                
                // اعتبارسنجی مهارت‌ها
                $valid_skills = [];
                foreach($data->required_skills as $skill) {
                    if (!isset($skill->class_id) || !isset($skill->proficiency_level)) {
                        continue;
                    }
                    
                    // اعتبارسنجی سطح تسلط
                    if (!in_array($skill->proficiency_level, ['beginner', 'intermediate', 'expert'])) {
                        continue;
                    }
                    
                    $valid_skills[] = [
                        'class_id' => (int)$skill->class_id,
                        'subclass_id' => isset($skill->subclass_id) && $skill->subclass_id ? (int)$skill->subclass_id : null,
                        'proficiency_level' => $skill->proficiency_level
                    ];
                }
                
                if (!empty($valid_skills)) {
                    $skills_saved = $jobRequiredSkill->createMultiple($job_id, $valid_skills);
                    if(!$skills_saved) {
                        $skills_message = " (خطا در ثبت مهارت‌ها)";
                    }
                } else {
                    $skills_message = " (هیچ مهارت معتبری یافت نشد)";
                }
            }
            
            // ثبت لاگ موفقیت
            error_log("Job created successfully - ID: {$job_id}, User: {$user_data['user_id']}, Title: {$job->title}");
            
            http_response_code(201);
            echo json_encode(array(
                "message" => "آگهی شما با موفقیت ارسال شد!" . $skills_message,
                "job_id" => $job_id,
                "title" => $job->title,
                "budget_type" => $job->budget_type,
                "budget_min" => $job->budget_min,
                "budget_max" => $job->budget_max,
                "skills_saved" => $skills_saved,
                "skills_count" => $skills_count,
                "user_id" => $user_data['user_id'],
                "created_at" => date('Y-m-d H:i:s')
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "خطا در ثبت آگهی."));
        }
    } catch (Exception $e) {
        error_log("Job creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array(
            "message" => "خطا در سرور: " . $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ));
    }
} elseif($method == 'GET') {
    // دریافت آگهی‌های کاربر
    try {
        require_once '../../config/database.php';
        require_once '../../models/Job.php';
        require_once '../../models/JobRequiredSkill.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $job = new Job($db);
        $jobRequiredSkill = new JobRequiredSkill($db);
        
        $jobs = $job->getByUserId($user_data['user_id']);
        
        if($jobs !== false) {
            // دریافت مهارت‌های مورد نیاز برای هر آگهی
            foreach($jobs as &$job_item) {
                $job_item['required_skills'] = $jobRequiredSkill->getByJobId($job_item['job_id']);
            }
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "آگهی‌ها با موفقیت دریافت شدند.",
                "data" => $jobs,
                "count" => count($jobs)
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "آگهی‌ای یافت نشد."));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "message" => "خطا در سرور: " . $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "متد غیرمجاز."));
}
?> 
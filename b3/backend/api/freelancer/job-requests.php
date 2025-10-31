<?php
// API برای مدیریت درخواست‌های کاری کارجویان
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
    
    if(!$user_data || $user_data['user_type'] !== 'freelancer') {
        http_response_code(401);
        echo json_encode(array("message" => "دسترسی غیرمجاز. فقط کارجویان مجاز هستند."));
        exit();
    }
} else {
    http_response_code(401);
    echo json_encode(array("message" => "توکن ارسال نشده است."));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    // ارسال درخواست جدید
    try {
        require_once '../../config/database.php';
        require_once '../../models/JobRequest.php';
        require_once '../../models/SystemMessage.php';
        require_once '../../models/Job.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $jobRequest = new JobRequest($db);
        $job = new Job($db);

        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        // اعتبارسنجی فیلدهای اجباری
        if(empty($data->job_id) || empty($data->message)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه آگهی و پیام الزامی است."));
            exit();
        }

        // بررسی وجود آگهی
        if(!$job->jobExists($data->job_id)) {
            http_response_code(404);
            echo json_encode(array("message" => "آگهی یافت نشد."));
            exit();
        }

        // بررسی وجود درخواست قبلی
        if($jobRequest->requestExists($data->job_id, $user_data['user_id'])) {
            http_response_code(409);
            echo json_encode(array("message" => "شما قبلاً برای این آگهی درخواست ارسال کرده‌اید."));
            exit();
        }

        // تنظیم داده‌های درخواست
        $jobRequest->job_id = $data->job_id;
        $jobRequest->freelancer_id = $user_data['user_id'];
        $jobRequest->message = $data->message;
        $jobRequest->proposed_price = isset($data->proposed_price) ? $data->proposed_price : null;

        $request_id = $jobRequest->create();

        if($request_id) {
            // دریافت اطلاعات آگهی و کارفرما
            $job_info = $job->getById($data->job_id);
            
            if($job_info) {
                // ایجاد پیام برای کارفرما
                SystemMessage::createJobRequestMessage(
                    $db,
                    $job_info['user_id'], // employer_id
                    $user_data['user_id'], // freelancer_id
                    $data->job_id,
                    $request_id,
                    $job_info['title'],
                    $user_data['first_name'] . ' ' . $user_data['last_name']
                );
            }

            http_response_code(201);
            echo json_encode(array(
                "message" => "درخواست شما با موفقیت ارسال شد!",
                "request_id" => $request_id
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "خطا در ارسال درخواست."));
        }

    } catch (Exception $e) {
        error_log("Job request creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
    }

} elseif($method == 'GET') {
    // دریافت درخواست‌های کارجو
    try {
        require_once '../../config/database.php';
        require_once '../../models/JobRequest.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $jobRequest = new JobRequest($db);
        $requests = $jobRequest->getByFreelancerId($user_data['user_id']);

        if($requests !== false) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "درخواست‌ها با موفقیت دریافت شدند.",
                "data" => $requests,
                "count" => count($requests)
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "درخواستی یافت نشد."));
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

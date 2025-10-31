<?php
// API برای مدیریت درخواست‌های کاری کارفرمایان
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
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
    
    if(!$user_data || $user_data['user_type'] !== 'employer') {
        http_response_code(401);
        echo json_encode(array("message" => "دسترسی غیرمجاز. فقط کارفرمایان مجاز هستند."));
        exit();
    }
} else {
    http_response_code(401);
    echo json_encode(array("message" => "توکن ارسال نشده است."));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'GET') {
    // دریافت درخواست‌های آگهی‌های کارفرما
    try {
        require_once '../../config/database.php';
        require_once '../../models/JobRequest.php';
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

        // دریافت job_id از query parameter
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

        if($job_id) {
            // بررسی اینکه آگهی متعلق به این کارفرما است
            $job_info = $job->getById($job_id);
            if(!$job_info || $job_info['user_id'] != $user_data['user_id']) {
                http_response_code(403);
                echo json_encode(array("message" => "دسترسی به این آگهی ندارید."));
                exit();
            }

            // دریافت درخواست‌های این آگهی
            $requests = $jobRequest->getByJobId($job_id);
        } else {
            // دریافت تمام درخواست‌های آگهی‌های این کارفرما
            $employer_jobs = $job->getByUserId($user_data['user_id']);
            $requests = array();
            
            if($employer_jobs) {
                foreach($employer_jobs as $employer_job) {
                    $job_requests = $jobRequest->getByJobId($employer_job['job_id']);
                    if($job_requests) {
                        foreach($job_requests as $request) {
                            $request['job_title'] = $employer_job['title'];
                            $requests[] = $request;
                        }
                    }
                }
            }
        }

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

} elseif($method == 'PUT') {
    // تصمیم‌گیری روی درخواست (قبول/رد)
    try {
        require_once '../../config/database.php';
        require_once '../../models/JobRequest.php';
        require_once '../../models/SystemMessage.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
            exit();
        }
        
        $jobRequest = new JobRequest($db);

        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        // اعتبارسنجی فیلدهای اجباری
        if(empty($data->request_id) || empty($data->status)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه درخواست و وضعیت الزامی است."));
            exit();
        }

        if(!in_array($data->status, ['accepted', 'rejected'])) {
            http_response_code(400);
            echo json_encode(array("message" => "وضعیت باید 'accepted' یا 'rejected' باشد."));
            exit();
        }

        // دریافت اطلاعات درخواست
        $request_info = $jobRequest->getById($data->request_id);
        if(!$request_info) {
            http_response_code(404);
            echo json_encode(array("message" => "درخواست یافت نشد."));
            exit();
        }

        // به‌روزرسانی وضعیت درخواست
        if($jobRequest->updateStatus($data->request_id, $data->status, $user_data['user_id'])) {
            // ایجاد پیام برای کارجو
            SystemMessage::createRequestResponseMessage(
                $db,
                $request_info['freelancer_id'],
                $user_data['user_id'],
                $request_info['job_id'],
                $data->request_id,
                $request_info['job_title'],
                $data->status
            );

            $status_text = $data->status === 'accepted' ? 'پذیرفته' : 'رد';
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "درخواست با موفقیت {$status_text} شد.",
                "request_id" => $data->request_id,
                "status" => $data->status
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "خطا در به‌روزرسانی درخواست."));
        }

    } catch (Exception $e) {
        error_log("Job request update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
    }

} else {
    http_response_code(405);
    echo json_encode(array("message" => "متد غیرمجاز."));
}
?>

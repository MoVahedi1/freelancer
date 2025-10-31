<?php
// API برای مدیریت پروژه‌های در حال انجام کارجویان
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

if($method == 'GET') {
    // دریافت پروژه‌های در حال انجام کارجو
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
        
        // دریافت پروژه‌های پذیرفته شده کارجو
        $query = "SELECT 
                    jr.request_id,
                    jr.job_id,
                    jr.proposed_price,
                    jr.updated_at as accepted_at,
                    j.title as job_title,
                    j.description as job_description,
                    j.user_id as employer_id,
                    u.first_name as employer_first_name,
                    u.last_name as employer_last_name,
                    CONCAT(u.first_name, ' ', u.last_name) as employer_name
                  FROM JobRequests jr
                  JOIN Jobs j ON jr.job_id = j.job_id
                  JOIN Users u ON j.user_id = u.user_id
                  WHERE jr.freelancer_id = ? AND jr.status = 'accepted'
                  ORDER BY jr.updated_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_data['user_id']);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($projects !== false) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "پروژه‌های در حال انجام با موفقیت دریافت شدند.",
                "data" => $projects,
                "count" => count($projects)
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "پروژه‌ای در حال انجام یافت نشد."));
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

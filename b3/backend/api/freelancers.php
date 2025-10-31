<?php
// API برای جستجوی کارجویان
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
    
    if(!$user_data) {
        http_response_code(401);
        echo json_encode(array("message" => "توکن نامعتبر است."));
        exit();
    }
    
    // بررسی نوع کاربر - فقط کارفرمایان می‌توانند کارجویان را جستجو کنند
    if($user_data['user_type'] !== 'employer') {
        http_response_code(403);
        echo json_encode(array("message" => "فقط کارفرمایان می‌توانند کارجویان را جستجو کنند."));
        exit();
    }
} else {
    http_response_code(401);
    echo json_encode(array("message" => "توکن ارسال نشده است."));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
        exit();
    }
    
    // بررسی نوع کاربر - فقط کارفرمایان می‌توانند کارجویان را جستجو کنند
    if($user_data['user_type'] !== 'employer') {
        http_response_code(403);
        echo json_encode(array("message" => "فقط کارفرمایان می‌توانند کارجویان را جستجو کنند."));
        exit();
    }

    if($method == 'GET') {
        // پارامترهای جستجو
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;

        // ساخت کوئری جستجو
        $where_conditions = ["u.user_type = 'freelancer'"];
        $params = [];
        $param_types = "";

        // جستجو بر اساس نام
        if (!empty($search)) {
            $where_conditions[] = "(
                u.first_name LIKE ? OR 
                u.last_name LIKE ? OR 
                CONCAT(u.first_name, ' ', u.last_name) LIKE ?
            )";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
            $param_types .= "sss";
        }
        
        // فیلتر بر اساس کلاس شغلی
        if ($skill_id > 0) {
            $where_conditions[] = "fs.class_id = ?";
            $params[] = $skill_id;
            $param_types .= "i";
        }

        $where_clause = implode(" AND ", $where_conditions);

        // کوئری اصلی برای دریافت کارجویان
        $query = "SELECT DISTINCT
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    u.created_at,
                    GROUP_CONCAT(
                        DISTINCT jc.class_name SEPARATOR ', '
                    ) as skills
                  FROM Users u
                  LEFT JOIN FreelancerSkills fs ON u.user_id = fs.user_id
                  LEFT JOIN JobClasses jc ON fs.class_id = jc.class_id
                  WHERE {$where_clause}
                  GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.created_at
                  ORDER BY u.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $db->prepare($query);
        
        // باند کردن پارامترها
        $all_params = array_merge($params, [$limit, $offset]);
        $all_param_types = $param_types . "ii";

        if (!empty($all_params)) {
            $stmt->bind_param($all_param_types, ...$all_params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $freelancers = $result->fetch_all(MYSQLI_ASSOC);

        // شمارش کل نتایج
        $count_query = "SELECT COUNT(DISTINCT u.user_id) as total
                       FROM Users u
                       LEFT JOIN FreelancerSkills fs ON u.user_id = fs.user_id
                       LEFT JOIN JobClasses jc ON fs.class_id = jc.class_id
                       WHERE {$where_clause}";

        $count_stmt = $db->prepare($count_query);
        if (!empty($params)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()['total'];

        // پردازش نتایج
        foreach ($freelancers as &$freelancer) {
            $freelancer['user_id'] = (int)$freelancer['user_id'];
            $freelancer['skills'] = $freelancer['skills'] ? $freelancer['skills'] : 'بدون مهارت ثبت شده';
        }

        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "کارجویان با موفقیت دریافت شدند.",
            "freelancers" => $freelancers,
            "pagination" => array(
                "current_page" => $page,
                "total_count" => (int)$total_count,
                "per_page" => $limit,
                "total_pages" => ceil($total_count / $limit)
            )
        ));
    }

} catch (Exception $e) {
    error_log("Freelancers search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
}
?>

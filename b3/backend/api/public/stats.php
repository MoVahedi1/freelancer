<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array("message" => "خطا در اتصال به دیتابیس"));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // تعداد کل کاربران فعال (فریلنسرها)
        $freelancersQuery = "SELECT COUNT(*) as count FROM Users WHERE user_type = 'freelancer' AND status = 'active'";
        $freelancersStmt = $db->prepare($freelancersQuery);
        $freelancersStmt->execute();
        $freelancersCount = $freelancersStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // تعداد کل کارفرمایان فعال
        $employersQuery = "SELECT COUNT(*) as count FROM Users WHERE user_type = 'employer' AND status = 'active'";
        $employersStmt = $db->prepare($employersQuery);
        $employersStmt->execute();
        $employersCount = $employersStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // تعداد کل پروژه‌های تکمیل شده
        $completedJobsQuery = "SELECT COUNT(*) as count FROM Jobs WHERE status = 'completed' AND title IS NOT NULL";
        $completedJobsStmt = $db->prepare($completedJobsQuery);
        $completedJobsStmt->execute();
        $completedJobsCount = $completedJobsStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // تعداد کل آگهی‌های فعال
        $activeJobsQuery = "SELECT COUNT(*) as count FROM Jobs WHERE status = 'active' AND title IS NOT NULL";
        $activeJobsStmt = $db->prepare($activeJobsQuery);
        $activeJobsStmt->execute();
        $activeJobsCount = $activeJobsStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // تعداد کل کاربران (همه انواع)
        $totalUsersQuery = "SELECT COUNT(*) as count FROM Users WHERE status = 'active'";
        $totalUsersStmt = $db->prepare($totalUsersQuery);
        $totalUsersStmt->execute();
        $totalUsersCount = $totalUsersStmt->fetch(PDO::FETCH_ASSOC)['count'];

        // محاسبه درصد رضایت (فرضی بر اساس پروژه‌های تکمیل شده)
        $totalJobsQuery = "SELECT COUNT(*) as count FROM Jobs WHERE title IS NOT NULL";
        $totalJobsStmt = $db->prepare($totalJobsQuery);
        $totalJobsStmt->execute();
        $totalJobsCount = $totalJobsStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $satisfactionRate = $totalJobsCount > 0 ? round(($completedJobsCount / $totalJobsCount) * 100) : 95;
        if ($satisfactionRate < 85) $satisfactionRate = 95; // حداقل 95%

        $stats = array(
            "freelancers" => (int)$freelancersCount,
            "employers" => (int)$employersCount,
            "completed_projects" => (int)$completedJobsCount,
            "active_jobs" => (int)$activeJobsCount,
            "total_users" => (int)$totalUsersCount,
            "satisfaction_rate" => (int)$satisfactionRate,
            "success" => true
        );

        echo json_encode($stats, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "message" => "خطا در دریافت آمار",
            "error" => $e->getMessage(),
            "success" => false
        ), JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "متد غیرمجاز"));
}
?>

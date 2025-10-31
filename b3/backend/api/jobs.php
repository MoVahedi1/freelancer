<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../models/Job.php';
require_once '../models/JobRequiredSkill.php';

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);
$jobRequiredSkill = new JobRequiredSkill($db);

// دریافت پارامترهای صفحه‌بندی
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// دریافت تمام آگهی‌ها
$jobs = $job->getAll($limit, $offset);

if($jobs) {
    // دریافت مهارت‌های مورد نیاز برای هر آگهی
    foreach($jobs as &$job_item) {
        $job_item['required_skills'] = $jobRequiredSkill->getByJobId($job_item['job_id']);
    }
    
    http_response_code(200);
    echo json_encode(array(
        "message" => "آگهی‌ها با موفقیت دریافت شدند.",
        "data" => $jobs,
        "pagination" => array(
            "page" => $page,
            "limit" => $limit,
            "offset" => $offset
        )
    ));
} else {
    http_response_code(404);
    echo json_encode(array("message" => "آگهی‌ای یافت نشد."));
}
?> 
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../../models/JobClass.php';

$database = new Database();
$db = $database->getConnection();
$jobClass = new JobClass($db);

// دریافت تمام کلاس‌های شغلی
$classes = $jobClass->getAll();

if($classes) {
    http_response_code(200);
    echo json_encode(array(
        "message" => "کلاس‌های شغلی با موفقیت دریافت شدند.",
        "data" => $classes
    ));
} else {
    http_response_code(404);
    echo json_encode(array("message" => "کلاس شغلی‌ای یافت نشد."));
}
?> 
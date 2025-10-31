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

// دریافت class_id از URL
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;

if($class_id) {
    // دریافت زیرکلاس‌های کلاس مشخص
    $subclasses = $jobClass->getSubClasses($class_id);
    
    if($subclasses) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "زیرکلاس‌ها با موفقیت دریافت شدند.",
            "data" => $subclasses
        ));
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "زیرکلاس‌ای یافت نشد."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "شناسه کلاس ارسال نشده است."));
}
?> 
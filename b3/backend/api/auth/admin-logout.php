<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// حذف session ادمین
if (isset($_SESSION['admin_token'])) {
    unset($_SESSION['admin_token']);
    unset($_SESSION['admin_user']);
    session_destroy();
}

http_response_code(200);
echo json_encode(array("message" => "خروج موفقیت‌آمیز"));
?>

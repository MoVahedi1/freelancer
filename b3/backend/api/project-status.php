<?php
// API برای مدیریت وضعیت پروژه‌ها
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

    if($method == 'PUT') {
        // تغییر وضعیت پروژه
        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        if(empty($data->project_id) || empty($data->status) || empty($data->action)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه پروژه، وضعیت و عملیات الزامی است."));
            exit();
        }

        $project_id = $data->project_id;
        $new_status = $data->status;
        $action = $data->action;

        // بررسی دسترسی به پروژه
        $access_query = "SELECT jr.request_id, jr.freelancer_id, j.user_id as employer_id, j.title as job_title
                        FROM JobRequests jr
                        JOIN Jobs j ON jr.job_id = j.job_id
                        WHERE jr.request_id = ? AND jr.status = 'accepted'
                        AND (jr.freelancer_id = ? OR j.user_id = ?)";
        
        $access_stmt = $db->prepare($access_query);
        $access_stmt->bindParam(1, $project_id);
        $access_stmt->bindParam(2, $user_data['user_id']);
        $access_stmt->bindParam(3, $user_data['user_id']);
        $access_stmt->execute();
        
        $project = $access_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            http_response_code(403);
            echo json_encode(array("message" => "دسترسی به این پروژه ندارید."));
            exit();
        }

        $db->beginTransaction();

        try {
            if ($action === 'complete' && $user_data['user_type'] === 'freelancer') {
                // کارجو پروژه را تکمیل می‌کند
                $update_query = "UPDATE JobRequests SET status = 'completed', updated_at = NOW() WHERE request_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $project_id);
                
                if ($update_stmt->execute()) {
                    // ایجاد پیام اطلاع‌رسانی برای کارفرما
                    $message_content = "پروژه توسط کارجو تکمیل شد و در انتظار تایید شماست.";
                    $chat_query = "INSERT INTO ProjectChat (project_id, sender_id, content, message_type, created_at) 
                                  VALUES (?, ?, ?, 'system', NOW())";
                    
                    $chat_stmt = $db->prepare($chat_query);
                    $chat_stmt->bindParam(1, $project_id);
                    $chat_stmt->bindParam(2, $user_data['user_id']);
                    $chat_stmt->bindParam(3, $message_content);
                    $chat_stmt->execute();
                    
                    // ایجاد پیام سیستمی برای کارفرما
                    require_once '../models/SystemMessage.php';
                    SystemMessage::createProjectCompletionMessage(
                        $db,
                        $project['employer_id'],
                        $user_data['user_id'],
                        $project_id,
                        $project['job_title']
                    );
                }
                
            } elseif ($action === 'approve' && $user_data['user_type'] === 'employer') {
                // کارفرما پروژه را تایید می‌کند
                $update_query = "UPDATE JobRequests SET status = 'delivered', updated_at = NOW() WHERE request_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $project_id);
                
                if ($update_stmt->execute()) {
                    // حذف آگهی از فهرست عمومی (اختیاری - بر اساس نیاز)
                    $job_update_query = "UPDATE Jobs SET status = 'completed' WHERE job_id = (SELECT job_id FROM JobRequests WHERE request_id = ?)";
                    $job_update_stmt = $db->prepare($job_update_query);
                    $job_update_stmt->bindParam(1, $project_id);
                    $job_update_stmt->execute();
                    
                    // ایجاد پیام اطلاع‌رسانی
                    $message_content = "پروژه توسط کارفرما تایید شد و با موفقیت تحویل داده شد.";
                    $chat_query = "INSERT INTO ProjectChat (project_id, sender_id, content, message_type, created_at) 
                                  VALUES (?, ?, ?, 'system', NOW())";
                    
                    $chat_stmt = $db->prepare($chat_query);
                    $chat_stmt->bindParam(1, $project_id);
                    $chat_stmt->bindParam(2, $user_data['user_id']);
                    $chat_stmt->bindParam(3, $message_content);
                    $chat_stmt->execute();
                    
                    // ایجاد پیام سیستمی برای کارجو
                    require_once '../models/SystemMessage.php';
                    SystemMessage::createProjectDeliveryMessage(
                        $db,
                        $project['freelancer_id'],
                        $user_data['user_id'],
                        $project_id,
                        $project['job_title']
                    );
                }
                
            } elseif ($action === 'request_revision' && $user_data['user_type'] === 'employer') {
                // کارفرما درخواست اصلاح می‌کند
                $revision_message = isset($data->message) ? $data->message : "کارفرما درخواست اصلاح کرده است.";
                
                $update_query = "UPDATE JobRequests SET status = 'ongoing', updated_at = NOW() WHERE request_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $project_id);
                
                if ($update_stmt->execute()) {
                    // ایجاد پیام درخواست اصلاح
                    $chat_query = "INSERT INTO ProjectChat (project_id, sender_id, content, message_type, created_at) 
                                  VALUES (?, ?, ?, 'revision', NOW())";
                    
                    $chat_stmt = $db->prepare($chat_query);
                    $chat_stmt->bindParam(1, $project_id);
                    $chat_stmt->bindParam(2, $user_data['user_id']);
                    $chat_stmt->bindParam(3, $revision_message);
                    $chat_stmt->execute();
                }
                
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "عملیات نامعتبر یا عدم دسترسی."));
                $db->rollBack();
                exit();
            }

            $db->commit();
            
            $status_text = '';
            switch($new_status) {
                case 'completed': $status_text = 'تکمیل شده'; break;
                case 'delivered': $status_text = 'تحویل داده شده'; break;
                case 'ongoing': $status_text = 'در حال انجام'; break;
                default: $status_text = $new_status;
            }
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "وضعیت پروژه با موفقیت به '{$status_text}' تغییر یافت.",
                "new_status" => $new_status
            ));

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

} catch (Exception $e) {
    error_log("Project status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
}
?>

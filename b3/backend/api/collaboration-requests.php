<?php
// API برای مدیریت درخواست‌های همکاری
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

    if($method == 'POST') {
        // ارسال درخواست همکاری جدید
        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        if(empty($data->freelancer_id) || empty($data->message)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه کارجو و پیام الزامی است."));
            exit();
        }

        // بررسی نوع کاربر - فقط کارفرمایان می‌توانند درخواست همکاری ارسال کنند
        if($user_data['user_type'] !== 'employer') {
            http_response_code(403);
            echo json_encode(array("message" => "فقط کارفرمایان می‌توانند درخواست همکاری ارسال کنند."));
            exit();
        }

        // بررسی وجود کارجو
        $freelancer_check = "SELECT user_id, first_name, last_name FROM Users WHERE user_id = ? AND user_type = 'freelancer'";
        $stmt = $db->prepare($freelancer_check);
        $stmt->bindParam(1, $data->freelancer_id);
        $stmt->execute();
        $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$freelancer) {
            http_response_code(404);
            echo json_encode(array("message" => "کارجوی مورد نظر یافت نشد."));
            exit();
        }

        // بررسی عدم وجود درخواست قبلی
        $existing_check = "SELECT collaboration_id FROM CollaborationRequests 
                          WHERE employer_id = ? AND freelancer_id = ? AND status IN ('pending', 'accepted')";
        $stmt = $db->prepare($existing_check);
        $stmt->bindParam(1, $user_data['user_id']);
        $stmt->bindParam(2, $data->freelancer_id);
        $stmt->execute();

        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(array("message" => "شما قبلاً برای این کارجو درخواست همکاری ارسال کرده‌اید."));
            exit();
        }

        // ایجاد درخواست همکاری جدید
        $insert_query = "INSERT INTO CollaborationRequests (employer_id, freelancer_id, message, proposed_budget, status, created_at) 
                        VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $db->prepare($insert_query);
        $proposed_budget = isset($data->proposed_budget) ? $data->proposed_budget : null;
        
        $stmt->bindParam(1, $user_data['user_id']);
        $stmt->bindParam(2, $data->freelancer_id);
        $stmt->bindParam(3, $data->message);
        $stmt->bindParam(4, $proposed_budget);

        if ($stmt->execute()) {
            $collaboration_id = $db->lastInsertId();
            
            // ایجاد پیام سیستمی برای کارجو
            require_once '../models/SystemMessage.php';
            SystemMessage::createCollaborationRequestMessage(
                $db,
                $data->freelancer_id,
                $user_data['user_id'],
                $collaboration_id,
                $user_data['first_name'] . ' ' . $user_data['last_name']
            );

            http_response_code(201);
            echo json_encode(array(
                "message" => "درخواست همکاری با موفقیت ارسال شد.",
                "collaboration_id" => $collaboration_id
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در ارسال درخواست همکاری."));
        }

    } elseif($method == 'GET') {
        // دریافت درخواست‌های همکاری
        $type = isset($_GET['type']) ? $_GET['type'] : 'all'; // sent, received, all
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;

        if ($type === 'sent' && $user_data['user_type'] === 'employer') {
            // درخواست‌های ارسالی کارفرما
            $query = "SELECT 
                        cr.collaboration_id,
                        cr.freelancer_id,
                        cr.message,
                        cr.proposed_budget,
                        cr.status,
                        cr.created_at,
                        cr.updated_at,
                        u.first_name as freelancer_first_name,
                        u.last_name as freelancer_last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as freelancer_name
                      FROM CollaborationRequests cr
                      JOIN Users u ON cr.freelancer_id = u.user_id
                      WHERE cr.employer_id = ?
                      ORDER BY cr.created_at DESC
                      LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $user_data['user_id']);
            $stmt->bindParam(2, $limit);
            $stmt->bindParam(3, $offset);

        } elseif ($type === 'received' && $user_data['user_type'] === 'freelancer') {
            // درخواست‌های دریافتی کارجو
            $query = "SELECT 
                        cr.collaboration_id,
                        cr.employer_id,
                        cr.message,
                        cr.proposed_budget,
                        cr.status,
                        cr.created_at,
                        cr.updated_at,
                        u.first_name as employer_first_name,
                        u.last_name as employer_last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as employer_name
                      FROM CollaborationRequests cr
                      JOIN Users u ON cr.employer_id = u.user_id
                      WHERE cr.freelancer_id = ?
                      ORDER BY cr.created_at DESC
                      LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $user_data['user_id']);
            $stmt->bindParam(2, $limit);
            $stmt->bindParam(3, $offset);

        } else {
            http_response_code(400);
            echo json_encode(array("message" => "نوع درخواست نامعتبر است."));
            exit();
        }

        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // پردازش نتایج
        foreach ($requests as &$request) {
            $request['collaboration_id'] = (int)$request['collaboration_id'];
            $request['proposed_budget'] = $request['proposed_budget'] ? (float)$request['proposed_budget'] : null;
        }

        http_response_code(200);
        echo json_encode(array(
            "message" => "درخواست‌های همکاری با موفقیت دریافت شدند.",
            "data" => $requests
        ));

    } elseif($method == 'PUT') {
        // تغییر وضعیت درخواست همکاری
        $input_data = file_get_contents("php://input");
        $data = json_decode($input_data);

        if(!$data) {
            http_response_code(400);
            echo json_encode(array("message" => "داده‌های JSON نامعتبر است."));
            exit();
        }

        if(empty($data->collaboration_id) || empty($data->status)) {
            http_response_code(400);
            echo json_encode(array("message" => "شناسه درخواست و وضعیت الزامی است."));
            exit();
        }

        // بررسی وجود درخواست و دسترسی
        $check_query = "SELECT cr.*, u.first_name, u.last_name 
                       FROM CollaborationRequests cr
                       JOIN Users u ON cr.employer_id = u.user_id
                       WHERE cr.collaboration_id = ? AND cr.freelancer_id = ?";
        
        $stmt = $db->prepare($check_query);
        $stmt->bindParam(1, $data->collaboration_id);
        $stmt->bindParam(2, $user_data['user_id']);
        $stmt->execute();
        $collaboration = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$collaboration) {
            http_response_code(404);
            echo json_encode(array("message" => "درخواست همکاری یافت نشد یا دسترسی ندارید."));
            exit();
        }

        // تغییر وضعیت
        $update_query = "UPDATE CollaborationRequests SET status = ?, updated_at = NOW() WHERE collaboration_id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(1, $data->status);
        $stmt->bindParam(2, $data->collaboration_id);

        if ($stmt->execute()) {
            // ایجاد پیام سیستمی برای کارفرما
            require_once '../models/SystemMessage.php';
            SystemMessage::createCollaborationResponseMessage(
                $db,
                $collaboration['employer_id'],
                $user_data['user_id'],
                $data->collaboration_id,
                $user_data['first_name'] . ' ' . $user_data['last_name'],
                $data->status
            );

            http_response_code(200);
            echo json_encode(array(
                "message" => "وضعیت درخواست همکاری با موفقیت تغییر یافت.",
                "new_status" => $data->status
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "خطا در تغییر وضعیت درخواست."));
        }
    }

} catch (Exception $e) {
    error_log("Collaboration requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "خطا در سرور: " . $e->getMessage()));
}
?>

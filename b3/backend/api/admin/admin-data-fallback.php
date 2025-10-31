<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Fallback admin API - works without database
function checkAdminAuth() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(array("message" => "توکن احراز هویت مورد نیاز است."));
        exit();
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = json_decode(base64_decode($token), true);
    
    if (!$decoded || !isset($decoded['email']) || $decoded['email'] !== 'mohammadvahediorg@gmail.com') {
        http_response_code(401);
        echo json_encode(array("message" => "دسترسی غیرمجاز."));
        exit();
    }
    
    return $decoded;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// بررسی احراز هویت ادمین
checkAdminAuth();

switch($method) {
    case 'GET':
        handleGet($action);
        break;
    case 'POST':
        handlePost($action);
        break;
    case 'PUT':
        handlePut($action);
        break;
    case 'DELETE':
        handleDelete($action);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "متد غیرمجاز"));
        break;
}

function handleGet($action) {
    switch($action) {
        case 'users':
            getAllUsers();
            break;
        case 'jobs':
            getAllJobs();
            break;
        case 'projects':
            getAllProjects();
            break;
        case 'requests':
            getAllRequests();
            break;
        case 'messages':
            getAllMessages();
            break;
        case 'stats':
            getStats();
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handlePost($action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'user':
            createUser($input);
            break;
        case 'job':
            createJob($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handlePut($action) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    
    switch($action) {
        case 'user':
            updateUser($id, $input);
            break;
        case 'job':
            updateJob($id, $input);
            break;
        case 'project':
            updateProject($id, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

function handleDelete($action) {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    
    switch($action) {
        case 'user':
            deleteUser($id);
            break;
        case 'job':
            deleteJob($id);
            break;
        case 'project':
            deleteProject($id);
            break;
        case 'request':
            deleteRequest($id);
            break;
        case 'message':
            deleteMessage($id);
            break;
        default:
            http_response_code(400);
            echo json_encode(array("message" => "عملیات نامعتبر"));
            break;
    }
}

// Sample data functions
function getAllUsers() {
    $users = [
        [
            'user_id' => 1,
            'first_name' => 'احمد',
            'last_name' => 'محمدی',
            'email' => 'ahmad@example.com',
            'user_type' => 'employer',
            'company_name' => 'شرکت نمونه',
            'created_at' => '2024-01-15 10:30:00'
        ],
        [
            'user_id' => 2,
            'first_name' => 'فاطمه',
            'last_name' => 'احمدی',
            'email' => 'fateme@example.com',
            'user_type' => 'job_seeker',
            'company_name' => null,
            'created_at' => '2024-01-20 14:15:00'
        ],
        [
            'user_id' => 3,
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'email' => 'ali@example.com',
            'user_type' => 'job_seeker',
            'company_name' => null,
            'created_at' => '2024-02-01 09:45:00'
        ]
    ];
    
    echo json_encode(array("users" => $users));
}

function getAllJobs() {
    $jobs = [
        [
            'job_id' => 1,
            'title' => 'طراحی وب‌سایت فروشگاهی',
            'description' => 'نیاز به طراحی یک وب‌سایت فروشگاهی مدرن',
            'budget' => 5000000,
            'status' => 'open',
            'employer_id' => 1,
            'first_name' => 'احمد',
            'last_name' => 'محمدی',
            'company_name' => 'شرکت نمونه',
            'created_at' => '2024-02-10 11:00:00'
        ],
        [
            'job_id' => 2,
            'title' => 'توسعه اپلیکیشن موبایل',
            'description' => 'ساخت اپلیکیشن موبایل برای iOS و Android',
            'budget' => 8000000,
            'status' => 'in_progress',
            'employer_id' => 1,
            'first_name' => 'احمد',
            'last_name' => 'محمدی',
            'company_name' => 'شرکت نمونه',
            'created_at' => '2024-02-15 16:30:00'
        ]
    ];
    
    echo json_encode(array("jobs" => $jobs));
}

function getAllProjects() {
    $projects = [
        [
            'request_id' => 1,
            'job_id' => 2,
            'job_title' => 'توسعه اپلیکیشن موبایل',
            'employer_first_name' => 'احمد',
            'employer_last_name' => 'محمدی',
            'freelancer_first_name' => 'فاطمه',
            'freelancer_last_name' => 'احمدی',
            'status' => 'ongoing',
            'proposed_price' => 7500000,
            'created_at' => '2024-02-20 10:15:00'
        ]
    ];
    
    echo json_encode(array("projects" => $projects));
}

function getAllRequests() {
    $requests = [
        [
            'request_id' => 1,
            'job_id' => 1,
            'job_title' => 'طراحی وب‌سایت فروشگاهی',
            'employer_first_name' => 'احمد',
            'employer_last_name' => 'محمدی',
            'freelancer_first_name' => 'علی',
            'freelancer_last_name' => 'رضایی',
            'status' => 'pending',
            'proposed_price' => 4500000,
            'created_at' => '2024-02-12 14:20:00'
        ],
        [
            'request_id' => 2,
            'job_id' => 2,
            'job_title' => 'توسعه اپلیکیشن موبایل',
            'employer_first_name' => 'احمد',
            'employer_last_name' => 'محمدی',
            'freelancer_first_name' => 'فاطمه',
            'freelancer_last_name' => 'احمدی',
            'status' => 'accepted',
            'proposed_price' => 7500000,
            'created_at' => '2024-02-18 09:30:00'
        ]
    ];
    
    echo json_encode(array("requests" => $requests));
}

function getAllMessages() {
    $messages = [
        [
            'message_id' => 1,
            'sender_id' => 1,
            'receiver_id' => 2,
            'sender_first_name' => 'احمد',
            'sender_last_name' => 'محمدی',
            'receiver_first_name' => 'فاطمه',
            'receiver_last_name' => 'احمدی',
            'subject' => 'درخواست همکاری',
            'message' => 'سلام، علاقه‌مند به همکاری در پروژه هستم',
            'created_at' => '2024-02-16 13:45:00'
        ],
        [
            'message_id' => 2,
            'sender_id' => 2,
            'receiver_id' => 1,
            'sender_first_name' => 'فاطمه',
            'sender_last_name' => 'احمدی',
            'receiver_first_name' => 'احمد',
            'receiver_last_name' => 'محمدی',
            'subject' => 'پاسخ درخواست',
            'message' => 'سلام، بله حتماً. لطفاً جزئیات بیشتری ارسال کنید',
            'created_at' => '2024-02-16 15:20:00'
        ]
    ];
    
    echo json_encode(array("messages" => $messages));
}

function getStats() {
    $stats = [
        'users' => 3,
        'jobs' => 2,
        'projects' => 1,
        'requests' => 2,
        'messages' => 2
    ];
    
    echo json_encode(array("stats" => $stats));
}

function createUser($data) {
    // Simulate user creation
    $newUser = [
        'user_id' => rand(100, 999),
        'first_name' => $data['first_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
        'email' => $data['email'] ?? '',
        'user_type' => $data['user_type'] ?? 'job_seeker',
        'company_name' => $data['company_name'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode(array("message" => "کاربر با موفقیت ایجاد شد", "user" => $newUser));
}

function createJob($data) {
    // Simulate job creation
    $newJob = [
        'job_id' => rand(100, 999),
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'budget' => $data['budget'] ?? 0,
        'employer_id' => $data['employer_id'] ?? 1,
        'status' => 'open',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode(array("message" => "آگهی با موفقیت ایجاد شد", "job" => $newJob));
}

function updateUser($id, $data) {
    echo json_encode(array("message" => "کاربر با موفقیت به‌روزرسانی شد", "user_id" => $id));
}

function updateJob($id, $data) {
    echo json_encode(array("message" => "آگهی با موفقیت به‌روزرسانی شد", "job_id" => $id));
}

function updateProject($id, $data) {
    echo json_encode(array("message" => "پروژه با موفقیت به‌روزرسانی شد", "project_id" => $id));
}

function deleteUser($id) {
    echo json_encode(array("message" => "کاربر با موفقیت حذف شد", "user_id" => $id));
}

function deleteJob($id) {
    echo json_encode(array("message" => "آگهی با موفقیت حذف شد", "job_id" => $id));
}

function deleteProject($id) {
    echo json_encode(array("message" => "پروژه با موفقیت حذف شد", "project_id" => $id));
}

function deleteRequest($id) {
    echo json_encode(array("message" => "درخواست با موفقیت حذف شد", "request_id" => $id));
}

function deleteMessage($id) {
    echo json_encode(array("message" => "پیام با موفقیت حذف شد", "message_id" => $id));
}
?>

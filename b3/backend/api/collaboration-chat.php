<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../models/User.php';

// Authentication
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'توکن احراز هویت مورد نیاز است']);
    exit();
}

$token = $matches[1];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify token and get user
    $user = new User($db);
    $userData = $user->validateToken($token);
    
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['error' => 'توکن نامعتبر است']);
        exit();
    }
    
    $currentUserId = $userData['user_id'];
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($db, $currentUserId);
            break;
        case 'POST':
            handlePostRequest($db, $currentUserId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'متد غیرمجاز']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطای سرور: ' . $e->getMessage()]);
}

function handleGetRequest($db, $currentUserId) {
    $collaborationId = $_GET['collaboration_id'] ?? null;
    $action = $_GET['action'] ?? 'messages';
    
    if (!$collaborationId) {
        http_response_code(400);
        echo json_encode(['error' => 'شناسه همکاری مورد نیاز است']);
        return;
    }
    
    // Verify user has access to this collaboration
    if (!hasCollaborationAccess($db, $collaborationId, $currentUserId)) {
        http_response_code(403);
        echo json_encode(['error' => 'دسترسی غیرمجاز']);
        return;
    }
    
    if ($action === 'messages') {
        getMessages($db, $collaborationId);
    } else if ($action === 'info') {
        getCollaborationInfo($db, $collaborationId);
    }
}

function handlePostRequest($db, $currentUserId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'داده‌های ورودی نامعتبر']);
        return;
    }
    
    $collaborationId = $input['collaboration_id'] ?? null;
    $content = $input['content'] ?? '';
    $messageType = $input['message_type'] ?? 'text';
    
    if (!$collaborationId || !$content) {
        http_response_code(400);
        echo json_encode(['error' => 'شناسه همکاری و محتوای پیام مورد نیاز است']);
        return;
    }
    
    // Verify user has access to this collaboration
    if (!hasCollaborationAccess($db, $collaborationId, $currentUserId)) {
        http_response_code(403);
        echo json_encode(['error' => 'دسترسی غیرمجاز']);
        return;
    }
    
    sendMessage($db, $collaborationId, $currentUserId, $content, $messageType);
}

function hasCollaborationAccess($db, $collaborationId, $userId) {
    $query = "SELECT collaboration_id FROM CollaborationRequests 
              WHERE collaboration_id = :collaboration_id 
              AND (employer_id = :user_id OR freelancer_id = :user_id)
              AND status = 'accepted'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaborationId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

function getMessages($db, $collaborationId) {
    $query = "SELECT cc.*, u.full_name as sender_name
              FROM CollaborationChat cc
              JOIN Users u ON cc.sender_id = u.user_id
              WHERE cc.collaboration_id = :collaboration_id
              ORDER BY cc.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaborationId);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

function getCollaborationInfo($db, $collaborationId) {
    $query = "SELECT cr.*, 
                     e.full_name as employer_name, e.email as employer_email,
                     f.full_name as freelancer_name, f.email as freelancer_email
              FROM CollaborationRequests cr
              JOIN Users e ON cr.employer_id = e.user_id
              JOIN Users f ON cr.freelancer_id = f.user_id
              WHERE cr.collaboration_id = :collaboration_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaborationId);
    $stmt->execute();
    
    $collaboration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$collaboration) {
        http_response_code(404);
        echo json_encode(['error' => 'همکاری یافت نشد']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'collaboration' => $collaboration
    ]);
}

function sendMessage($db, $collaborationId, $senderId, $content, $messageType) {
    $query = "INSERT INTO CollaborationChat (collaboration_id, sender_id, content, message_type)
              VALUES (:collaboration_id, :sender_id, :content, :message_type)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaborationId);
    $stmt->bindParam(':sender_id', $senderId);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':message_type', $messageType);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'پیام با موفقیت ارسال شد'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'خطا در ارسال پیام']);
    }
}
?>

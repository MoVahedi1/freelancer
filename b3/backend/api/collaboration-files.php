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
    
    getCollaborationFiles($db, $collaborationId);
}

function handlePostRequest($db, $currentUserId) {
    $collaborationId = $_POST['collaboration_id'] ?? null;
    
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
    
    if (!isset($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error' => 'هیچ فایلی انتخاب نشده است']);
        return;
    }
    
    uploadFiles($db, $collaborationId, $currentUserId, $_FILES['files']);
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

function getCollaborationFiles($db, $collaborationId) {
    $query = "SELECT cc.file_url, cc.file_name, cc.created_at, u.full_name as uploader_name
              FROM CollaborationChat cc
              JOIN Users u ON cc.sender_id = u.user_id
              WHERE cc.collaboration_id = :collaboration_id 
              AND cc.message_type = 'file'
              ORDER BY cc.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaborationId);
    $stmt->execute();
    
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
}

function uploadFiles($db, $collaborationId, $uploaderId, $files) {
    $uploadDir = "../../uploads/collaborations/{$collaborationId}/";
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Handle multiple files
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "خطا در آپلود فایل: {$fileName}";
            continue;
        }
        
        // Validate file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $errors[] = "فایل {$fileName} بیش از حد مجاز (10MB) است";
            continue;
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Save file info to database
            $relativeFilePath = "uploads/collaborations/{$collaborationId}/{$uniqueFileName}";
            
            $query = "INSERT INTO CollaborationChat (collaboration_id, sender_id, content, message_type, file_url, file_name)
                      VALUES (:collaboration_id, :sender_id, :content, 'file', :file_url, :file_name)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':collaboration_id', $collaborationId);
            $stmt->bindParam(':sender_id', $uploaderId);
            $stmt->bindParam(':content', "فایل ارسال شد: {$fileName}");
            $stmt->bindParam(':file_url', $relativeFilePath);
            $stmt->bindParam(':file_name', $fileName);
            
            if ($stmt->execute()) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'file_url' => $relativeFilePath,
                    'file_size' => $fileSize
                ];
            } else {
                $errors[] = "خطا در ذخیره اطلاعات فایل: {$fileName}";
                unlink($filePath); // Delete uploaded file if database insert fails
            }
        } else {
            $errors[] = "خطا در آپلود فایل: {$fileName}";
        }
    }
    
    if (empty($uploadedFiles) && !empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }
}
?>

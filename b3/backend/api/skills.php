<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getSkills($db);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'متد غیرمجاز']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطای سرور: ' . $e->getMessage()]);
}

function getSkills($db) {
    try {
        // دریافت کلاس‌های شغلی از جدول JobClasses
        $query = "SELECT class_id as skill_id, class_name as skill_name 
                  FROM JobClasses 
                  ORDER BY class_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'skills' => $skills
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'خطا در دریافت کلاس‌های شغلی: ' . $e->getMessage()]);
    }
}
?>

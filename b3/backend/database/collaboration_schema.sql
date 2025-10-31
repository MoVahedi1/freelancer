-- جدول درخواست‌های همکاری
CREATE TABLE IF NOT EXISTS CollaborationRequests (
    collaboration_id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    message TEXT NOT NULL,
    proposed_budget DECIMAL(10,2) NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_collaboration_employer (employer_id),
    INDEX idx_collaboration_freelancer (freelancer_id),
    INDEX idx_collaboration_status (status),
    INDEX idx_collaboration_created (created_at),
    UNIQUE KEY unique_pending_collaboration (employer_id, freelancer_id, status)
);

-- جدول چت همکاری‌ها (برای همکاری‌های پذیرفته شده)
CREATE TABLE IF NOT EXISTS CollaborationChat (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    collaboration_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    message_type ENUM('text', 'file', 'system') DEFAULT 'text',
    file_url VARCHAR(500) NULL,
    file_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collaboration_id) REFERENCES CollaborationRequests(collaboration_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_collab_chat_collaboration (collaboration_id),
    INDEX idx_collab_chat_sender (sender_id),
    INDEX idx_collab_chat_created (created_at)
);

-- به‌روزرسانی جدول SystemMessages برای پشتیبانی از انواع پیام جدید
ALTER TABLE SystemMessages 
MODIFY COLUMN message_type ENUM(
    'job_request', 
    'request_response', 
    'project_completion', 
    'project_delivery', 
    'collaboration_request', 
    'collaboration_response',
    'system'
) DEFAULT 'system';

-- ایجاد ایندکس برای بهبود عملکرد
CREATE INDEX IF NOT EXISTS idx_system_messages_type ON SystemMessages(message_type);
CREATE INDEX IF NOT EXISTS idx_system_messages_related ON SystemMessages(related_request_id);

-- ایجاد پوشه‌های آپلود برای همکاری‌ها
-- این دستورات باید در سیستم فایل اجرا شوند:
-- mkdir -p ../../uploads/collaborations
-- chmod 755 ../../uploads/collaborations

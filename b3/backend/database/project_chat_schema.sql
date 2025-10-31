-- جدول چت پروژه‌ها
CREATE TABLE IF NOT EXISTS ProjectChat (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    message_type ENUM('text', 'file', 'system', 'revision') DEFAULT 'text',
    file_url VARCHAR(500) NULL,
    file_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES JobRequests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_project_chat_project (project_id),
    INDEX idx_project_chat_sender (sender_id),
    INDEX idx_project_chat_created (created_at)
);

-- جدول فایل‌های پروژه (اختیاری - می‌توان از ProjectChat استفاده کرد)
CREATE TABLE IF NOT EXISTS ProjectFiles (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    uploader_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES JobRequests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_project_files_project (project_id),
    INDEX idx_project_files_uploader (uploader_id)
);

-- به‌روزرسانی جدول JobRequests برای پشتیبانی از وضعیت‌های جدید
ALTER TABLE JobRequests 
MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'ongoing', 'completed', 'delivered', 'cancelled') DEFAULT 'pending';

-- ایجاد پوشه‌های آپلود
-- این دستورات باید در سیستم فایل اجرا شوند:
-- mkdir -p ../../uploads/projects
-- chmod 755 ../../uploads/projects

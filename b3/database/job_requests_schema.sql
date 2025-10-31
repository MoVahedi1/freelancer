-- جداول مربوط به سیستم درخواست‌های کاری

-- جدول درخواست‌های کاری
CREATE TABLE JobRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    message TEXT NOT NULL,
    proposed_price DECIMAL(10,2),
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES Jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_request (job_id, freelancer_id)
);

-- جدول پیام‌های سیستم
CREATE TABLE SystemMessages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT,
    message_type ENUM('job_request', 'request_response', 'system_notification') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_request_id INT,
    related_job_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (related_request_id) REFERENCES JobRequests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (related_job_id) REFERENCES Jobs(job_id) ON DELETE CASCADE
);

-- ایندکس‌ها برای بهینه‌سازی
CREATE INDEX idx_job_requests_job ON JobRequests(job_id);
CREATE INDEX idx_job_requests_freelancer ON JobRequests(freelancer_id);
CREATE INDEX idx_job_requests_status ON JobRequests(status);
CREATE INDEX idx_system_messages_user ON SystemMessages(user_id);
CREATE INDEX idx_system_messages_read ON SystemMessages(is_read);
CREATE INDEX idx_system_messages_type ON SystemMessages(message_type);

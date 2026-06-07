CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    ignored TINYINT(1) NOT NULL DEFAULT 0,
    follow_up_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    auto_response_sent_at TIMESTAMP NULL DEFAULT NULL,
    follow_up_sent_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_submissions_email (email),
    INDEX idx_submissions_ignored_created (ignored, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

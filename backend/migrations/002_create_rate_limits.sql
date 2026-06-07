CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_type ENUM('email', 'ip') NOT NULL,
    key_value VARCHAR(255) NOT NULL,
    window_type ENUM('minute', 'hour', 'lifetime') NOT NULL,
    count INT NOT NULL DEFAULT 0,
    window_start TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_rate_limits (key_type, key_value, window_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking Logs Table
-- This table tracks all changes made to bookings (updates, cancellations, etc.)

CREATE TABLE IF NOT EXISTS booking_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'update, cancel, create, etc.',
    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for better query performance
CREATE INDEX idx_booking_id ON booking_logs(booking_id);
CREATE INDEX idx_user_id ON booking_logs(user_id);
CREATE INDEX idx_action_date ON booking_logs(action_date);

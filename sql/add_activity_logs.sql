-- ============================================================
-- Migration: Add activity_logs table for tracking user actions
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(50) NOT NULL COMMENT 'Cached username for display even if user deleted',
  `action` varchar(50) NOT NULL COMMENT 'login, logout, create, update, delete',
  `module` varchar(50) NOT NULL COMMENT 'auth, home_content, produk, kasir, stok, produksi, operasional',
  `target_id` int DEFAULT NULL COMMENT 'ID of the affected record',
  `description` text COMMENT 'Human-readable description of the action',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

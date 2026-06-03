-- Migration: Add home_content table for managing home page content
CREATE TABLE `home_content` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `content` longtext,
  `icon` varchar(10) DEFAULT NULL,
  `order_index` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `home_content_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `home_content_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default home content
INSERT INTO `home_content` (`section`, `title`, `subtitle`, `content`, `icon`, `order_index`, `is_active`, `created_by`) VALUES
('hero', 'DapurKu - Cita Rasa Terbaik', 'Nikmati pengalaman kuliner yang tak terlupakan dengan menu pilihan berkualitas tinggi', 'Kami menghadirkan menu makanan istimewa yang dibuat dengan passion dan bahan-bahan pilihan terbaik. Setiap hidangan dirancang untuk memberikan kepuasan maksimal kepada pelanggan setia kami.', '🍢', 1, 1, 1),
('feature', 'Kualitas Premium', 'Bahan-bahan pilihan berkualitas tinggi dari supplier terpercaya', '✓', 1, 1, 1),
('feature', 'Cepat & Segar', 'Disiapkan fresh setiap hari dengan standar kebersihan tertinggi', '⚡', 2, 1, 1),
('feature', 'Harga Terjangkau', 'Nikmati kelezatan dengan harga yang kompetitif dan terjangkau', '💰', 3, 1, 1),
('footer', 'DapurKu Restaurant', 'Jl. Contoh No. 123, Kota Pilihan | Phone: 021-XXXX-XXXX', 'Temukan kami di berbagai platform delivery atau kunjungi outlet kami langsung untuk pengalaman terbaik.', '🏪', 1, 1, 1);

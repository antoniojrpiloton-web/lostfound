-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               9.1.0 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for boracay_lf
DROP DATABASE IF EXISTS `boracay_lf`;
CREATE DATABASE IF NOT EXISTS `boracay_lf` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `boracay_lf`;

-- Dumping structure for table boracay_lf.audit_logs
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_organization_id_created_at_index` (`organization_id`,`created_at`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_action_created_at_index` (`action`,`created_at`),
  CONSTRAINT `audit_logs_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.audit_logs: ~6 rows (approximately)
DELETE FROM `audit_logs`;
INSERT INTO `audit_logs` (`id`, `user_id`, `organization_id`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
	(1, 1, NULL, 'auth.auto_role_assigned', 'App\\Models\\User', 1, NULL, '{"role_key": "platform_super_admin", "organization_id": null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:14:48'),
	(2, 1, NULL, 'auth.registered_via_google', 'App\\Models\\User', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:14:48'),
	(3, 1, NULL, 'auth.login', 'App\\Models\\User', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:14:48'),
	(4, 1, NULL, 'masterdata.setting_updated', 'App\\Models\\SystemSetting', 19, '{"key": "translation.content_enabled", "value": {"value": false}}', '{"key": "translation.content_enabled", "value": {"value": true}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:23:22'),
	(5, 1, NULL, 'masterdata.setting_updated', 'App\\Models\\SystemSetting', 5, '{"key": "matching.image_enabled", "value": {"value": false}}', '{"key": "matching.image_enabled", "value": {"value": true}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:23:27'),
	(6, 1, NULL, 'masterdata.setting_updated', 'App\\Models\\SystemSetting', 6, '{"key": "matching.visual_search_enabled", "value": {"value": false}}', '{"key": "matching.visual_search_enabled", "value": {"value": true}}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', '2026-11-26 06:23:30');

-- Dumping structure for table boracay_lf.barangays
DROP TABLE IF EXISTS `barangays`;
CREATE TABLE IF NOT EXISTS `barangays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `municipality` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Malay',
  `province` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aklan',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barangays_code_unique` (`code`),
  KEY `barangays_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.barangays: ~3 rows (approximately)
DELETE FROM `barangays`;
INSERT INTO `barangays` (`id`, `code`, `name`, `municipality`, `province`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'BRGY-BALABAG', 'Balabag', 'Malay', 'Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 'BRGY-MANOCMANOC', 'Manoc-Manoc', 'Malay', 'Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 'BRGY-YAPAK', 'Yapak', 'Malay', 'Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.cache
DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.cache: ~10 rows (approximately)
DELETE FROM `cache`;
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
	('boracay-lost-and-found-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1795677559),
	('boracay-lost-and-found-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1795677559;', 1795677559),
	('boracay-lost-and-found-cache-424f74a6a7ed4d4ed4761507ebcd209a6ef0937b', 'i:2;', 1795673739),
	('boracay-lost-and-found-cache-424f74a6a7ed4d4ed4761507ebcd209a6ef0937b:timer', 'i:1795673739;', 1795673739),
	('boracay-lost-and-found-cache-blf:disposal_retention_autoscan', 'i:0;', 1795674302),
	('boracay-lost-and-found-cache-blf.master.barangays.v2', 'a:3:{i:0;a:8:{s:2:"id";i:1;s:4:"code";s:12:"BRGY-BALABAG";s:4:"name";s:7:"Balabag";s:12:"municipality";s:5:"Malay";s:8:"province";s:5:"Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:1;a:8:{s:2:"id";i:2;s:4:"code";s:15:"BRGY-MANOCMANOC";s:4:"name";s:11:"Manoc-Manoc";s:12:"municipality";s:5:"Malay";s:8:"province";s:5:"Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:2;a:8:{s:2:"id";i:3;s:4:"code";s:10:"BRGY-YAPAK";s:4:"name";s:5:"Yapak";s:12:"municipality";s:5:"Malay";s:8:"province";s:5:"Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}}', 1795677628),
	('boracay-lost-and-found-cache-blf.master.categories.v2', 'a:19:{i:0;a:8:{s:2:"id";i:1;s:9:"parent_id";N;s:4:"slug";s:11:"electronics";s:6:"labels";s:43:"{"en": "Electronics", "fil": "Elektroniks"}";s:9:"is_active";i:1;s:10:"sort_order";i:10;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:1;a:8:{s:2:"id";i:2;s:9:"parent_id";i:1;s:4:"slug";s:13:"mobile-phones";s:6:"labels";s:43:"{"en": "Mobile Phones", "fil": "Cellphone"}";s:9:"is_active";i:1;s:10:"sort_order";i:11;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:2;a:8:{s:2:"id";i:3;s:9:"parent_id";i:1;s:4:"slug";s:19:"cameras-action-cams";s:6:"labels";s:48:"{"en": "Cameras & Action Cams", "fil": "Kamera"}";s:9:"is_active";i:1;s:10:"sort_order";i:12;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:3;a:8:{s:2:"id";i:4;s:9:"parent_id";i:1;s:4:"slug";s:15:"audio-wearables";s:6:"labels";s:68:"{"en": "Earphones & Smartwatches", "fil": "Earphones at Smartwatch"}";s:9:"is_active";i:1;s:10:"sort_order";i:13;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:4;a:8:{s:2:"id";i:5;s:9:"parent_id";i:1;s:4:"slug";s:15:"laptops-tablets";s:6:"labels";s:54:"{"en": "Laptops & Tablets", "fil": "Laptop at Tablet"}";s:9:"is_active";i:1;s:10:"sort_order";i:14;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:5;a:8:{s:2:"id";i:6;s:9:"parent_id";N;s:4:"slug";s:12:"wallets-bags";s:6:"labels";s:48:"{"en": "Wallets & Bags", "fil": "Pitaka at Bag"}";s:9:"is_active";i:1;s:10:"sort_order";i:20;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:6;a:8:{s:2:"id";i:7;s:9:"parent_id";i:6;s:4:"slug";s:14:"wallets-purses";s:6:"labels";s:43:"{"en": "Wallets & Purses", "fil": "Pitaka"}";s:9:"is_active";i:1;s:10:"sort_order";i:21;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:7;a:8:{s:2:"id";i:8;s:9:"parent_id";i:6;s:4:"slug";s:14:"backpacks-bags";s:6:"labels";s:60:"{"en": "Backpacks & Dry Bags", "fil": "Backpack at Dry Bag"}";s:9:"is_active";i:1;s:10:"sort_order";i:22;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:8;a:8:{s:2:"id";i:9;s:9:"parent_id";i:6;s:4:"slug";s:7:"luggage";s:6:"labels";s:46:"{"en": "Luggage & Suitcases", "fil": "Maleta"}";s:9:"is_active";i:1;s:10:"sort_order";i:23;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:9;a:8:{s:2:"id";i:10;s:9:"parent_id";N;s:4:"slug";s:13:"ids-documents";s:6:"labels";s:51:"{"en": "IDs & Documents", "fil": "ID at Dokumento"}";s:9:"is_active";i:1;s:10:"sort_order";i:30;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:10;a:8:{s:2:"id";i:11;s:9:"parent_id";i:10;s:4:"slug";s:14:"government-ids";s:6:"labels";s:49:"{"en": "Government IDs", "fil": "ID ng Gobyerno"}";s:9:"is_active";i:1;s:10:"sort_order";i:31;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:11;a:8:{s:2:"id";i:12;s:9:"parent_id";i:10;s:4:"slug";s:9:"passports";s:6:"labels";s:77:"{"en": "Passports & Travel Documents", "fil": "Pasaporte at Travel Document"}";s:9:"is_active";i:1;s:10:"sort_order";i:32;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:12;a:8:{s:2:"id";i:13;s:9:"parent_id";i:10;s:4:"slug";s:10:"bank-cards";s:6:"labels";s:57:"{"en": "ATM & Credit Cards", "fil": "ATM at Credit Card"}";s:9:"is_active";i:1;s:10:"sort_order";i:33;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:13;a:8:{s:2:"id";i:14;s:9:"parent_id";N;s:4:"slug";s:20:"personal-accessories";s:6:"labels";s:58:"{"en": "Personal Accessories", "fil": "Personal na Gamit"}";s:9:"is_active";i:1;s:10:"sort_order";i:40;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:14;a:8:{s:2:"id";i:15;s:9:"parent_id";i:14;s:4:"slug";s:15:"jewelry-watches";s:6:"labels";s:52:"{"en": "Jewelry & Watches", "fil": "Alahas at Relo"}";s:9:"is_active";i:1;s:10:"sort_order";i:41;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:15;a:8:{s:2:"id";i:16;s:9:"parent_id";i:14;s:4:"slug";s:7:"eyewear";s:6:"labels";s:59:"{"en": "Eyeglasses & Sunglasses", "fil": "Salamin sa Mata"}";s:9:"is_active";i:1;s:10:"sort_order";i:42;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:16;a:8:{s:2:"id";i:17;s:9:"parent_id";i:14;s:4:"slug";s:4:"keys";s:6:"labels";s:51:"{"en": "Keys & Keycards", "fil": "Susi at Keycard"}";s:9:"is_active";i:1;s:10:"sort_order";i:43;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:17;a:8:{s:2:"id";i:18;s:9:"parent_id";N;s:4:"slug";s:18:"clothing-beachwear";s:6:"labels";s:60:"{"en": "Clothing & Beachwear", "fil": "Damit at Pang-beach"}";s:9:"is_active";i:1;s:10:"sort_order";i:50;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:18;a:8:{s:2:"id";i:19;s:9:"parent_id";N;s:4:"slug";s:6:"others";s:6:"labels";s:33:"{"en": "Others", "fil": "Iba pa"}";s:9:"is_active";i:1;s:10:"sort_order";i:99;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}}', 1795677628),
	('boracay-lost-and-found-cache-blf.master.locations.v2', 'a:13:{i:0;a:11:{s:2:"id";i:5;s:11:"barangay_id";i:1;s:15:"organization_id";i:4;s:4:"name";s:38:"Boracay Tourist Assistance Center Area";s:4:"type";s:8:"landmark";s:11:"description";N;s:8:"latitude";s:10:"11.9640000";s:9:"longitude";s:11:"121.9261000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:1;a:11:{s:2:"id";i:4;s:11:"barangay_id";i:1;s:15:"organization_id";i:1;s:4:"name";s:13:"Bulabog Beach";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9647000";s:9:"longitude";s:11:"121.9308000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:2;a:11:{s:2:"id";i:7;s:11:"barangay_id";i:2;s:15:"organization_id";i:2;s:4:"name";s:17:"Cagban Jetty Port";s:4:"type";s:4:"port";s:11:"description";N;s:8:"latitude";s:10:"11.9396000";s:9:"longitude";s:11:"121.9467000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:3;a:11:{s:2:"id";i:13;s:11:"barangay_id";i:3;s:15:"organization_id";i:3;s:4:"name";s:16:"CityMall Boracay";s:4:"type";s:10:"commercial";s:11:"description";N;s:8:"latitude";s:10:"11.9789000";s:9:"longitude";s:11:"121.9175000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:4;a:11:{s:2:"id";i:3;s:11:"barangay_id";i:1;s:15:"organization_id";i:1;s:4:"name";s:17:"D\'Mall de Boracay";s:4:"type";s:10:"commercial";s:11:"description";N;s:8:"latitude";s:10:"11.9628000";s:9:"longitude";s:11:"121.9267000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:5;a:11:{s:2:"id";i:12;s:11:"barangay_id";i:3;s:15:"organization_id";i:3;s:4:"name";s:13:"Diniwid Beach";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9765000";s:9:"longitude";s:11:"121.9114000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:6;a:11:{s:2:"id";i:11;s:11:"barangay_id";i:3;s:15:"organization_id";i:3;s:4:"name";s:17:"Ilig-Iligan Beach";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9888000";s:9:"longitude";s:11:"121.9267000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:7;a:11:{s:2:"id";i:10;s:11:"barangay_id";i:3;s:15:"organization_id";i:3;s:4:"name";s:16:"Puka Shell Beach";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9953000";s:9:"longitude";s:11:"121.9126000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:8;a:11:{s:2:"id";i:1;s:11:"barangay_id";i:1;s:15:"organization_id";i:1;s:4:"name";s:20:"Station 1 Beachfront";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9690000";s:9:"longitude";s:11:"121.9212000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:9;a:11:{s:2:"id";i:2;s:11:"barangay_id";i:1;s:15:"organization_id";i:1;s:4:"name";s:20:"Station 2 Beachfront";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9615000";s:9:"longitude";s:11:"121.9255000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:10;a:11:{s:2:"id";i:6;s:11:"barangay_id";i:2;s:15:"organization_id";i:2;s:4:"name";s:20:"Station 3 Beachfront";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9535000";s:9:"longitude";s:11:"121.9304000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:11;a:11:{s:2:"id";i:8;s:11:"barangay_id";i:2;s:15:"organization_id";i:2;s:4:"name";s:20:"Tambisaan Jetty Port";s:4:"type";s:4:"port";s:11:"description";N;s:8:"latitude";s:10:"11.9451000";s:9:"longitude";s:11:"121.9502000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:12;a:11:{s:2:"id";i:9;s:11:"barangay_id";i:2;s:15:"organization_id";i:2;s:4:"name";s:14:"Tulubhan Beach";s:4:"type";s:5:"beach";s:11:"description";N;s:8:"latitude";s:10:"11.9524000";s:9:"longitude";s:11:"121.9435000";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}}', 1795677628),
	('boracay-lost-and-found-cache-blf.master.organizations.v2', 'a:4:{i:0;a:10:{s:2:"id";i:3;s:4:"type";s:12:"barangay_lgu";s:4:"code";s:9:"LGU-YAPAK";s:4:"name";s:52:"Boracay Police Assistance Booth - Puka & North Beach";s:13:"contact_email";N;s:13:"contact_phone";N;s:7:"address";s:60:"Puka Shell Beach / North Coast, Boracay Island, Malay, Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:1;a:10:{s:2:"id";i:1;s:4:"type";s:12:"barangay_lgu";s:4:"code";s:11:"LGU-BALABAG";s:4:"name";s:57:"Boracay Police Assistance Booth - Station 1 & 2 (Central)";s:13:"contact_email";N;s:13:"contact_phone";N;s:7:"address";s:53:"White Beach Station 1-2, Boracay Island, Malay, Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:2;a:10:{s:2:"id";i:2;s:4:"type";s:12:"barangay_lgu";s:4:"code";s:14:"LGU-MANOCMANOC";s:4:"name";s:52:"Boracay Police Assistance Booth - Station 3 & Cagban";s:13:"contact_email";N;s:13:"contact_phone";N;s:7:"address";s:71:"White Beach Station 3 / Cagban Jetty Port, Boracay Island, Malay, Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}i:3;a:10:{s:2:"id";i:4;s:4:"type";s:14:"police_station";s:4:"code";s:14:"PNP-MALAY-BTAC";s:4:"name";s:66:"Malay Municipal Police Station - Boracay Tourist Assistance Center";s:13:"contact_email";N;s:13:"contact_phone";N;s:7:"address";s:39:"Main Road, Boracay Island, Malay, Aklan";s:9:"is_active";i:1;s:10:"created_at";s:19:"2026-11-26 14:12:43";s:10:"updated_at";s:19:"2026-11-26 14:12:43";}}', 1795677628),
	('boracay-lost-and-found-cache-blf.master.settings.v2', 'a:19:{s:15:"match.min_score";a:1:{s:5:"value";d:0.55;}s:16:"match.high_score";a:1:{s:5:"value";d:0.75;}s:29:"match.date_window_before_days";a:1:{s:5:"value";i:2;}s:28:"match.date_window_after_days";a:1:{s:5:"value";i:90;}s:22:"matching.image_enabled";a:1:{s:5:"value";b:1;}s:30:"matching.visual_search_enabled";a:1:{s:5:"value";b:1;}s:32:"matching.visual_search_min_score";a:1:{s:5:"value";d:0.64;}s:13:"match.weights";a:8:{s:4:"date";d:0.1;s:5:"brand";d:0.1;s:5:"color";d:0.1;s:5:"model";d:0.05;s:8:"category";d:0.2;s:8:"location";d:0.1;s:9:"item_name";d:0.15;s:11:"description";d:0.2;}s:26:"image_validation.fail_mode";a:1:{s:5:"value";s:6:"review";}s:38:"image_validation.blur_reject_threshold";a:1:{s:5:"value";i:80;}s:38:"image_validation.blur_review_threshold";a:1:{s:5:"value";i:120;}s:36:"image_validation.dark_mean_threshold";a:1:{s:5:"value";i:45;}s:38:"image_validation.bright_mean_threshold";a:1:{s:5:"value";i:220;}s:40:"image_validation.object_reject_threshold";a:1:{s:5:"value";d:0.35;}s:40:"image_validation.object_review_threshold";a:1:{s:5:"value";d:0.55;}s:23:"disposal.retention_days";a:1:{s:5:"value";i:180;}s:36:"institutions.admins_can_manage_staff";a:1:{s:5:"value";b:1;}s:27:"notifications.email_enabled";a:1:{s:5:"value";b:1;}s:27:"translation.content_enabled";a:1:{s:5:"value";b:1;}}', 1795680530);

-- Dumping structure for table boracay_lf.cache_locks
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.cache_locks: ~0 rows (approximately)
DELETE FROM `cache_locks`;

-- Dumping structure for table boracay_lf.categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `labels` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_is_active_sort_order_index` (`parent_id`,`is_active`,`sort_order`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.categories: ~19 rows (approximately)
DELETE FROM `categories`;
INSERT INTO `categories` (`id`, `parent_id`, `slug`, `labels`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
	(1, NULL, 'electronics', '{"en": "Electronics", "fil": "Elektroniks"}', 1, 10, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 1, 'mobile-phones', '{"en": "Mobile Phones", "fil": "Cellphone"}', 1, 11, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 1, 'cameras-action-cams', '{"en": "Cameras & Action Cams", "fil": "Kamera"}', 1, 12, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 1, 'audio-wearables', '{"en": "Earphones & Smartwatches", "fil": "Earphones at Smartwatch"}', 1, 13, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(5, 1, 'laptops-tablets', '{"en": "Laptops & Tablets", "fil": "Laptop at Tablet"}', 1, 14, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(6, NULL, 'wallets-bags', '{"en": "Wallets & Bags", "fil": "Pitaka at Bag"}', 1, 20, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(7, 6, 'wallets-purses', '{"en": "Wallets & Purses", "fil": "Pitaka"}', 1, 21, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(8, 6, 'backpacks-bags', '{"en": "Backpacks & Dry Bags", "fil": "Backpack at Dry Bag"}', 1, 22, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(9, 6, 'luggage', '{"en": "Luggage & Suitcases", "fil": "Maleta"}', 1, 23, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(10, NULL, 'ids-documents', '{"en": "IDs & Documents", "fil": "ID at Dokumento"}', 1, 30, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(11, 10, 'government-ids', '{"en": "Government IDs", "fil": "ID ng Gobyerno"}', 1, 31, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(12, 10, 'passports', '{"en": "Passports & Travel Documents", "fil": "Pasaporte at Travel Document"}', 1, 32, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(13, 10, 'bank-cards', '{"en": "ATM & Credit Cards", "fil": "ATM at Credit Card"}', 1, 33, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(14, NULL, 'personal-accessories', '{"en": "Personal Accessories", "fil": "Personal na Gamit"}', 1, 40, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(15, 14, 'jewelry-watches', '{"en": "Jewelry & Watches", "fil": "Alahas at Relo"}', 1, 41, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(16, 14, 'eyewear', '{"en": "Eyeglasses & Sunglasses", "fil": "Salamin sa Mata"}', 1, 42, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(17, 14, 'keys', '{"en": "Keys & Keycards", "fil": "Susi at Keycard"}', 1, 43, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(18, NULL, 'clothing-beachwear', '{"en": "Clothing & Beachwear", "fil": "Damit at Pang-beach"}', 1, 50, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(19, NULL, 'others', '{"en": "Others", "fil": "Iba pa"}', 1, 99, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.claims
DROP TABLE IF EXISTS `claims`;
CREATE TABLE IF NOT EXISTS `claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_report_id` bigint unsigned NOT NULL,
  `lost_report_id` bigint unsigned DEFAULT NULL,
  `claimant_user_id` bigint unsigned NOT NULL,
  `ownership_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `distinguishing_details` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `completed_item_key` bigint unsigned GENERATED ALWAYS AS (if((`status` = _utf8mb4'completed'),`item_report_id`,NULL)) STORED,
  `turnover_scheduled_at` timestamp NULL DEFAULT NULL,
  `pickup_mode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_location` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decision_remarks` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `claims_reference_no_unique` (`reference_no`),
  UNIQUE KEY `claims_completed_item_unique` (`completed_item_key`),
  KEY `claims_lost_report_id_foreign` (`lost_report_id`),
  KEY `claims_decided_by_foreign` (`decided_by`),
  KEY `claims_item_report_id_status_index` (`item_report_id`,`status`),
  KEY `claims_claimant_user_id_status_index` (`claimant_user_id`,`status`),
  CONSTRAINT `claims_claimant_user_id_foreign` FOREIGN KEY (`claimant_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `claims_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claims_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `claims_lost_report_id_foreign` FOREIGN KEY (`lost_report_id`) REFERENCES `item_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_claims_status` CHECK ((`status` in (_utf8mb4'submitted',_utf8mb4'under_review',_utf8mb4'info_requested',_utf8mb4'approved',_utf8mb4'rejected',_utf8mb4'completed',_utf8mb4'cancelled',_utf8mb4'withdrawn')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.claims: ~0 rows (approximately)
DELETE FROM `claims`;

-- Dumping structure for table boracay_lf.claim_evidence
DROP TABLE IF EXISTS `claim_evidence`;
CREATE TABLE IF NOT EXISTS `claim_evidence` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint unsigned NOT NULL,
  `private_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claim_evidence_claim_id_foreign` (`claim_id`),
  CONSTRAINT `claim_evidence_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.claim_evidence: ~0 rows (approximately)
DELETE FROM `claim_evidence`;

-- Dumping structure for table boracay_lf.claim_histories
DROP TABLE IF EXISTS `claim_histories`;
CREATE TABLE IF NOT EXISTS `claim_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint unsigned NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `claim_histories_changed_by_foreign` (`changed_by`),
  KEY `claim_histories_claim_id_created_at_index` (`claim_id`,`created_at`),
  CONSTRAINT `claim_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `claim_histories_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.claim_histories: ~0 rows (approximately)
DELETE FROM `claim_histories`;

-- Dumping structure for table boracay_lf.failed_jobs
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.failed_jobs: ~0 rows (approximately)
DELETE FROM `failed_jobs`;

-- Dumping structure for table boracay_lf.image_embeddings
DROP TABLE IF EXISTS `image_embeddings`;
CREATE TABLE IF NOT EXISTS `image_embeddings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_image_id` bigint unsigned NOT NULL,
  `item_report_id` bigint unsigned NOT NULL,
  `model_version` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dimensions` smallint unsigned NOT NULL,
  `vector` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_embeddings_item_image_id_unique` (`item_image_id`),
  KEY `image_embeddings_item_report_id_model_version_index` (`item_report_id`,`model_version`),
  CONSTRAINT `image_embeddings_item_image_id_foreign` FOREIGN KEY (`item_image_id`) REFERENCES `item_images` (`id`) ON DELETE CASCADE,
  CONSTRAINT `image_embeddings_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.image_embeddings: ~9 rows (approximately)
DELETE FROM `image_embeddings`;
INSERT INTO `image_embeddings` (`id`, `item_image_id`, `item_report_id`, `model_version`, `dimensions`, `vector`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.00882, -0.12965, 0.068233, 0.00388, -0.044543, -0.018259, 0.045379, -0.01678, 0.023039, 0.020151, 0.01555, 0.01954, -0.032291, 0.046226, 0.044951, 0.055667, -0.074692, -0.033773, -0.066373, -0.072957, -0.014453, 0.006514, 0.044353, 0.009422, 0.030042, -0.025112, -0.043374, 0.004158, 0.014997, -0.011885, 0.079557, 0.000254, 0.055097, 0.001957, 0.007613, -0.056718, -0.014151, 0.035779, 0.000847, 0.042372, 0.009074, 0.047158, -0.032249, -0.028753, 0.084383, -0.000611, 0.095959, -0.044415, -0.026714, -0.025018, 0.007071, 0.004668, 0.001661, 0.01327, -0.094184, -0.087851, 0.007609, 0.025067, 0.06523, -0.005388, -0.142263, 0.040626, -0.072306, 0.021183, 0.045664, -0.01112, 0.069838, 0.076592, -0.033084, -0.001217, -0.04115, -0.054527, 0.018241, -0.063748, 0.069683, 0.01249, -0.014054, 0.011415, 0.037314, -0.00673, 0.006096, -0.049711, -0.018276, -0.028685, -0.021518, 0.010703, 0.032723, 0.019165, -0.055826, -0.014348, -0.011648, -0.037114, 0.010633, 0.03387, -0.01995, 0.018377, 0.005238, 0.025569, 0.048997, 0.005415, 0.01538, 0.046936, 0.008213, -0.018738, 0.011711, -0.005081, -0.002275, -0.015276, -0.02661, 0.0032, 0.057153, -0.000039, 0.008691, 0.004147, -0.01741, 0.015189, -0.026909, 0.040937, -0.006933, -0.002956, -0.031307, -0.023994, 0.023424, -0.017146, -0.024697, -0.038881, 0.019754, -0.010689, -0.006147, -0.009071, 0.03976, -0.045561, 0.017561, 0.014706, 0.003253, 0.016594, 0.013059, -0.011397, -0.031487, 0.034103, -0.063772, 0.007268, 0.030964, 0.015292, -0.07615, -0.067002, 0.000072, -0.019, 0.018178, -0.025731, -0.001454, 0.008524, 0.317033, -0.027075, 0.000058, 0.10209, -0.009238, 0.006424, 0.009474, 0.013774, 0.027008, 0.011466, 0.031749, 0.025537, 0.054528, 0.08901, -0.025482, 0.017971, 0.074292, -0.010647, 0.05662, 0.06814, 0.093232, 0.013594, 0.03152, -0.054495, 0.045453, 0.043454, 0.025568, 0.020192, 0.018691, -0.001412, -0.029357, 0.049596, 0.010968, -0.039826, 0.079779, 0.046783, -0.010414, 0.036001, -0.036089, -0.079649, 0.051479, 0.025119, 0.021124, 0.050033, -0.040452, 0.006274, 0.000172, -0.011446, 0.010052, -0.019183, 0.010094, -0.01358, -0.00408, -0.044928, -0.046563, 0.017313, 0.023285, 0.007401, -0.021386, 0.047212, -0.015664, 0.005804, 0.032366, 0.04181, 0.00609, -0.029659, 0.056213, -0.047825, 0.016549, 0.002434, -0.006135, -0.002188, 0.055861, -0.003027, -0.005976, 0.026538, -0.041234, 0.012811, 0.025743, 0.017438, 0.007479, 0.076818, -0.013142, 0.003055, 0.007232, 0.000748, 0.017286, -0.003656, -0.020782, -0.037449, -0.005334, -0.029484, 0.005682, 0.099458, -0.010235, -0.193192, -0.011412, 0.04506, 0.101322, -0.01741, -0.042647, -0.041186, -0.009656, -0.022101, -0.035589, -0.021551, 0.000849, -0.086497, 0.011581, -0.010849, 0.000699, 0.00591, 0.037096, 0.025063, 0.020729, 0.030444, 0.005946, -0.022993, 0.067012, -0.017257, 0.033089, 0.013405, 0.007983, -0.036124, -0.007261, 0.011472, -0.04681, -0.099954, 0.065446, 0.016595, -0.017094, 0.102156, -0.024076, -0.023428, 0.067742, 0.012195, 0.055297, 0.004246, -0.020931, 0.006056, 0.007011, 0.02427, 0.022086, 0.065811, -0.01631, 0.014098, 0.073929, -0.018043, -0.011498, -0.008454, 0.05012, -0.033768, -0.043381, 0.003277, -0.050866, -0.315518, 0.016908, -0.003357, 0.007253, 0.086217, 0.026531, -0.017322, 0.004729, 0.048021, 0.041236, -0.009473, -0.02986, 0.035931, -0.009718, 0.016021, -0.010517, 0.015195, 0.043769, -0.023938, 0.053807, -0.009215, 0.012994, -0.030428, -0.043289, 0.025616, 0.014593, 0.008835, -0.009867, 0.013545, 0.018021, 0.003578, -0.016847, 0.015732, -0.027124, -0.02684, -0.007841, -0.014889, -0.040239, 0.015525, -0.031305, 0.002584, -0.175732, 0.004732, 0.018386, 0.003713, -0.0205, -0.008519, -0.068787, 0.024693, 0.004814, 0.057167, 0.048345, 0.077718, -0.032003, 0.031596, -0.035402, 0.027972, -0.029052, 0.028904, -0.030896, -0.022463, -0.012474, -0.030855, 0.016468, 0.045177, 0.018372, 0.027691, 0.056914, 0.01764, 0.126061, -0.020875, 0.037578, -0.021299, -0.020912, -0.00194, 0.049124, 0.057431, -0.020084, 0.067627, -0.011364, 0.00096, -0.040931, -0.015957, 0.063867, 0.034418, 0.011857, 0.005272, -0.002301, 0.000889, 0.08572, 0.010797, -0.008741, 0.009931, 0.004486, 0.044224, 0.026034, 0.075281, -0.014865, 0.013485, -0.005383, 0.071745, -0.041846, 0.015765, 0.027333, 0.044659, 0.045101, -0.020052, 0.017337, -0.053093, 0.036901, 0.039793, 0.023118, -0.032249, -0.004309, -0.106305, -0.034224, 0.025052, -0.016073, 0.030724, -0.018524, -0.01154, -0.020158, -0.053021, -0.014807, 0.109284, -0.012093, -0.042697, -0.043572, 0.011838, -0.015788, -0.00261, -0.021581, -0.018385, 0.017076, -0.044617, -0.059606, 0.063542, -0.066542, -0.021046, 0.011872, 0.021384, -0.016835, -0.036369, 0.017487, 0.01049, -0.03651, 0.012381, 0.015224, 0.049386, 0.005023, -0.024698, 0.043933, 0.046713, 0.015509, -0.005327, 0.021124, 0.044317, -0.021825, 0.011816, -0.054693, -0.007335, 0.003981, 0.006501, -0.009042, -0.003435, -0.114086, 0.021177, 0.021783, -0.037552, 0.047128, -0.004482, 0.005246, -0.017158, -0.016642, 0.025956, 0.02306, 0.034568, -0.004307, -0.00428, -0.032088, -0.029266, 0.080093, 0.014586, -0.074159, -0.009276, -0.064956, -0.006182, -0.007365, 0.065138, 0.040326, -0.006806, -0.064794, -0.010323, 0.057901, 0.032227, -0.052856, 0.065875, 0.029233, 0.019364, -0.004833, -0.059062, 0.008251, 0.01954, 0.004319, -0.02141]', '2026-11-26 06:21:04', '2026-11-26 06:21:04'),
	(2, 2, 2, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.020163, -0.147145, 0.085475, 0.019387, -0.048627, -0.02092, 0.031647, -0.007348, 0.030257, 0.016076, 0.010491, 0.020686, -0.026755, 0.056179, 0.054445, 0.077176, -0.071597, -0.038954, -0.068571, -0.07306, -0.025035, -0.001288, 0.040695, 0.012527, 0.033429, -0.010543, -0.046885, -0.002443, 0.025084, -0.023378, 0.066634, 0.014345, 0.055961, -0.000192, -0.004088, -0.053878, -0.001972, 0.028499, -0.016921, 0.039007, 0.017428, 0.065734, -0.039436, -0.032575, 0.07716, 0.002536, 0.078954, -0.04358, -0.01674, -0.019553, 0.003681, 0.003431, 0.000512, 0.014745, -0.075571, -0.085215, 0.021764, 0.012031, 0.068621, -0.01053, -0.141689, 0.02733, -0.078405, 0.026999, 0.047664, 0.004673, 0.072732, 0.051631, -0.039843, -0.001407, -0.04162, -0.047881, 0.02178, -0.037217, 0.070021, 0.017794, -0.015018, 0.015013, 0.027027, -0.01118, 0.018528, -0.058289, -0.02832, -0.038884, -0.027855, -0.00628, 0.034939, 0.013013, -0.043337, 0.000473, -0.000867, -0.031203, 0.009476, 0.018888, -0.019734, 0.013171, -0.003621, 0.009727, 0.041572, -0.000756, 0.024478, 0.053092, 0.024938, -0.028955, 0.004838, -0.020646, 0.01567, -0.006822, -0.034535, 0.010077, 0.069389, 0.023422, 0.011035, 0.009263, -0.019757, 0.012431, -0.024804, 0.04518, -0.0122, 0.002824, -0.046114, -0.016888, 0.015179, -0.016107, -0.024072, -0.034398, 0.014997, -0.02507, -0.009658, -0.014683, 0.036987, -0.047242, 0.019762, 0.016067, 0.00533, 0.022556, 0.003779, -0.004808, -0.022761, 0.020655, -0.043579, 0.002487, 0.028857, 0.01558, -0.094834, -0.065133, 0.009272, -0.022737, 0.015319, -0.040167, -0.020613, 0.009415, 0.320172, -0.032667, 0.029156, 0.092298, 0.004164, 0.026795, 0.005687, 0.017596, 0.028434, 0.006821, 0.023302, 0.048013, 0.05475, 0.092233, -0.00553, 0.018664, 0.070859, -0.018834, 0.055079, 0.071814, 0.10414, 0.01805, 0.020376, -0.077206, 0.047658, 0.040978, 0.014751, 0.016355, -0.001429, 0.021101, -0.042849, 0.045976, 0.004392, -0.039931, 0.07168, 0.045226, -0.018349, 0.038243, -0.033337, -0.05517, 0.054268, 0.019256, 0.033037, 0.051515, -0.03117, 0.003116, -0.008533, 0.010096, 0.025435, 0.007586, -0.009933, -0.008215, -0.012671, -0.041237, -0.042399, 0.00824, 0.019826, 0.011724, -0.035944, 0.04126, -0.007985, -0.002482, 0.023495, 0.037845, 0.003056, -0.018365, 0.050878, -0.036275, 0.000142, 0.006287, 0.003482, 0.012415, 0.055783, -0.02085, -0.013994, 0.024062, -0.048713, 0.017958, 0.020423, 0.025321, 0.009963, 0.074049, -0.028042, -0.00871, 0.003862, 0.008672, 0.007773, 0.006463, -0.023011, -0.032039, 0.003585, -0.02735, 0.015583, 0.09512, -0.012328, -0.165523, -0.009782, 0.032463, 0.101577, -0.036945, -0.053782, -0.041647, -0.006011, -0.034322, -0.038295, -0.004587, -0.023115, -0.080787, -0.003867, -0.015171, 0.002835, 0.004109, 0.034328, 0.037106, 0.02648, 0.025985, 0.009227, -0.021231, 0.097939, -0.013207, 0.02628, 0.003758, 0.013318, -0.031926, -0.001713, 0.017164, -0.031586, -0.08121, 0.057376, 0.003328, -0.02036, 0.102017, -0.013389, -0.025335, 0.073828, 0.010803, 0.045219, 0.011844, -0.031009, 0.005502, -0.005421, 0.031843, 0.025651, 0.047255, -0.009679, 0.001788, 0.083643, -0.019451, -0.020567, -0.008755, 0.041243, -0.029607, -0.042022, 0.014857, -0.04826, -0.31853, 0.020461, -0.006464, 0.01228, 0.065739, 0.033428, -0.014518, 0.000414, 0.069879, 0.035858, -0.004027, -0.013919, 0.034706, -0.008779, 0.020287, -0.009564, 0.024839, 0.068459, -0.023928, 0.054364, -0.013362, 0.020855, -0.02169, -0.049714, 0.030831, 0.021024, -0.000518, -0.010384, 0.013982, 0.019797, 0.003304, 0.002492, 0.023771, -0.035366, -0.013516, -0.01074, -0.006823, -0.037698, 0.007193, -0.02812, -0.012489, -0.158897, -0.016264, 0.008904, 0.011871, 0.000648, -0.003513, -0.04919, 0.047841, 0.01113, 0.054571, 0.051788, 0.065841, -0.029995, 0.0249, -0.025956, 0.032343, -0.030233, 0.012874, -0.039439, -0.038608, -0.005501, -0.037208, 0.005699, 0.03204, 0.018998, 0.022683, 0.033597, -0.014083, 0.116312, -0.025187, 0.044552, -0.018295, -0.033371, 0.002585, 0.048285, 0.051334, -0.019689, 0.058283, -0.010021, -0.003298, -0.034567, -0.002938, 0.07185, 0.022798, 0.001235, -0.00804, -0.013859, 0.000996, 0.080347, -0.000127, -0.001586, 0.021366, -0.005524, 0.038148, 0.017183, 0.068985, -0.02471, 0.00046, 0.011867, 0.080015, -0.053137, 0.005761, 0.027873, 0.055555, 0.047126, -0.003965, 0.000074, -0.058834, 0.048854, 0.034475, 0.02292, -0.04332, -0.010544, -0.101718, -0.039753, 0.022887, -0.022089, 0.034338, -0.016232, 0.015773, -0.010355, -0.067482, -0.021373, 0.100867, -0.01446, -0.041609, -0.048256, 0.015327, -0.015129, 0.001782, -0.020542, -0.019549, 0.017688, -0.039593, -0.04552, 0.057729, -0.069079, -0.025055, 0.017223, 0.037744, -0.02049, -0.046847, 0.04019, 0.00988, -0.025312, 0.011356, 0.017588, 0.063821, 0.015357, -0.022662, 0.044233, 0.043185, 0.014862, 0.011418, 0.008004, 0.041571, -0.033364, 0.00528, -0.058991, -0.010635, -0.002424, 0.009248, -0.017949, -0.004525, -0.095433, 0.028649, 0.025474, -0.0361, 0.054972, -0.008403, -0.015477, -0.025536, -0.017237, -0.000445, 0.01328, 0.044506, -0.014899, 0.001656, -0.027629, -0.031241, 0.085236, 0.026132, -0.097447, -0.00174, -0.065496, -0.006003, -0.010635, 0.076721, 0.038605, -0.005559, -0.08412, -0.028029, 0.052027, 0.042246, -0.05787, 0.056999, 0.043008, 0.019308, -0.005984, -0.054052, -0.00048, 0.024736, -0.008588, -0.026678]', '2026-11-26 06:21:04', '2026-11-26 06:21:04'),
	(3, 3, 3, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.056865, 0.069092, 0.071771, 0.030352, -0.022375, -0.005398, -0.02324, 0.00753, -0.001747, -0.055586, 0.054385, 0.03855, -0.023752, 0.084392, 0.005176, -0.009562, -0.105753, 0.017773, 0.028961, 0.025731, -0.011171, 0.065929, 0.026996, -0.002307, 0.02076, -0.032471, 0.02253, 0.020008, 0.011873, 0.015569, 0.041511, 0.016931, 0.0363, -0.001037, 0.040497, 0.005835, -0.074755, 0.010628, -0.010181, 0.004517, -0.021931, -0.003935, 0.012248, 0.018739, 0.0414, -0.042557, 0.040811, -0.011635, 0.007559, -0.022371, 0.02809, 0.01825, -0.029329, -0.039175, 0.053931, -0.060341, 0.001511, 0.050717, 0.045524, 0.007167, -0.054627, 0.001112, -0.040299, -0.024941, -0.061025, 0.019974, 0.013388, 0.025105, 0.015471, 0.030215, -0.027353, -0.020061, 0.020438, 0.045126, -0.031783, -0.028654, 0.093921, 0.009415, 0.025813, 0.042235, 0.032488, 0.036648, -0.01118, 0.023294, 0.062868, 0.010489, 0.024813, 0.007263, 0.024379, 0.019225, 0.003907, -0.038192, -0.0288, 0.015551, 0.015218, -0.023264, -0.020315, 0.001512, 0.057419, 0.013051, 0.02211, 0.010097, -0.000967, 0.020826, 0.003964, 0.022173, -0.000213, -0.061385, 0.002546, 0.009061, 0.013527, -0.011682, -0.015213, -0.001057, 0.063101, 0.050637, -0.022872, -0.035087, 0.042016, -0.033746, 0.031328, -0.037599, -0.015012, 0.011167, 0.032297, 0.027289, -0.018864, -0.031862, 0.015993, 0.004663, 0.066963, -0.034277, -0.002554, 0.018557, -0.006021, 0.042066, 0.022115, 0.005511, 0.046074, -0.003229, 0.014158, -0.010727, -0.01397, 0.021422, -0.045174, -0.017393, -0.075977, 0.00568, -0.008479, -0.048204, -0.056731, 0.030873, 0.360369, 0.036915, -0.043168, -0.010904, 0.012496, -0.010259, 0.025601, 0.02951, -0.013344, 0.028594, 0.05016, -0.055324, 0.074922, 0.098784, -0.019115, -0.054751, -0.001972, 0.010656, 0.0634, 0.047581, 0.027923, 0.030898, 0.009373, -0.132838, -0.020946, -0.031544, -0.094, 0.008126, 0.065123, -0.044441, 0.002067, -0.006756, -0.044498, -0.023489, 0.045808, 0.017586, 0.000357, -0.034459, 0.050805, -0.095207, 0.038451, 0.012812, 0.007959, 0.027107, 0.022469, 0.037665, -0.00224, 0.00171, -0.077602, 0.083968, -0.014785, -0.024294, -0.047597, 0.011214, 0.003781, 0.020349, -0.063311, 0.023247, 0.001525, 0.014924, -0.034067, -0.003004, -0.050262, 0.003603, 0.015828, 0.012019, 0.005375, -0.065817, -0.025118, -0.009268, 0.004348, -0.001557, 0.020108, 0.004582, 0.004474, -0.008306, -0.006083, -0.006402, 0.016696, 0.035505, -0.028159, 0.055572, -0.027557, -0.006216, 0.035859, 0.017498, 0.010889, 0.040202, -0.003135, 0.034485, -0.005625, -0.051652, 0.007194, 0.023443, -0.051749, -0.076171, -0.022503, -0.0269, 0.017706, 0.032088, -0.058315, -0.027372, -0.07241, -0.050702, -0.013111, 0.051637, -0.009427, -0.029591, -0.056478, -0.114555, -0.009035, 0.086795, 0.01203, 0.01548, -0.01628, -0.038102, 0.015553, -0.018501, -0.061567, -0.06657, 0.002745, 0.010679, 0.032006, -0.009735, -0.025112, -0.033264, -0.012847, -0.028021, -0.007161, 0.024053, -0.008344, 0.003521, -0.012419, -0.005079, -0.014995, 0.015924, -0.018514, -0.006712, 0.048053, -0.00218, 0.039182, -0.010704, 0.041111, 0.064962, -0.027677, -0.028445, 0.068983, 0.022578, 0.029989, -0.010692, 0.015009, 0.009853, 0.015304, -0.068124, -0.102996, -0.361491, 0.024781, 0.033275, 0.026216, 0.131426, 0.020246, -0.000487, 0.032495, -0.007699, 0.005206, -0.023283, -0.032601, 0.005728, 0.040214, 0.046045, 0.015129, -0.043969, -0.088847, -0.006163, 0.054618, 0.025995, -0.019175, 0.001142, -0.01721, 0.043276, 0.00647, 0.002912, 0.051337, -0.003411, 0.047498, -0.027721, -0.031164, 0.018002, -0.059966, 0.011078, -0.029567, 0.008697, 0.010235, 0.009175, 0.030988, 0.04805, -0.140247, -0.059604, 0.059278, -0.026908, -0.046296, -0.0443, -0.042427, -0.022893, 0.031759, 0.005501, -0.013223, -0.033948, -0.045302, 0.060082, 0.001192, 0.034624, 0.021006, -0.006179, -0.021801, -0.012048, -0.015444, 0.019885, -0.001981, 0.020755, -0.006751, 0.080972, -0.001497, 0.027305, -0.021021, -0.013586, 0.030754, 0.021159, -0.021345, -0.031087, 0.008126, 0.023645, -0.066247, 0.016393, 0.002813, 0.052063, 0.002745, 0.003559, 0.066664, -0.033008, 0.011301, 0.056715, -0.098933, 0.011133, -0.048817, -0.024567, 0.005743, 0.039984, -0.042808, -0.015667, 0.07276, 0.010584, -0.043791, 0.007999, 0.001179, 0.103443, -0.008506, -0.0555, 0.047643, 0.005551, 0.145966, 0.041756, 0.021332, -0.013024, 0.009125, -0.02332, 0.01728, -0.03999, -0.043993, -0.060491, 0.049883, 0.050475, -0.030032, 0.050664, -0.020712, -0.004448, 0.030875, -0.029324, -0.011685, 0.090267, -0.024084, 0.04658, 0.029495, 0.006426, 0.000047, 0.031116, 0.084647, 0.037171, 0.01054, -0.01927, 0.001946, -0.01795, -0.009193, -0.050264, -0.066839, 0.013344, -0.059783, 0.014104, -0.039329, 0.004742, -0.041412, -0.003089, 0.008493, 0.018947, -0.006056, 0.041621, 0.043278, 0.048642, 0.001665, -0.006537, 0.003174, 0.031448, 0.031922, -0.005749, 0.016745, 0.025757, -0.013551, -0.017708, 0.011392, -0.025132, -0.016203, -0.011887, 0.006629, 0.137113, 0.032773, 0.005548, 0.014838, -0.025729, -0.004747, -0.055756, 0.058244, -0.030447, 0.01432, -0.044592, 0.020884, -0.032668, 0.022238, -0.026457, -0.047897, -0.003378, 0.030181, 0.025068, -0.015738, 0.053187, 0.02255, 0.046108, -0.025726, 0.006019, 0.0517, 0.033973, 0.041428, 0.049875, -0.051823, -0.034293, -0.023504, -0.037397, 0.007697, -0.014967, -0.006437, -0.039895]', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(4, 4, 4, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.036193, 0.04677, 0.012729, 0.012441, -0.013304, 0.02392, -0.016031, 0.00162, 0.01138, -0.064927, 0.04866, 0.033469, -0.024671, 0.063666, -0.003544, 0.002937, -0.091916, 0.005964, 0.024876, 0.03843, -0.000347, 0.085065, 0.004405, -0.001106, 0.014934, -0.018393, 0.036228, 0.024911, -0.007582, 0.007864, 0.038069, 0.011421, 0.029484, 0.003047, 0.025051, 0.024425, -0.057377, 0.016446, -0.011696, -0.01394, -0.008114, -0.002361, 0.004618, 0.016897, 0.034722, -0.034028, 0.029561, -0.011917, 0.010603, -0.035978, 0.02285, 0.033228, -0.042511, -0.039352, 0.031529, -0.04339, -0.000101, 0.043606, 0.029279, -0.003031, -0.024408, -0.003421, -0.025875, -0.028507, -0.057521, 0.017787, 0.02044, -0.024561, 0.016034, 0.026356, -0.022689, -0.033604, 0.019584, 0.060908, -0.040756, -0.048016, 0.088352, 0.005873, 0.025357, 0.050212, 0.027972, 0.026116, -0.010447, 0.019405, 0.060071, -0.006019, 0.026082, 0.018562, 0.008131, 0.033706, -0.002057, -0.044589, -0.007461, 0.014728, 0.027326, -0.025658, -0.029402, -0.009866, 0.045533, 0.019472, 0.02239, 0.014851, -0.006886, 0.043035, -0.008718, 0.031644, 0.010052, -0.076611, 0.019261, 0.00961, -0.012192, -0.011402, -0.02595, -0.014552, 0.04904, 0.08287, -0.019936, -0.042084, 0.045723, 0.006472, 0.01542, -0.027534, -0.018956, 0.008865, 0.037193, 0.019106, -0.01652, -0.012437, 0.011926, 0.023835, 0.067423, -0.016761, -0.005531, 0.023634, 0.00008, 0.022404, 0.041143, 0.009195, 0.033446, -0.023208, 0.042383, -0.009281, -0.015472, 0.016327, -0.007959, -0.034355, -0.082016, -0.018194, -0.013643, -0.032349, -0.063168, 0.034577, 0.379077, 0.037087, -0.027474, -0.003508, 0.012895, -0.000205, 0.013414, 0.039955, -0.006506, 0.037406, 0.052875, -0.043517, 0.049094, 0.114048, -0.009223, -0.049397, -0.013772, 0.007762, 0.068211, 0.030094, 0.024905, 0.038996, 0.020014, -0.136347, -0.015179, -0.026049, -0.176087, 0.04022, 0.048048, -0.046774, -0.006999, -0.027575, -0.035917, -0.015611, 0.041344, 0.01681, 0.00369, -0.022339, 0.058378, -0.090645, 0.038314, 0.02057, 0.002149, 0.01321, 0.027515, 0.043903, -0.00808, 0.003505, -0.102609, 0.108615, -0.026642, -0.005235, -0.032675, 0.020693, -0.013236, 0.016648, -0.070346, 0.026091, -0.006762, 0.012523, -0.025572, 0.015327, -0.054358, -0.004163, 0.028651, 0.004987, -0.000383, -0.062248, -0.032493, -0.008806, -0.002771, 0.011612, 0.035283, 0.010227, -0.007952, -0.004588, -0.008082, -0.011887, 0.010206, 0.049159, -0.031178, 0.051318, -0.019511, -0.013778, 0.058721, 0.031724, 0.014356, 0.037301, 0.031186, 0.047688, 0.017729, -0.065619, 0.002609, 0.035574, -0.046442, -0.081161, -0.026435, -0.026002, 0.0122, 0.032132, -0.059051, -0.02273, -0.061449, -0.045639, 0.020491, 0.052726, -0.005582, -0.001255, -0.057589, -0.095177, -0.030026, 0.097788, 0.00926, -0.00934, -0.01842, -0.027663, 0, -0.017341, -0.036158, -0.070051, 0.015661, 0.02532, 0.03572, -0.014293, -0.029257, -0.046775, -0.007409, -0.010042, -0.013018, 0.042312, -0.005461, -0.013721, -0.006004, -0.002346, -0.008686, 0.031618, -0.024115, 0.004245, 0.021128, 0.020685, 0.030023, -0.028561, 0.036003, 0.059141, -0.041484, -0.038543, 0.042771, 0.020329, 0.007863, -0.022213, 0.013768, 0.00529, 0.014792, -0.058554, -0.094269, -0.379863, 0.014756, 0.031148, 0.004418, 0.131031, 0.025321, 0.003576, 0.024999, -0.003821, 0.004587, -0.032755, -0.041521, -0.004683, 0.0286, 0.032041, 0.022391, -0.017494, -0.076905, 0.001257, 0.056217, 0.007123, -0.014118, 0.010246, -0.018197, 0.026992, 0.01285, 0.009167, 0.041538, -0.008182, 0.034168, -0.018513, -0.032085, 0.013665, -0.072842, -0.001869, -0.034848, 0.02049, 0.016697, -0.006988, 0.023849, 0.043973, -0.11629, -0.067711, 0.012692, -0.047814, -0.032656, -0.038898, -0.037206, -0.014774, 0.02012, -0.017942, -0.020626, -0.033015, -0.056398, 0.043093, 0.003256, 0.027309, 0.037083, -0.01122, -0.032897, -0.007438, -0.006636, 0.027719, -0.003342, -0.000691, -0.006509, 0.06788, 0.018308, -0.016763, -0.021762, -0.020434, 0.037155, 0.039987, -0.042468, -0.030306, 0.004041, 0.012114, -0.059555, 0.001053, -0.000953, 0.052166, -0.000135, 0.003323, 0.063377, -0.01541, 0.008757, 0.052011, -0.064445, 0.020114, -0.071014, -0.016073, 0.011777, 0.034885, -0.048365, -0.018651, 0.066514, 0.008334, -0.036467, 0.025001, 0.00182, 0.086617, -0.021179, -0.053478, 0.039494, -0.015996, 0.164546, 0.033101, 0.021186, -0.021221, 0.027783, -0.031377, 0.014619, -0.041254, -0.04716, -0.028415, 0.064166, 0.046939, -0.026199, 0.019921, -0.006651, -0.005783, 0.051811, -0.005784, -0.008068, 0.073547, -0.021054, 0.04395, 0.011585, 0.011089, -0.004038, 0.0237, 0.066796, 0.019588, 0.018267, -0.007633, -0.021132, -0.019219, -0.002622, -0.05358, -0.063292, -0.006295, -0.081263, 0.011182, -0.019817, -0.010643, -0.030759, -0.009621, 0.008971, 0.036055, 0.022757, 0.045634, 0.039496, 0.045805, 0.020568, -0.003016, 0.008519, 0.01568, 0.007763, 0.003367, 0.00789, 0.027447, -0.015291, 0.020643, 0.02253, -0.014297, -0.016337, -0.011976, 0.00645, 0.10639, 0.018197, 0.004941, -0.003592, -0.030992, -0.003029, -0.05485, 0.05085, -0.003481, 0.012908, -0.045393, 0.048337, -0.018239, 0.028022, -0.011595, -0.042674, -0.007517, 0.010802, 0.017183, -0.005496, 0.060287, 0.024835, 0.043319, -0.036624, 0.018001, 0.050175, 0.042984, 0.045836, 0.009595, -0.043564, -0.040519, -0.018611, -0.057429, -0.009316, -0.012854, -0.018361, -0.04819]', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(5, 5, 5, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.014934, -0.063075, 0.047074, -0.011613, 0.012396, -0.022037, -0.018457, -0.02974, -0.029618, 0.041698, -0.022793, 0.002711, 0.003056, -0.020931, -0.00409, 0.053246, -0.101205, -0.008724, -0.007784, 0.002852, -0.0194, -0.083743, 0.027015, 0.014596, -0.016238, -0.115367, -0.046585, 0.005956, 0.021888, 0.063208, 0.137292, -0.017503, 0.00561, 0.038191, -0.023151, -0.088626, -0.047188, 0.000871, 0.002538, 0.026396, -0.030511, 0.013748, -0.003626, 0.023332, 0.01762, -0.007769, 0.040964, 0.001497, -0.055612, -0.001413, 0.00415, 0.029349, 0.001748, 0.028137, -0.080081, 0.014029, -0.004401, -0.023299, -0.007972, -0.049981, -0.030649, -0.047754, -0.021819, 0.04028, -0.02281, -0.011608, 0.041699, 0.080464, -0.071856, -0.023337, -0.03524, 0.034895, 0.014162, -0.056743, 0.040978, -0.034902, 0.064503, 0.009025, 0.047859, -0.008916, -0.046969, 0.036008, -0.045404, -0.00864, 0.029641, 0.014176, 0.068057, -0.055402, 0.009477, 0.024687, -0.017021, 0.001733, -0.021739, -0.015947, 0.010273, -0.000392, -0.040938, 0.000965, 0.012897, 0.047176, 0.01828, 0.025699, 0.033274, -0.002777, 0.010768, 0.02493, 0.007315, 0.052, -0.045521, 0.054627, 0.031227, 0.002756, -0.055096, -0.009909, 0.017638, 0.03749, 0.045017, -0.00826, 0.009821, -0.061499, 0.021447, 0.000823, 0.004469, 0.035263, 0.02239, 0.035035, -0.017636, -0.021555, 0.014546, -0.01421, 0.038689, -0.062806, 0.063757, -0.004637, -0.021981, -0.045727, -0.030802, 0.046574, 0.045625, -0.044626, -0.001948, 0.033773, -0.010957, 0.030496, -0.043057, -0.022161, -0.027802, 0.06553, 0.02784, -0.023184, 0.005175, -0.044253, 0.332281, 0.016386, -0.0053, 0.035933, 0.010136, 0.025794, -0.016544, -0.036852, 0.013569, 0.027528, 0.007182, -0.010402, -0.037765, 0.00099, -0.042091, -0.037249, -0.009885, -0.000552, 0.020879, 0.011926, 0.076779, 0.035766, 0.011065, 0.101321, 0.059876, 0.004766, -0.048857, 0.009068, -0.030161, -0.018983, 0.036942, 0.016934, 0.01235, -0.036063, 0.043542, 0.018269, -0.030951, 0.053963, -0.004522, -0.137958, -0.063625, 0.033436, 0.050364, 0.066148, -0.026278, 0.048419, -0.020443, -0.061639, 0.016848, -0.152014, 0.00244, -0.017648, -0.008511, -0.029684, -0.023477, 0.030472, -0.020029, -0.061874, -0.012669, -0.080143, -0.033242, 0.021055, 0.003472, 0.029538, 0.027044, -0.039972, 0.02331, 0.023364, 0.005413, -0.029204, 0.022985, -0.071206, -0.03202, -0.039754, -0.006165, 0.007573, -0.021102, 0.002209, 0.084032, 0.00087, 0.026962, -0.011874, -0.01297, 0.0249, -0.034429, 0.014838, -0.008341, 0.020588, 0.013449, -0.024648, -0.009466, -0.00812, 0.024383, 0.042258, -0.037487, -0.112943, 0.080346, -0.001417, 0.048738, 0.013508, -0.025228, -0.035207, -0.025356, -0.004642, 0.017026, 0.014503, -0.06799, -0.084893, -0.025548, 0.00992, -0.002751, -0.023505, 0.041961, -0.02798, -0.000567, 0.015012, 0.006918, -0.009111, -0.075688, -0.043112, -0.033971, 0.030134, -0.046765, -0.023351, 0.010676, 0.038275, -0.019314, -0.034204, 0.000623, 0.004379, 0.002237, 0.004725, 0.019717, -0.017188, 0.054542, -0.03277, 0.02065, -0.049584, 0.061698, -0.020352, 0.006333, -0.000274, -0.04112, -0.000799, -0.100869, -0.02877, 0.024397, 0.042971, 0.021156, -0.049477, -0.034467, -0.004553, 0.03995, 0.034045, -0.023138, -0.330594, 0.009022, 0.05967, 0.044729, 0.078825, 0.045632, 0.036243, -0.021566, -0.048155, -0.001617, -0.034499, -0.035472, -0.051036, -0.022684, 0.00966, 0.029349, 0.002118, 0.016388, -0.021908, 0.038014, 0.033087, -0.006478, 0.01133, -0.03165, 0.011262, -0.001261, -0.023661, 0.038766, 0.016885, 0.027733, -0.003778, -0.070076, -0.023702, 0.000352, -0.00116, -0.045138, 0.02082, 0.034497, -0.066124, -0.007502, 0.055981, -0.026232, 0.039305, -0.031687, -0.000213, 0.030894, 0.038974, -0.10072, 0.002234, 0.008851, 0.035284, 0.005082, -0.006484, -0.051544, 0.05787, -0.016066, -0.006986, 0.01079, -0.002006, -0.075263, -0.008945, 0.001751, -0.050916, -0.044774, 0.125125, 0.002995, 0.02691, 0.029922, 0.00437, 0.049081, 0.007447, -0.002446, 0.012676, 0.019025, -0.039556, -0.00881, 0.040536, -0.037325, 0.069316, 0.008636, 0.017952, -0.018067, -0.020506, 0.011782, -0.048896, 0.003739, -0.006451, -0.090449, -0.002888, 0.096986, 0.025176, 0.005904, 0.021248, -0.028293, 0.042889, 0.015256, 0.005417, -0.001168, 0.010828, -0.02962, 0.023722, -0.008762, 0.000197, 0.032249, -0.028795, -0.076388, -0.03564, -0.006124, -0.005536, -0.089305, 0.030613, -0.001251, -0.016476, -0.008479, -0.095618, -0.049115, 0.040554, 0.048755, 0.04303, -0.022995, -0.033576, -0.051101, -0.035423, -0.049289, 0.202093, 0.02328, -0.022911, 0.004732, -0.038039, 0.009072, -0.018974, 0.018298, 0.018815, -0.008513, -0.086616, 0.033946, -0.008508, -0.008924, -0.031568, -0.051282, -0.007032, -0.02653, 0.052156, 0.022788, 0.003557, -0.04442, 0.046839, -0.007313, -0.015429, -0.039651, 0.025029, 0.038955, 0.038103, -0.054203, 0.087224, -0.06746, -0.002612, 0.031613, 0.061611, 0.008994, 0.005313, 0.019984, -0.032764, -0.079963, -0.017002, -0.007687, 0.008622, 0.034419, 0.006215, 0.073923, 0.037387, 0.030654, -0.018721, 0.021966, -0.015264, 0.025702, -0.021487, -0.023187, 0.022005, -0.002783, -0.031057, 0.020943, -0.000477, 0.0349, 0.027517, -0.025638, -0.004181, 0.003728, 0.05476, 0.019472, 0.023134, -0.069614, -0.003513, -0.006271, -0.035424, 0.032199, 0.050756, 0.004572, -0.040674, 0.008323, -0.030913, 0.029222, 0.029346, 0.015217, -0.053705]', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(6, 6, 6, 'ViT-B-32:laion2b_s34b_b79k', 512, '[-0.00425, -0.075835, 0.040602, -0.033611, 0.022543, -0.004466, -0.023447, -0.029871, -0.019901, 0.061236, -0.01397, 0.0113, -0.002184, -0.024932, -0.012084, 0.069535, -0.086584, -0.016109, -0.023875, -0.010117, -0.012757, -0.088945, 0.020962, 0.008444, -0.027316, -0.122872, -0.048, 0.015622, 0.031627, 0.070998, 0.124757, -0.037446, 0.000696, 0.022238, -0.03626, -0.090618, -0.041445, -0.029824, -0.010332, 0.013985, -0.026322, 0.029626, -0.006355, 0.031701, 0.006592, -0.001142, 0.041078, -0.0149, -0.063171, 0.004683, 0.006696, -0.004881, 0.003051, 0.025567, -0.075794, 0.009937, -0.009548, -0.022867, 0.002338, -0.029682, -0.035578, -0.037699, -0.021809, 0.041988, -0.004161, -0.000643, 0.043209, 0.045572, -0.065378, -0.021003, -0.032933, 0.028777, 0.009578, -0.047204, 0.051986, -0.076047, 0.025408, 0.017757, 0.035998, -0.020727, -0.040235, 0.027647, -0.049476, -0.005367, 0.031702, 0.008912, 0.062738, -0.046657, -0.005069, 0.051173, -0.006138, -0.012937, -0.022749, -0.013248, -0.006915, -0.013012, -0.05, -0.01255, 0.005865, 0.028003, 0.031222, 0.026354, 0.027463, 0.007184, 0.01812, 0.019658, 0.009408, 0.059867, -0.048609, 0.044514, 0.029555, 0.009178, -0.046803, -0.012341, 0.000528, 0.043647, 0.024973, 0.014079, 0.008942, -0.022394, 0.01021, 0.008926, 0.001124, 0.039968, 0.005638, 0.034432, -0.025396, -0.023702, -0.004362, -0.003844, 0.025567, -0.052794, 0.052856, -0.006008, -0.002731, -0.036989, -0.033601, 0.050216, 0.054508, -0.048867, 0.010102, 0.01774, 0.008887, 0.042734, -0.049201, -0.031861, -0.034965, 0.060992, 0.005687, -0.025249, -0.015283, -0.037356, 0.371885, 0.03094, -0.006598, 0.022114, 0.012031, 0.017371, -0.015208, -0.031069, 0.027391, 0.028649, 0.006905, -0.018135, -0.046895, -0.0103, -0.02655, -0.01959, -0.029057, -0.009747, 0.034606, 0.00791, 0.073138, 0.025645, 0.001841, 0.07058, 0.057049, 0.038478, -0.040946, 0.016546, -0.042724, -0.003159, 0.029036, 0.012696, 0.014892, -0.014024, 0.050804, 0.027511, -0.022388, 0.036276, -0.026004, -0.139598, -0.044844, 0.045039, 0.056671, 0.076123, -0.015971, 0.037797, -0.029177, -0.044616, 0.029994, -0.143105, 0.0185, -0.01213, -0.029312, -0.015319, -0.021463, 0.05752, -0.025495, -0.055352, -0.023766, -0.060919, -0.026187, 0.00907, -0.004744, 0.022773, 0.038908, -0.021989, 0.029993, 0.038145, -0.016399, -0.01891, 0.021873, -0.051462, -0.012269, -0.040651, -0.008665, 0.028592, -0.043082, 0.010508, 0.065879, 0.009961, 0.016178, -0.016031, -0.009071, 0.02707, -0.011451, 0.015044, -0.00513, 0.013691, 0.015785, -0.026378, -0.007084, -0.017751, 0.019986, 0.042604, -0.035084, -0.111786, 0.096857, 0.029321, 0.036376, 0.004902, -0.044938, -0.0329, -0.039601, 0.00856, 0.019808, 0.012596, -0.091846, -0.067838, -0.018283, 0.02497, -0.02906, -0.002142, 0.034402, -0.045664, -0.014376, 0.02253, 0.013287, 0.011536, -0.064825, -0.038279, -0.028417, 0.022476, -0.051208, -0.003113, 0.01228, 0.015182, -0.021115, -0.006612, 0.006111, 0.001483, 0.009454, -0.000775, 0.008003, -0.013551, 0.051426, -0.044502, 0.01857, -0.031458, 0.053882, -0.019771, -0.010086, 0.002216, -0.040232, -0.013222, -0.103679, -0.044251, 0.004545, 0.044893, 0.049612, -0.061525, -0.037091, -0.006217, 0.039026, 0.051176, 0.00384, -0.369042, 0.000423, 0.059087, 0.065884, 0.077032, 0.050283, 0.039085, -0.036449, -0.037639, -0.00004, -0.045807, -0.031056, -0.061414, -0.037515, 0.012534, 0.019025, 0.021006, 0.014457, -0.004293, 0.049943, 0.014589, 0.001366, 0.005079, -0.037467, 0.002925, -0.001076, -0.028357, 0.032757, 0.02337, 0.020041, -0.012233, -0.049984, -0.034541, -0.00223, -0.007654, -0.069732, 0.033343, 0.051036, -0.04591, -0.014183, 0.045627, -0.026939, 0.028882, -0.019018, -0.01848, 0.027502, 0.034494, -0.053336, 0.023501, 0.005481, 0.009705, 0.005839, -0.00777, -0.056071, 0.034622, -0.034593, 0.0052, 0.025695, 0.007579, -0.084266, 0.002626, 0.035794, -0.045319, -0.022016, 0.099653, -0.003064, 0.026651, 0.024876, -0.001386, 0.041898, 0.000561, 0.011874, -0.001068, -0.007275, -0.030312, -0.007492, 0.040073, -0.022749, 0.057181, -0.00027, 0.015787, -0.029919, -0.018337, 0.005468, -0.032384, 0.005766, -0.012461, -0.101808, 0.011464, 0.064851, 0.017943, 0.001171, 0.018748, -0.026646, 0.046151, 0.005465, 0.002317, -0.000981, 0.02356, -0.004021, 0.009228, -0.014531, -0.00959, 0.03741, -0.017811, -0.075529, -0.046512, -0.001527, -0.01304, -0.074389, 0.025622, -0.010781, -0.010806, -0.010457, -0.089186, -0.045999, 0.03884, 0.040475, 0.040714, -0.030775, -0.010304, -0.029466, -0.040078, -0.06994, 0.202502, 0.023278, -0.033345, 0.001771, -0.022269, 0.006591, -0.047414, 0.006562, 0.026274, -0.012425, -0.087489, 0.017048, -0.003555, 0.007628, -0.031621, -0.031963, -0.013844, 0.010629, 0.046151, 0.043442, 0.00153, -0.039037, 0.046133, -0.003034, -0.00726, -0.018619, 0.013448, 0.025814, 0.022244, -0.0418, 0.075013, -0.034827, 0.007793, 0.013374, 0.070258, 0.000606, 0.028546, 0.019232, 0.002198, -0.071505, -0.012722, -0.016646, -0.006205, 0.050148, -0.0101, 0.066029, 0.040567, 0.017905, -0.005966, 0.038633, -0.032846, 0.012307, 0.007792, -0.030412, 0.024782, 0.015824, -0.013865, 0.021001, 0.017104, -0.038425, 0.037364, -0.009682, -0.008675, 0.01357, 0.024239, 0.015897, 0.018658, -0.081948, -0.00378, 0.003512, -0.020319, 0.007754, 0.042799, 0.004487, -0.05005, 0.025895, -0.045787, 0.017819, 0.026545, 0.018513, -0.064473]', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(7, 7, 7, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.070841, -0.043507, 0.101363, -0.004966, 0.034248, -0.038392, -0.000142, 0.021491, -0.005165, 0.007423, 0.028169, -0.042957, 0.023746, -0.000746, 0.036909, -0.000605, -0.065396, -0.0131, -0.055826, -0.028991, -0.038705, -0.003826, -0.037818, -0.008043, 0.018072, -0.036285, 0.025043, -0.039696, 0.034023, 0.037789, 0.033657, -0.013585, -0.003695, -0.024989, 0.037656, 0.000129, -0.019864, 0.008527, 0.00499, 0.049321, 0.004286, -0.014422, 0.019731, -0.003482, 0.002773, -0.03828, 0.010808, 0.014089, -0.003907, -0.021038, 0.03, -0.031167, 0.005429, 0.044285, -0.077211, -0.071573, -0.014332, -0.045325, 0.019379, 0.010685, -0.097453, -0.042772, -0.042992, 0.034035, -0.042311, 0.006236, -0.024792, 0.01889, -0.001955, -0.04182, -0.029419, 0.0395, 0.051273, 0.034514, -0.000872, 0.038447, -0.040073, 0.008872, -0.002516, 0.036713, 0.043527, -0.01414, -0.011962, 0.036691, 0.024004, 0.046267, -0.029061, -0.042405, 0.001162, -0.05062, -0.003271, 0.002993, -0.010376, 0.01838, -0.052969, 0.014221, -0.019695, 0.01979, 0.008834, -0.021362, -0.031016, -0.009789, -0.00484, -0.009812, 0.021183, -0.004175, 0.050097, 0.049049, -0.056451, 0.048424, -0.019795, -0.047985, -0.043312, 0.007201, 0.049168, 0.020163, 0.011979, 0.006358, -0.013669, 0.056929, 0.018806, 0.042395, 0.000362, 0.019655, -0.048387, 0.028459, -0.02564, 0.002447, 0.022608, 0.025106, 0.004979, -0.000021, -0.003495, 0.033901, 0.009066, 0.097775, -0.028525, 0.025348, -0.050821, -0.000654, -0.021278, 0.019279, 0.002023, 0.019923, -0.063125, -0.081073, -0.02869, 0.048088, -0.060805, 0.009512, 0.001255, -0.03941, 0.406149, 0.010916, -0.002396, 0.027866, 0.007441, 0.018401, 0.044869, -0.010166, 0.002719, 0.072357, -0.005439, -0.024794, -0.000305, 0.053422, 0.02492, -0.029766, 0.062363, -0.013189, 0.032686, 0.012201, -0.02141, 0.017914, 0.019038, -0.094986, -0.010099, -0.023897, 0.008473, -0.060605, -0.026739, -0.037069, -0.033137, 0.045209, -0.014994, -0.034729, 0.050146, 0.022411, -0.019147, -0.045334, 0.063865, -0.116817, -0.040135, 0.013391, 0.052359, 0.043548, -0.011829, 0.082132, 0.049883, -0.032292, 0.061051, -0.029344, 0.021049, -0.044409, 0.031641, -0.037552, -0.012115, -0.023375, 0.018358, -0.042109, -0.026164, 0.00886, 0.04571, -0.054279, -0.013014, 0.046208, 0.029502, -0.002332, 0.040214, -0.054228, 0.048777, -0.028252, -0.012089, 0.000285, -0.01059, 0.035565, 0.00395, -0.002464, 0.010518, 0.009303, -0.010789, -0.019946, 0.022648, 0.07149, -0.043624, 0.007747, -0.010229, 0.01808, -0.006631, 0.0581, 0.043718, -0.027768, -0.05116, -0.015979, -0.008529, 0.020426, 0.015184, -0.12008, -0.055832, -0.069394, 0.029883, -0.030181, -0.042084, -0.029485, -0.007723, 0.007739, -0.01156, 0.02126, 0.052101, -0.020001, -0.031949, -0.060665, 0.013431, 0.020789, -0.013376, 0.019881, 0.004219, 0.01862, 0.039692, -0.056621, -0.08334, -0.072386, -0.02849, 0.041705, -0.02132, -0.028032, 0.005314, 0.044853, 0.022375, -0.018458, 0.059209, -0.015627, -0.015665, 0.017766, -0.014839, -0.006201, 0.014778, -0.009807, 0.016811, 0.016175, 0.009465, -0.015701, 0.016752, 0.052882, 0.037471, 0.025708, 0.026957, -0.063271, 0.001939, 0.025593, 0.096274, -0.010359, -0.054978, 0.000694, 0.088038, -0.020008, -0.023828, -0.404579, 0.015414, 0.00501, 0.007753, 0.077067, -0.016803, 0.029012, 0.001864, 0.062274, 0.025232, 0.034027, -0.028969, -0.015637, 0.019695, 0.000379, -0.022661, 0.008727, -0.022626, 0.048324, 0.050706, 0.045951, 0.019111, 0.014379, -0.019521, 0.017731, 0.003845, -0.006762, 0.026332, -0.003117, 0.025342, -0.003927, 0.022906, 0.036855, -0.024667, -0.006785, 0.035202, -0.027516, 0.018466, 0.000852, 0.042727, 0.032102, -0.052589, -0.033394, 0.003155, 0.015999, -0.005886, 0.021998, -0.089879, -0.02231, 0.025557, 0.051536, -0.0105, 0.050896, -0.000098, 0.037965, 0.003554, -0.008213, -0.032554, -0.023954, -0.051058, 0.054528, 0.060449, -0.054834, 0.046744, 0.035116, -0.005151, 0.04518, 0.033956, 0.028207, 0.030156, 0.00403, 0.020143, -0.009298, 0.031132, 0.051092, 0.019183, 0.009169, -0.035361, 0.051694, 0.013517, 0.010916, 0.011344, 0.012133, 0.04653, -0.00122, -0.005677, -0.00208, -0.084618, -0.041266, -0.033367, -0.020144, 0.061124, -0.016101, 0.008823, 0.026692, -0.023476, 0.034519, 0.004667, -0.002776, -0.023328, 0.030368, -0.001898, 0.013881, 0.004378, 0.000749, 0.064816, 0.029309, 0.02024, -0.042059, -0.007054, -0.058067, -0.009258, -0.057473, -0.010184, -0.079257, -0.033945, -0.005425, -0.019538, 0.040352, -0.007661, -0.036244, 0.000116, 0.009488, 0.010073, 0.011602, -0.003832, -0.013368, -0.067549, 0.00357, -0.002171, -0.012001, 0.005073, 0.005544, 0.003488, 0.011635, -0.01235, -0.050956, 0.006217, 0.034563, -0.073828, 0.007844, -0.065218, 0.002665, -0.028765, 0.047593, -0.047217, 0.010146, 0.020101, -0.033883, 0.031187, 0.017879, 0.072441, -0.011398, 0.00042, -0.044603, -0.065449, 0.030546, 0.040024, -0.018673, -0.023318, -0.028764, -0.03272, -0.042364, 0.025248, 0.011212, 0.008764, 0.00439, -0.07394, 0.097017, 0.025967, 0.056113, 0.056155, -0.031954, -0.02405, -0.031525, 0.029492, -0.005167, -0.048493, -0.039507, -0.05222, -0.017413, -0.004847, -0.059447, -0.15646, -0.054816, -0.016279, 0.041753, -0.022274, 0.051829, 0.002413, -0.03247, -0.048022, -0.073624, -0.015464, 0.006894, -0.026759, -0.011449, 0.019636, 0.025142, -0.003295, 0.058511, -0.025185, 0.021837, 0.024692, 0.017414]', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(8, 8, 8, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.019132, -0.032542, -0.005652, 0.007278, -0.026408, -0.015312, 0.024968, -0.048791, -0.024996, 0.0087, 0.008462, -0.01075, 0.086946, 0.003872, -0.032696, -0.029632, -0.062126, -0.065242, -0.030932, -0.012171, -0.00012, -0.022873, -0.037153, -0.007979, 0.019751, -0.094772, -0.013972, 0.001689, 0.061813, -0.011061, 0.079691, -0.007655, 0.008161, 0.032927, -0.00209, -0.041219, 0.001653, -0.026161, 0.026087, -0.002306, -0.019341, -0.016994, 0.068921, 0.024499, 0.024075, -0.0188, 0.004191, -0.056952, -0.011964, 0.007888, 0.013173, 0.034498, 0.007263, 0.001995, -0.021042, 0.030499, -0.005766, 0.013762, 0.044958, 0.020533, -0.159475, -0.024054, -0.004525, -0.030179, 0.004458, 0.023788, 0.012781, 0.060183, -0.048229, 0.016239, -0.022197, -0.009592, -0.041392, 0.005423, 0.017176, -0.046869, -0.040674, 0.006125, 0.05157, 0.003485, -0.033877, 0.023212, -0.030204, -0.012991, -0.014988, -0.001169, 0.047492, -0.058527, -0.031609, -0.038707, -0.009402, -0.036367, -0.005882, -0.013538, 0.029938, 0.028909, -0.015023, 0.004105, -0.025521, -0.009651, 0.011485, 0.002549, 0.044217, -0.024807, 0.031581, 0.008246, 0.083441, 0.072478, -0.000383, 0.044729, 0.04659, 0.007033, -0.030884, 0.01697, 0.048796, -0.015492, 0.00881, 0.012783, 0.001583, -0.033668, -0.007261, 0.019341, -0.015936, -0.010132, -0.010677, 0.038182, 0.056906, -0.007819, -0.007594, -0.002954, -0.022273, -0.024896, 0.059022, 0.05143, 0.019327, 0.025089, 0.006461, 0.025102, -0.004509, -0.016765, -0.028439, -0.030066, -0.025195, 0.012907, -0.019638, 0.035425, -0.001434, 0.035056, 0.025377, -0.013147, 0.023841, 0.006126, 0.380802, 0.004218, -0.000402, 0.043304, -0.030808, 0.006088, 0.014018, -0.016861, 0.003306, 0.004474, -0.002364, -0.019222, -0.02884, 0.051596, -0.095271, 0.009814, 0.01668, 0.003012, 0.009769, 0.022149, 0.06154, -0.003529, -0.03736, 0.112903, -0.00587, 0.038354, -0.184004, -0.002386, -0.044847, -0.017159, -0.019182, 0.059373, -0.028624, 0.006477, -0.02382, 0.030656, -0.019048, 0.015815, -0.001896, -0.143091, 0.037871, -0.017618, -0.042431, 0.022655, 0.011809, 0.011103, 0.002976, -0.050245, 0.001261, -0.098194, 0.032792, 0.031079, -0.02707, -0.040348, -0.00708, -0.016125, 0.025464, -0.020318, -0.017957, -0.035253, 0.002948, 0.007983, 0.04642, 0.050319, -0.007314, -0.009082, 0.027099, 0.035231, 0.013357, -0.007379, -0.064307, 0.03886, 0.056097, 0.013577, 0.028749, -0.013331, -0.037777, 0.011571, 0.046027, -0.006601, -0.00623, -0.006419, 0.004812, -0.000664, -0.020803, -0.01204, -0.031973, 0.033593, -0.038747, -0.005755, 0.017291, -0.063818, 0.04013, -0.014558, -0.012699, -0.166134, 0.01379, 0.046667, 0.102155, -0.005868, -0.003411, -0.022258, 0.016105, -0.037665, -0.017583, 0.004921, -0.051977, -0.078805, -0.017032, 0.010175, 0.011417, -0.025702, 0.01667, 0.060697, -0.025216, -0.023925, 0.04982, -0.040749, 0.000169, 0.004096, -0.00852, 0.006619, -0.026822, -0.024824, 0.009332, 0.04605, -0.007822, 0.017207, -0.032406, 0.087075, -0.024161, -0.030276, -0.033531, -0.021263, 0.027996, 0.030422, 0.028548, -0.000359, -0.034284, -0.005746, 0.026359, -0.017104, -0.009756, -0.014556, 0.030693, 0.030723, -0.005627, 0.027736, 0.147977, -0.014797, -0.040433, 0.018128, -0.049791, -0.016325, -0.046588, -0.378969, -0.001067, 0.037343, -0.01461, 0.025033, 0.030326, 0.007761, -0.00063, -0.080694, -0.042839, 0.036421, -0.026699, -0.046437, 0.032787, 0.002682, 0.006186, 0.127998, -0.060236, -0.010989, 0.100496, -0.007453, 0.008433, 0.018958, 0.01645, 0.011197, -0.014006, -0.046744, 0.050757, 0.01532, -0.009046, 0.007667, -0.099242, 0.002376, -0.026507, -0.042753, -0.010821, -0.01734, -0.025022, -0.050585, 0.018754, -0.014659, -0.07857, 0.025641, -0.010261, 0.006948, -0.018562, 0.024317, -0.051435, -0.009032, -0.010479, -0.007664, 0.012441, -0.045143, 0.007184, -0.00057, 0.011655, 0.008787, -0.010357, 0.006408, -0.003112, -0.001403, 0.003362, 0.017032, -0.047489, 0.089839, -0.037751, -0.003759, 0.067824, -0.003844, 0.002146, 0.004428, 0.007722, 0.001326, -0.009538, -0.013673, -0.025978, 0.010149, -0.043329, 0.063401, 0.006895, 0.005086, 0.005146, -0.009986, 0.021127, -0.016825, -0.020738, 0.022394, 0.013327, 0.026605, 0.034732, 0.002824, 0.027611, -0.011769, -0.044093, 0.004316, -0.014513, -0.01038, -0.025529, -0.027481, -0.027999, 0.043676, -0.015971, 0.008664, 0.011868, 0.047128, 0.093189, 0.006097, -0.002237, -0.008127, -0.094171, 0.008191, 0.005918, 0.005299, 0.046984, -0.067588, -0.010214, 0.039816, -0.021404, 0.028662, 0.015252, 0.023398, -0.023371, 0.013508, -0.023383, 0.125856, -0.011183, -0.034334, 0.05783, -0.053431, -0.023554, 0.063637, 0.057064, 0.011367, -0.0391, -0.002834, 0.029914, 0.006321, -0.082019, 0.023106, -0.028916, 0.048104, -0.058697, -0.002267, 0.024994, 0.012187, -0.014584, 0.019838, 0.010872, 0.0159, -0.104785, 0.023494, 0.023477, 0.051594, -0.007998, 0.02745, -0.050586, 0.011332, 0.03838, -0.022365, 0.042737, 0.037491, -0.027094, -0.004849, -0.025219, 0.006381, 0.020901, -0.032541, -0.049727, 0.042786, 0.021751, 0.027835, -0.029707, -0.067812, 0.021257, -0.01674, 0.023471, -0.077345, -0.026871, 0.024474, 0.003614, -0.012723, -0.027761, 0.023046, -0.030589, -0.017056, -0.021545, -0.006082, -0.022273, 0.01746, 0.015395, -0.029104, -0.067256, -0.033772, 0.038409, -0.01271, -0.008086, -0.009043, -0.053991, -0.00329, -0.032021, 0.061006, 0.023155, -0.025661, 0.021768, 0.013578]', '2026-11-26 06:21:06', '2026-11-26 06:21:06'),
	(9, 9, 9, 'ViT-B-32:laion2b_s34b_b79k', 512, '[0.009642, -0.226517, 0.070226, 0.025127, -0.030922, 0.008549, 0.010015, -0.002661, 0.003087, -0.013092, 0.022537, -0.05294, -0.002101, 0.041957, 0.034383, 0.022956, -0.10988, -0.022649, -0.035129, -0.014781, -0.055562, 0.026386, 0.01174, -0.002744, 0.047532, -0.078578, -0.007583, -0.017579, -0.047913, 0.001123, 0.015608, -0.046737, 0.03617, -0.021628, -0.014683, -0.001229, -0.026769, 0.057845, 0.034741, 0.054686, 0.018674, 0.016092, 0.035411, 0.006158, 0.027031, -0.01088, 0.054943, -0.055919, -0.012277, -0.031982, 0.009121, 0.041526, 0.013633, 0.029375, -0.039853, -0.063627, 0.01455, -0.003479, -0.025013, -0.016858, -0.15342, -0.004941, -0.033788, 0.01508, -0.011252, 0.009256, 0.011808, 0.130736, -0.033806, -0.024946, -0.022573, 0.027775, -0.01035, -0.004797, -0.010061, -0.069992, 0.058646, 0.042182, -0.024132, -0.017263, 0.019453, -0.01894, 0.008418, 0.056575, -0.015686, 0.075247, 0.038222, 0.028529, -0.008426, 0.01913, -0.022419, 0.033879, 0.018807, -0.01854, -0.035924, -0.000259, -0.047513, 0.019048, 0.004224, -0.005037, -0.037757, 0.043358, 0.005258, 0.014256, -0.003151, 0.040185, 0.080386, 0.043875, 0.016089, 0.027031, 0.01958, -0.002916, -0.021361, 0.016403, 0.009788, 0.048257, -0.014071, -0.002717, -0.004157, 0.00053, 0.000867, -0.027293, 0.008837, -0.009121, -0.0106, 0.019616, 0.001673, -0.002714, 0.033612, 0.004028, 0.034004, -0.000431, 0.023194, 0.030237, -0.035448, -0.003653, -0.002822, 0.021797, -0.00062, 0.037074, -0.019645, -0.022691, -0.020402, 0.019936, -0.056639, -0.042125, 0.026695, -0.033263, -0.005017, -0.068301, -0.015484, -0.013096, 0.371226, -0.03448, -0.004088, 0.073824, 0.00126, 0.045721, -0.013385, 0.043253, 0.031451, 0.013363, -0.006157, 0.032205, 0.005037, 0.093539, -0.033514, 0.008158, -0.040711, -0.039918, -0.012609, 0.035823, -0.002735, 0.027433, 0.05208, -0.105383, -0.001351, 0.041514, -0.009187, -0.01539, -0.030467, 0.061257, -0.017433, 0.048422, -0.000684, -0.035997, 0.015314, 0.010593, 0.044479, 0.036915, -0.014848, -0.044447, -0.019685, 0.008002, 0.013011, 0.035111, -0.025284, 0.037468, -0.031248, 0.011024, 0.02305, -0.019856, 0.048012, -0.015179, -0.000199, -0.010478, -0.03053, 0.017525, 0.017993, -0.018921, -0.008484, -0.018564, -0.013723, 0.049279, -0.020907, 0.064073, 0.00398, -0.058799, 0.053956, -0.062239, -0.007152, -0.018866, 0.019034, 0.044737, 0.009662, -0.019935, -0.009459, 0.009515, -0.046931, 0.001629, 0.006857, 0.032941, -0.030768, 0.022864, -0.030373, 0.003251, 0.015619, 0.00343, 0.026215, 0.014717, -0.030334, 0.020814, -0.069278, -0.058097, -0.010337, 0.038689, -0.026652, -0.131347, -0.006196, -0.047764, 0.085098, 0.031395, -0.060366, -0.022615, -0.028267, -0.041505, -0.055669, 0.0118, -0.058727, -0.024029, 0.024519, -0.061729, 0.01831, -0.032857, 0.008561, 0.023816, -0.031646, -0.00995, -0.017696, -0.048014, 0.006254, -0.028237, 0.031561, 0.001203, -0.037364, -0.027706, -0.007666, -0.057867, -0.015017, 0.019356, -0.018893, 0.021656, 0.023647, -0.000914, -0.036728, -0.006285, -0.036461, 0.057559, 0.033819, 0.017394, -0.049324, 0.022003, -0.024296, -0.080135, -0.015113, 0.016353, 0.002578, -0.012648, 0.022178, 0.001372, -0.11983, 0.018059, -0.032502, -0.017113, -0.005196, -0.052383, -0.077301, -0.368938, 0.035986, 0.033447, -0.008175, 0.041884, -0.003876, -0.023066, 0.019583, 0.064111, -0.045276, -0.044491, -0.016006, -0.022017, 0.010658, 0.000446, 0.013315, 0.011579, -0.027404, -0.00813, 0.034745, 0.03903, 0.008147, 0.014849, -0.011676, 0.065832, 0.039792, -0.009842, 0.084376, 0.009749, -0.056826, 0.016688, -0.044928, 0.003972, 0.030178, 0.026527, 0.034118, 0.024983, 0.00565, -0.024242, -0.026634, 0.089794, -0.109545, 0.022289, 0.032193, 0.010754, -0.018206, -0.016514, 0.018471, 0.018234, -0.040562, -0.002274, 0.049945, 0.046782, -0.041455, -0.008024, -0.01023, 0.02489, -0.020081, 0.024584, -0.005814, -0.00016, 0.020655, -0.01222, -0.027093, 0.033033, 0.001, 0.024938, 0.003762, -0.017625, 0.011967, -0.013292, 0.018548, 0.009701, 0.045903, 0.011252, 0.027913, 0.051967, 0.004435, -0.003796, 0.008402, 0.033302, -0.032075, -0.007269, 0.021115, 0.000261, 0.006198, 0.008087, 0.041605, -0.001531, 0.025906, -0.02332, 0.008242, 0.029866, -0.003506, 0.029573, -0.004532, 0.082112, -0.002931, -0.034995, 0.013572, 0.083392, -0.033223, -0.023071, 0.00949, 0.001528, 0.078343, 0.025467, -0.014842, -0.034078, -0.006094, -0.034445, -0.019404, 0.007955, 0.014923, -0.103163, 0.015246, 0.000422, 0.004372, -0.019796, -0.034421, 0.030433, -0.015992, 0.004169, 0.021984, 0.169936, 0.001684, -0.006343, 0.003967, -0.040266, -0.034558, -0.028495, 0.02082, 0.016223, 0.03123, 0.006874, -0.058082, -0.040871, 0.001866, -0.021238, -0.072089, 0.004906, -0.037006, -0.042339, 0.033515, 0.058862, -0.048286, 0.008777, 0.007728, -0.00341, -0.018836, 0.007508, 0.046783, 0.034804, -0.045529, -0.040088, -0.042109, 0.036264, 0.001391, 0.0196, -0.037457, -0.043933, 0.030552, 0.028521, 0.010376, 0.026549, -0.080048, 0.006405, -0.01603, 0.140635, -0.00449, -0.003555, -0.035769, -0.018241, -0.002197, -0.037414, -0.001306, -0.01815, -0.029244, -0.011806, -0.03167, -0.01627, 0.00695, -0.044437, -0.046082, -0.0307, -0.011457, 0.052444, 0.016798, 0.042923, -0.014466, -0.021239, -0.093517, 0.00104, 0.031879, 0.002766, -0.033406, 0.003617, 0.040616, -0.014217, -0.039956, -0.054018, -0.051824, -0.017047, 0.024211, -0.02498]', '2026-11-26 06:21:06', '2026-11-26 06:21:06');

-- Dumping structure for table boracay_lf.item_custody_records
DROP TABLE IF EXISTS `item_custody_records`;
CREATE TABLE IF NOT EXISTS `item_custody_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_report_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `from_organization_id` bigint unsigned DEFAULT NULL,
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_from` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_custody_records_organization_id_foreign` (`organization_id`),
  KEY `item_custody_records_from_organization_id_foreign` (`from_organization_id`),
  KEY `item_custody_records_recorded_by_foreign` (`recorded_by`),
  KEY `item_custody_records_item_report_id_recorded_at_index` (`item_report_id`,`recorded_at`),
  CONSTRAINT `item_custody_records_from_organization_id_foreign` FOREIGN KEY (`from_organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_custody_records_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_custody_records_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_custody_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_item_custody_records_action` CHECK ((`action` in (_utf8mb4'received',_utf8mb4'transferred',_utf8mb4'released',_utf8mb4'disposed')))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_custody_records: ~6 rows (approximately)
DELETE FROM `item_custody_records`;
INSERT INTO `item_custody_records` (`id`, `item_report_id`, `organization_id`, `from_organization_id`, `action`, `received_from`, `recorded_by`, `recorded_at`, `remarks`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:04', 'Demo custody record.', '2026-11-26 06:21:04', '2026-11-26 06:21:04'),
	(2, 3, 1, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:05', 'Demo custody record.', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(3, 5, 3, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:05', 'Demo custody record.', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(4, 7, 2, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:05', 'Demo custody record.', '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(5, 8, 2, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:06', 'Demo custody record.', '2026-11-26 06:21:06', '2026-11-26 06:21:06'),
	(6, 9, 1, NULL, 'received', 'Boracay Desk Officer', 1, '2026-11-26 06:21:06', 'Demo custody record.', '2026-11-26 06:21:06', '2026-11-26 06:21:06');

-- Dumping structure for table boracay_lf.item_disposals
DROP TABLE IF EXISTS `item_disposals`;
CREATE TABLE IF NOT EXISTS `item_disposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_report_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `eligible_on` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'eligible',
  `method` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proposed_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disposed_on` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_disposals_item_report_id_unique` (`item_report_id`),
  KEY `item_disposals_proposed_by_foreign` (`proposed_by`),
  KEY `item_disposals_approved_by_foreign` (`approved_by`),
  KEY `item_disposals_organization_id_status_index` (`organization_id`,`status`),
  KEY `item_disposals_eligible_on_status_index` (`eligible_on`,`status`),
  CONSTRAINT `item_disposals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_disposals_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_disposals_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_disposals_proposed_by_foreign` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_item_disposals_status` CHECK ((`status` in (_utf8mb4'eligible',_utf8mb4'proposed',_utf8mb4'approved',_utf8mb4'completed',_utf8mb4'cancelled')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_disposals: ~0 rows (approximately)
DELETE FROM `item_disposals`;

-- Dumping structure for table boracay_lf.item_disposal_documents
DROP TABLE IF EXISTS `item_disposal_documents`;
CREATE TABLE IF NOT EXISTS `item_disposal_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_disposal_id` bigint unsigned NOT NULL,
  `private_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_disposal_documents_item_disposal_id_foreign` (`item_disposal_id`),
  CONSTRAINT `item_disposal_documents_item_disposal_id_foreign` FOREIGN KEY (`item_disposal_id`) REFERENCES `item_disposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_disposal_documents: ~0 rows (approximately)
DELETE FROM `item_disposal_documents`;

-- Dumping structure for table boracay_lf.item_images
DROP TABLE IF EXISTS `item_images`;
CREATE TABLE IF NOT EXISTS `item_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_report_id` bigint unsigned NOT NULL,
  `private_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validation_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'review',
  `validation_result` json DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_images_reviewed_by_foreign` (`reviewed_by`),
  KEY `item_images_item_report_id_is_primary_index` (`item_report_id`,`is_primary`),
  KEY `item_images_validation_status_index` (`validation_status`),
  CONSTRAINT `item_images_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_images_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_item_images_validation_status` CHECK ((`validation_status` in (_utf8mb4'passed',_utf8mb4'review',_utf8mb4'rejected',_utf8mb4'overridden')))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_images: ~9 rows (approximately)
DELETE FROM `item_images`;
INSERT INTO `item_images` (`id`, `item_report_id`, `private_path`, `public_path`, `mime`, `size`, `width`, `height`, `sha256`, `validation_status`, `validation_result`, `reviewed_by`, `reviewed_at`, `is_primary`, `created_at`, `updated_at`) VALUES
	(1, 1, 'item-images/private/1/875d6732-1411-4c68-9908-839b6e5558d6.jpg', 'item-images/public/1/e51b300b-f545-4191-83dd-09cb75f8ac99.jpg', 'image/jpeg', 773805, 1200, 896, 'b45c76cb1c1c108a319e8df3f0ffb34aa0c9fabbacd91c7b158729c8925c67a1', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:02', '2026-11-26 06:21:02'),
	(2, 2, 'item-images/private/2/aa2c0887-3c99-4b08-98fc-85081ca49278.jpg', 'item-images/public/2/de68fb75-96f1-45a8-8138-905fbbdb28b5.jpg', 'image/jpeg', 145858, 1104, 824, '4156c42ada7ca945a7e8e7392919ad22441ac3cc9b8be0944e11f500229f24ca', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:04', '2026-11-26 06:21:04'),
	(3, 3, 'item-images/private/3/c4f5a15a-cf5e-48c6-bd1f-c885072a734f.jpg', 'item-images/public/3/e23c7737-627e-45b2-b951-c616a6a6cbb4.jpg', 'image/jpeg', 647710, 1200, 896, '853d58798061e8ab0113b1054bbfc2001ed78805d32033852d2d4b0e4af49aed', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(4, 4, 'item-images/private/4/0c713913-cd32-4dad-aac8-4b7e3f7c2b59.jpg', 'item-images/public/4/07b6030a-d777-477e-a642-e781c4f28f79.jpg', 'image/jpeg', 103936, 1104, 824, 'f7a27a013542dba3e33c69c4d01cf60d7cc703b4510ab84a497aac8714889752', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(5, 5, 'item-images/private/5/b3338f2b-2bbb-408e-9a5a-ca2cecb06f6c.jpg', 'item-images/public/5/d8a02970-3f8a-4385-b9e4-f022cbc9e41d.jpg', 'image/jpeg', 635116, 1024, 1024, 'c6d5942e7c42521bc2ffc3e154e0b572cdcd905b6656231c9cf4f2aa5a611a5c', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(6, 6, 'item-images/private/6/9d7e209b-c4ea-4cd5-a10d-abdc8aa170ee.jpg', 'item-images/public/6/11ed9095-0b71-47d5-8faf-917da9003b3a.jpg', 'image/jpeg', 89459, 942, 942, '46d9fe8483d025af977dc6648d828fadd484fdbffbe6fc9a9934aadc3bfbc593', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(7, 7, 'item-images/private/7/66245eed-c163-44d9-8954-9b2acfd2dabd.jpg', 'item-images/public/7/083176cd-aced-4cca-b870-320a806b3fe5.jpg', 'image/jpeg', 683709, 1200, 896, '73ed6fab2a7d94dc3f33ea0d46a585ed6822763429060a8970a82000915ea3e2', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:05', '2026-11-26 06:21:05'),
	(8, 8, 'item-images/private/8/7d7c691d-02c4-4e59-8641-1925d74fcd54.jpg', 'item-images/public/8/b939a4fb-f4ce-4aa6-814a-48b50078d74f.jpg', 'image/jpeg', 765914, 1024, 1024, '4f999143627e34a3e58fa316ec6189d95f1cde28f1591abb304e983e4629e2a2', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:06', '2026-11-26 06:21:06'),
	(9, 9, 'item-images/private/9/bf9e8aac-e317-4b44-a7a0-ddc591bc5f35.jpg', 'item-images/public/9/6f542233-b03d-4aba-bb3d-6ab062399b28.jpg', 'image/jpeg', 696727, 1200, 896, '86dc9bdcdaeea9191801930fedf7c53894c233f5fcfdf87d3546486617a1495b', 'passed', '{"source": "demo_seeder", "status": "passed"}', NULL, NULL, 1, '2026-11-26 06:21:06', '2026-11-26 06:21:06');

-- Dumping structure for table boracay_lf.item_matches
DROP TABLE IF EXISTS `item_matches`;
CREATE TABLE IF NOT EXISTS `item_matches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lost_report_id` bigint unsigned NOT NULL,
  `found_report_id` bigint unsigned NOT NULL,
  `score` decimal(5,4) NOT NULL,
  `factors` json NOT NULL,
  `algorithm_version` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'meta-v1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suggested',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_matches_pair_unique` (`lost_report_id`,`found_report_id`),
  KEY `item_matches_found_report_id_foreign` (`found_report_id`),
  KEY `item_matches_reviewed_by_foreign` (`reviewed_by`),
  KEY `item_matches_status_score_index` (`status`,`score`),
  CONSTRAINT `item_matches_found_report_id_foreign` FOREIGN KEY (`found_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_matches_lost_report_id_foreign` FOREIGN KEY (`lost_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_matches_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_item_matches_status` CHECK ((`status` in (_utf8mb4'suggested',_utf8mb4'possible',_utf8mb4'dismissed')))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_matches: ~2 rows (approximately)
DELETE FROM `item_matches`;
INSERT INTO `item_matches` (`id`, `lost_report_id`, `found_report_id`, `score`, `factors`, `algorithm_version`, `status`, `reviewed_by`, `reviewed_at`, `review_remarks`, `created_at`, `updated_at`) VALUES
	(1, 4, 3, 0.8625, '{"date": 1, "brand": 1, "color": 1, "image": 0.9505, "model": 1, "category": 1, "location": 0.6, "item_name": 1, "description": 0.3656}', 'meta-v1+img:ViT-B-32:laion2b_s34b_b79k', 'suggested', NULL, NULL, NULL, '2026-11-26 06:21:08', '2026-11-26 06:49:53'),
	(2, 6, 5, 0.8799, '{"date": 1, "brand": 1, "color": 1, "image": 0.9518, "model": 1, "category": 1, "location": 0.6, "item_name": 1, "description": 0.4797}', 'meta-v1+img:ViT-B-32:laion2b_s34b_b79k', 'suggested', NULL, NULL, NULL, '2026-11-26 06:21:20', '2026-11-26 06:49:53');

-- Dumping structure for table boracay_lf.item_recoveries
DROP TABLE IF EXISTS `item_recoveries`;
CREATE TABLE IF NOT EXISTS `item_recoveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_report_id` bigint unsigned NOT NULL,
  `claim_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `claimant_user_id` bigint unsigned NOT NULL,
  `received_by_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_last4` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turnover_at` timestamp NOT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `verification_outcome` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_recoveries_item_report_id_unique` (`item_report_id`),
  UNIQUE KEY `item_recoveries_claim_id_unique` (`claim_id`),
  KEY `item_recoveries_organization_id_foreign` (`organization_id`),
  KEY `item_recoveries_claimant_user_id_foreign` (`claimant_user_id`),
  KEY `item_recoveries_processed_by_foreign` (`processed_by`),
  KEY `item_recoveries_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `item_recoveries_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_recoveries_claimant_user_id_foreign` FOREIGN KEY (`claimant_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_recoveries_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_recoveries_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_recoveries_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_recoveries_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_recoveries: ~0 rows (approximately)
DELETE FROM `item_recoveries`;

-- Dumping structure for table boracay_lf.item_reports
DROP TABLE IF EXISTS `item_reports`;
CREATE TABLE IF NOT EXISTS `item_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reporter_user_id` bigint unsigned DEFAULT NULL,
  `encoded_by` bigint unsigned DEFAULT NULL,
  `handling_organization_id` bigint unsigned DEFAULT NULL,
  `custody_organization_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distinguishing_features` text COLLATE utf8mb4_unicode_ci,
  `occurred_date` date NOT NULL,
  `occurred_time` time DEFAULT NULL,
  `barangay_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `location_details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_offered` tinyint(1) NOT NULL DEFAULT '0',
  `reward_details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_preferences` json DEFAULT NULL,
  `contact_phone_enc` text COLLATE utf8mb4_unicode_ci,
  `finder_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `finder_contact_enc` text COLLATE utf8mb4_unicode_ci,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `verification_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `item_status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `custody_stage` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booth_location` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `station_transferred_at` timestamp NULL DEFAULT NULL,
  `is_confidential` tinyint(1) NOT NULL DEFAULT '0',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `photo_exception_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_exception_by` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `received_at` timestamp NULL DEFAULT NULL,
  `source_locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `search_text` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_reports_reference_no_unique` (`reference_no`),
  KEY `item_reports_encoded_by_foreign` (`encoded_by`),
  KEY `item_reports_barangay_id_foreign` (`barangay_id`),
  KEY `item_reports_location_id_foreign` (`location_id`),
  KEY `item_reports_photo_exception_by_foreign` (`photo_exception_by`),
  KEY `item_reports_verified_by_foreign` (`verified_by`),
  KEY `item_reports_public_filter_idx` (`is_public`,`type`,`item_status`,`occurred_date`),
  KEY `item_reports_handling_verif_idx` (`handling_organization_id`,`verification_status`),
  KEY `item_reports_custody_status_idx` (`custody_organization_id`,`item_status`),
  KEY `item_reports_cat_brgy_idx` (`category_id`,`barangay_id`),
  KEY `item_reports_reporter_type_idx` (`reporter_user_id`,`type`),
  KEY `item_reports_custody_stage_index` (`custody_stage`),
  FULLTEXT KEY `item_reports_search_text_fulltext` (`search_text`),
  CONSTRAINT `item_reports_barangay_id_foreign` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_reports_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_reports_custody_organization_id_foreign` FOREIGN KEY (`custody_organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_reports_encoded_by_foreign` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_reports_handling_organization_id_foreign` FOREIGN KEY (`handling_organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_reports_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_reports_photo_exception_by_foreign` FOREIGN KEY (`photo_exception_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `item_reports_reporter_user_id_foreign` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `item_reports_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_item_reports_item_status` CHECK ((`item_status` in (_utf8mb4'open',_utf8mb4'resolved',_utf8mb4'closed',_utf8mb4'awaiting_turnover',_utf8mb4'in_custody',_utf8mb4'claim_pending',_utf8mb4'returned',_utf8mb4'disposal_pending',_utf8mb4'disposed'))),
  CONSTRAINT `chk_item_reports_type` CHECK ((`type` in (_utf8mb4'lost',_utf8mb4'found'))),
  CONSTRAINT `chk_item_reports_verification_status` CHECK ((`verification_status` in (_utf8mb4'pending',_utf8mb4'verified',_utf8mb4'needs_revision',_utf8mb4'rejected',_utf8mb4'withdrawn')))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.item_reports: ~9 rows (approximately)
DELETE FROM `item_reports`;
INSERT INTO `item_reports` (`id`, `reference_no`, `type`, `reporter_user_id`, `encoded_by`, `handling_organization_id`, `custody_organization_id`, `category_id`, `item_name`, `description`, `brand`, `model`, `color`, `distinguishing_features`, `occurred_date`, `occurred_time`, `barangay_id`, `location_id`, `location_details`, `reward_offered`, `reward_details`, `contact_preferences`, `contact_phone_enc`, `finder_name`, `finder_contact_enc`, `remarks`, `verification_status`, `item_status`, `custody_stage`, `booth_location`, `station_transferred_at`, `is_confidential`, `is_public`, `photo_exception_reason`, `photo_exception_by`, `verified_by`, `verified_at`, `rejection_reason`, `received_at`, `source_locale`, `search_text`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(1, 'BLF-F-2026-000001', 'found', NULL, 1, 1, 1, 2, 'Blue Titanium iPhone 15 Pro', 'Blue Titanium iPhone 15 Pro inside a clear protective case found near Station 2 beachfront.', 'Apple', 'iPhone 15 Pro', 'Blue', NULL, '2026-11-21', NULL, 1, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:01', NULL, '2026-11-26 06:21:01', 'en', 'blf f 2026 000001 blue titanium iphone 15 pro blue titanium iphone 15 pro inside a clear protective case found near station 2 beachfront apple iphone 15 pro blue balabag mobile phones cellphone', '2026-11-26 06:21:01', '2026-11-26 06:21:01', NULL),
	(2, 'BLF-L-2026-000001', 'lost', 1, 1, 1, NULL, 2, 'Blue Titanium iPhone 15 Pro', 'Lost my Blue Titanium iPhone 15 Pro around Balabag beach area earlier today. Please contact me if found.', 'Apple', 'iPhone 15 Pro', 'Blue', NULL, '2026-11-26', NULL, 1, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'verified', 'open', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:04', NULL, NULL, 'en', 'blf l 2026 000001 blue titanium iphone 15 pro lost my blue titanium iphone 15 pro around balabag beach area earlier today please contact me if found apple iphone 15 pro blue balabag mobile phones cellphone', '2026-11-26 06:21:04', '2026-11-26 06:21:04', NULL),
	(3, 'BLF-F-2026-000002', 'found', NULL, 1, 1, 1, 7, 'Black Leather Bifold Wallet', 'Classic black leather bifold wallet found on a bench along D-Mall walkway.', 'Coach', 'Bifold', 'Black', NULL, '2026-11-23', NULL, 1, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:04', NULL, '2026-11-26 06:21:04', 'en', 'blf f 2026 000002 black leather bifold wallet classic black leather bifold wallet found on a bench along d mall walkway coach bifold black balabag wallets purses pitaka', '2026-11-26 06:21:04', '2026-11-26 06:21:04', NULL),
	(4, 'BLF-L-2026-000002', 'lost', 1, 1, 1, NULL, 7, 'Black Leather Bifold Wallet', 'Lost my Black Leather Bifold Wallet around Balabag beach area earlier today. Please contact me if found.', 'Coach', 'Bifold', 'Black', NULL, '2026-11-23', NULL, 1, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'verified', 'open', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:05', NULL, NULL, 'en', 'blf l 2026 000002 black leather bifold wallet lost my black leather bifold wallet around balabag beach area earlier today please contact me if found coach bifold black balabag wallets purses pitaka', '2026-11-26 06:21:05', '2026-11-26 06:21:05', NULL),
	(5, 'BLF-F-2026-000003', 'found', NULL, 1, 3, 3, 1, 'GoPro Hero 12 Action Camera with Yellow Grip', 'Black GoPro action camera attached to a bright yellow floating hand grip found near Puka Beach.', 'GoPro', 'Hero 12 Black', 'Black', NULL, '2026-11-23', NULL, 3, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:05', NULL, '2026-11-26 06:21:05', 'en', 'blf f 2026 000003 gopro hero 12 action camera with yellow grip black gopro action camera attached to a bright yellow floating hand grip found near puka beach gopro hero 12 black black yapak electronics elektroniks', '2026-11-26 06:21:05', '2026-11-26 06:21:05', NULL),
	(6, 'BLF-L-2026-000003', 'lost', 1, 1, 3, NULL, 1, 'GoPro Hero 12 Action Camera with Yellow Grip', 'Lost my GoPro Hero 12 Action Camera with Yellow Grip around Yapak beach area earlier today. Please contact me if found.', 'GoPro', 'Hero 12 Black', 'Black', NULL, '2026-11-24', NULL, 3, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'verified', 'open', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:05', NULL, NULL, 'en', 'blf l 2026 000003 gopro hero 12 action camera with yellow grip lost my gopro hero 12 action camera with yellow grip around yapak beach area earlier today please contact me if found gopro hero 12 black black yapak electronics elektroniks', '2026-11-26 06:21:05', '2026-11-26 06:21:05', NULL),
	(7, 'BLF-F-2026-000004', 'found', NULL, 1, 2, 2, 16, 'Tortoiseshell Wayfarer Sunglasses', 'Dark tortoiseshell Wayfarer sunglasses left at a beachfront table near Station 3.', 'Ray-Ban', 'Wayfarer', 'Brown', NULL, '2026-11-21', NULL, 2, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:05', NULL, '2026-11-26 06:21:05', 'en', 'blf f 2026 000004 tortoiseshell wayfarer sunglasses dark tortoiseshell wayfarer sunglasses left at a beachfront table near station 3 ray ban wayfarer brown manoc manoc eyeglasses sunglasses salamin sa mata', '2026-11-26 06:21:05', '2026-11-26 06:21:05', NULL),
	(8, 'BLF-F-2026-000005', 'found', NULL, 1, 2, 2, 1, 'Ocean Blue 10L Waterproof Dry Bag', 'Ocean-blue 10L waterproof dry bag with black buckle straps found at Cagban Jetty Port waiting area.', 'Naturehike', '10L Dry Bag', 'Blue', NULL, '2026-11-24', NULL, 2, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:06', NULL, '2026-11-26 06:21:06', 'en', 'blf f 2026 000005 ocean blue 10l waterproof dry bag ocean blue 10l waterproof dry bag with black buckle straps found at cagban jetty port waiting area naturehike 10l dry bag blue manoc manoc electronics elektroniks', '2026-11-26 06:21:06', '2026-11-26 06:21:06', NULL),
	(9, 'BLF-F-2026-000006', 'found', NULL, 1, 1, 1, 1, 'White AirPods Pro with Navy Case', 'White wireless earbuds inside charging case with navy silicone cover found near White Beach Station 1.', 'Apple', 'AirPods Pro 2', 'White', NULL, '2026-11-22', NULL, 1, NULL, NULL, 0, NULL, NULL, NULL, 'Boracay Desk Officer', NULL, NULL, 'verified', 'in_custody', NULL, NULL, NULL, 0, 1, NULL, NULL, 1, '2026-11-26 06:21:06', NULL, '2026-11-26 06:21:06', 'en', 'blf f 2026 000006 white airpods pro with navy case white wireless earbuds inside charging case with navy silicone cover found near white beach station 1 apple airpods pro 2 white balabag electronics elektroniks', '2026-11-26 06:21:06', '2026-11-26 06:21:06', NULL);

-- Dumping structure for table boracay_lf.jobs
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.jobs: ~0 rows (approximately)
DELETE FROM `jobs`;

-- Dumping structure for table boracay_lf.job_batches
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.job_batches: ~0 rows (approximately)
DELETE FROM `job_batches`;

-- Dumping structure for table boracay_lf.locations
DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `barangay_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_barangay_id_name_unique` (`barangay_id`,`name`),
  KEY `locations_organization_id_foreign` (`organization_id`),
  KEY `locations_is_active_index` (`is_active`),
  CONSTRAINT `locations_barangay_id_foreign` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `locations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.locations: ~13 rows (approximately)
DELETE FROM `locations`;
INSERT INTO `locations` (`id`, `barangay_id`, `organization_id`, `name`, `type`, `description`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 'Station 1 Beachfront', 'beach', NULL, 11.9690000, 121.9212000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 1, 1, 'Station 2 Beachfront', 'beach', NULL, 11.9615000, 121.9255000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 1, 1, 'D\'Mall de Boracay', 'commercial', NULL, 11.9628000, 121.9267000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 1, 1, 'Bulabog Beach', 'beach', NULL, 11.9647000, 121.9308000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(5, 1, 4, 'Boracay Tourist Assistance Center Area', 'landmark', NULL, 11.9640000, 121.9261000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(6, 2, 2, 'Station 3 Beachfront', 'beach', NULL, 11.9535000, 121.9304000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(7, 2, 2, 'Cagban Jetty Port', 'port', NULL, 11.9396000, 121.9467000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(8, 2, 2, 'Tambisaan Jetty Port', 'port', NULL, 11.9451000, 121.9502000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(9, 2, 2, 'Tulubhan Beach', 'beach', NULL, 11.9524000, 121.9435000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(10, 3, 3, 'Puka Shell Beach', 'beach', NULL, 11.9953000, 121.9126000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(11, 3, 3, 'Ilig-Iligan Beach', 'beach', NULL, 11.9888000, 121.9267000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(12, 3, 3, 'Diniwid Beach', 'beach', NULL, 11.9765000, 121.9114000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(13, 3, 3, 'CityMall Boracay', 'commercial', NULL, 11.9789000, 121.9175000, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.migrations
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.migrations: ~18 rows (approximately)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2026_11_23_165635_create_notifications_table', 1),
	(5, '2026_11_23_165636_add_profile_and_google_fields_to_users_table', 1),
	(6, '2026_11_23_165638_create_roles_and_permissions_tables', 1),
	(7, '2026_11_23_165639_create_organizations_and_barangays_tables', 1),
	(8, '2026_11_23_165641_create_locations_and_categories_tables', 1),
	(9, '2026_11_23_165643_create_item_reports_and_images_tables', 1),
	(10, '2026_11_23_165644_create_item_matches_and_status_histories_tables', 1),
	(11, '2026_11_23_165646_create_claims_and_evidence_tables', 1),
	(12, '2026_11_23_165648_create_recoveries_custody_and_disposals_tables', 1),
	(13, '2026_11_23_165649_create_audit_settings_and_supporting_tables', 1),
	(14, '2026_11_23_212541_create_image_embeddings_table', 1),
	(15, '2026_11_23_214826_create_translations_table', 1),
	(16, '2026_11_24_102051_widen_algorithm_version_on_item_matches_table', 1),
	(17, '2026_11_24_171310_make_barangay_id_nullable_on_item_reports_table', 1),
	(18, '2026_11_24_172615_add_booth_station_custody_tracking_columns', 1);

-- Dumping structure for table boracay_lf.notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.notifications: ~2 rows (approximately)
DELETE FROM `notifications`;
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
	('4ed0a9ea-b123-4fc5-bd13-3af8f81fefaf', 'App\\Notifications\\SystemEventNotification', 'App\\Models\\User', 1, '{"event":"match.identified","title":"Possible Similar Item Found (BLF-L-2026-000003)","body":"A similar found item (\\"GoPro Hero 12 Action Camera with Yellow Grip\\", 86% match) was detected for your lost report \\"GoPro Hero 12 Action Camera with Yellow Grip\\". Review the found item and submit an ownership claim if it is yours.","action_url":"http:\\/\\/boracay.dev.com\\/items\\/BLF-F-2026-000003","meta":{"match_id":2,"lost_report_id":6,"found_report_id":5,"score":86}}', '2026-11-26 06:42:07', '2026-11-26 06:21:20', '2026-11-26 06:42:07'),
	('8b1e8ed0-3528-4be7-892d-c6067c7f464e', 'App\\Notifications\\SystemEventNotification', 'App\\Models\\User', 1, '{"event":"match.identified","title":"Possible Similar Item Found (BLF-L-2026-000002)","body":"A similar found item (\\"Black Leather Bifold Wallet\\", 83% match) was detected for your lost report \\"Black Leather Bifold Wallet\\". Review the found item and submit an ownership claim if it is yours.","action_url":"http:\\/\\/boracay.dev.com\\/items\\/BLF-F-2026-000002","meta":{"match_id":1,"lost_report_id":4,"found_report_id":3,"score":83}}', NULL, '2026-11-26 06:21:09', '2026-11-26 06:21:09');

-- Dumping structure for table boracay_lf.notification_deliveries
DROP TABLE IF EXISTS `notification_deliveries`;
CREATE TABLE IF NOT EXISTS `notification_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `channel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_deliveries_user_id_status_index` (`user_id`,`status`),
  KEY `notification_deliveries_notification_id_index` (`notification_id`),
  CONSTRAINT `notification_deliveries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_notification_deliveries_status` CHECK ((`status` in (_utf8mb4'queued',_utf8mb4'sent',_utf8mb4'failed')))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.notification_deliveries: ~4 rows (approximately)
DELETE FROM `notification_deliveries`;
INSERT INTO `notification_deliveries` (`id`, `notification_id`, `user_id`, `channel`, `notification_type`, `status`, `attempts`, `error`, `sent_at`, `created_at`, `updated_at`) VALUES
	(1, NULL, 1, 'database', 'match.identified', 'sent', 1, NULL, '2026-11-26 06:21:19', '2026-11-26 06:21:08', '2026-11-26 06:21:19'),
	(2, NULL, 1, 'mail', 'match.identified', 'sent', 1, NULL, '2026-11-26 06:21:19', '2026-11-26 06:21:19', '2026-11-26 06:21:19'),
	(3, NULL, 1, 'database', 'match.identified', 'sent', 1, NULL, '2026-11-26 06:21:21', '2026-11-26 06:21:20', '2026-11-26 06:21:21'),
	(4, NULL, 1, 'mail', 'match.identified', 'sent', 1, NULL, '2026-11-26 06:21:21', '2026-11-26 06:21:21', '2026-11-26 06:21:21');

-- Dumping structure for table boracay_lf.organizations
DROP TABLE IF EXISTS `organizations`;
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organizations_code_unique` (`code`),
  KEY `organizations_type_is_active_index` (`type`,`is_active`),
  CONSTRAINT `chk_organizations_type` CHECK ((`type` in (_utf8mb4'barangay_lgu',_utf8mb4'police_station')))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.organizations: ~4 rows (approximately)
DELETE FROM `organizations`;
INSERT INTO `organizations` (`id`, `type`, `code`, `name`, `contact_email`, `contact_phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'barangay_lgu', 'LGU-BALABAG', 'Boracay Police Assistance Booth - Station 1 & 2 (Central)', NULL, NULL, 'White Beach Station 1-2, Boracay Island, Malay, Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 'barangay_lgu', 'LGU-MANOCMANOC', 'Boracay Police Assistance Booth - Station 3 & Cagban', NULL, NULL, 'White Beach Station 3 / Cagban Jetty Port, Boracay Island, Malay, Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 'barangay_lgu', 'LGU-YAPAK', 'Boracay Police Assistance Booth - Puka & North Beach', NULL, NULL, 'Puka Shell Beach / North Coast, Boracay Island, Malay, Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 'police_station', 'PNP-MALAY-BTAC', 'Malay Municipal Police Station - Boracay Tourist Assistance Center', NULL, NULL, 'Main Road, Boracay Island, Malay, Aklan', 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.organization_barangay
DROP TABLE IF EXISTS `organization_barangay`;
CREATE TABLE IF NOT EXISTS `organization_barangay` (
  `organization_id` bigint unsigned NOT NULL,
  `barangay_id` bigint unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`organization_id`,`barangay_id`),
  KEY `organization_barangay_barangay_id_is_default_index` (`barangay_id`,`is_default`),
  CONSTRAINT `organization_barangay_barangay_id_foreign` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_barangay_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.organization_barangay: ~6 rows (approximately)
DELETE FROM `organization_barangay`;
INSERT INTO `organization_barangay` (`organization_id`, `barangay_id`, `is_default`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 2, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 3, 1, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 1, 0, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 2, 0, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 3, 0, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.organization_invitations
DROP TABLE IF EXISTS `organization_invitations`;
CREATE TABLE IF NOT EXISTS `organization_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invited_by` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `accepted_user_id` bigint unsigned DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_invitations_token_hash_unique` (`token_hash`),
  KEY `organization_invitations_organization_id_foreign` (`organization_id`),
  KEY `organization_invitations_role_id_foreign` (`role_id`),
  KEY `organization_invitations_invited_by_foreign` (`invited_by`),
  KEY `organization_invitations_accepted_user_id_foreign` (`accepted_user_id`),
  KEY `organization_invitations_email_expires_at_index` (`email`,`expires_at`),
  CONSTRAINT `organization_invitations_accepted_user_id_foreign` FOREIGN KEY (`accepted_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organization_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organization_invitations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_invitations_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.organization_invitations: ~0 rows (approximately)
DELETE FROM `organization_invitations`;

-- Dumping structure for table boracay_lf.organization_memberships
DROP TABLE IF EXISTS `organization_memberships`;
CREATE TABLE IF NOT EXISTS `organization_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `org_key` bigint unsigned GENERATED ALWAYS AS (coalesce(`organization_id`,0)) STORED,
  `role_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invited_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_memberships_user_org_unique` (`user_id`,`org_key`),
  KEY `organization_memberships_role_id_foreign` (`role_id`),
  KEY `organization_memberships_invited_by_foreign` (`invited_by`),
  KEY `organization_memberships_approved_by_foreign` (`approved_by`),
  KEY `organization_memberships_organization_id_status_index` (`organization_id`,`status`),
  CONSTRAINT `organization_memberships_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organization_memberships_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `organization_memberships_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `organization_memberships_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `organization_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_org_memberships_status` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'active',_utf8mb4'suspended',_utf8mb4'revoked')))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.organization_memberships: ~1 rows (approximately)
DELETE FROM `organization_memberships`;
INSERT INTO `organization_memberships` (`id`, `user_id`, `organization_id`, `role_id`, `status`, `invited_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
	(1, 1, NULL, 1, 'active', NULL, NULL, '2026-11-26 06:14:48', '2026-11-26 06:14:48', '2026-11-26 06:14:48');

-- Dumping structure for table boracay_lf.password_reset_tokens
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.password_reset_tokens: ~0 rows (approximately)
DELETE FROM `password_reset_tokens`;

-- Dumping structure for table boracay_lf.permissions
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_key_unique` (`key`),
  KEY `permissions_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.permissions: ~35 rows (approximately)
DELETE FROM `permissions`;
INSERT INTO `permissions` (`id`, `key`, `name`, `group`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'public.browse', 'Browse Public Listings', 'public', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 'report.create', 'Submit Lost or Found Report', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 'report.view_own', 'View Own Reports', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 'report.encode_on_behalf', 'Encode Reports on Behalf of Walk-ins', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(5, 'report.view_org', 'View Organization Reports', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(6, 'report.update_org', 'Update Pre-Verification Organization Reports', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(7, 'report.verify', 'Verify and Publish Reports', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(8, 'report.reject', 'Reject Reports', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(9, 'report.photo_exception', 'Approve Found Item Photo Exception', 'reports', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(10, 'image.review', 'Review Flagged Images', 'images', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(11, 'match.view', 'View Item Matches', 'matches', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(12, 'match.review', 'Confirm or Dismiss Item Matches', 'matches', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(13, 'claim.create', 'Submit Ownership Claim', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(14, 'claim.view_own', 'View Own Claims', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(15, 'claim.view_org', 'View Organization Claims', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(16, 'claim.evidence_view', 'View Private Claim Evidence', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(17, 'claim.request_info', 'Request Additional Claim Information', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(18, 'claim.approve', 'Approve Claims', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(19, 'claim.reject', 'Reject Claims', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(20, 'claim.complete', 'Complete Claims', 'claims', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(21, 'recovery.record', 'Draft Recovery Turnover Record', 'recoveries', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(22, 'recovery.confirm', 'Confirm Physical Item Recovery', 'recoveries', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(23, 'custody.manage', 'Manage Item Custody Records', 'custody', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(24, 'disposal.view', 'View Disposal Monitoring Queue', 'disposals', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(25, 'disposal.propose', 'Propose Item Disposal', 'disposals', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(26, 'disposal.approve', 'Approve and Record Item Disposal', 'disposals', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(27, 'staff.invite', 'Invite Organization Staff', 'organizations', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(28, 'staff.manage', 'Manage Organization Staff', 'organizations', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(29, 'organization.manage', 'Manage Organizations', 'organizations', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(30, 'masterdata.manage', 'Manage Categories and Barangays', 'masterdata', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(31, 'location.manage', 'Manage Locations', 'masterdata', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(32, 'settings.manage', 'Manage System Settings', 'settings', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(33, 'analytics.view', 'View Dashboard and Analytics', 'analytics', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(34, 'export.generate', 'Generate CSV and PDF Exports', 'analytics', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(35, 'audit.view', 'View Audit Logs', 'audit', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.permission_role
DROP TABLE IF EXISTS `permission_role`;
CREATE TABLE IF NOT EXISTS `permission_role` (
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.permission_role: ~87 rows (approximately)
DELETE FROM `permission_role`;
INSERT INTO `permission_role` (`role_id`, `permission_id`) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 1),
	(1, 2),
	(2, 2),
	(3, 2),
	(4, 2),
	(1, 3),
	(2, 3),
	(3, 3),
	(4, 3),
	(1, 4),
	(2, 4),
	(3, 4),
	(1, 5),
	(2, 5),
	(3, 5),
	(1, 6),
	(2, 6),
	(3, 6),
	(1, 7),
	(2, 7),
	(1, 8),
	(2, 8),
	(1, 9),
	(2, 9),
	(1, 10),
	(2, 10),
	(3, 10),
	(1, 11),
	(2, 11),
	(3, 11),
	(1, 12),
	(2, 12),
	(4, 13),
	(1, 14),
	(2, 14),
	(3, 14),
	(4, 14),
	(1, 15),
	(2, 15),
	(3, 15),
	(1, 16),
	(2, 16),
	(3, 16),
	(1, 17),
	(2, 17),
	(3, 17),
	(1, 18),
	(2, 18),
	(1, 19),
	(2, 19),
	(1, 20),
	(2, 20),
	(1, 21),
	(2, 21),
	(3, 21),
	(1, 22),
	(2, 22),
	(1, 23),
	(2, 23),
	(3, 23),
	(1, 24),
	(2, 24),
	(3, 24),
	(1, 25),
	(2, 25),
	(3, 25),
	(1, 26),
	(2, 26),
	(1, 27),
	(2, 27),
	(1, 28),
	(2, 28),
	(1, 29),
	(1, 30),
	(1, 31),
	(2, 31),
	(1, 32),
	(1, 33),
	(2, 33),
	(3, 33),
	(1, 34),
	(2, 34),
	(1, 35),
	(2, 35);

-- Dumping structure for table boracay_lf.reference_sequences
DROP TABLE IF EXISTS `reference_sequences`;
CREATE TABLE IF NOT EXISTS `reference_sequences` (
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint unsigned NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`prefix`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.reference_sequences: ~2 rows (approximately)
DELETE FROM `reference_sequences`;
INSERT INTO `reference_sequences` (`prefix`, `year`, `last_number`, `created_at`, `updated_at`) VALUES
	('BLF-F', 2026, 6, '2026-11-26 06:21:01', '2026-11-26 06:21:06'),
	('BLF-L', 2026, 3, '2026-11-26 06:21:04', '2026-11-26 06:21:05');

-- Dumping structure for table boracay_lf.report_status_histories
DROP TABLE IF EXISTS `report_status_histories`;
CREATE TABLE IF NOT EXISTS `report_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_report_id` bigint unsigned NOT NULL,
  `dimension` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `related_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_status_histories_changed_by_foreign` (`changed_by`),
  KEY `report_status_histories_item_report_id_created_at_index` (`item_report_id`,`created_at`),
  KEY `report_status_histories_related_type_related_id_index` (`related_type`,`related_id`),
  CONSTRAINT `report_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_status_histories_item_report_id_foreign` FOREIGN KEY (`item_report_id`) REFERENCES `item_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_report_status_histories_dimension` CHECK ((`dimension` in (_utf8mb4'verification',_utf8mb4'item')))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.report_status_histories: ~9 rows (approximately)
DELETE FROM `report_status_histories`;
INSERT INTO `report_status_histories` (`id`, `item_report_id`, `dimension`, `from_status`, `to_status`, `changed_by`, `remarks`, `related_type`, `related_id`, `created_at`) VALUES
	(1, 1, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:04'),
	(2, 2, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:04'),
	(3, 3, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:05'),
	(4, 4, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:05'),
	(5, 5, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:05'),
	(6, 6, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:05'),
	(7, 7, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:05'),
	(8, 8, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:06'),
	(9, 9, 'verification', 'pending', 'verified', 1, 'Verified demo report with photo.', NULL, NULL, '2026-11-26 06:21:06');

-- Dumping structure for table boracay_lf.roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_key_unique` (`key`),
  KEY `roles_scope_index` (`scope`),
  CONSTRAINT `chk_roles_scope` CHECK ((`scope` in (_utf8mb4'platform',_utf8mb4'organization',_utf8mb4'public')))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.roles: ~4 rows (approximately)
DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `key`, `name`, `scope`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'platform_super_admin', 'Platform Super Administrator', 'platform', 'System-wide administration across all Boracay institutions.', '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 'institution_admin', 'Institution Administrator', 'organization', 'Verifies reports, decides claims, confirms recoveries, and manages institution operations.', '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 'institution_staff', 'Institution Staff', 'organization', 'Encodes reports, manages custody, and assists with institutional workflows.', '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 'public_user', 'Public User', 'public', 'Authenticated resident, tourist, finder, or item owner.', '2026-11-26 06:12:43', '2026-11-26 06:12:43');

-- Dumping structure for table boracay_lf.sessions
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.sessions: ~1 rows (approximately)
DELETE FROM `sessions`;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('Usw3tt7jNzWHi4GtVqSuajyrVAVfili8ftIQIr3e', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36 Edg/158.0.0.0', 'eyJfdG9rZW4iOiJSWjJMenNOdkthT3B0dElDdEs3Tnh2eXBkT2RtWFVjNjlpWDNydXZDIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2JvcmFjYXkuZGV2LmNvbVwvYWRtaW4iLCJyb3V0ZSI6ImFkbWluLmRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==', 1795677521);

-- Dumping structure for table boracay_lf.system_settings
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json DEFAULT NULL,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  KEY `system_settings_updated_by_foreign` (`updated_by`),
  KEY `system_settings_group_index` (`group`),
  CONSTRAINT `system_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.system_settings: ~19 rows (approximately)
DELETE FROM `system_settings`;
INSERT INTO `system_settings` (`id`, `key`, `value`, `group`, `updated_by`, `created_at`, `updated_at`) VALUES
	(1, 'match.min_score', '{"value": 0.55}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(2, 'match.high_score', '{"value": 0.75}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(3, 'match.date_window_before_days', '{"value": 2}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(4, 'match.date_window_after_days', '{"value": 90}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(5, 'matching.image_enabled', '{"value": true}', 'matching', 1, '2026-11-26 06:12:43', '2026-11-26 06:23:27'),
	(6, 'matching.visual_search_enabled', '{"value": true}', 'matching', 1, '2026-11-26 06:12:43', '2026-11-26 06:23:30'),
	(7, 'matching.visual_search_min_score', '{"value": 0.64}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 07:08:50'),
	(8, 'match.weights', '{"date": 0.1, "brand": 0.1, "color": 0.1, "model": 0.05, "category": 0.2, "location": 0.1, "item_name": 0.15, "description": 0.2}', 'matching', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(9, 'image_validation.fail_mode', '{"value": "review"}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(10, 'image_validation.blur_reject_threshold', '{"value": 80}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(11, 'image_validation.blur_review_threshold', '{"value": 120}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(12, 'image_validation.dark_mean_threshold', '{"value": 45}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(13, 'image_validation.bright_mean_threshold', '{"value": 220}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(14, 'image_validation.object_reject_threshold', '{"value": 0.35}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(15, 'image_validation.object_review_threshold', '{"value": 0.55}', 'image_validation', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(16, 'disposal.retention_days', '{"value": 180}', 'disposal', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(17, 'institutions.admins_can_manage_staff', '{"value": true}', 'institutions', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:43'),
	(18, 'notifications.email_enabled', '{"value": true}', 'notifications', NULL, '2026-11-26 06:12:43', '2026-11-26 06:12:44'),
	(19, 'translation.content_enabled', '{"value": true}', 'localization', 1, '2026-11-26 06:12:43', '2026-11-26 06:23:22');

-- Dumping structure for table boracay_lf.translations
DROP TABLE IF EXISTS `translations`;
CREATE TABLE IF NOT EXISTS `translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `translatable_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translatable_id` bigint unsigned NOT NULL,
  `field` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translations_unique_target` (`translatable_type`,`translatable_id`,`field`,`locale`),
  KEY `translations_morph_idx` (`translatable_type`,`translatable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.translations: ~0 rows (approximately)
DELETE FROM `translations`;

-- Dumping structure for table boracay_lf.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `google_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `chk_users_status` CHECK ((`status` in (_utf8mb4'active',_utf8mb4'suspended')))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table boracay_lf.users: ~1 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `google_id`, `name`, `email`, `avatar_url`, `locale`, `status`, `last_login_at`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, '111780749876608043517', 'Boracay Island L&FS', 'boracaylostandfoundsystem@gmail.com', 'https://lh3.googleusercontent.com/a/ACg8ocLF-GNdxeDK6dvjWanDcCqx1BHPffAarTEWf3cL9fz8yuGXKw=s96-c', 'en', 'active', '2026-11-26 06:14:48', '2026-11-26 06:14:48', NULL, 'nixJENrBtFbHkB6AU0lhPyVIEPlTy4fW2TdOYXfdyuPt69bzS5ZGcH9vXYC4', '2026-11-26 06:14:48', '2026-11-26 06:14:48');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

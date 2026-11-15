/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_detail_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_detail_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `account_subtype_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_subtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_subtypes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `account_type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_dailies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_dailies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `first_in` datetime DEFAULT NULL,
  `last_out` datetime DEFAULT NULL,
  `worked_minutes` int(11) NOT NULL DEFAULT 0,
  `late_minutes` int(11) NOT NULL DEFAULT 0,
  `overtime_minutes` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'absent',
  `is_finalized` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_dailies_employee_id_date_unique` (`employee_id`,`date`),
  KEY `attendance_dailies_shift_id_foreign` (`shift_id`),
  CONSTRAINT `attendance_dailies_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_dailies_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_manual_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_manual_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_daily_id` bigint(20) unsigned NOT NULL,
  `adjusted_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `original_in` datetime DEFAULT NULL,
  `new_in` datetime DEFAULT NULL,
  `original_out` datetime DEFAULT NULL,
  `new_out` datetime DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_manual_adjustments_attendance_daily_id_foreign` (`attendance_daily_id`),
  CONSTRAINT `attendance_manual_adjustments_attendance_daily_id_foreign` FOREIGN KEY (`attendance_daily_id`) REFERENCES `attendance_dailies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_raw_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_raw_logs` (
  `id` char(36) NOT NULL,
  `device_ip` varchar(255) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `employee_device_id` varchar(255) NOT NULL,
  `event_time` datetime NOT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unique_hash` varchar(255) NOT NULL COMMENT 'md5(device_id+time+emp_id)',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_raw_logs_unique_hash_unique` (`unique_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `check_in_time` timestamp NULL DEFAULT NULL,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendances_user_id_index` (`user_id`),
  KEY `attendances_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barcode_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sticker_width` double NOT NULL DEFAULT 1.5,
  `sticker_height` double NOT NULL DEFAULT 0.98,
  `paper_width` double NOT NULL DEFAULT 1.5,
  `top_margin` double NOT NULL DEFAULT 0,
  `left_margin` double NOT NULL DEFAULT 0,
  `row_distance` double NOT NULL DEFAULT 0,
  `col_distance` double NOT NULL DEFAULT 0,
  `stickers_per_row` int(11) NOT NULL DEFAULT 1,
  `is_continuous` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_capital`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_capital` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `created_by_id` int(11) NOT NULL,
  `debit` decimal(11,2) DEFAULT NULL,
  `credit` decimal(11,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `chart_of_account_id` bigint(20) unsigned NOT NULL,
  `financial_year` varchar(255) NOT NULL,
  `month_1` double NOT NULL,
  `month_2` double NOT NULL,
  `month_3` double NOT NULL,
  `month_4` double NOT NULL,
  `month_5` double NOT NULL,
  `month_6` double NOT NULL,
  `month_7` double NOT NULL,
  `month_8` double NOT NULL,
  `month_9` double NOT NULL,
  `month_10` double NOT NULL,
  `month_11` double NOT NULL,
  `month_12` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `business`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business` (
  `id` int(10) unsigned NOT NULL,
  `fy_start_month` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `business_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(10) unsigned NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL DEFAULT 'Main',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_locations_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chart_of_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `business_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL DEFAULT 133,
  `payment_type_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `account_subtype_id` bigint(20) unsigned DEFAULT NULL,
  `detail_type_id` bigint(20) unsigned DEFAULT NULL,
  `name` text DEFAULT NULL,
  `gl_code` varchar(50) DEFAULT NULL,
  `account_type` enum('asset','expense','equity','liability','income') NOT NULL DEFAULT 'asset',
  `opening_balance` decimal(11,2) NOT NULL DEFAULT 0.00,
  `has_opening_balance` tinyint(1) NOT NULL DEFAULT 0,
  `reconcile_opening_balance` int(11) DEFAULT NULL,
  `allow_manual` tinyint(4) NOT NULL DEFAULT 0,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commission_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commission_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `commission_type` enum('percentage','fixed') NOT NULL,
  `commission_rate` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sortname` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(100) NOT NULL,
  `currency` varchar(100) NOT NULL,
  `code` varchar(25) NOT NULL,
  `symbol` varchar(25) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `due_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `customer_type` varchar(20) NOT NULL DEFAULT 'retail',
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_added_by_foreign` (`added_by`),
  CONSTRAINT `customers_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cutting_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cutting_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_issue_id` bigint(20) unsigned DEFAULT NULL,
  `pieces_cut` int(11) NOT NULL,
  `style_no` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cutting_batches_material_issue_id_foreign` (`material_issue_id`),
  CONSTRAINT `cutting_batches_material_issue_id_foreign` FOREIGN KEY (`material_issue_id`) REFERENCES `material_issues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designations_department_id_foreign` (`department_id`),
  CONSTRAINT `designations_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `license_no` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drivers_user_id_foreign` (`user_id`),
  CONSTRAINT `drivers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_bonuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_bonuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bonus_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_bonuses_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_bonuses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_salaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salaries_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `is_off_day` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_shifts_employee_id_date_unique` (`employee_id`,`date`),
  KEY `employee_shifts_shift_id_foreign` (`shift_id`),
  CONSTRAINT `employee_shifts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_shifts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nic` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `assigned_line` varchar(255) DEFAULT NULL,
  `join_date` date NOT NULL,
  `effective_period_start` date DEFAULT NULL,
  `effective_period_end` date DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `production_section_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `device_enroll_id` varchar(255) DEFAULT NULL COMMENT 'Hikvision employeeNo',
  `production_line_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `self_service` tinyint(1) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_nic_unique` (`nic`),
  UNIQUE KEY `employees_email_unique` (`email`),
  UNIQUE KEY `employees_device_enroll_id_unique` (`device_enroll_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_production_section_id_foreign` (`production_section_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_designation_id_foreign` (`designation_id`),
  KEY `employees_production_line_id_foreign` (`production_line_id`),
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_production_line_id_foreign` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_production_section_id_foreign` FOREIGN KEY (`production_section_id`) REFERENCES `production_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'cash',
  `expense_date` date NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `register_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_register_id_foreign` (`register_id`),
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_register_id_foreign` FOREIGN KEY (`register_id`) REFERENCES `registers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hourly_productions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hourly_productions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manufacturing_job_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `line_name` varchar(255) NOT NULL,
  `production_date` date NOT NULL,
  `hour` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hourly_productions_manufacturing_job_id_foreign` (`manufacturing_job_id`),
  KEY `hourly_productions_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hourly_productions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hourly_productions_manufacturing_job_id_foreign` FOREIGN KEY (`manufacturing_job_id`) REFERENCES `manufacturing_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_line_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_line_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manufacturing_job_id` bigint(20) unsigned NOT NULL,
  `line_name` varchar(255) NOT NULL,
  `assigned_quantity` int(11) NOT NULL,
  `dispatch_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_line_assignments_manufacturing_job_id_foreign` (`manufacturing_job_id`),
  CONSTRAINT `job_line_assignments_manufacturing_job_id_foreign` FOREIGN KEY (`manufacturing_job_id`) REFERENCES `manufacturing_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_number` varchar(255) DEFAULT NULL,
  `payment_detail_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` int(10) unsigned DEFAULT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `chart_of_account_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_type` varchar(255) DEFAULT NULL,
  `transaction_sub_type` varchar(255) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `month` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `contact_id` int(10) unsigned DEFAULT NULL,
  `debit` decimal(65,4) DEFAULT NULL,
  `credit` decimal(65,4) DEFAULT NULL,
  `balance` decimal(65,4) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `reversed` tinyint(4) NOT NULL DEFAULT 0,
  `reversible` tinyint(4) NOT NULL DEFAULT 1,
  `manual_entry` tinyint(4) NOT NULL DEFAULT 0,
  `receipt` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chart_of_account_id_index` (`chart_of_account_id`),
  KEY `currency_id_index` (`currency_id`),
  KEY `created_by_id_index` (`created_by_id`),
  KEY `journal_entries_contact_id_index` (`contact_id`),
  KEY `journal_entries_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `days_per_year` int(11) NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manufacturing_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manufacturing_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_number` varchar(255) NOT NULL,
  `material_issue_id` bigint(20) unsigned DEFAULT NULL,
  `style_ref` varchar(255) DEFAULT NULL,
  `batch_ref` varchar(255) DEFAULT NULL,
  `issued_fabric_quantity` decimal(10,2) NOT NULL,
  `accessories` text DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `issue_date` date NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `fabric_used_qty` decimal(10,2) DEFAULT NULL,
  `fabric_waste_qty` decimal(10,2) DEFAULT NULL,
  `total_cut_pieces` int(11) DEFAULT NULL,
  `cutting_reject_qty` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `manufacturing_jobs_job_number_unique` (`job_number`),
  KEY `manufacturing_jobs_material_issue_id_foreign` (`material_issue_id`),
  KEY `manufacturing_jobs_created_by_foreign` (`created_by`),
  KEY `manufacturing_jobs_product_id_foreign` (`product_id`),
  CONSTRAINT `manufacturing_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `manufacturing_jobs_material_issue_id_foreign` FOREIGN KEY (`material_issue_id`) REFERENCES `material_issues` (`id`) ON DELETE SET NULL,
  CONSTRAINT `manufacturing_jobs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_id` bigint(20) unsigned NOT NULL,
  `quantity_issued` decimal(10,2) NOT NULL,
  `issued_to` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `manufacturing_job_id` bigint(20) unsigned DEFAULT NULL,
  `production_section_id` bigint(20) unsigned DEFAULT NULL,
  `recipe_step_id` bigint(20) unsigned DEFAULT NULL,
  `packing_batch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_issues_material_id_foreign` (`material_id`),
  KEY `material_issues_manufacturing_job_id_foreign` (`manufacturing_job_id`),
  KEY `material_issues_packing_batch_id_foreign` (`packing_batch_id`),
  KEY `material_issues_production_section_id_foreign` (`production_section_id`),
  KEY `material_issues_recipe_step_id_foreign` (`recipe_step_id`),
  CONSTRAINT `material_issues_manufacturing_job_id_foreign` FOREIGN KEY (`manufacturing_job_id`) REFERENCES `manufacturing_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_issues_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_issues_packing_batch_id_foreign` FOREIGN KEY (`packing_batch_id`) REFERENCES `packing_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_issues_production_section_id_foreign` FOREIGN KEY (`production_section_id`) REFERENCES `production_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_issues_recipe_step_id_foreign` FOREIGN KEY (`recipe_step_id`) REFERENCES `recipe_steps` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_variation_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_product_variation_id_foreign` (`product_variation_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_variation_id_foreign` FOREIGN KEY (`product_variation_id`) REFERENCES `product_variations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `register_id` bigint(20) unsigned DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `tax_type` varchar(255) DEFAULT NULL,
  `tax_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_store_id_foreign` (`store_id`),
  KEY `orders_register_id_foreign` (`register_id`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_register_id_foreign` FOREIGN KEY (`register_id`) REFERENCES `registers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packing_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packing_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manufacturing_job_id` bigint(20) unsigned NOT NULL,
  `washing_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('good','damage') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'packing',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `packing_batches_manufacturing_job_id_foreign` (`manufacturing_job_id`),
  KEY `packing_batches_washing_transfer_id_foreign` (`washing_transfer_id`),
  CONSTRAINT `packing_batches_manufacturing_job_id_foreign` FOREIGN KEY (`manufacturing_job_id`) REFERENCES `manufacturing_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `packing_batches_washing_transfer_id_foreign` FOREIGN KEY (`washing_transfer_id`) REFERENCES `washing_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) DEFAULT NULL,
  `payment_type_id` varchar(11) DEFAULT NULL,
  `transaction_type` varchar(255) DEFAULT NULL,
  `reference` int(11) DEFAULT NULL,
  `cheque_number` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `routing_code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `system_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_cash` tinyint(4) NOT NULL DEFAULT 0,
  `is_online` tinyint(4) NOT NULL DEFAULT 0,
  `is_system` tinyint(4) NOT NULL DEFAULT 0,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `position` int(11) DEFAULT NULL,
  `options` text DEFAULT NULL,
  `unique_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `cheque_date` date DEFAULT NULL,
  `cheque_image_path` varchar(255) DEFAULT NULL,
  `cheque_exception_requested` tinyint(1) NOT NULL DEFAULT 0,
  `cheque_exception_reason` text DEFAULT NULL,
  `cheque_followup_sent_at` timestamp NULL DEFAULT NULL,
  `customer_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign` (`order_id`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_run_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_run_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `days_present` smallint(5) unsigned NOT NULL DEFAULT 0,
  `days_absent` smallint(5) unsigned NOT NULL DEFAULT 0,
  `leave_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `work_duration` decimal(10,2) NOT NULL DEFAULT 1.00,
  `work_duration_unit` varchar(30) NOT NULL DEFAULT 'Month',
  `amount_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `basic_salary` decimal(12,2) NOT NULL,
  `gross_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(20) NOT NULL DEFAULT 'due',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_run_items_payroll_run_id_employee_id_unique` (`payroll_run_id`,`employee_id`),
  KEY `payroll_run_items_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_run_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_run_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `price_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_groups_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `old_price` decimal(12,2) NOT NULL,
  `new_price` decimal(12,2) NOT NULL,
  `adjustment_type` enum('fixed','percentage') NOT NULL,
  `adjustment_value` decimal(10,2) NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price_history_updated_by_foreign` (`updated_by`),
  KEY `price_history_product_id_index` (`product_id`),
  CONSTRAINT `price_history_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_history_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `price_group_id` bigint(20) unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_prices_product_id_price_group_id_unique` (`product_id`,`price_group_id`),
  KEY `product_prices_price_group_id_foreign` (`price_group_id`),
  CONSTRAINT `product_prices_price_group_id_foreign` FOREIGN KEY (`price_group_id`) REFERENCES `price_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_recipes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'Standard',
  `instructions` text DEFAULT NULL,
  `total_output_quantity` decimal(10,2) DEFAULT 1.00,
  `output_unit` varchar(255) NOT NULL DEFAULT 'pcs',
  `wastage_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `production_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `sub_sku` varchar(255) DEFAULT NULL,
  `default_purchase_price` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `default_sell_price` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `wholesale_price` decimal(12,2) DEFAULT NULL,
  `retail_price` decimal(12,2) DEFAULT NULL,
  `variation_name` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `combo_variations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`combo_variations`)),
  `barcode_type` varchar(255) NOT NULL DEFAULT 'C128',
  `barcode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variations_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_lines_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_name` varchar(255) NOT NULL,
  `production_date` date NOT NULL,
  `daily_output` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `production_stage_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('department','line') NOT NULL DEFAULT 'department',
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supervisor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_sections_slug_unique` (`slug`),
  KEY `production_sections_parent_id_foreign` (`parent_id`),
  KEY `production_sections_supervisor_id_foreign` (`supervisor_id`),
  KEY `production_sections_production_stage_id_foreign` (`production_stage_id`),
  CONSTRAINT `production_sections_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `production_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_sections_production_stage_id_foreign` FOREIGN KEY (`production_stage_id`) REFERENCES `production_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_sections_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_stages_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `barcode_type` varchar(255) NOT NULL DEFAULT 'C128',
  `category_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `warranty_id` bigint(20) unsigned DEFAULT NULL,
  `product_type` enum('simple','variable') NOT NULL DEFAULT 'simple',
  `parent_product_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(12,2) DEFAULT NULL,
  `retail_price` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `enable_stock` tinyint(1) NOT NULL DEFAULT 1,
  `alert_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `classification` varchar(255) NOT NULL DEFAULT 'standard',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_warranty_id_foreign` (`warranty_id`),
  KEY `products_parent_product_id_index` (`parent_product_id`),
  KEY `products_category_id_brand_id_index` (`category_id`,`brand_id`),
  KEY `products_name_index` (`name`),
  KEY `products_product_type_index` (`product_type`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `products_parent_product_id_foreign` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `products_warranty_id_foreign` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `buy_price` decimal(12,2) NOT NULL,
  `sell_price` decimal(12,2) DEFAULT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_material_id_foreign` (`material_id`),
  CONSTRAINT `purchase_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_payments_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_payments_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `purchase_type` varchar(255) NOT NULL DEFAULT 'credit',
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_purchase_no_unique` (`purchase_no`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_created_by_foreign` (`created_by`),
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qc_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qc_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `washing_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `total_checked` int(11) NOT NULL,
  `finished_good_qty` int(11) NOT NULL,
  `damaged_qty` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qc_checks_washing_transfer_id_foreign` (`washing_transfer_id`),
  CONSTRAINT `qc_checks_washing_transfer_id_foreign` FOREIGN KEY (`washing_transfer_id`) REFERENCES `washing_transfers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_step_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,4) NOT NULL,
  `wastage_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_ingredients_recipe_step_id_foreign` (`recipe_step_id`),
  KEY `recipe_ingredients_material_id_foreign` (`material_id`),
  CONSTRAINT `recipe_ingredients_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_ingredients_recipe_step_id_foreign` FOREIGN KEY (`recipe_step_id`) REFERENCES `recipe_steps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recipe_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_recipe_id` bigint(20) unsigned NOT NULL,
  `production_stage_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_steps_product_recipe_id_foreign` (`product_recipe_id`),
  KEY `recipe_steps_production_stage_id_foreign` (`production_stage_id`),
  CONSTRAINT `recipe_steps_product_recipe_id_foreign` FOREIGN KEY (`product_recipe_id`) REFERENCES `product_recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_steps_production_stage_id_foreign` FOREIGN KEY (`production_stage_id`) REFERENCES `production_stages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `opening_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_cash` decimal(12,2) DEFAULT NULL,
  `expected_cash` decimal(12,2) DEFAULT NULL,
  `difference` decimal(12,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `opened_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `opening_note` text DEFAULT NULL,
  `closing_note` text DEFAULT NULL,
  `opened_by` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registers_store_id_foreign` (`store_id`),
  KEY `registers_opened_by_foreign` (`opened_by`),
  KEY `registers_closed_by_foreign` (`closed_by`),
  CONSTRAINT `registers_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registers_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registers_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repairs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repairs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `item` varchar(255) NOT NULL,
  `issue` text DEFAULT NULL,
  `estimate` decimal(12,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repairs_ticket_no_unique` (`ticket_no`),
  KEY `repairs_customer_id_foreign` (`customer_id`),
  KEY `repairs_created_by_foreign` (`created_by`),
  CONSTRAINT `repairs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repairs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `calculation_type` varchar(255) NOT NULL DEFAULT 'fixed',
  `default_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_components_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel` enum('retail','online','wholesale') NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `month` varchar(255) NOT NULL,
  `cash_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cheque_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','bank_transfer') DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_commissions_user_id_month_unique` (`user_id`,`month`),
  CONSTRAINT `sales_commissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period_mins` int(11) NOT NULL DEFAULT 15,
  `late_threshold_mins` int(11) NOT NULL DEFAULT 30,
  `early_leave_allowed_mins` int(11) NOT NULL DEFAULT 0,
  `is_overnight` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_variation_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_type` enum('opening','purchase','sale','adjustment','transfer_in','transfer_out') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_ledgers_created_by_foreign` (`created_by`),
  KEY `stock_ledgers_product_id_transaction_type_index` (`product_id`,`transaction_type`),
  KEY `stock_ledgers_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_ledgers_transaction_type_index` (`transaction_type`),
  KEY `stock_ledgers_reference_type_index` (`reference_type`),
  KEY `stock_ledgers_reference_id_index` (`reference_id`),
  CONSTRAINT `stock_ledgers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_ledgers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_vat_no` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `todos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT 1,
  `task_id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `todos_task_id_unique` (`task_id`),
  KEY `todos_business_id_index` (`business_id`),
  KEY `todos_status_index` (`status`),
  KEY `todos_priority_index` (`priority`),
  KEY `todos_assigned_to_index` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_transaction_number` varchar(255) NOT NULL,
  `transfer_from_id` bigint(20) unsigned NOT NULL,
  `transfer_to_id` bigint(20) unsigned NOT NULL,
  `transfer_by_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `base_unit_id` bigint(20) unsigned DEFAULT NULL,
  `conversion_factor` decimal(10,4) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `units_base_unit_id_foreign` (`base_unit_id`),
  CONSTRAINT `units_base_unit_id_foreign` FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicle_daily_run_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicle_daily_run_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_daily_run_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'others',
  `name` varchar(255) NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `slip_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_daily_run_items_vehicle_daily_run_id_foreign` (`vehicle_daily_run_id`),
  CONSTRAINT `vehicle_daily_run_items_vehicle_daily_run_id_foreign` FOREIGN KEY (`vehicle_daily_run_id`) REFERENCES `vehicle_daily_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicle_daily_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicle_daily_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `run_date` date NOT NULL,
  `start_meter_photo_path` varchar(255) DEFAULT NULL,
  `start_km` decimal(12,2) DEFAULT NULL,
  `end_km` decimal(12,2) DEFAULT NULL,
  `start_route` varchar(255) DEFAULT NULL,
  `end_route` varchar(255) DEFAULT NULL,
  `google_map_link` text DEFAULT NULL,
  `distance_km` decimal(10,2) DEFAULT NULL,
  `fuel_received` varchar(255) DEFAULT NULL,
  `fuel_receipt_path` varchar(255) DEFAULT NULL,
  `meter_photo_path` varchar(255) DEFAULT NULL,
  `slip_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'started',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_daily_runs_vehicle_id_run_date_index` (`vehicle_id`,`run_date`),
  KEY `vehicle_daily_runs_driver_id_foreign` (`driver_id`),
  CONSTRAINT `vehicle_daily_runs_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_daily_runs_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `number_plate` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_number_plate_unique` (`number_plate`),
  KEY `vehicles_driver_id_foreign` (`driver_id`),
  CONSTRAINT `vehicles_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warranties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `duration_months` int(11) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `washing_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `washing_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quantity_sent` int(11) NOT NULL,
  `sent_from` varchar(255) NOT NULL,
  `batch_no` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `quantity_returned` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `manufacturing_job_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `washing_transfers_manufacturing_job_id_foreign` (`manufacturing_job_id`),
  CONSTRAINT `washing_transfers_manufacturing_job_id_foreign` FOREIGN KEY (`manufacturing_job_id`) REFERENCES `manufacturing_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_21_000000_create_attendances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_02_03_000001_add_role_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_02_03_000002_create_erp_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_02_04_000001_create_job_workflow_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_02_04_000002_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_02_04_000003_change_employee_role_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_02_04_000004_change_job_status_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_02_04_000005_create_hourly_productions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_02_04_000006_add_job_id_to_washing_transfers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_02_04_000007_create_production_lines_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_02_04_000008_add_line_id_to_employees',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_02_04_000009_refactor_lines_to_sections',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_02_05_000001_add_supervisor_to_sections',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_02_05_000002_add_email_to_employees',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_02_05_073058_add_unit_price_to_materials_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_02_05_103317_create_production_stages_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_02_05_114828_change_washing_status_to_string',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_02_05_115421_add_job_id_to_material_issues',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_02_05_121650_create_packing_batches_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_02_05_121651_add_batch_id_to_material_issues',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_02_05_144719_add_washing_transfer_id_to_packing_batches',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_02_06_071951_create_categories_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_02_06_071952_create_brands_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_02_06_071953_create_units_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_02_06_071954_create_warranties_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_02_06_071955_create_products_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_02_06_071956_create_product_variations_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_02_06_071957_create_price_groups_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_02_06_071958_create_product_prices_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_02_06_071959_create_stock_ledgers_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_02_06_072000_create_price_history_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_02_06_072001_create_product_recipes_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_02_06_072002_create_recipe_steps_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_02_06_072003_create_recipe_ingredients_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_02_06_072004_add_product_id_to_manufacturing_jobs_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_02_06_074614_add_barcode_type_and_location_to_products_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_02_06_075018_update_product_variations_and_add_attributes',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_02_06_103719_add_status_and_classification_to_products_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_02_06_140000_add_barcode_type_and_location_to_products_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_02_06_143000_update_product_variations_and_add_attributes',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_02_06_150000_create_barcode_settings_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_02_06_160000_create_todos_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_02_06_163000_create_essentials_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_02_11_083909_create_hrm_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_02_13_082535_create_customers_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_02_13_083201_create_orders_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_02_13_083202_create_order_items_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_02_13_202322_add_product_variation_id_to_order_items_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_02_14_053733_create_payments_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_02_14_060309_add_commission_rate_to_orders_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_02_14_080145_create_sales_commissions_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_02_14_082613_add_payment_details_to_commissions_and_users',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_02_25_100000_create_leave_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_02_25_120000_create_salary_tables',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_02_25_000000_restore_production_lines_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_02_26_000001_create_expense_categories_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_02_26_000002_create_expenses_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_02_26_100001_create_announcements_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_02_26_120000_add_status_to_employees_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_02_27_090000_create_drivers_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_02_27_100000_create_vehicles_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_02_27_110000_create_vehicle_daily_runs_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_02_28_100000_add_start_photo_and_status_to_vehicle_daily_runs',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_02_28_110000_create_vehicle_daily_run_items_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_02_28_120000_allow_multiple_jobs_per_day_vehicle_runs',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_02_28_130000_add_discount_amount_to_orders_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_02_28_140000_add_discount_type_and_value_to_orders_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_02_28_150000_add_wholesale_retail_prices_to_products',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_02_28_150001_add_wholesale_retail_prices_to_product_variations',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_02_28_160000_add_customer_type_to_customers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_02_28_170000_add_employee_profile_and_import_fields',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_02_28_170001_create_employee_bonuses_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_02_28_100000_add_attendance_work_to_payroll_run_items',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_02_28_120000_add_payment_fields_to_payroll_run_items',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_02_28_130000_add_stage_to_recipe_steps_and_sections',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_02_28_130001_add_section_and_step_to_material_issues',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_02_28_140000_rename_sewing_stage_to_line_production',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_02_28_180000_create_stores_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_02_28_180001_create_registers_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_02_28_180002_add_pos_fields_to_orders_and_customers',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_02_28_180003_add_register_and_method_to_expenses',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_02_28_180004_create_repairs_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_02_28_140000_create_purchase_module_tables',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2019_07_07_093258_create_chart_of_accounts_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2019_07_07_093648_create_journal_entries_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2019_07_07_110645_create_payment_types_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2021_08_23_175321_add_contact_and_location_id_to_journal_entries_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2021_11_29_170819_add_business_id_to_chart_of_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2022_01_17_202319_create_payment_details_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2022_01_19_034231_create_countries_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2022_02_01_031031_create_transfers_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2022_02_03_215602_create_budgets_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2022_02_08_113906_add_opening_balance_to_chart_of_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2022_02_08_121045_add_currency_id_to_chart_of_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2022_02_09_002406_add_payment_type_id_to_chart_of_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2022_02_09_125328_create_account_detail_types_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2022_02_09_223848_create_account_subtypes_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2022_02_09_223849_add_account_subtype_id_and_detail_type_id_to_chart_of_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2022_02_23_130555_add_journal_entry_id_to_transactions_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2022_03_17_140457_add_reconcile_opening_balance_to_chart_of_accounts_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2022_04_11_163625_populate_account_subtypes_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2022_04_11_165143_populate_account_detail_types_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2022_06_08_105942_create_branch_capital_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2022_07_25_100234_change_payment_type_id_column_from_int_to_string_in_payment_details_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_04_18_120000_create_accounting_business_and_location_stubs',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_04_17_202428_create_currencies_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_04_18_120000_align_chart_of_accounts_for_ui',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_04_18_160000_add_cheque_fields_to_payments_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_04_18_200000_create_app_settings_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_04_18_200001_add_cheque_followup_sent_at_to_payments_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_04_18_220000_add_customer_reminder_columns_to_payments_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_04_18_230000_make_employee_id_nullable_in_hourly_productions',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_04_19_100000_add_permissions_to_roles_and_role_id_to_users',40);

-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: scdc_db
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ipaddress` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `windows` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `adjustments`
--

DROP TABLE IF EXISTS `adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_period_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adjustment_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adjustments_id_index` (`id`),
  KEY `adjustments_employee_id_index` (`employee_id`),
  KEY `adjustments_date_period_id_index` (`date_period_id`),
  KEY `adjustments_adjustment_id_index` (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adjustments`
--

LOCK TABLES `adjustments` WRITE;
/*!40000 ALTER TABLE `adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `status` int NOT NULL,
  `verification_mode` int NOT NULL,
  `work_code` int NOT NULL DEFAULT '0',
  `reserved` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_logs_user_id_index` (`user_id`),
  KEY `attendance_logs_project_code_index` (`project_code`),
  KEY `attendance_logs_recorded_at_index` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_logs`
--

LOCK TABLES `attendance_logs` WRITE;
/*!40000 ALTER TABLE `attendance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `date_periods`
--

DROP TABLE IF EXISTS `date_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `date_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employeetype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `partners` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `projectid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datefrom` date NOT NULL,
  `dateto` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `overtime_rate` decimal(8,2) DEFAULT NULL COMMENT 'Overtime multiplier percentage or baseline rate setting',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_periods_partners_index` (`partners`),
  KEY `date_periods_projectid_index` (`projectid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `date_periods`
--

LOCK TABLES `date_periods` WRITE;
/*!40000 ALTER TABLE `date_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `date_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `earnings`
--

DROP TABLE IF EXISTS `earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `earnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `hierarchy` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `earnings_employee_id_index` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `earnings`
--

LOCK TABLES `earnings` WRITE;
/*!40000 ALTER TABLE `earnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `earnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emp_schedule`
--

DROP TABLE IF EXISTS `emp_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emp_schedule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employeeid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timein` time NOT NULL,
  `timeout` time NOT NULL,
  `workingHours` tinyint unsigned NOT NULL DEFAULT '8',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emp_schedule_employeeid_index` (`employeeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emp_schedule`
--

LOCK TABLES `emp_schedule` WRITE;
/*!40000 ALTER TABLE `emp_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `emp_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_project_histories`
--

DROP TABLE IF EXISTS `employee_project_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_project_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employeeid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employeetype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `projectid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `datestarted` date NOT NULL,
  `dateended` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_project_histories_employeeid_index` (`employeeid`),
  KEY `employee_project_histories_projectid_index` (`projectid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_project_histories`
--

LOCK TABLES `employee_project_histories` WRITE;
/*!40000 ALTER TABLE `employee_project_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_project_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employeeid` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middlename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `datehired` date DEFAULT NULL,
  `dateseperated` date DEFAULT NULL,
  `employeetype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_id` bigint unsigned DEFAULT NULL,
  `project_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `empstatus` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `partners` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employeeid_unique` (`employeeid`),
  KEY `employees_partners_index` (`partners`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gov_deduction_logs`
--

DROP TABLE IF EXISTS `gov_deduction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gov_deduction_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gov_deduction_id` bigint unsigned NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_period_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gov_deduction_logs_gov_deduction_id_index` (`gov_deduction_id`),
  KEY `gov_deduction_logs_employee_id_index` (`employee_id`),
  KEY `gov_deduction_logs_date_period_id_index` (`date_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gov_deduction_logs`
--

LOCK TABLES `gov_deduction_logs` WRITE;
/*!40000 ALTER TABLE `gov_deduction_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `gov_deduction_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gov_deductions`
--

DROP TABLE IF EXISTS `gov_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gov_deductions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_started` date NOT NULL,
  `date_ended` date NOT NULL,
  `amount` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gov_deductions`
--

LOCK TABLES `gov_deductions` WRITE;
/*!40000 ALTER TABLE `gov_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `gov_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday_logs`
--

DROP TABLE IF EXISTS `holiday_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holiday_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `holidayid` bigint unsigned NOT NULL,
  `employeeid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timein` datetime DEFAULT NULL,
  `timeout` datetime DEFAULT NULL,
  `dateperiod_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holiday_logs_holidayid_index` (`holidayid`),
  KEY `holiday_logs_employeeid_index` (`employeeid`),
  KEY `holiday_logs_dateperiod_id_index` (`dateperiod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday_logs`
--

LOCK TABLES `holiday_logs` WRITE;
/*!40000 ALTER TABLE `holiday_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_10_29_092814_create_categories_table',1),(5,'2025_10_29_125601_create_projects_table',1),(6,'2025_10_29_131648_create_skills_table',1),(7,'2025_10_29_132625_create_employees_table',1),(8,'2025_10_29_133814_create_employee_project_histories_table',1),(9,'2025_10_29_134346_add_employee_column_to_employee_project_histories_table',1),(10,'2025_10_29_134538_rename_employee_column_to_employeetype_in_employee_project_histories_table',1),(11,'2025_10_29_134751_add_employee_status_to_employee_project_histories_table',1),(12,'2025_10_29_151903_create_date_periods_table',1),(13,'2025_10_30_035954_add_role_and_status_to_users_table',1),(14,'2025_10_30_045258_create_other_deductions_table',1),(15,'2025_10_30_045942_create_gov_deductions_table',1),(16,'2025_10_30_050937_create_gov_deduction_logs_table',1),(17,'2025_10_30_051401_create_other_deduction_logs_table',1),(18,'2025_11_02_041138_update_email_nullable_in_employees_table',1),(19,'2025_11_02_100251_update_email_nullable_and_remove_unique_in_employees',1),(20,'2025_11_11_063603_create_thirteenth_months_table',1),(21,'2025_11_11_064444_add_amount_to_other_deduction_logs_table',1),(22,'2025_11_13_065403_create_staff_table',1),(23,'2025_11_14_032251_create_atlogs_table',1),(24,'2025_11_14_043031_create_holidays_table',1),(25,'2025_11_17_063144_create_earnings_table',1),(26,'2025_11_17_072102_create_holiday_logs_table',1),(27,'2025_11_17_080235_create_payrolls_table',1),(28,'2025_11_19_094515_add_code_to_date_periods_table',1),(29,'2025_11_20_133647_add_employeetype_to_employees_table',1),(30,'2026_03_17_115703_add_details_to_payrolls_table',1),(31,'2026_04_07_101022_change_project_id_to_string_in_employees_table',1),(32,'2026_05_05_093205_add_emp_status_to_employees_table',1),(33,'2026_05_05_111104_update_earnings_table_change_category_to_title',1),(34,'2026_05_13_073958_drop_atlogs_table',1),(35,'2026_05_13_074207_create_attendance_logs_table',1),(36,'2026_05_16_101053_add_project_code_to_attendance_logs_table',1),(37,'2026_06_09_084729_add_cat_and_status_to_categories_table',1),(38,'2026_06_10_021843_add_status_to_date_periods_table',1),(39,'2026_06_10_062055_add_index_to_recorded_at_in_attendance_logs_table',1),(40,'2026_06_10_082424_change_date_period_id_to_string_in_gov_deduction_logs_table',1),(41,'2026_06_10_091828_change_date_period_id_to_string_in_other_deduction_logs_table',1),(42,'2026_06_10_092917_create_adjustments_table',1),(43,'2026_06_11_053731_update_holiday_logs_table',1),(44,'2026_06_15_082630_add_overtime_rate_to_date_periods_table',1),(45,'2026_06_16_022633_create_payroll_reports_table',1),(46,'2026_06_16_023836_create_payroll_summary_reports_table',1),(47,'2026_06_16_032856_add_amount_to_gov_deduction_logs_table',1),(48,'2026_06_18_040654_add_cat_id_to_payroll_summary_reports_table',1),(49,'2026_06_18_043036_drop_cat_id_from_payroll_summary_reports_table',1),(50,'2026_06_18_043056_add_cat_id_to_payroll_summary_reports_table',1),(51,'2026_06_18_043306_drop_cat_id_from_payroll_summary_reports_table',1),(52,'2026_06_18_043355_add_cat_id_to_payroll_reports_table',1),(53,'2026_06_19_051533_add_grosspay_to_payroll_summary_reports_table',1),(54,'2026_06_19_055526_add_status_to_multiple_tables',1),(55,'2026_06_19_070517_create_userlogs_table',1),(56,'2026_06_19_080832_add_partners_to_employees_table',1),(57,'2026_06_19_083241_add_partners_to_date_periods_table',1),(58,'2026_06_21_091225_add_required_hours_to_payroll_summary_reports_table',1),(59,'2026_06_21_114004_add_frequency_to_categories_table',1),(60,'2026_06_22_031107_drop_frequency_column_from_categories_table',1),(61,'2026_06_22_031233_add_frequency_to_earnings_table',1),(62,'2026_06_22_050957_add_required_income_to_payroll_summary_reports_table',1),(63,'2026_06_23_015550_create_emp_schedules_table',1),(64,'2026_06_23_024124_add_working_hours_to_emp_schedule_table',1),(65,'2026_06_23_030414_add_sched_id_to_payroll_reports_table',1),(66,'2026_06_26_045547_add_hierarchy_to_earnings_table',1),(67,'2026_07_02_113308__change_periodid_to_string_in_thirteenth_months_table',1),(68,'2026_07_02_114016__add_and_modify_columns_in_thirteenth_months_table',1),(69,'2026_07_02_115349__drop_empstatus_and_emptype_from_thirteenth_months_table',1),(70,'2026_07_02_120217_create_year_end_reports_table',1),(71,'2026_07_02_123015__add_dates_to_year_end_reports_table',1),(72,'2026_07_03_021401__add_rep_type_to_year_end_reports_table',1),(73,'2026_07_03_051028_add_projectid_to_date_periods_table',1),(74,'2026_07_03_083340_make_project_nullable_in_thirteenth_months_table',1),(75,'2026_07_07_045652_create_user_permissions_table',1),(76,'2026_07_10_053020_create_activity_logs_table',1),(77,'2026_07_14_014834_create_notifications_table',1),(78,'2026_07_14_044838_add_yearendcode_to_thirteenth_months_table',1),(79,'2026_07_14_074510_add_status_to_thirteenth_months_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_deduction_logs`
--

DROP TABLE IF EXISTS `other_deduction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_deduction_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `other_deduction_id` bigint unsigned NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_period_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `other_deduction_logs_other_deduction_id_index` (`other_deduction_id`),
  KEY `other_deduction_logs_employee_id_index` (`employee_id`),
  KEY `other_deduction_logs_date_period_id_index` (`date_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_deduction_logs`
--

LOCK TABLES `other_deduction_logs` WRITE;
/*!40000 ALTER TABLE `other_deduction_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `other_deduction_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_deductions`
--

DROP TABLE IF EXISTS `other_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_deductions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_deductions`
--

LOCK TABLES `other_deductions` WRITE;
/*!40000 ALTER TABLE `other_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `other_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_reports`
--

DROP TABLE IF EXISTS `payroll_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `cat_id` int NOT NULL,
  `sched_id` int DEFAULT NULL,
  `dateperiod_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paytype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_entry` date NOT NULL,
  `overtime` decimal(8,2) NOT NULL DEFAULT '0.00',
  `acquired_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `late_undertime` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_reports_dateperiod_id_index` (`dateperiod_id`),
  KEY `payroll_reports_employee_id_index` (`employee_id`),
  KEY `payroll_reports_paytype_index` (`paytype`),
  KEY `payroll_reports_cat_id_index` (`cat_id`),
  KEY `payroll_reports_sched_id_index` (`sched_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_reports`
--

LOCK TABLES `payroll_reports` WRITE;
/*!40000 ALTER TABLE `payroll_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_summary_reports`
--

DROP TABLE IF EXISTS `payroll_summary_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_summary_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `dateperiod_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `totalhours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `required_hours` decimal(10,2) NOT NULL DEFAULT '0.00',
  `required_income` decimal(10,2) NOT NULL DEFAULT '0.00',
  `totalovertime` decimal(8,2) NOT NULL DEFAULT '0.00',
  `totalabsent` decimal(8,2) NOT NULL DEFAULT '0.00',
  `lateundertime` decimal(8,2) NOT NULL DEFAULT '0.00',
  `totaldeductionn` decimal(8,2) NOT NULL DEFAULT '0.00',
  `totalearnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `totaladjustment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `totalnetpay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grosspay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_summary_reports_dateperiod_id_index` (`dateperiod_id`),
  KEY `payroll_summary_reports_employee_id_index` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_summary_reports`
--

LOCK TABLES `payroll_summary_reports` WRITE;
/*!40000 ALTER TABLE `payroll_summary_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_summary_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrolls`
--

DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payrolls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employeeid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodid` bigint unsigned NOT NULL,
  `totalhours` decimal(8,2) NOT NULL,
  `acquirehours` decimal(8,2) NOT NULL,
  `Sunday` decimal(10,2) DEFAULT NULL,
  `Monday` decimal(10,2) DEFAULT NULL,
  `Tuesday` decimal(10,2) DEFAULT NULL,
  `Wednesday` decimal(10,2) DEFAULT NULL,
  `Thursday` decimal(10,2) DEFAULT NULL,
  `Friday` decimal(10,2) DEFAULT NULL,
  `Saturday` decimal(10,2) DEFAULT NULL,
  `RegularOT` decimal(10,2) DEFAULT NULL,
  `Project` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payrolls_employeeid_index` (`employeeid`),
  KEY `payrolls_periodid_index` (`periodid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_project_code_unique` (`project_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('sFJ6F2ZkalIMKWd9IR2VIQaknILKQtDnAvmdus25',NULL,'172.20.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiUTBJZUthYVNnYWcxaUJCUVBaZmFrQUM5WTJUNXBuVWlrMElWUFl2MSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1784785172);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills`
--

LOCK TABLES `skills` WRITE;
/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thirteenth_months`
--

DROP TABLE IF EXISTS `thirteenth_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thirteenth_months` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `periodid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employeeid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yearendcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `earnings` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `partners` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allowance` decimal(12,2) DEFAULT NULL,
  `datestart` date DEFAULT NULL,
  `dateend` date DEFAULT NULL,
  `yearendrepid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `thirteenth_months_employeeid_index` (`employeeid`),
  KEY `thirteenth_months_yearendrepid_index` (`yearendrepid`),
  KEY `thirteenth_months_yearendcode_index` (`yearendcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thirteenth_months`
--

LOCK TABLES `thirteenth_months` WRITE;
/*!40000 ALTER TABLE `thirteenth_months` DISABLE KEYS */;
/*!40000 ALTER TABLE `thirteenth_months` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_permissions_user_id_index` (`user_id`),
  KEY `user_permissions_module_index` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
INSERT INTO `user_permissions` VALUES (1,1,'SUPERADMIN','2026-07-22 21:36:09','2026-07-22 21:36:09');
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `userlogs`
--

DROP TABLE IF EXISTS `userlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userlogs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userlogs_created_by_index` (`created_by`),
  KEY `userlogs_module_id_index` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userlogs`
--

LOCK TABLES `userlogs` WRITE;
/*!40000 ALTER TABLE `userlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `userlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','jclibredo@example.com',NULL,'$2y$12$CQjSPzI2j2hjl8Um6wSck.3XcnTs/Dt0Axmbz2rxOGujb9FYH9Z.O','admin',1,NULL,'2026-07-22 21:36:09','2026-07-22 21:36:09');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `year_end_reports`
--

DROP TABLE IF EXISTS `year_end_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `year_end_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emptype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `empstatus` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `partners` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `projectid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `datefrom` date DEFAULT NULL,
  `dateto` date DEFAULT NULL,
  `rep_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `year_end_reports_code_index` (`code`),
  KEY `year_end_reports_emptype_index` (`emptype`),
  KEY `year_end_reports_empstatus_index` (`empstatus`),
  KEY `year_end_reports_partners_index` (`partners`),
  KEY `year_end_reports_projectid_index` (`projectid`),
  KEY `year_end_reports_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `year_end_reports`
--

LOCK TABLES `year_end_reports` WRITE;
/*!40000 ALTER TABLE `year_end_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `year_end_reports` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-23 13:40:36

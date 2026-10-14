-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: remi_saas
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ai_interaction_logs`
--

DROP TABLE IF EXISTS `ai_interaction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_interaction_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(64) NOT NULL DEFAULT 'nvidia_nim',
  `model` varchar(128) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'success',
  `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `prompt_tokens` int(10) unsigned DEFAULT NULL,
  `completion_tokens` int(10) unsigned DEFAULT NULL,
  `total_tokens` int(10) unsigned DEFAULT NULL,
  `error_category` varchar(64) DEFAULT NULL,
  `detected_language` varchar(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_interaction_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `ai_interaction_logs_status_index` (`status`),
  KEY `ai_interaction_logs_provider_index` (`provider`),
  CONSTRAINT `ai_interaction_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_interaction_logs`
--

LOCK TABLES `ai_interaction_logs` WRITE;
/*!40000 ALTER TABLE `ai_interaction_logs` DISABLE KEYS */;
INSERT INTO `ai_interaction_logs` VALUES (1,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:03:45','2026-10-14 06:03:45'),(2,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:04:00','2026-10-14 06:04:00'),(3,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:05:54','2026-10-14 06:05:54'),(4,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:06:46','2026-10-14 06:06:46'),(5,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:13:58','2026-10-14 06:13:58'),(6,2,'nvidia_nim','openai/gpt-oss-20b','failed',0,NULL,NULL,NULL,'missing_api_key',NULL,'2026-10-14 06:31:17','2026-10-14 06:31:17'),(7,NULL,'nvidia_nim','openai/gpt-oss-20b','success',6655,1245,300,1545,NULL,'english','2026-10-14 06:35:09','2026-10-14 06:35:09'),(8,NULL,'nvidia_nim','openai/gpt-oss-20b','success',5217,1244,253,1497,NULL,'bengali','2026-10-14 06:35:20','2026-10-14 06:35:20'),(9,2,'nvidia_nim','openai/gpt-oss-20b','success',5762,1279,196,1475,NULL,'english','2026-10-14 06:38:54','2026-10-14 06:38:54'),(10,2,'nvidia_nim','openai/gpt-oss-20b','success',4696,1302,185,1487,NULL,'banglish','2026-10-14 06:39:05','2026-10-14 06:39:05'),(11,2,'nvidia_nim','openai/gpt-oss-20b','success',2370,1336,212,1548,NULL,'bengali','2026-10-14 06:39:16','2026-10-14 06:39:16'),(12,2,'nvidia_nim','openai/gpt-oss-20b','failed',3311,1392,304,1696,'malformed_json',NULL,'2026-10-14 06:39:37','2026-10-14 06:39:37'),(13,NULL,'nvidia_nim','openai/gpt-oss-20b','success',9068,1254,400,1654,NULL,'bengali','2026-10-14 06:41:07','2026-10-14 06:41:07'),(14,2,'nvidia_nim','openai/gpt-oss-20b','success',6868,1433,292,1725,NULL,'bengali','2026-10-14 06:42:11','2026-10-14 06:42:11'),(15,2,'nvidia_nim','openai/gpt-oss-20b','success',3468,1439,289,1728,NULL,'bengali','2026-10-14 06:42:40','2026-10-14 06:42:40'),(16,2,'nvidia_nim','openai/gpt-oss-20b','failed',5853,1349,333,1682,'malformed_json',NULL,'2026-10-14 06:42:51','2026-10-14 06:42:51'),(17,2,'nvidia_nim','openai/gpt-oss-20b','success',7162,1465,334,1799,NULL,'bengali','2026-10-14 06:43:00','2026-10-14 06:43:00'),(18,2,'nvidia_nim','openai/gpt-oss-20b','success',5826,1460,401,1861,NULL,'bengali','2026-10-14 06:43:14','2026-10-14 06:43:14'),(19,2,'nvidia_nim','openai/gpt-oss-20b','success',8856,1455,273,1728,NULL,'bengali','2026-10-14 06:43:33','2026-10-14 06:43:33'),(20,2,'nvidia_nim','openai/gpt-oss-20b','success',7214,1349,320,1669,NULL,'bengali','2026-10-14 06:44:21','2026-10-14 06:44:21'),(21,2,'nvidia_nim','openai/gpt-oss-20b','success',6469,1500,282,1782,NULL,'bengali','2026-10-14 06:44:53','2026-10-14 06:44:53'),(22,2,'nvidia_nim','openai/gpt-oss-20b','success',8453,1533,435,1968,NULL,'bengali','2026-10-14 06:46:04','2026-10-14 06:46:04'),(23,2,'nvidia_nim','openai/gpt-oss-20b','success',9054,1529,539,2068,NULL,'banglish','2026-10-14 06:57:15','2026-10-14 06:57:15'),(24,2,'nvidia_nim','openai/gpt-oss-20b','success',9224,1507,506,2013,NULL,'english','2026-10-14 07:00:30','2026-10-14 07:00:30'),(25,2,'nvidia_nim','openai/gpt-oss-20b','success',9002,1500,489,1989,NULL,'english','2026-10-14 07:00:48','2026-10-14 07:00:48'),(26,2,'nvidia_nim','openai/gpt-oss-20b','success',7763,1456,514,1970,NULL,'bengali','2026-10-14 07:03:18','2026-10-14 07:03:18'),(27,2,'nvidia_nim','openai/gpt-oss-20b','success',4612,1403,234,1637,NULL,'bengali','2026-10-14 07:03:43','2026-10-14 07:03:43'),(28,2,'nvidia_nim','openai/gpt-oss-20b','success',8020,1432,509,1941,NULL,'bengali','2026-10-14 07:04:03','2026-10-14 07:04:03'),(29,2,'nvidia_nim','openai/gpt-oss-20b','success',10455,1472,416,1888,NULL,'english','2026-10-14 07:05:04','2026-10-14 07:05:04'),(30,2,'nvidia_nim','openai/gpt-oss-20b','success',3626,1486,332,1818,NULL,'banglish','2026-10-14 07:06:03','2026-10-14 07:06:03'),(31,2,'nvidia_nim','openai/gpt-oss-20b','success',25179,1492,861,2353,NULL,'banglish','2026-10-14 07:06:50','2026-10-14 07:06:50'),(32,2,'nvidia_nim','openai/gpt-oss-20b','success',7917,1523,289,1812,NULL,'bengali','2026-10-14 07:07:14','2026-10-14 07:07:14'),(33,2,'nvidia_nim','openai/gpt-oss-20b','success',4363,1520,353,1873,NULL,'banglish','2026-10-14 07:07:38','2026-10-14 07:07:38'),(34,2,'nvidia_nim','openai/gpt-oss-20b','success',2531,1581,154,1735,NULL,'banglish','2026-10-14 07:08:12','2026-10-14 07:08:12'),(35,2,'nvidia_nim','openai/gpt-oss-20b','success',4907,1582,163,1745,NULL,'bengali','2026-10-14 07:08:17','2026-10-14 07:08:17'),(36,2,'nvidia_nim','openai/gpt-oss-20b','success',4281,1579,318,1897,NULL,'english','2026-10-14 07:08:22','2026-10-14 07:08:22'),(37,2,'nvidia_nim','openai/gpt-oss-20b','success',12628,1578,396,1974,NULL,'banglish','2026-10-14 07:08:54','2026-10-14 07:08:54'),(38,2,'nvidia_nim','openai/gpt-oss-20b','success',10860,1581,574,2155,NULL,'bengali','2026-10-14 07:09:05','2026-10-14 07:09:05'),(39,2,'nvidia_nim','openai/gpt-oss-20b','success',12070,1580,356,1936,NULL,'english','2026-10-14 07:09:17','2026-10-14 07:09:17'),(40,2,'nvidia_nim','openai/gpt-oss-20b','success',47740,1810,207,2017,NULL,'bengali','2026-10-14 08:29:54','2026-10-14 08:29:54'),(41,2,'nvidia_nim','openai/gpt-oss-20b','failed',60543,NULL,NULL,NULL,'timeout',NULL,'2026-10-14 09:11:09','2026-10-14 09:11:09'),(42,2,'nvidia_nim','openai/gpt-oss-20b','success',51930,1817,275,2092,NULL,'banglish','2026-10-14 09:14:25','2026-10-14 09:14:25'),(43,2,'nvidia_nim','meta/llama-3.2-11b-vision-instruct','success',6825,1789,150,1939,NULL,'banglish','2026-10-14 09:17:10','2026-10-14 09:17:10'),(44,2,'nvidia_nim','meta/llama-3.2-11b-vision-instruct','success',4130,1794,156,1950,NULL,'banglish','2026-10-14 09:17:14','2026-10-14 09:17:14'),(45,2,'nvidia_nim','meta/llama-3.2-11b-vision-instruct','success',41060,1824,226,2050,NULL,'bengali','2026-10-14 09:17:55','2026-10-14 09:17:55'),(46,2,'nvidia_nim','openai/gpt-oss-20b','success',5416,1765,175,1940,NULL,'banglish','2026-10-14 09:21:57','2026-10-14 09:21:57'),(47,2,'nvidia_nim','openai/gpt-oss-20b','success',7001,1711,255,1966,NULL,'banglish','2026-10-14 09:22:12','2026-10-14 09:22:12'),(48,2,'nvidia_nim','openai/gpt-oss-20b','success',3192,706,156,862,NULL,'english','2026-10-14 09:24:25','2026-10-14 09:24:25'),(49,2,'nvidia_nim','openai/gpt-oss-20b','success',4203,701,194,895,NULL,'banglish','2026-10-14 09:24:39','2026-10-14 09:24:39'),(50,2,'nvidia_nim','openai/gpt-oss-20b','success',18040,704,248,952,NULL,'banglish','2026-10-14 09:25:31','2026-10-14 09:25:31'),(51,2,'nvidia_nim','openai/gpt-oss-20b','success',7656,721,279,1000,NULL,'bengali','2026-10-14 09:25:43','2026-10-14 09:25:43'),(52,2,'nvidia_nim','openai/gpt-oss-20b','success',25697,780,333,1113,NULL,'bengali','2026-10-14 09:32:35','2026-10-14 09:32:35'),(53,2,'nvidia_nim','openai/gpt-oss-20b','success',6045,777,313,1090,NULL,'english','2026-10-14 09:32:51','2026-10-14 09:32:51'),(54,2,'nvidia_nim','openai/gpt-oss-20b','failed',7965,716,350,1066,'empty_response',NULL,'2026-10-14 09:33:10','2026-10-14 09:33:10'),(55,2,'nvidia_nim','openai/gpt-oss-20b','success',7890,712,301,1013,NULL,'bengali','2026-10-14 09:33:36','2026-10-14 09:33:36'),(56,2,'nvidia_nim','openai/gpt-oss-20b','success',4771,738,237,975,NULL,'banglish','2026-10-14 09:34:02','2026-10-14 09:34:02'),(57,2,'gemini','gemini-2.5-flash','failed',4754,NULL,NULL,1239,'malformed_json',NULL,'2026-10-14 09:37:04','2026-10-14 09:37:04'),(58,2,'gemini','gemini-2.5-flash','success',2089,784,153,982,NULL,'english','2026-10-14 09:37:27','2026-10-14 09:37:27'),(59,2,'gemini','gemini-2.5-flash','success',2762,703,210,1001,NULL,'banglish','2026-10-14 09:37:42','2026-10-14 09:37:42'),(60,2,'gemini','gemini-2.5-flash','success',3396,703,196,1033,NULL,'banglish','2026-10-14 09:38:28','2026-10-14 09:38:28'),(61,2,'gemini','gemini-2.5-flash','success',5097,792,196,1141,NULL,'banglish','2026-10-14 09:41:12','2026-10-14 09:41:12'),(62,2,'gemini','gemini-2.5-flash','success',2989,801,162,1123,NULL,'bengali','2026-10-14 09:42:37','2026-10-14 09:42:37'),(63,2,'gemini','gemini-2.5-flash','failed',3582,NULL,NULL,1175,'malformed_json',NULL,'2026-10-14 09:43:01','2026-10-14 09:43:01'),(64,2,'gemini','gemini-2.5-flash','failed',626,NULL,NULL,NULL,'auth_error',NULL,'2026-10-14 10:44:46','2026-10-14 10:44:46'),(65,2,'gemini','gemini-2.5-flash','failed',665,NULL,NULL,NULL,'auth_error',NULL,'2026-10-14 10:44:48','2026-10-14 10:44:48');
/*!40000 ALTER TABLE `ai_interaction_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(64) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_target_type_index` (`target_type`),
  KEY `audit_logs_target_id_index` (`target_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_action_created_idx` (`action`,`created_at`),
  KEY `audit_logs_user_created_idx` (`user_id`,`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'system.initialized','System',1,'Application seeded with Super Admin and default template','127.0.0.1','{\"seeded\":true}','2026-10-14 05:01:01'),(2,NULL,'system.backup_created',NULL,NULL,'Automated system backup created (34 rows, 31712 bytes)','127.0.0.1','{\"checksum\":\"acf0e1aeabab9cf8d83248e83df827148e8c1b248581fdcbe6652aabdf9c3ceb\",\"file\":\"remique_db_2026-10-14_113555.sql\",\"verified\":true}','2026-10-14 05:35:55'),(3,2,'telegram.account_linked','TelegramIdentity',1,'Telegram account linked and verified via deep link token','127.0.0.1','{\"chat_id\":\"5443234556\",\"username\":\"SOJOL_DEV\"}','2026-10-14 06:03:23'),(4,2,'vault.file_uploaded','VaultFile',1,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":86236,\"checksum\":\"5a9f9391ce3d3971cf388bf2173b39c40a3429dee0745ae1a1ebc747195dab5a\"}','2026-10-14 06:41:52'),(5,2,'vault.file_uploaded','VaultFile',2,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":287538,\"checksum\":\"f0cad061a0e08ec4b0bc4f3bd48d83224ee4a43815d9e27a4573307c1e647e17\"}','2026-10-14 07:03:08'),(6,2,'vault.file_uploaded','VaultFile',3,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":287538,\"checksum\":\"f0cad061a0e08ec4b0bc4f3bd48d83224ee4a43815d9e27a4573307c1e647e17\"}','2026-10-14 07:04:31'),(7,2,'vault.file_uploaded','VaultFile',4,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":173973,\"checksum\":\"3a816cec874ace43e7431c6320976b3c5c02a5584972f06b8854098a873ffec0\"}','2026-10-14 07:05:46'),(8,2,'vault.file_uploaded','VaultFile',5,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":164343,\"checksum\":\"e555b77fbd0d67a2f5a6367d0e89a1b20012e3173835eb7dd8bb71fe7c635649\"}','2026-10-14 10:44:40'),(9,2,'vault.file_uploaded','VaultFile',6,'User stored file into personal memory vault','127.0.0.1','{\"mime_type\":\"image\\/jpeg\",\"file_size\":277830,\"checksum\":\"131116a353b684cb96ee0949f04dc47d936ecc75dc9edf9120b35a1d3779b023\"}','2026-10-14 10:44:44');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

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

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('remi-ai-reminder-cache-sys_setting_ai_active_provider','s:6:\"gemini\";',1791999885),('remi-ai-reminder-cache-sys_setting_ai_gemini_api_key','s:39:\"AIzaSyD2NN8Jo5m-9FYTNcmsIvzPODVPYlpD2jY\";',1791995818),('remi-ai-reminder-cache-sys_setting_ai_gemini_model','s:16:\"gemini-2.5-flash\";',1791995818),('remi-ai-reminder-cache-sys_setting_ai_nvidia_api_key','s:70:\"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\";',1791995818),('remi-ai-reminder-cache-sys_setting_ai_nvidia_model','s:18:\"openai/gpt-oss-20b\";',1791995818);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

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

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_14_100001_create_reminders_table',1),(5,'2026_10_14_100002_create_ai_interaction_logs_table',1),(6,'2026_10_14_200001_add_role_to_users_table',2),(7,'2026_10_14_200002_create_whatsapp_identities_table',2),(8,'2026_10_14_200003_create_whatsapp_messages_table',2),(9,'2026_10_14_200004_create_reminder_delivery_attempts_table',2),(10,'2026_10_14_200005_create_vault_files_table',2),(11,'2026_10_14_200006_create_audit_logs_table',2),(12,'2026_10_14_200007_create_whatsapp_templates_table',2),(13,'2026_10_14_300001_add_performance_composite_indexes',3),(14,'2026_10_14_400001_create_telegram_identities_table',4),(15,'2026_10_14_400002_create_telegram_messages_table',5),(16,'2026_10_14_500001_create_system_settings_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

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

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminder_delivery_attempts`
--

DROP TABLE IF EXISTS `reminder_delivery_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminder_delivery_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reminder_id` bigint(20) unsigned NOT NULL,
  `whatsapp_identity_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` varchar(128) NOT NULL,
  `channel` varchar(32) NOT NULL DEFAULT 'whatsapp',
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_name` varchar(64) DEFAULT NULL,
  `whatsapp_message_id` bigint(20) unsigned DEFAULT NULL,
  `attempt_number` int(10) unsigned NOT NULL DEFAULT 1,
  `error_code` varchar(64) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `scheduled_for` datetime NOT NULL,
  `processing_started_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reminder_delivery_attempts_idempotency_key_unique` (`idempotency_key`),
  KEY `reminder_delivery_attempts_whatsapp_message_id_foreign` (`whatsapp_message_id`),
  KEY `reminder_delivery_attempts_reminder_id_status_index` (`reminder_id`,`status`),
  KEY `reminder_delivery_attempts_status_scheduled_for_index` (`status`,`scheduled_for`),
  KEY `reminder_delivery_attempts_status_index` (`status`),
  KEY `delivery_attempts_identity_status_idx` (`whatsapp_identity_id`,`status`),
  CONSTRAINT `reminder_delivery_attempts_reminder_id_foreign` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminder_delivery_attempts_whatsapp_identity_id_foreign` FOREIGN KEY (`whatsapp_identity_id`) REFERENCES `whatsapp_identities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reminder_delivery_attempts_whatsapp_message_id_foreign` FOREIGN KEY (`whatsapp_message_id`) REFERENCES `whatsapp_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminder_delivery_attempts`
--

LOCK TABLES `reminder_delivery_attempts` WRITE;
/*!40000 ALTER TABLE `reminder_delivery_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_delivery_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_at` datetime NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'scheduled',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `recurrence` varchar(32) NOT NULL DEFAULT 'none',
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_user_id_status_index` (`user_id`,`status`),
  KEY `reminders_user_id_due_at_index` (`user_id`,`due_at`),
  KEY `reminders_due_at_index` (`due_at`),
  KEY `reminders_status_index` (`status`),
  KEY `reminders_status_due_at_idx` (`status`,`due_at`),
  CONSTRAINT `reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
INSERT INTO `reminders` VALUES (1,1,'Client Strategy Sync','Review Q4 roadmap with overseas partner','2026-10-25 09:00:00','completed','high','none','2026-10-14 10:33:03',NULL,'2026-10-14 04:32:28','2026-10-14 04:33:03'),(2,2,'ক্লায়েন্টকে ফোন',NULL,'2026-10-14 13:00:00','completed','medium','none','2026-10-14 13:00:02',NULL,'2026-10-14 06:43:33','2026-10-14 07:00:02'),(3,2,'মিটিং',NULL,'2026-10-14 12:50:00','completed','medium','none','2026-10-14 12:53:14',NULL,'2026-10-14 06:44:53','2026-10-14 06:53:14'),(4,2,'Ammu\'s arrival','Ammu will come at 9:30 AM','2026-10-14 03:30:00','completed','medium','none','2026-10-14 15:25:45',NULL,'2026-10-14 09:25:43','2026-10-14 09:25:45'),(5,2,'Ammu er asha',NULL,'2026-10-14 15:35:00','completed','medium','none','2026-10-14 15:35:13',NULL,'2026-10-14 09:34:02','2026-10-14 09:35:13'),(6,2,'client meeting',NULL,'2026-10-14 15:40:00','completed','medium','none','2026-10-14 15:40:07',NULL,'2026-10-14 09:38:28','2026-10-14 09:40:07');
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('5CGJ3MGKixeyMFvG6PqKCmooPnSvTM2SbLFBe9ff',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YToyOntzOjY6Il90b2tlbiI7czo0MDoiUUs4Y2t0VWV1ZFU2NUpDTHRYdDREdGw5TkRNRFdhOEhVbmROZGE2VyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791973908),('8v3fndSD8ihd5nwh561NwIfttKv8ICrHVxQC5qlL',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMTZJcmZmQ21oTko0MEF0VW9iOHFPcFFLTUpTNWQ2NVZTamlERkFkcSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly9sb2NhbGhvc3QvcmVtaS9wdWJsaWMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791974196),('9oVMpXlxFihkI5Oa3dtIrsUeZeXCGUxFEmYBC9q4',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiMkl2T1dvekp3bjk0WXZKbk9TSVpDUHZVdDNrYjJYcjhka3hCZlJXbSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtzOjk6ImRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImRjYzg1NzJiNDRjY2RiM2M4NDQwNGY5OGY3NGIwYjdlNTJmYjk0Nzg0ZWYxYWM4OTI2OWI3OTFiMGNkNWZjN2MiO30=',1791996434),('bzXNivV1bBmOfxUj8hHvN3Dt0IThcMuWDhmSm1kT',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVFphUzAyb0dQRFlIc1FsUVFibXdkZVZnOEhWSmphbk4xOTlyZ3dubSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3JlbWluZGVycyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791973963),('dG2Bo6LY3FqmfupxvrzihhbYR2KDGQz0b2P1tgbi',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMzhWZ08zOUJqQm0yQVNsQnM3enFWa2pweUttQkx6Uk5GQ00zRlR0QSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozODoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3JlbWluZGVycy9jcmVhdGUiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791973929),('eot7KaVmfPL3kAqOKTq7LE7Py8kBXnbUK4UrBOxR',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YToyOntzOjY6Il90b2tlbiI7czo0MDoiQnBpS3l5b05WaTNmaTVrQXFyWXZzVmR3eEQwT0ViRHc3RE0wNUNVUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791973963),('HYNZm7FXGCWWMHoBj5bnYZghlwoc9yzojEJV6upH',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiOHRhZUtpTkdCNlgycFlNaWhnMlFCSlV1R3VieEZuVkc5Zjhya2YzTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yZW1pbmRlcnMiO3M6NToicm91dGUiO3M6MTU6InJlbWluZGVycy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjM2NTEzMTBlODMzNzYwYTFjZWVkOTIxZDdjMTA1NzY4OTcwZWZlNGRkM2E4NDUxMzdkYWVkNzVlOThkMzcyNGEiO30=',1791973983),('Iky4LLyIWzEHLZeZExguOagOT0LOU5YKW3KRoZGd',2,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaHdta2dKWjR6Y0M4Q2F5M1d3cHoyR2QybkZrMm5JeXhJaE10M1NucSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9sb2NhbGhvc3QvcmVtaS9wdWJsaWMvZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtzOjk6ImRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImE5MzI4YzdjMDRkOGIwODAwMWY4MWMwNTc3ZGRkMzlkYzhjOGNlNTViM2RhYWVjOTFlMmQ1MGIwM2ZhYzUwOWQiO30=',1791975074),('Jfa99eo2cu7BKnwowiZVcZUv5YOSfJOP6d7uGzrs',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiN0wxTkxhdm0yUUFFUzRJMm1GOTBEdXo0UnhjZ3lua2g3V2R1bWR0USI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozODoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3JlbWluZGVycy9jcmVhdGUiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791973907),('kyaoHL1JHYTdUpaGoTqF6URe1eJygwF2ixzq0Brh',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTFo2Z1Bia1RNWURvTWd4MldBenVubzBPWmJEaERSdnpnNkp6TnFMVCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3JlbWluZGVycyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791973973),('l3qLD3odiNWmZSgHXp3tB0NRi5KSQSuS7ltLdeVO',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoibTU3ZzEybFkzTDdYM21EeXkyZFNKaTZVNzVEZXppWjVSck1UMGs0byI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9zZXR0aW5ncyI7czo1OiJyb3V0ZSI7czoxMzoic2V0dGluZ3MuZWRpdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImE5MzI4YzdjMDRkOGIwODAwMWY4MWMwNTc3ZGRkMzlkYzhjOGNlNTViM2RhYWVjOTFlMmQ1MGIwM2ZhYzUwOWQiO30=',1791979392),('n7GefnKFOXMxjXo5qakbxHmc148PhHkxeHDHybdH',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YToyOntzOjY6Il90b2tlbiI7czo0MDoiQTlOWkRqWEkwNTNTQ0poVW80UWtxTEd1R01XVXdhVTl2NjVkS0RjNCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791973973),('PG9ohIqIk1GjagkGxPwHa9HkRa05kjOJyhLPoyk8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUGdCWDRpQkdNeHFRd2FncmpFWWR1dWkzZ3Nyc1I0RTNhd1BneG55NCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791973835),('rs4wnLA5A6Ujqc67R70YF7XOrRUD3A9uzwSnugd6',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiblNkczhTek1HeTJ5RkpqSWVNSDVMTmZNR1gxa29OSVZMNHY2NG9FbCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yZW1pbmRlcnMiO3M6NToicm91dGUiO3M6MTU6InJlbWluZGVycy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjM2NTEzMTBlODMzNzYwYTFjZWVkOTIxZDdjMTA1NzY4OTcwZWZlNGRkM2E4NDUxMzdkYWVkNzVlOThkMzcyNGEiO30=',1791973948),('UtW7qhHlRLq0FcwUncbhviVGef5MuR7zNyWb1PCb',NULL,'127.0.0.1','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOGl1ZGszd0hGb2o1VXhVU2pYSjliZmQwOEpyWTIyT1VPeUN6WXJQWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791978303),('xwvYf5O7CikxKD28Po5ZeP4mJ90FWtV8ET3ymU9T',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQlpuMHNuSzJnODVQeGxQbzVTVEFIWFdxanVYWFZXVnBveHZlY3BUbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly9sb2NhbGhvc3QvcmVtaS9wdWJsaWMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791974283),('XwZWfHFZTSNH90oKy6Ea0OiU588ccmTziK8xmEad',NULL,'127.0.0.1','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRTE5RVV0d0YyaVZmQ1ZWNWVoYlpHejgyZ0xCSlpRNE9jMHlBQjVYMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791992349),('YBJkIQUPiwPKK2N2mQ68YV5o7zeZirYRjH5BfFG5',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTDhFaXhEcHhOUWVyVXV6VjlMQTVEUnIzTHNBb1dBSTROdlVwRXVmRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMzY1MTMxMGU4MzM3NjBhMWNlZWQ5MjFkN2MxMDU3Njg5NzBlZmU0ZGQzYTg0NTEzN2RhZWQ3NWU5OGQzNzI0YSI7fQ==',1791973899),('YESnNTLeJ0uCZJbsjiMNHLnWz0nPXc7LJ0Fuvehq',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9711','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ1h4OXQ0aEw0Tmh3eEpqeDduVDhBRG1FanBJYURxWmRDY25DV2Y5RiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly9sb2NhbGhvc3QvcmVtaS9wdWJsaWMvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791974165);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_identities`
--

DROP TABLE IF EXISTS `telegram_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_identities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `chat_id` varchar(64) NOT NULL,
  `username` varchar(128) DEFAULT NULL,
  `first_name` varchar(128) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending_verification',
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `last_inbound_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telegram_identities_chat_id_unique` (`chat_id`),
  KEY `telegram_identities_user_id_status_index` (`user_id`,`status`),
  KEY `telegram_identities_username_index` (`username`),
  KEY `telegram_identities_status_index` (`status`),
  KEY `telegram_identities_verification_token_index` (`verification_token`),
  KEY `telegram_identities_last_inbound_at_index` (`last_inbound_at`),
  CONSTRAINT `telegram_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_identities`
--

LOCK TABLES `telegram_identities` WRITE;
/*!40000 ALTER TABLE `telegram_identities` DISABLE KEYS */;
INSERT INTO `telegram_identities` VALUES (1,2,'5443234556','SOJOL_DEV','MD SAJOL','active',NULL,NULL,'2026-10-14 12:03:23','2026-10-14 16:44:47','2026-10-14 05:52:23','2026-10-14 10:44:47');
/*!40000 ALTER TABLE `telegram_identities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_messages`
--

DROP TABLE IF EXISTS `telegram_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `telegram_identity_id` bigint(20) unsigned NOT NULL,
  `chat_id` varchar(64) NOT NULL,
  `direction` varchar(16) NOT NULL,
  `role` varchar(16) NOT NULL DEFAULT 'user',
  `message_id` varchar(64) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `telegram_messages_telegram_identity_id_created_at_index` (`telegram_identity_id`,`created_at`),
  KEY `telegram_messages_chat_id_index` (`chat_id`),
  KEY `telegram_messages_direction_index` (`direction`),
  CONSTRAINT `telegram_messages_telegram_identity_id_foreign` FOREIGN KEY (`telegram_identity_id`) REFERENCES `telegram_identities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_messages`
--

LOCK TABLES `telegram_messages` WRITE;
/*!40000 ALTER TABLE `telegram_messages` DISABLE KEYS */;
INSERT INTO `telegram_messages` VALUES (1,1,'5443234556','inbound','user','18','Remi kemon aso','2026-10-14 06:31:17','2026-10-14 06:31:17'),(2,1,'5443234556','outbound','assistant',NULL,'⚠️ NVIDIA NIM API key is not configured. Please set NVIDIA_NIM_API_KEY in your environment or use manual reminder creation.','2026-10-14 06:31:18','2026-10-14 06:31:18'),(3,1,'5443234556','inbound','user','20','Hello','2026-10-14 06:38:48','2026-10-14 06:38:48'),(4,1,'5443234556','outbound','assistant',NULL,'Hey there! How can I help you today?','2026-10-14 06:38:54','2026-10-14 06:38:54'),(5,1,'5443234556','inbound','user','22','Kemon aso','2026-10-14 06:39:00','2026-10-14 06:39:00'),(6,1,'5443234556','outbound','assistant',NULL,'Hey! Ami bhalo achi, thanks for asking. Apni kemon achen?','2026-10-14 06:39:06','2026-10-14 06:39:06'),(7,1,'5443234556','inbound','user','24','Alhamdulillah valo','2026-10-14 06:39:14','2026-10-14 06:39:14'),(8,1,'5443234556','outbound','assistant',NULL,'Alhamdulillah, khub bhalo! 😊 Ki korben aajkal? Kichu help dorkar?','2026-10-14 06:39:17','2026-10-14 06:39:17'),(9,1,'5443234556','inbound','user','26','একটা রিমাইন্ডার সেট করো সকাল আটটার সময় কাল মানে ১৫ তারিখ','2026-10-14 06:39:34','2026-10-14 06:39:34'),(10,1,'5443234556','outbound','assistant',NULL,'⚠️ Unable to parse structured reminder from AI output. Please use manual reminder creation.','2026-10-14 06:39:38','2026-10-14 06:39:38'),(11,1,'5443234556','inbound','user','30','আমার পেমেন্ট স্লিপ টা দাও তো','2026-10-14 06:42:04','2026-10-14 06:42:04'),(12,1,'5443234556','outbound','assistant',NULL,'[File sent: file_0.jpg] অবশ্যই! আপনার পেমেন্ট স্লিপটি খুঁজে এনে দিচ্ছি।','2026-10-14 06:42:13','2026-10-14 06:42:13'),(13,1,'5443234556','inbound','user','32','একটা রিমাইন্ডার সেট করো','2026-10-14 06:42:37','2026-10-14 06:42:37'),(14,1,'5443234556','outbound','assistant',NULL,'রিমাইন্ডারটি কী বিষয়ে হবে, কখন এবং কত সময়ে সেট করতে চান?','2026-10-14 06:42:42','2026-10-14 06:42:42'),(15,1,'5443234556','inbound','user','34','৭ টার সময় ক্লাইন্টকে ফোন দিতে হবে আমাকে মনে করিয়ে দিবে','2026-10-14 06:42:52','2026-10-14 06:42:52'),(16,1,'5443234556','outbound','assistant',NULL,'কোন তারিখে রিমাইন্ডারটি সেট করতে চান?','2026-10-14 06:43:01','2026-10-14 06:43:01'),(17,1,'5443234556','inbound','user','36','আজকে','2026-10-14 06:43:08','2026-10-14 06:43:08'),(18,1,'5443234556','outbound','assistant',NULL,'আপনি কি ৭ টা সকাল না ৭ টা রাত বোঝাতে চাচ্ছেন?','2026-10-14 06:43:15','2026-10-14 06:43:15'),(19,1,'5443234556','inbound','user','38','রাত সাতটা','2026-10-14 06:43:24','2026-10-14 06:43:24'),(20,1,'5443234556','outbound','assistant',NULL,'🔔 Reminder Scheduled!\n\nঠিক আছে, আজ রাত ৭টায় ক্লায়েন্টকে ফোন করার রিমাইন্ডার সেট করেছি।\n\n📌 Title: ক্লায়েন্টকে ফোন\n⏰ Due: Oct 14, 2026 07:00 PM (Asia/Dhaka)\n🔁 Recurrence: None','2026-10-14 06:43:34','2026-10-14 06:43:34'),(21,1,'5443234556','inbound','user','40','রাত ৬:৫০ এ আমার একটা মিটিং আছে মনে করিয়ে দিও','2026-10-14 06:44:46','2026-10-14 06:44:46'),(22,1,'5443234556','outbound','assistant',NULL,'🔔 Reminder Scheduled!\n\nঠিক আছে, আজ রাত ৬:৫০ টায় আপনার মিটিংয়ের রিমাইন্ডার সেট করেছি।\n\n📌 Title: মিটিং\n⏰ Due: Oct 14, 2026 06:50 PM (Asia/Dhaka)\n🔁 Recurrence: None','2026-10-14 06:44:53','2026-10-14 06:44:53'),(23,1,'5443234556','inbound','user','42','আমার প্রেমেন্ট ছিল টা দাও তো','2026-10-14 06:45:55','2026-10-14 06:45:55'),(24,1,'5443234556','outbound','assistant',NULL,'আপনার \'প্রেমেন্ট\' ফাইলটি খুঁজে বের করছি, একটু অপেক্ষা করুন।','2026-10-14 06:46:05','2026-10-14 06:46:05'),(25,1,'5443234556','inbound','user','45','payment slip peyecho ?','2026-10-14 06:57:06','2026-10-14 06:57:06'),(26,1,'5443234556','outbound','assistant',NULL,'[File sent: file_0.jpg]','2026-10-14 06:57:16','2026-10-14 06:57:16'),(27,1,'5443234556','inbound','user','48','Thank','2026-10-14 07:00:21','2026-10-14 07:00:21'),(28,1,'5443234556','outbound','assistant',NULL,'Thanks! If you need anything else, just let me know.','2026-10-14 07:00:31','2026-10-14 07:00:31'),(29,1,'5443234556','inbound','user','50','Okay remi','2026-10-14 07:00:39','2026-10-14 07:00:39'),(30,1,'5443234556','outbound','assistant',NULL,'Sure! How can I help you today?','2026-10-14 07:00:49','2026-10-14 07:00:49'),(31,1,'5443234556','inbound','user','54','এটা আমার বিড়ালের ছবি রেখে দাও ঠিক আছে নাম হচ্ছে মিষ্টি','2026-10-14 07:03:10','2026-10-14 07:03:10'),(32,1,'5443234556','outbound','assistant',NULL,'অবশ্যই! আপনার বিড়ালের ছবি ‘মিষ্টি’ নামে সংরক্ষণ করেছি।','2026-10-14 07:03:19','2026-10-14 07:03:19'),(33,1,'5443234556','inbound','user','56','মিষ্টির ছবিটা দাও তো','2026-10-14 07:03:39','2026-10-14 07:03:39'),(34,1,'5443234556','outbound','assistant',NULL,'🔍 দুঃখিত, আপনার মেমোরি ভল্টে \'মিষ্টি\' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি। অনুগ্রহ করে ফাইলের সঠিক বিষয় বা নাম উল্লেখ করুন।','2026-10-14 07:03:44','2026-10-14 07:03:44'),(35,1,'5443234556','inbound','user','58','বিড়াল এর ছবিটা দাও','2026-10-14 07:03:54','2026-10-14 07:03:54'),(36,1,'5443234556','outbound','assistant',NULL,'🔍 দুঃখিত, আপনার মেমোরি ভল্টে \'মিষ্টি\' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি। অনুগ্রহ করে ফাইলের সঠিক বিষয় বা নাম উল্লেখ করুন।','2026-10-14 07:04:03','2026-10-14 07:04:03'),(37,1,'5443234556','inbound','user','62','Show me my photo','2026-10-14 07:04:53','2026-10-14 07:04:53'),(38,1,'5443234556','outbound','assistant',NULL,'[File sent: এটা আমার বিড়ালের ছবি বিড়ালটির নাম হচ্ছে মিষ্টি]','2026-10-14 07:05:07','2026-10-14 07:05:07'),(39,1,'5443234556','inbound','user','66','Laptop er photo ta amake fast deo','2026-10-14 07:06:00','2026-10-14 07:06:00'),(40,1,'5443234556','outbound','assistant',NULL,'[File sent: Laptop]','2026-10-14 07:06:06','2026-10-14 07:06:06'),(41,1,'5443234556','inbound','user','68','Fast amake Amar পেমেন্ট স্লিপ দাও','2026-10-14 07:06:25','2026-10-14 07:06:25'),(42,1,'5443234556','outbound','assistant',NULL,'🔍 দুঃখিত, আপনার মেমোরি ভল্টে \'পেমেন্ট স্লিপ\' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি। অনুগ্রহ করে ফাইলের সঠিক বিষয় বা নাম উল্লেখ করুন।','2026-10-14 07:06:51','2026-10-14 07:06:51'),(43,1,'5443234556','inbound','user','70','Fast amake Amar পেমেন্ট স্লিপ দাও07:06 PM','2026-10-14 07:07:06','2026-10-14 07:07:06'),(44,1,'5443234556','outbound','assistant',NULL,'🔍 দুঃখিত, আপনার মেমোরি ভল্টে \'পেমেন্ট স্লিপ\' সম্পর্কিত কোনো ফাইল খুঁজে পাওয়া যায়নি। অনুগ্রহ করে ফাইলের সঠিক বিষয় বা নাম উল্লেখ করুন।','2026-10-14 07:07:15','2026-10-14 07:07:15'),(45,1,'5443234556','inbound','user','72','payment slip deo','2026-10-14 07:07:34','2026-10-14 07:07:34'),(46,1,'5443234556','outbound','assistant',NULL,'[File sent: Ata Amar college payment slip]','2026-10-14 07:07:40','2026-10-14 07:07:40'),(47,1,'5443234556','inbound','user','74','কেমন আছো','2026-10-14 08:29:06','2026-10-14 08:29:06'),(48,1,'5443234556','outbound','assistant',NULL,'আমি ভালো আছি, ধন্যবাদ! আপনি কেমন আছেন?','2026-10-14 08:29:55','2026-10-14 08:29:55'),(49,1,'5443234556','inbound','user','76','আছি ভালো','2026-10-14 09:10:09','2026-10-14 09:10:09'),(50,1,'5443234556','outbound','assistant',NULL,'⚠️ Connection to NVIDIA NIM timed out. Please check your internet connection or use manual creation.','2026-10-14 09:11:20','2026-10-14 09:11:20'),(51,1,'5443234556','inbound','user','77','akta reminder set korte hobe','2026-10-14 09:13:34','2026-10-14 09:13:34'),(52,1,'5443234556','outbound','assistant',NULL,'Kokhon er reminder set korte chan?','2026-10-14 09:14:28','2026-10-14 09:14:28'),(53,1,'5443234556','inbound','user','79','kemon aso ??','2026-10-14 09:21:52','2026-10-14 09:21:52'),(54,1,'5443234556','outbound','assistant',NULL,'Ami bhalo achi, thanks! Apni kemon?','2026-10-14 09:21:58','2026-10-14 09:21:58'),(55,1,'5443234556','inbound','user','81','valo','2026-10-14 09:22:05','2026-10-14 09:22:05'),(56,1,'5443234556','outbound','assistant',NULL,'Thik ache, valo! Kichu help chai?','2026-10-14 09:22:13','2026-10-14 09:22:13'),(57,1,'5443234556','inbound','user','84','hey remi','2026-10-14 09:24:22','2026-10-14 09:24:22'),(58,1,'5443234556','outbound','assistant',NULL,'Hey! How can I help you today?','2026-10-14 09:24:26','2026-10-14 09:24:26'),(59,1,'5443234556','inbound','user','86','kemon aso ?','2026-10-14 09:24:35','2026-10-14 09:24:35'),(60,1,'5443234556','outbound','assistant',NULL,'Bhalo achi, tumi kemon aso?','2026-10-14 09:24:40','2026-10-14 09:24:40'),(61,1,'5443234556','inbound','user','88','akta reminder set koro','2026-10-14 09:25:13','2026-10-14 09:25:13'),(62,1,'5443234556','outbound','assistant',NULL,'Could you please provide the title, date, time, priority, and recurrence for the reminder?','2026-10-14 09:25:31','2026-10-14 09:25:31'),(63,1,'5443234556','inbound','user','89','9 ta 30 a  ammu asbe','2026-10-14 09:25:35','2026-10-14 09:25:35'),(64,1,'5443234556','outbound','assistant',NULL,'🔔 Reminder Set Kora Hoyeche!\n\nAmmu er 9:30 AM e asbe reminder set kore dichi!\n\n📌 Title: Ammu\'s arrival\n⏰ Shomoy: Oct 14, 2026 09:30 AM (Asia/Dhaka)\n🔁 Recurrence: Ekbar','2026-10-14 09:25:43','2026-10-14 09:25:43'),(65,1,'5443234556','inbound','user','93','এখন AM নাকি PM','2026-10-14 09:32:09','2026-10-14 09:32:09'),(66,1,'5443234556','outbound','assistant',NULL,'আপনি কি 9:30 AM নাকি 9:30 PM বলতে চাচ্ছেন?','2026-10-14 09:32:36','2026-10-14 09:32:36'),(67,1,'5443234556','inbound','user','95','Hum','2026-10-14 09:32:45','2026-10-14 09:32:45'),(68,1,'5443234556','outbound','assistant',NULL,'Do you mean 9:30 AM or 9:30 PM?','2026-10-14 09:32:52','2026-10-14 09:32:52'),(69,1,'5443234556','inbound','user','97','Yea','2026-10-14 09:33:02','2026-10-14 09:33:02'),(70,1,'5443234556','outbound','assistant',NULL,'⚠️ AI provider returned an empty response. Please use manual reminder creation.','2026-10-14 09:33:11','2026-10-14 09:33:11'),(71,1,'5443234556','inbound','user','99','Akta reminder set koro','2026-10-14 09:33:29','2026-10-14 09:33:29'),(72,1,'5443234556','outbound','assistant',NULL,'দয়া করে রিমাইন্ডারের শিরোনাম, তারিখ, সময়, অগ্রাধিকার ও পুনরাবৃত্তি উল্লেখ করুন।','2026-10-14 09:33:38','2026-10-14 09:33:38'),(73,1,'5443234556','inbound','user','101','Ammu asbe 9:35 pm','2026-10-14 09:33:57','2026-10-14 09:33:57'),(74,1,'5443234556','outbound','assistant',NULL,'🔔 Reminder Set Kora Hoyeche!\n\nAmmu er asha 9:35 pm e set kore dichi!\n\n📌 Title: Ammu er asha\n⏰ Shomoy: Oct 14, 2026 09:35 PM (Asia/Dhaka)\n🔁 Recurrence: Ekbar','2026-10-14 09:34:02','2026-10-14 09:34:02'),(75,1,'5443234556','inbound','user','104','Same reminder 9:37pm a set koro 2 bar  jenk amake mone koriye dei','2026-10-14 09:36:43','2026-10-14 09:36:43'),(76,1,'5443234556','inbound','user','104','Same reminder 9:37pm a set koro 2 bar  jenk amake mone koriye dei','2026-10-14 09:37:00','2026-10-14 09:37:00'),(77,1,'5443234556','outbound','assistant',NULL,'⚠️ Unable to parse structured reminder from Gemini response.','2026-10-14 09:37:05','2026-10-14 09:37:05'),(78,1,'5443234556','inbound','user','106','Hello','2026-10-14 09:37:25','2026-10-14 09:37:25'),(79,1,'5443234556','outbound','assistant',NULL,'Hello! How can I assist you today?','2026-10-14 09:37:30','2026-10-14 09:37:30'),(80,1,'5443234556','inbound','user','108','Reminder set koro akta','2026-10-14 09:37:39','2026-10-14 09:37:39'),(81,1,'5443234556','outbound','assistant',NULL,'Sure, ami ekta reminder set korte pari. Ki bisoye reminder ta hobe, kobe ar koytay?','2026-10-14 09:37:46','2026-10-14 09:37:46'),(82,1,'5443234556','inbound','user','110','9:40 pm a client meeting ache Amar','2026-10-14 09:38:24','2026-10-14 09:38:24'),(83,1,'5443234556','outbound','assistant',NULL,'🔔 Reminder Set Kora Hoyeche!\n\nThik ache, ajke raat 9:40 pm-e \'client meeting\' er jonno ekta reminder set kora holo.\n\n📌 Title: client meeting\n⏰ Shomoy: Oct 14, 2026 09:40 PM (Asia/Dhaka)\n🔁 Recurrence: Ekbar','2026-10-14 09:38:32','2026-10-14 09:38:32'),(84,1,'5443234556','inbound','user','113','Meeting ta pichiye deoya hoyeche 9:42pm a','2026-10-14 09:41:07','2026-10-14 09:41:07'),(85,1,'5443234556','outbound','assistant',NULL,'Thik ache, \'client meeting\' reminder ta ajker jonno raat 9:42 PM-e reschedule kora holo.','2026-10-14 09:41:14','2026-10-14 09:41:14'),(86,1,'5443234556','inbound','user','115','রিমাইন্ডার লিস্ট টা দেও তো আমাকে','2026-10-14 09:42:34','2026-10-14 09:42:34'),(87,1,'5443234556','outbound','assistant',NULL,'📋 আপনার এই মুহূর্তে কোনো পেন্ডিং রিমাইন্ডার শিডিউল করা নেই।','2026-10-14 09:42:38','2026-10-14 09:42:38'),(88,1,'5443234556','inbound','user','117','যে গুলা কমপ্লিট হয়েছে সে গুলা দেও','2026-10-14 09:42:58','2026-10-14 09:42:58'),(89,1,'5443234556','outbound','assistant',NULL,'⚠️ Unable to parse structured reminder from Gemini response.','2026-10-14 09:43:03','2026-10-14 09:43:03'),(90,1,'5443234556','inbound','user','121','Ki korcho ??','2026-10-14 10:44:45','2026-10-14 10:44:45'),(91,1,'5443234556','outbound','assistant',NULL,'⚠️ Gemini connection error: HTTP request returned status code 403:\n{\n  \"error\": {\n    \"code\": 403,\n    \"message\": \"Your API key was reported as leaked. Please use another API key.\",\n    \" (truncated...)\n','2026-10-14 10:44:47','2026-10-14 10:44:47'),(92,1,'5443234556','inbound','user','122','He\'ll','2026-10-14 10:44:48','2026-10-14 10:44:48'),(93,1,'5443234556','outbound','assistant',NULL,'⚠️ Gemini connection error: HTTP request returned status code 403:\n{\n  \"error\": {\n    \"code\": 403,\n    \"message\": \"Your API key was reported as leaked. Please use another API key.\",\n    \" (truncated...)\n','2026-10-14 10:44:49','2026-10-14 10:44:49');
/*!40000 ALTER TABLE `telegram_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'customer',
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `preferred_language` varchar(10) NOT NULL DEFAULT 'en',
  `preferred_channel` varchar(32) NOT NULL DEFAULT 'all',
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
  `theme` varchar(20) NOT NULL DEFAULT 'light',
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Arefin Shuvo','arefin.live@example.com',NULL,'$2y$12$fBf73Ls6yd55chGCbYqlh.4z0/wZi/Trb06Hucnkhi5O9j9OCvaDi','customer','active','en','all','Asia/Dhaka','light','{\"email\":true,\"sound\":true}',NULL,'2026-10-14 04:31:38','2026-10-14 04:31:38'),(2,'Sojol','sojoldev31@gmail.com',NULL,'$2y$12$2ULai/Tu/qvX7DqUumYpo.2L8kihYQt.4Xn6AtvgMTx9JaBQjrILa','super_admin','active','en','all','Asia/Dhaka','light','{\"email\":true,\"sound\":true}','LwFqZHOx8ZY9gByGO5gWO3ZUzOFsm2RLz8EHvH6C9feh8yX1MN6cjNxi9rXb','2026-10-14 04:50:55','2026-10-14 05:18:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vault_files`
--

DROP TABLE IF EXISTS `vault_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vault_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `whatsapp_message_id` bigint(20) unsigned DEFAULT NULL,
  `storage_disk` varchar(32) NOT NULL DEFAULT 'local',
  `storage_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `checksum_sha256` varchar(64) NOT NULL,
  `caption` text DEFAULT NULL,
  `category` varchar(64) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `search_text` longtext DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vault_files_whatsapp_message_id_foreign` (`whatsapp_message_id`),
  KEY `vault_files_user_id_status_index` (`user_id`,`status`),
  KEY `vault_files_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `vault_files_checksum_sha256_index` (`checksum_sha256`),
  KEY `vault_files_category_index` (`category`),
  KEY `vault_files_status_index` (`status`),
  CONSTRAINT `vault_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vault_files_whatsapp_message_id_foreign` FOREIGN KEY (`whatsapp_message_id`) REFERENCES `whatsapp_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vault_files`
--

LOCK TABLES `vault_files` WRITE;
/*!40000 ALTER TABLE `vault_files` DISABLE KEYS */;
INSERT INTO `vault_files` VALUES (1,2,NULL,'local','vault/2/02e803ec-32a6-4d6e-9aeb-ee82ff919353.jpg','College_Payment_Slip.jpg','image/jpeg',86236,'5a9f9391ce3d3971cf388bf2173b39c40a3429dee0745ae1a1ebc747195dab5a','Ata Amar college payment slip','photo','[]','Ata Amar college payment slip file_0','active',NULL,'2026-10-14 06:41:52','2026-10-14 06:41:52'),(2,2,NULL,'local','vault/2/b0e4e187-0954-4259-9a51-60a01a8f1d63.jpg','Mishti_Cat_Photo.jpg','image/jpeg',287538,'f0cad061a0e08ec4b0bc4f3bd48d83224ee4a43815d9e27a4573307c1e647e17','???????? ??? ??????','photo','[]','?????? ???????? ??? ?????? Cat Photo Mishti','active',NULL,'2026-10-14 07:03:08','2026-10-14 07:03:08'),(3,2,NULL,'local','vault/2/a4728547-e477-4cd8-af89-e14bb74c624e.jpg','eta_amar_bidaler_chbi_bidaltir_nam.jpg','image/jpeg',287538,'f0cad061a0e08ec4b0bc4f3bd48d83224ee4a43815d9e27a4573307c1e647e17','এটা আমার বিড়ালের ছবি বিড়ালটির নাম হচ্ছে মিষ্টি','photo','[]','এটা আমার বিড়ালের ছবি বিড়ালটির নাম হচ্ছে মিষ্টি eta_amar_bidaler_chbi_bidaltir_nam','active',NULL,'2026-10-14 07:04:31','2026-10-14 07:04:31'),(4,2,NULL,'local','vault/2/7c9183e0-1b06-411e-ad4c-6b8585900855.jpg','laptop.jpg','image/jpeg',173973,'3a816cec874ace43e7431c6320976b3c5c02a5584972f06b8854098a873ffec0','Laptop','photo','[]','Laptop laptop','active',NULL,'2026-10-14 07:05:46','2026-10-14 07:05:46'),(5,2,NULL,'local','vault/2/df7701e4-6e7b-4ef0-bcff-e9f91e14a68a.jpg','door_lock.jpg','image/jpeg',164343,'e555b77fbd0d67a2f5a6367d0e89a1b20012e3173835eb7dd8bb71fe7c635649','Door lock','photo','[]','Door lock door_lock','active',NULL,'2026-10-14 10:44:40','2026-10-14 10:44:40'),(6,2,NULL,'local','vault/2/6cfbaf48-9850-4b4c-a2d5-e455dd5fad52.jpg','Photo_2026-10-14_1644.jpg','image/jpeg',277830,'131116a353b684cb96ee0949f04dc47d936ecc75dc9edf9120b35a1d3779b023','','photo','[]','Photo_2026-10-14_1644','active',NULL,'2026-10-14 10:44:44','2026-10-14 10:44:44');
/*!40000 ALTER TABLE `vault_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_identities`
--

DROP TABLE IF EXISTS `whatsapp_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_identities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(32) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending_verification',
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `manual_verification_reason` text DEFAULT NULL,
  `consent_recorded_at` datetime DEFAULT NULL,
  `last_inbound_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_identities_phone_number_unique` (`phone_number`),
  KEY `whatsapp_identities_verified_by_user_id_foreign` (`verified_by_user_id`),
  KEY `whatsapp_identities_user_id_status_index` (`user_id`,`status`),
  KEY `whatsapp_identities_status_index` (`status`),
  KEY `whatsapp_identities_verification_token_index` (`verification_token`),
  KEY `whatsapp_identities_last_inbound_at_index` (`last_inbound_at`),
  CONSTRAINT `whatsapp_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_identities_verified_by_user_id_foreign` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_identities`
--

LOCK TABLES `whatsapp_identities` WRITE;
/*!40000 ALTER TABLE `whatsapp_identities` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_identities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_messages`
--

DROP TABLE IF EXISTS `whatsapp_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wamid` varchar(128) DEFAULT NULL,
  `whatsapp_identity_id` bigint(20) unsigned DEFAULT NULL,
  `direction` varchar(16) NOT NULL,
  `from_number` varchar(32) NOT NULL,
  `to_number` varchar(32) NOT NULL,
  `message_type` varchar(32) NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `media_id` varchar(128) DEFAULT NULL,
  `mime_type` varchar(128) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'received',
  `error_code` varchar(64) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_messages_wamid_unique` (`wamid`),
  KEY `whatsapp_messages_direction_created_at_index` (`direction`,`created_at`),
  KEY `whatsapp_messages_whatsapp_identity_id_created_at_index` (`whatsapp_identity_id`,`created_at`),
  KEY `whatsapp_messages_direction_index` (`direction`),
  KEY `whatsapp_messages_from_number_index` (`from_number`),
  KEY `whatsapp_messages_to_number_index` (`to_number`),
  KEY `whatsapp_messages_status_index` (`status`),
  KEY `whatsapp_messages_from_created_idx` (`from_number`,`created_at`),
  KEY `whatsapp_messages_status_created_idx` (`status`,`created_at`),
  CONSTRAINT `whatsapp_messages_whatsapp_identity_id_foreign` FOREIGN KEY (`whatsapp_identity_id`) REFERENCES `whatsapp_identities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_messages`
--

LOCK TABLES `whatsapp_messages` WRITE;
/*!40000 ALTER TABLE `whatsapp_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `category` varchar(32) NOT NULL DEFAULT 'UTILITY',
  `language` varchar(16) NOT NULL DEFAULT 'en_US',
  `status` varchar(32) NOT NULL DEFAULT 'APPROVED',
  `body_text` text NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_templates_name_unique` (`name`),
  KEY `whatsapp_templates_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
INSERT INTO `whatsapp_templates` VALUES (1,'remi_reminder_alert','UTILITY','en_US','APPROVED','Hello {{1}}, this is your Remique reminder: {{2}} scheduled for {{3}}.','[\"user_name\",\"reminder_title\",\"formatted_due_time\"]','2026-10-14 05:01:01','2026-10-14 05:01:01');
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-14 22:48:54

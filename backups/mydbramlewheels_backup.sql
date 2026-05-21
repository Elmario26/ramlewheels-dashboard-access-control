-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: mydbramlewheels
-- ------------------------------------------------------
-- Server version	8.0.43

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
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `affected_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_activity_user` (`user_id`),
  KEY `IDX_activity_action` (`action`),
  KEY `IDX_activity_created_at` (`created_at`),
  CONSTRAINT `FK_F34B1DCEA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 08:47:04','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(2,1,'update','User',1,'Updated user: Elmar Lariosa (Elmar)','{\"username\": {\"after\": \"Elmar\", \"before\": \"ramlÃ©_admin\"}, \"firstName\": {\"after\": \"Elmar\", \"before\": \"RamlÃ©\"}}','2026-05-21 08:47:59','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(3,1,'create','Vehicle',1,'Created vehicle: Hyundai 2019','{\"year\": {\"after\": \"2019\"}, \"brand\": {\"after\": \"Hyundai\"}, \"price\": {\"after\": 350000.0}, \"mileage\": {\"after\": \"10000\"}}','2026-05-21 09:04:19','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(4,1,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 10:58:47','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(5,1,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 14:27:07','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(6,1,'logout',NULL,NULL,'User logged out',NULL,'2026-05-21 15:21:30','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(7,4,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 15:21:45','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(8,4,'logout',NULL,NULL,'User logged out',NULL,'2026-05-21 15:22:55','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(9,1,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 15:54:31','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(10,1,'login',NULL,NULL,'User logged in',NULL,'2026-05-21 18:01:38','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(11,1,'create','Vehicle',2,'Created vehicle: Toyota 2017','{\"year\": {\"after\": \"2017\"}, \"brand\": {\"after\": \"Toyota\"}, \"price\": {\"after\": 500000.0}, \"mileage\": {\"after\": \"100004\"}}','2026-05-21 20:00:44','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(12,1,'create','Vehicle',3,'Created vehicle: Toyota 2022','{\"year\": {\"after\": \"2022\"}, \"brand\": {\"after\": \"Toyota\"}, \"price\": {\"after\": 15.0}, \"mileage\": {\"after\": \"10000\"}}','2026-05-21 20:02:08','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),(13,1,'create','Vehicle',4,'Created vehicle: Toyota 2017','{\"year\": {\"after\": \"2017\"}, \"brand\": {\"after\": \"Toyota\"}, \"price\": {\"after\": 500000.0}, \"mileage\": {\"after\": \"1212414\"}}','2026-05-21 20:05:07','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mileage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL,
  `images` json DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `damage_description` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (1,'Hyundai','2019','10000','Good as new',350000,'[\"accent1-6a0eae73c4323.jpg\", \"accent2-6a0eae73c5edb.jpg\", \"accent3-6a0eae73c6f43.jpg\", \"accent4-6a0eae73c8024.jpg\"]','available','Accent','White','GAC5456','12345678912365487',NULL),(2,'Toyota','2017','100004','Good',500000,'[\"blackvios1-6a0f484c25f2b.jpg\", \"blackvios2-6a0f484c26697.jpg\", \"blackvios3-6a0f484c26c86.jpg\", \"blackvios4-6a0f484c2798e.jpg\"]','available','Vios','Black','AFF1142','1234567892',NULL),(3,'Toyota','2022','10000','Good as new',15,'[\"vios1-6a0f48a02bb7b.jpg\", \"vios2-6a0f48a02c963.jpg\", \"vios3-6a0f48a02d89c.jpg\", \"vios4-6a0f48a02ebb6.jpg\"]','available','Vios','White','ABC124','1234567892',NULL),(4,'Toyota','2017','1212414','Good as new',500000,'[\"wigo1-6a0f495385146.jpg\", \"wigo2-6a0f495385cf2.jpg\", \"wigo3-6a0f49538637c.jpg\", \"wigo4-6a0f49538686f.jpg\"]','available','Wigo','Silver','GAC5456','123456789112346548712323',NULL);
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
INSERT INTO `customer` VALUES (1,'elmar','lariosa','ramle@gmail.com',NULL,NULL,NULL,NULL,'Registered via API / mobile app','2026-05-21 15:20:12',NULL),(2,'Jane','Smith','jane@example.com',NULL,NULL,NULL,NULL,'Registered via API / mobile app','2026-05-21 15:03:34',NULL);
/*!40000 ALTER TABLE `customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES ('DoctrineMigrations\\Version20250930092636','2026-05-21 08:29:30',118),('DoctrineMigrations\\Version20251007093520','2026-05-21 08:29:30',190),('DoctrineMigrations\\Version20251007133412','2026-05-21 08:29:31',100),('DoctrineMigrations\\Version20251007141134','2026-05-21 08:29:31',68),('DoctrineMigrations\\Version20251007150527','2026-05-21 08:29:31',78),('DoctrineMigrations\\Version20251009113748','2026-05-21 08:29:31',358),('DoctrineMigrations\\Version20251009141836','2026-05-21 08:29:31',276),('DoctrineMigrations\\Version20251009154753','2026-05-21 08:29:31',84),('DoctrineMigrations\\Version20251013040353','2026-05-21 08:29:32',67),('DoctrineMigrations\\Version20251014050028','2026-05-21 08:29:32',271),('DoctrineMigrations\\Version20251014054047','2026-05-21 08:29:32',272),('DoctrineMigrations\\Version20251210043448','2026-05-21 08:29:32',40),('DoctrineMigrations\\Version20251210045350','2026-05-21 08:29:32',771),('DoctrineMigrations\\Version20251210063000','2026-05-21 08:29:33',271),('DoctrineMigrations\\Version20251210100914','2026-05-21 08:29:33',100),('DoctrineMigrations\\Version20251210160140','2026-05-21 08:29:33',192),('DoctrineMigrations\\Version20251210162344','2026-05-21 08:29:34',102),('DoctrineMigrations\\Version20251212003831','2026-05-21 08:29:34',230),('DoctrineMigrations\\Version20260330040905','2026-05-21 08:29:34',67),('DoctrineMigrations\\Version20260521140100','2026-05-21 16:01:10',920);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_activity_logs`
--

DROP TABLE IF EXISTS `document_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D4E4B945C33F7837` (`document_id`),
  KEY `IDX_D4E4B945A76ED395` (`user_id`),
  CONSTRAINT `FK_D4E4B945A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_D4E4B945C33F7837` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_activity_logs`
--

LOCK TABLES `document_activity_logs` WRITE;
/*!40000 ALTER TABLE `document_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_entity_id` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `uploaded_at` datetime NOT NULL,
  `file_size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by_id` int DEFAULT NULL,
  `updated_by_id` int DEFAULT NULL,
  `parent_document_id` int DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int DEFAULT NULL,
  `is_latest_version` tinyint(1) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A2B07288A2B28FE8` (`uploaded_by_id`),
  KEY `IDX_A2B07288896DBBDE` (`updated_by_id`),
  KEY `IDX_A2B07288A8136A47` (`parent_document_id`),
  CONSTRAINT `FK_A2B07288896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A2B07288A2B28FE8` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A2B07288A8136A47` FOREIGN KEY (`parent_document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `down_payment` decimal(10,2) DEFAULT NULL,
  `financing_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `sale_date` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `customer_id` int NOT NULL,
  `created_by_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6B817044545317D1` (`vehicle_id`),
  KEY `IDX_6B8170449395C3F3` (`customer_id`),
  KEY `IDX_6B817044B03A8386` (`created_by_id`),
  CONSTRAINT `FK_6B817044545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `FK_6B8170449395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  CONSTRAINT `FK_6B817044B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `cost` decimal(10,2) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_date` datetime NOT NULL,
  `completion_date` datetime DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `assigned_mechanic_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7332E1699395C3F3` (`customer_id`),
  KEY `IDX_7332E169545317D1` (`vehicle_id`),
  KEY `IDX_7332E169BD5D3BC9` (`assigned_mechanic_id`),
  CONSTRAINT `FK_7332E169545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `FK_7332E1699395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`),
  CONSTRAINT `FK_7332E169BD5D3BC9` FOREIGN KEY (`assigned_mechanic_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_drive_booking`
--

DROP TABLE IF EXISTS `test_drive_booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_drive_booking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `car_id` int NOT NULL,
  `approved_by_id` int DEFAULT NULL,
  `requested_date_time` datetime NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `staff_remarks` longtext COLLATE utf8mb4_unicode_ci,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_18E6152E9395C3F3` (`customer_id`),
  KEY `IDX_18E6152EC3C6F69F` (`car_id`),
  KEY `IDX_18E6152E2D234F6A` (`approved_by_id`),
  CONSTRAINT `FK_18E6152E2D234F6A` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_18E6152E9395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_18E6152EC3C6F69F` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_drive_booking`
--

LOCK TABLES `test_drive_booking` WRITE;
/*!40000 ALTER TABLE `test_drive_booking` DISABLE KEYS */;
INSERT INTO `test_drive_booking` VALUES (1,3,1,NULL,'2026-05-25 10:00:00','pending','Interested in test drive',NULL,NULL,'2026-05-21 16:02:13','2026-05-21 16:02:13'),(2,4,1,1,'2026-05-22 12:00:00','approved','test','See you soon!','2026-05-21 18:20:09','2026-05-21 16:42:04','2026-05-21 18:20:09'),(3,5,1,NULL,'2026-05-25 10:00:00','pending','Test drive booking',NULL,NULL,'2026-05-21 16:56:03','2026-05-21 16:56:03'),(4,4,1,1,'2026-05-23 12:30:00','rejected',NULL,'Test reject','2026-05-21 20:18:43','2026-05-21 18:34:18','2026-05-21 20:18:43');
/*!40000 ALTER TABLE `test_drive_booking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verifications`
--

DROP TABLE IF EXISTS `user_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_verified` tinyint(1) DEFAULT NULL,
  `verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verifications`
--

LOCK TABLES `user_verifications` WRITE;
/*!40000 ALTER TABLE `user_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `username` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) DEFAULT NULL,
  `verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9F85E0677` (`username`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'ramlelariosa26@gmail.com','[\"ROLE_ADMIN\"]','$2y$13$EwP4PR21XzLlQkhLAgeQyukrjD6QmHwxcpx7KIh/cJ/ctNAYTMUVW','Elmar','Lariosa',NULL,'admin','active',NULL,'2026-05-21 06:39:02','2026-05-21 08:47:59','2026-05-21 18:01:37','Elmar',1,NULL),(2,'john@example.com','[\"ROLE_USER\"]','$2y$13$Xvgy1XvcEb5kuunc8MW2vOzu//a.cZsuaKfWdjTR4GQfjbB9FhCFu','John','Doe',NULL,'staff','active',NULL,'2026-05-21 15:00:25',NULL,NULL,'john@example.com',0,'674ed5b7330feb891ed119b404a37395cb66d072869200b9c8954306fb453142'),(3,'jane@example.com','[\"ROLE_CUSTOMER\"]','$2y$13$v02FIoMUBDxEOrhfeTGVAe80lQsHUfaggIVK3Rly09nKDRogvYduO','Jane','Smith',NULL,'staff','active',NULL,'2026-05-21 15:03:34',NULL,NULL,'jane@example.com',0,'b67e69fc8740c641041c72398dc227f3b4f09e9f9123bda4fb59beb5746f767c'),(4,'ramle@gmail.com','[\"ROLE_CUSTOMER\"]','$2y$13$e1KJ/F6r4y5Z024c.24x5O5BwX9E9cSA/u/NTSJ9J8kwq2NKPgPWO','elmar','lariosa',NULL,'staff','active',NULL,'2026-05-21 15:20:12',NULL,'2026-05-21 15:21:45','ramle@gmail.com',0,'c44531f51602b63a30e6c7749eb5a488b1dd4c48d27de5126067025e7a0903f9'),(5,'testuser@example.com','[\"ROLE_STAFF\"]','$2y$13$ZS/oz9rxGHTmZNDvCp4eFOIOcuKq6dUM3s5gCPTFULgru/dyhW0ge','Test','User',NULL,'staff','active',NULL,'2026-05-21 16:55:44',NULL,NULL,'testuser@example.com',0,'5e93c329c1182cfe39a4ec18c00ce4d8398b08e0c470e4e294dbe6e2d1d0fbfb');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-21 19:28:38

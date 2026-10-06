-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: aegis_db
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Current Database: `aegis_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `aegis_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `aegis_db`;

--
-- Table structure for table `achievements`
--

DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('combat','missions','gear','economy','social','special') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL DEFAULT '10',
  `badge_icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unlock_condition` json NOT NULL,
  `is_secret` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_achievement_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `achievements`
--

LOCK TABLES `achievements` WRITE;
/*!40000 ALTER TABLE `achievements` DISABLE KEYS */;
INSERT INTO `achievements` VALUES (1,'First Blood','Win your first battle','combat',10,'ÔÜö´©Å','{\"type\": \"battles_won\", \"threshold\": 1}',0,'2026-09-05 12:35:27'),(2,'Warrior','Win 50 battles','combat',25,'­ƒùí´©Å','{\"type\": \"battles_won\", \"threshold\": 50}',0,'2026-09-05 12:35:27'),(3,'Champion','Win 100 battles','combat',50,'­ƒÅå','{\"type\": \"battles_won\", \"threshold\": 100}',0,'2026-09-05 12:35:27'),(4,'Legend','Win 500 battles','combat',100,'­ƒææ','{\"type\": \"battles_won\", \"threshold\": 500}',0,'2026-09-05 12:35:27'),(5,'Undefeated','Win 10 battles in a row','combat',30,'­ƒöÑ','{\"type\": \"win_streak\", \"threshold\": 10}',0,'2026-09-05 12:35:27'),(6,'Mercenary','Complete 10 missions','missions',15,'­ƒôï','{\"type\": \"missions_completed\", \"threshold\": 10}',0,'2026-09-05 12:35:27'),(7,'Veteran','Complete 50 missions','missions',30,'­ƒÄ»','{\"type\": \"missions_completed\", \"threshold\": 50}',0,'2026-09-05 12:35:27'),(8,'Bounty Hunter','Collect 100 bounties','missions',40,'­ƒÆ░','{\"type\": \"bounties_collected\", \"threshold\": 100}',0,'2026-09-05 12:35:27'),(9,'Legendary Hunter','Collect 500 bounties','missions',75,'­ƒÆÄ','{\"type\": \"bounties_collected\", \"threshold\": 500}',0,'2026-09-05 12:35:27'),(10,'Collector','Own 20 unique gear items','gear',20,'­ƒÄÆ','{\"type\": \"gear_owned\", \"threshold\": 20}',0,'2026-09-05 12:35:27'),(11,'Master Collector','Own 50 unique gear items','gear',40,'­ƒôª','{\"type\": \"gear_owned\", \"threshold\": 50}',0,'2026-09-05 12:35:27'),(12,'Tier 5 Owner','Own 5 Tier 5 items','gear',35,'Ô¡É','{\"type\": \"tier5_gear\", \"threshold\": 5}',0,'2026-09-05 12:35:27'),(13,'Legendary Collector','Own 10 Legendary items','gear',50,'­ƒîƒ','{\"type\": \"legendary_gear\", \"threshold\": 10}',0,'2026-09-05 12:35:27'),(14,'Gear Enthusiast','Own at least one item from each slot','gear',15,'­ƒöº','{\"type\": \"complete_set\", \"threshold\": 1}',0,'2026-09-05 12:35:27'),(15,'Millionaire','Earn 1,000,000 total credits','economy',50,'­ƒÆÁ','{\"type\": \"total_credits_earned\", \"threshold\": 1000000}',0,'2026-09-05 12:35:27'),(16,'Tycoon','Earn 10,000,000 total credits','economy',100,'­ƒÅª','{\"type\": \"total_credits_earned\", \"threshold\": 10000000}',0,'2026-09-05 12:35:27'),(17,'Spender','Spend 100,000 credits','economy',25,'­ƒøì´©Å','{\"type\": \"total_credits_spent\", \"threshold\": 100000}',0,'2026-09-05 12:35:27'),(18,'Big Spender','Spend 1,000,000 credits','economy',50,'­ƒÆ│','{\"type\": \"total_credits_spent\", \"threshold\": 1000000}',0,'2026-09-05 12:35:27'),(19,'Secret Agent','Complete a mission without taking damage','special',30,'­ƒòÁ´©Å','{\"type\": \"mission_perfect\", \"threshold\": 1}',1,'2026-09-05 12:35:27'),(20,'Iron Man','Win 10 battles without losing','special',35,'­ƒª¥','{\"type\": \"win_streak\", \"threshold\": 10}',0,'2026-09-05 12:35:27'),(21,'The Collector','Own all items from a single slot','special',45,'­ƒôª','{\"type\": \"complete_set\", \"threshold\": 1}',1,'2026-09-05 12:35:27'),(22,'Night Owl','Play for 100 hours total','special',20,'­ƒªë','{\"type\": \"total_hours\", \"threshold\": 100}',0,'2026-09-05 12:35:27'),(23,'Faction Hero','Reach rank 1 in your faction','special',50,'­ƒÅà','{\"type\": \"faction_rank\", \"threshold\": 1}',0,'2026-09-05 12:35:27');
/*!40000 ALTER TABLE `achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `activity_type` enum('login','purchase','sell','mission','battle','craft','event','achievement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `credits_change` int DEFAULT '0',
  `logged_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_combatant` (`combatant_id`),
  KEY `idx_activity_type` (`activity_type`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,1,'login','{\"ip\": \"192.168.1.1\"}',0,'2024-04-15 01:00:00'),(2,1,'purchase','{\"price\": 5000, \"gear_id\": \"h4-01\"}',-5000,'2024-04-15 01:30:00'),(3,1,'mission','{\"result\": \"completed\", \"mission_id\": 5}',2000,'2024-04-15 02:00:00'),(4,1,'battle','{\"result\": \"win\", \"opponent\": \"Shadow Syndicate\"}',500,'2024-04-15 02:30:00'),(5,1,'sell','{\"price\": 400, \"gear_id\": \"b2-01\"}',400,'2024-04-15 03:00:00'),(6,4,'login','{\"ip\": \"192.168.1.100\"}',0,'2024-04-15 00:00:00'),(7,4,'purchase','{\"price\": 15000, \"gear_id\": \"c5-01\"}',-15000,'2024-04-15 00:30:00'),(8,4,'battle','{\"result\": \"win\", \"opponent\": \"Steel Guardian\"}',750,'2024-04-15 01:00:00'),(9,21,'battle','{\"result\": \"loss\", \"opponent\": \"Steel Guardian\", \"damage_dealt\": 282}',-716,'2026-09-15 11:20:22'),(10,21,'battle','{\"result\": \"loss\", \"opponent\": \"Crimson Wraith\", \"damage_dealt\": 246}',-290,'2026-09-15 11:21:51'),(11,21,'battle','{\"result\": \"loss\", \"opponent\": \"Shadow Syndicate\", \"damage_dealt\": 358}',-678,'2026-09-15 11:22:08'),(12,21,'battle','{\"result\": \"loss\", \"opponent\": \"Soncedar Bezotosniy\", \"damage_dealt\": 214}',-414,'2026-09-15 11:33:23'),(13,21,'battle','{\"result\": \"win\", \"opponent\": \"Neuro Hack\", \"damage_dealt\": 279}',1072,'2026-09-15 11:33:43'),(14,21,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-15 14:04:07'),(15,8,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-15 14:47:33'),(16,10,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-15 15:03:58'),(17,21,'battle','{\"result\": \"win\", \"opponent\": \"Neuro Hack\", \"damage_dealt\": 224}',1347,'2026-09-15 15:27:16'),(18,21,'battle','{\"result\": \"loss\", \"opponent\": \"Neuro Hack\", \"damage_dealt\": 393}',-619,'2026-09-15 15:27:27'),(19,21,'battle','{\"result\": \"win\", \"opponent\": \"Shadow Syndicate\", \"damage_dealt\": 417}',762,'2026-09-15 15:59:16'),(20,21,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-23 09:42:38'),(21,22,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-23 10:19:56'),(22,22,'battle','{\"result\": \"win\", \"opponent\": \"Neuro Hack\", \"damage_dealt\": 488}',1216,'2026-09-23 10:36:01'),(23,21,'mission','{\"day\": 2, \"type\": \"daily_reward\"}',200,'2026-09-25 03:03:48'),(24,21,'battle','{\"result\": \"win\", \"opponent\": \"Shadow Syndicate\", \"damage_dealt\": 170}',1337,'2026-09-25 05:34:32'),(25,21,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-09-30 09:50:53'),(26,23,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-10-02 05:38:50'),(27,8,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-10-02 06:09:33'),(28,21,'mission','{\"day\": 1, \"type\": \"daily_reward\"}',100,'2026-10-03 13:25:35');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `battle_history`
--

DROP TABLE IF EXISTS `battle_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `battle_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `opponent_id` int DEFAULT NULL,
  `opponent_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `battle_type` enum('pvp','pve','event','training') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pve',
  `result` enum('win','loss','draw') COLLATE utf8mb4_unicode_ci NOT NULL,
  `loadout_used` json NOT NULL,
  `opponent_loadout` json DEFAULT NULL,
  `damage_dealt` int DEFAULT NULL,
  `damage_taken` int DEFAULT NULL,
  `credits_earned` int DEFAULT '0',
  `gear_dropped` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `battle_duration_seconds` int DEFAULT NULL,
  `fought_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_battle_combatant` (`combatant_id`),
  KEY `idx_battle_result` (`result`),
  CONSTRAINT `battle_history_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `battle_history`
--

LOCK TABLES `battle_history` WRITE;
/*!40000 ALTER TABLE `battle_history` DISABLE KEYS */;
INSERT INTO `battle_history` VALUES (1,1,NULL,'Shadow Syndicate','pvp','win','{\"core\": \"c3-01\", \"helmet\": \"h3-01\", \"battery\": \"b3-01\", \"dampener\": \"d2-01\", \"gauntlets\": \"g3-01\"}',NULL,350,120,500,NULL,45,'2024-04-14 06:00:00'),(2,1,NULL,'Crimson Wraith','pvp','win','{\"core\": \"c3-01\", \"helmet\": \"h3-01\", \"battery\": \"b3-01\", \"dampener\": \"d2-01\", \"gauntlets\": \"g3-01\"}',NULL,420,180,750,NULL,52,'2024-04-13 08:30:00'),(3,4,NULL,'Steel Guardian','pvp','win','{\"core\": \"c5-01\", \"helmet\": \"h5-01\", \"battery\": \"b5-01\", \"dampener\": \"d4-01\", \"gauntlets\": \"g5-01\"}',NULL,500,250,1000,NULL,60,'2024-04-12 12:00:00'),(4,1,NULL,'Neuro Hack','pvp','loss','{\"core\": \"c3-01\", \"helmet\": \"h3-01\", \"battery\": \"b3-01\", \"dampener\": \"d2-01\", \"gauntlets\": \"g3-01\"}',NULL,200,350,0,NULL,30,'2024-04-11 02:00:00'),(5,4,NULL,'Solar Flare','pvp','win','{\"core\": \"c5-01\", \"helmet\": \"h5-01\", \"battery\": \"b5-01\", \"dampener\": \"d4-01\", \"gauntlets\": \"g5-01\"}',NULL,450,150,800,NULL,40,'2024-04-10 05:00:00'),(6,21,3,'Steel Guardian','pvp','loss','{\"attacker_power\": 912, \"defender_power\": 1522}',NULL,282,300,-716,NULL,NULL,'2026-09-15 11:20:22'),(7,21,5,'Crimson Wraith','pvp','loss','{\"attacker_power\": 718, \"defender_power\": 1057}',NULL,246,274,-290,NULL,NULL,'2026-09-15 11:21:51'),(8,21,4,'Shadow Syndicate','pvp','loss','{\"attacker_power\": 850, \"defender_power\": 909}',NULL,358,410,-678,NULL,NULL,'2026-09-15 11:22:08'),(9,21,11,'Soncedar Bezotosniy','pvp','loss','{\"attacker_power\": 754, \"defender_power\": 1108}',NULL,214,168,-414,NULL,NULL,'2026-09-15 11:33:23'),(10,21,6,'Neuro Hack','pvp','win','{\"attacker_power\": 718, \"defender_power\": 658}',NULL,279,382,1072,NULL,NULL,'2026-09-15 11:33:43'),(11,21,6,'Neuro Hack','pvp','win','{\"attacker_power\": 1015, \"defender_power\": 700}',NULL,224,106,1347,NULL,NULL,'2026-09-15 15:27:16'),(12,21,6,'Neuro Hack','pvp','loss','{\"attacker_power\": 871, \"defender_power\": 898}',NULL,393,327,-619,NULL,NULL,'2026-09-15 15:27:27'),(13,21,4,'Shadow Syndicate','pvp','win','{\"attacker_power\": 1038, \"defender_power\": 857}',NULL,417,386,762,NULL,NULL,'2026-09-15 15:59:16'),(14,22,6,'Neuro Hack','pvp','win','{\"attacker_power\": 978, \"defender_power\": 782}',NULL,488,201,1216,NULL,NULL,'2026-09-23 10:36:01'),(15,21,4,'Shadow Syndicate','pvp','win','{\"attacker_power\": 1037, \"defender_power\": 875}',NULL,170,126,1337,NULL,NULL,'2026-09-25 05:34:32');
/*!40000 ALTER TABLE `battle_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `battle_replays`
--

DROP TABLE IF EXISTS `battle_replays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `battle_replays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `battle_history_id` int DEFAULT NULL,
  `combatant_id` int NOT NULL,
  `opponent_id` int DEFAULT NULL,
  `opponent_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `replay_data` json NOT NULL,
  `winner_id` int NOT NULL,
  `result` enum('win','loss','draw') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_damage_dealt` int NOT NULL DEFAULT '0',
  `total_damage_taken` int NOT NULL DEFAULT '0',
  `replay_duration` int NOT NULL DEFAULT '0',
  `views` int NOT NULL DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_replay_combatant` (`combatant_id`),
  KEY `idx_replay_created` (`created_at`),
  KEY `idx_replay_result` (`result`),
  CONSTRAINT `battle_replays_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `battle_replays`
--

LOCK TABLES `battle_replays` WRITE;
/*!40000 ALTER TABLE `battle_replays` DISABLE KEYS */;
INSERT INTO `battle_replays` VALUES (1,NULL,21,4,'Shadow Syndicate','{\"steps\": [{\"step\": 1, \"type\": \"start\", \"message\": \"⚔️ hiro challenges Shadow Syndicate!\", \"timestamp\": 0, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 2, \"type\": \"reveal\", \"message\": \"📊 Power: hiro (1,038) vs Shadow Syndicate (857)\", \"timestamp\": 1500, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 3, \"type\": \"attack\", \"damage\": 417, \"message\": \"💥 hiro strikes for 417 damage!\", \"timestamp\": 3000, \"attackerHP\": 100, \"defenderHP\": 58}, {\"step\": 4, \"type\": \"counter\", \"damage\": 386, \"message\": \"🛡️ Shadow Syndicate counters for 386 damage!\", \"timestamp\": 4500, \"attackerHP\": 61, \"defenderHP\": 58}, {\"step\": 5, \"type\": \"critical\", \"message\": \"🎯 hiro lands a critical blow!\", \"timestamp\": 6000, \"attackerHP\": 61, \"defenderHP\": 10}, {\"step\": 6, \"type\": \"victory\", \"message\": \"🏆 hiro WINS THE BATTLE!\", \"timestamp\": 7500, \"attackerHP\": 30, \"defenderHP\": 0}, {\"step\": 7, \"type\": \"reward\", \"message\": \"💰 +₵762 credits!\", \"timestamp\": 9000, \"attackerHP\": 30, \"defenderHP\": 0}], \"result\": \"win\", \"winnerId\": 21, \"winnerName\": \"hiro\", \"damageDealt\": 417, \"damageTaken\": 386, \"attackerName\": \"hiro\", \"defenderName\": \"Shadow Syndicate\", \"attackerPower\": 1038, \"creditsEarned\": 762, \"defenderPower\": 857}',21,'win',417,386,9000,1,1,'2026-09-15 15:59:16'),(2,NULL,22,6,'Neuro Hack','{\"steps\": [{\"step\": 1, \"type\": \"start\", \"message\": \"⚔️ Motzz challenges Neuro Hack!\", \"timestamp\": 0, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 2, \"type\": \"reveal\", \"message\": \"📊 Power: Motzz (978) vs Neuro Hack (782)\", \"timestamp\": 1500, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 3, \"type\": \"attack\", \"damage\": 488, \"message\": \"💥 Motzz strikes for 488 damage!\", \"timestamp\": 3000, \"attackerHP\": 100, \"defenderHP\": 51}, {\"step\": 4, \"type\": \"counter\", \"damage\": 201, \"message\": \"🛡️ Neuro Hack counters for 201 damage!\", \"timestamp\": 4500, \"attackerHP\": 80, \"defenderHP\": 51}, {\"step\": 5, \"type\": \"critical\", \"message\": \"🎯 Motzz lands a critical blow!\", \"timestamp\": 6000, \"attackerHP\": 80, \"defenderHP\": 10}, {\"step\": 6, \"type\": \"victory\", \"message\": \"🏆 Motzz WINS THE BATTLE!\", \"timestamp\": 7500, \"attackerHP\": 30, \"defenderHP\": 0}, {\"step\": 7, \"type\": \"reward\", \"message\": \"💰 +₵1216 credits!\", \"timestamp\": 9000, \"attackerHP\": 30, \"defenderHP\": 0}], \"result\": \"win\", \"winnerId\": 22, \"winnerName\": \"Motzz\", \"damageDealt\": 488, \"damageTaken\": 201, \"attackerName\": \"Motzz\", \"defenderName\": \"Neuro Hack\", \"attackerPower\": 978, \"creditsEarned\": 1216, \"defenderPower\": 782}',22,'win',488,201,9000,1,1,'2026-09-23 10:36:01'),(3,NULL,21,4,'Shadow Syndicate','{\"steps\": [{\"step\": 1, \"type\": \"start\", \"message\": \"⚔️ hiro challenges Shadow Syndicate!\", \"timestamp\": 0, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 2, \"type\": \"reveal\", \"message\": \"📊 Power: hiro (1,037) vs Shadow Syndicate (875)\", \"timestamp\": 1500, \"attackerHP\": 100, \"defenderHP\": 100}, {\"step\": 3, \"type\": \"attack\", \"damage\": 170, \"message\": \"💥 hiro strikes for 170 damage!\", \"timestamp\": 3000, \"attackerHP\": 100, \"defenderHP\": 83}, {\"step\": 4, \"type\": \"counter\", \"damage\": 126, \"message\": \"🛡️ Shadow Syndicate counters for 126 damage!\", \"timestamp\": 4500, \"attackerHP\": 87, \"defenderHP\": 83}, {\"step\": 5, \"type\": \"critical\", \"message\": \"🎯 hiro lands a critical blow!\", \"timestamp\": 6000, \"attackerHP\": 87, \"defenderHP\": 10}, {\"step\": 6, \"type\": \"victory\", \"message\": \"🏆 hiro WINS THE BATTLE!\", \"timestamp\": 7500, \"attackerHP\": 30, \"defenderHP\": 0}, {\"step\": 7, \"type\": \"reward\", \"message\": \"💰 +₵1337 credits!\", \"timestamp\": 9000, \"attackerHP\": 30, \"defenderHP\": 0}], \"result\": \"win\", \"winnerId\": 21, \"winnerName\": \"hiro\", \"damageDealt\": 170, \"damageTaken\": 126, \"attackerName\": \"hiro\", \"defenderName\": \"Shadow Syndicate\", \"attackerPower\": 1037, \"creditsEarned\": 1337, \"defenderPower\": 875}',21,'win',170,126,9000,1,1,'2026-09-25 05:34:32');
/*!40000 ALTER TABLE `battle_replays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `sender_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_role` enum('civilian','hero','villain','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_faction` enum('hero','villain') COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('global','faction','trade') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_channel` (`channel`),
  KEY `idx_chat_created` (`created_at`),
  KEY `idx_chat_combatant` (`combatant_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,1,'Vantablack','hero','hero','global','⚡ Welcome to Vantablack! Let\'s keep this city safe.','2026-09-15 14:12:08'),(2,4,'Shadow Syndicate','villain','villain','global','😈 The darkness rises... You heroes are done for!','2026-09-15 14:12:08'),(3,2,'Solar Flare','hero','hero','global','Any good gear drops today?','2026-09-15 14:12:08'),(4,5,'Crimson Wraith','villain','villain','global','Just crafted a Phantom Gauntlet. Watch out!','2026-09-15 14:12:08'),(5,1,'Vantablack','hero','hero','faction','Heroes, report your status.','2026-09-15 14:12:08'),(6,4,'Shadow Syndicate','villain','villain','faction','Villains, prepare for the next raid.','2026-09-15 14:12:08'),(7,21,'hiro','hero','hero','global','hello','2026-09-15 14:18:03'),(8,21,'hiro','hero','hero','global','test','2026-09-15 14:24:44'),(9,21,'hiro','hero','hero','trade','first','2026-09-15 14:25:04');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combatant_achievements`
--

DROP TABLE IF EXISTS `combatant_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combatant_achievements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `achievement_id` int NOT NULL,
  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `progress` int DEFAULT '0',
  `is_completed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_combatant_achievement` (`combatant_id`,`achievement_id`),
  KEY `achievement_id` (`achievement_id`),
  CONSTRAINT `combatant_achievements_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `combatant_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combatant_achievements`
--

LOCK TABLES `combatant_achievements` WRITE;
/*!40000 ALTER TABLE `combatant_achievements` DISABLE KEYS */;
INSERT INTO `combatant_achievements` VALUES (1,1,1,'2024-01-15 02:30:00',100,1),(2,1,2,'2024-02-20 07:00:00',100,1),(3,1,3,'2024-03-10 12:00:00',100,1),(4,1,6,'2024-02-01 04:00:00',100,1),(5,1,8,'2024-03-01 10:00:00',100,1),(6,1,10,'2024-03-15 06:00:00',100,1),(7,1,13,'2024-04-01 01:00:00',100,1),(8,4,1,'2024-01-10 01:00:00',100,1),(9,4,2,'2024-01-25 08:00:00',100,1),(10,4,3,'2024-02-15 12:00:00',100,1),(11,4,8,'2024-02-20 06:00:00',100,1),(12,4,12,'2024-03-01 02:00:00',100,1),(13,4,13,'2024-03-15 03:00:00',100,1);
/*!40000 ALTER TABLE `combatant_achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `combatant_loadout_view`
--

DROP TABLE IF EXISTS `combatant_loadout_view`;
/*!50001 DROP VIEW IF EXISTS `combatant_loadout_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `combatant_loadout_view` AS SELECT 
 1 AS `id`,
 1 AS `name`,
 1 AS `bio_capacity_max`,
 1 AS `base_recovery`,
 1 AS `base_risk`,
 1 AS `credits`,
 1 AS `faction`,
 1 AS `clearance_level`,
 1 AS `helmet_id`,
 1 AS `core_id`,
 1 AS `dampener_id`,
 1 AS `gauntlets_id`,
 1 AS `battery_id`,
 1 AS `helmet_name`,
 1 AS `helmet_bio_capacity`,
 1 AS `helmet_recovery_rate`,
 1 AS `helmet_risk_modifier`,
 1 AS `core_name`,
 1 AS `core_bio_capacity`,
 1 AS `core_recovery_rate`,
 1 AS `core_risk_modifier`,
 1 AS `dampener_name`,
 1 AS `dampener_bio_capacity`,
 1 AS `dampener_recovery_rate`,
 1 AS `dampener_risk_modifier`,
 1 AS `gauntlets_name`,
 1 AS `gauntlets_bio_capacity`,
 1 AS `gauntlets_recovery_rate`,
 1 AS `gauntlets_risk_modifier`,
 1 AS `battery_name`,
 1 AS `battery_bio_capacity`,
 1 AS `battery_recovery_rate`,
 1 AS `battery_risk_modifier`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `combatant_stats`
--

DROP TABLE IF EXISTS `combatant_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combatant_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `total_credits_earned` int NOT NULL DEFAULT '0',
  `total_credits_spent` int NOT NULL DEFAULT '0',
  `total_gear_purchased` int NOT NULL DEFAULT '0',
  `total_gear_sold` int NOT NULL DEFAULT '0',
  `total_missions_completed` int NOT NULL DEFAULT '0',
  `total_missions_failed` int NOT NULL DEFAULT '0',
  `total_bounties_collected` int NOT NULL DEFAULT '0',
  `total_battles_won` int NOT NULL DEFAULT '0',
  `total_battles_lost` int NOT NULL DEFAULT '0',
  `total_battles_drawn` int NOT NULL DEFAULT '0',
  `longest_win_streak` int NOT NULL DEFAULT '0',
  `current_win_streak` int NOT NULL DEFAULT '0',
  `total_gear_owned` int NOT NULL DEFAULT '0',
  `total_tier5_gear` int NOT NULL DEFAULT '0',
  `total_legendary_gear` int NOT NULL DEFAULT '0',
  `total_hours_played` int NOT NULL DEFAULT '0',
  `first_played_at` timestamp NULL DEFAULT NULL,
  `last_played_at` timestamp NULL DEFAULT NULL,
  `global_rank` int DEFAULT NULL,
  `faction_rank` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `combatant_id` (`combatant_id`),
  KEY `idx_stats_combatant` (`combatant_id`),
  CONSTRAINT `combatant_stats_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combatant_stats`
--

LOCK TABLES `combatant_stats` WRITE;
/*!40000 ALTER TABLE `combatant_stats` DISABLE KEYS */;
INSERT INTO `combatant_stats` VALUES (1,1,25000,15000,12,0,0,0,23,156,42,0,0,0,7,1,1,0,'2024-01-15 02:00:00','2026-09-05 12:35:27',NULL,NULL,'2026-09-05 12:35:27'),(2,2,12000,8000,8,0,0,0,15,89,31,0,0,0,5,0,0,0,'2024-01-20 06:30:00','2026-09-05 12:35:27',NULL,NULL,'2026-09-05 12:35:27'),(3,3,8000,5000,6,0,0,0,8,46,28,0,2,1,5,0,0,0,'2024-02-01 01:00:00','2026-09-05 12:35:27',NULL,NULL,'2026-09-15 11:20:22'),(4,4,35000,25000,20,0,0,0,45,204,69,0,2,0,10,3,3,0,'2024-01-10 00:00:00','2026-09-05 12:35:27',NULL,NULL,'2026-09-25 05:34:32'),(5,7,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-11 07:21:15'),(6,8,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-11 07:23:05'),(7,9,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-11 07:34:39'),(8,10,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 02:38:50'),(9,11,0,0,0,0,0,0,0,1,0,0,2,1,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 11:33:23'),(10,12,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:42:23'),(11,13,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:42:24'),(12,14,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:12'),(13,15,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:13'),(14,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:13'),(15,17,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:31'),(16,18,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:31'),(17,19,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:43:32'),(18,20,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-15 04:45:11'),(19,21,4518,0,0,0,0,0,0,4,5,0,3,2,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-25 05:34:32'),(20,22,1216,0,0,0,0,0,0,1,0,0,2,1,0,0,0,0,NULL,NULL,NULL,NULL,'2026-09-23 10:36:01'),(21,23,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,'2026-10-02 05:38:43');
/*!40000 ALTER TABLE `combatant_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combatants`
--

DROP TABLE IF EXISTS `combatants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combatants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio_capacity_max` int NOT NULL DEFAULT '1000',
  `base_recovery` int NOT NULL DEFAULT '3',
  `base_risk` int NOT NULL DEFAULT '12',
  `credits` int NOT NULL DEFAULT '5000',
  `faction` enum('hero','villain') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hero',
  `role` enum('civilian','hero','villain','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'civilian',
  `clearance_level` int NOT NULL DEFAULT '1',
  `last_bonus_claim` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combatants`
--

LOCK TABLES `combatants` WRITE;
/*!40000 ALTER TABLE `combatants` DISABLE KEYS */;
INSERT INTO `combatants` VALUES (1,'Vantablack',1400,3,12,8200,'hero','hero',2,NULL,'2026-09-05 12:35:27','2026-09-23 10:32:47'),(2,'Solar Flare',1200,5,8,4500,'hero','hero',2,NULL,'2026-09-05 12:35:27','2026-09-11 07:20:50'),(3,'Steel Guardian',1800,2,15,3000,'hero','hero',2,NULL,'2026-09-05 12:35:27','2026-09-23 10:32:47'),(4,'Shadow Syndicate',1100,4,20,9591,'villain','villain',3,NULL,'2026-09-05 12:35:27','2026-09-25 05:34:32'),(5,'Crimson Wraith',1500,1,25,6700,'villain','villain',3,NULL,'2026-09-05 12:35:27','2026-09-23 10:32:47'),(6,'Neuro Hack',900,6,10,2138,'villain','villain',3,NULL,'2026-09-05 12:35:27','2026-09-23 10:36:01'),(7,'Hiro',1200,3,10,4500,'hero','hero',2,NULL,'2026-09-11 07:21:15','2026-09-11 07:21:30'),(8,'Hiro',0,0,0,1000199,'hero','admin',4,NULL,'2026-09-11 07:23:05','2026-10-02 06:09:33'),(9,'doe',100,1,5,500,'hero','civilian',1,NULL,'2026-09-11 07:34:39','2026-09-11 07:34:39'),(10,'test',100,1,5,300,'hero','civilian',1,NULL,'2026-09-15 02:38:50','2026-09-15 15:03:58'),(11,'Soncedar Bezotosniy',1200,3,10,5000,'hero','hero',2,NULL,'2026-09-15 04:42:22','2026-09-15 04:42:22'),(12,'Natalie Heggernes',1200,3,10,5000,'hero','hero',2,NULL,'2026-09-15 04:42:23','2026-09-15 04:42:23'),(13,'Julian Faure',1200,3,10,5000,'hero','hero',2,NULL,'2026-09-15 04:42:24','2026-09-15 04:42:24'),(14,'Ljubica Cvejić',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:12','2026-09-15 04:43:12'),(15,'Gordana Lazić',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:12','2026-09-15 04:43:12'),(16,'Mats Nergård',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:13','2026-09-15 04:43:13'),(17,'Sai Dhamdhame',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:30','2026-09-15 04:43:30'),(18,'Luise Leroy',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:31','2026-09-15 04:43:31'),(19,'Maria-Luise Vogt',100,1,5,500,'hero','civilian',1,NULL,'2026-09-15 04:43:32','2026-09-15 04:43:32'),(20,'hiro',1200,3,10,4500,'hero','hero',2,NULL,'2026-09-15 04:45:11','2026-09-15 04:45:48'),(21,'hiro',1200,3,10,3196,'hero','hero',2,NULL,'2026-09-15 11:08:20','2026-10-03 13:25:35'),(22,'Motzz',1100,4,15,2916,'villain','villain',3,NULL,'2026-09-23 10:19:41','2026-09-23 10:36:01'),(23,'Steve',1100,4,15,5100,'villain','villain',3,NULL,'2026-10-02 05:38:43','2026-10-02 05:38:50');
/*!40000 ALTER TABLE `combatants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crafting_log`
--

DROP TABLE IF EXISTS `crafting_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafting_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `recipe_id` int NOT NULL,
  `result_gear_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `materials_used` json NOT NULL,
  `credits_spent` int NOT NULL DEFAULT '0',
  `crafted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `combatant_id` (`combatant_id`),
  KEY `recipe_id` (`recipe_id`),
  KEY `result_gear_id` (`result_gear_id`),
  CONSTRAINT `crafting_log_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crafting_log_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `crafting_recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crafting_log_ibfk_3` FOREIGN KEY (`result_gear_id`) REFERENCES `gear_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crafting_log`
--

LOCK TABLES `crafting_log` WRITE;
/*!40000 ALTER TABLE `crafting_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `crafting_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crafting_recipes`
--

DROP TABLE IF EXISTS `crafting_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafting_recipes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `result_gear_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_materials` json NOT NULL,
  `required_credits` int NOT NULL DEFAULT '0',
  `required_level` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `result_gear_id` (`result_gear_id`),
  CONSTRAINT `crafting_recipes_ibfk_1` FOREIGN KEY (`result_gear_id`) REFERENCES `gear_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crafting_recipes`
--

LOCK TABLES `crafting_recipes` WRITE;
/*!40000 ALTER TABLE `crafting_recipes` DISABLE KEYS */;
INSERT INTO `crafting_recipes` VALUES (1,'h2-01','{\"h1-01\": 1, \"c1-01\": 1}',1000,1,'2026-09-15 13:13:49'),(2,'c2-01','{\"c1-01\": 1, \"d1-01\": 1}',1500,1,'2026-09-15 13:13:49'),(3,'d2-01','{\"d1-01\": 1, \"g1-01\": 1}',1200,1,'2026-09-15 13:13:49'),(4,'g2-01','{\"g1-01\": 1, \"b1-01\": 1}',900,1,'2026-09-15 13:13:49'),(5,'b2-01','{\"b1-01\": 1, \"h1-01\": 1}',700,1,'2026-09-15 13:13:49'),(6,'h3-01','{\"h1-01\": 1, \"h2-01\": 1}',2500,2,'2026-09-15 13:13:49'),(7,'c3-01','{\"c1-01\": 1, \"c2-01\": 1}',3000,2,'2026-09-15 13:13:49'),(8,'d3-01','{\"d1-01\": 1, \"d2-01\": 1}',2800,2,'2026-09-15 13:13:49'),(9,'h4-01','{\"h2-01\": 1, \"h3-01\": 1}',5000,3,'2026-09-15 13:13:49'),(10,'c4-01','{\"c2-01\": 1, \"c3-01\": 1}',6000,3,'2026-09-15 13:13:49');
/*!40000 ALTER TABLE `crafting_recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_rewards_log`
--

DROP TABLE IF EXISTS `daily_rewards_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_rewards_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `day_streak` int NOT NULL,
  `credits_earned` int NOT NULL,
  `bonus_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bonus_value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claimed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_daily_combatant` (`combatant_id`),
  KEY `idx_daily_date` (`claimed_at`),
  CONSTRAINT `daily_rewards_log_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_rewards_log`
--

LOCK TABLES `daily_rewards_log` WRITE;
/*!40000 ALTER TABLE `daily_rewards_log` DISABLE KEYS */;
INSERT INTO `daily_rewards_log` VALUES (1,21,1,100,NULL,NULL,'2026-09-15 14:04:07'),(2,8,1,100,NULL,NULL,'2026-09-15 14:47:33'),(3,10,1,100,NULL,NULL,'2026-09-15 15:03:58'),(4,21,1,100,NULL,NULL,'2026-09-23 09:42:38'),(5,22,1,100,NULL,NULL,'2026-09-23 10:19:56'),(6,21,2,200,NULL,NULL,'2026-09-25 03:03:48'),(7,21,1,100,NULL,NULL,'2026-09-30 09:50:53'),(8,23,1,100,NULL,NULL,'2026-10-02 05:38:50'),(9,8,1,100,NULL,NULL,'2026-10-02 06:09:33'),(10,21,1,100,NULL,NULL,'2026-10-03 13:25:35');
/*!40000 ALTER TABLE `daily_rewards_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_participation`
--

DROP TABLE IF EXISTS `event_participation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_participation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `combatant_id` int NOT NULL,
  `score` int NOT NULL DEFAULT '0',
  `final_rank` int DEFAULT NULL,
  `rewards_claimed` tinyint(1) DEFAULT '0',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_action_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participation` (`event_id`,`combatant_id`),
  KEY `combatant_id` (`combatant_id`),
  KEY `idx_event_score` (`event_id`,`score`),
  CONSTRAINT `event_participation_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_participation_ibfk_2` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_participation`
--

LOCK TABLES `event_participation` WRITE;
/*!40000 ALTER TABLE `event_participation` DISABLE KEYS */;
INSERT INTO `event_participation` VALUES (1,2,21,0,NULL,0,'2026-09-23 10:38:31','2026-09-23 10:38:31');
/*!40000 ALTER TABLE `event_participation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `event_type` enum('holiday','tournament','special','weekly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'special',
  `icon` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 0xF09F8EAA,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL,
  `reward_pool` json NOT NULL,
  `entry_fee` int NOT NULL DEFAULT '0',
  `max_participants` int NOT NULL DEFAULT '0',
  `min_role` enum('civilian','hero','villain','admin','any') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_dates` (`start_date`,`end_date`),
  KEY `idx_events_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Grand Tournament of Champions','Battle your way to the top! Weekly PvP tournament with epic rewards.','tournament','🏆','2026-09-22 10:38:07','2026-09-30 10:38:07','{\"1st\": {\"gear\": \"h5-01\", \"credits\": 10000}, \"2nd\": {\"gear\": \"g4-01\", \"credits\": 5000}, \"3rd\": {\"gear\": \"c4-01\", \"credits\": 2500}, \"top10\": {\"credits\": 500}}',500,50,'any',1,'2026-09-23 10:38:07'),(2,'Holiday Invasion Event','Special event! Villains attack, heroes defend. Choose your side and earn exclusive holiday gear.','holiday','🎄','2026-09-21 10:38:07','2026-09-28 10:38:07','{\"top3\": {\"gear\": \"h5-02\", \"credits\": 5000}, \"participation\": {\"gear\": \"d3-01\", \"credits\": 1000}}',0,100,'any',1,'2026-09-23 10:38:07'),(3,'Rookie Challenge','New to Vantablack? Prove your worth in this beginner-friendly event!','special','🎯','2026-09-23 10:38:07','2026-09-26 10:38:07','{\"top5\": {\"gear\": \"h3-01\", \"credits\": 2000}, \"participation\": {\"credits\": 500}}',0,20,'any',1,'2026-09-23 10:38:07'),(4,'Weekend Warrior','Complete battles this weekend for bonus rewards!','weekly','⚔️','2026-09-22 10:38:07','2026-09-25 10:38:07','{\"top10\": {\"credits\": 1500}, \"participation\": {\"credits\": 300}}',100,30,'any',1,'2026-09-23 10:38:07'),(5,'Faction Supremacy','The faction with the most points wins epic territory bonuses!','special','🏴','2026-09-22 10:38:07','2026-09-30 10:38:07','{\"participation\": {\"credits\": 500}, \"winningFaction\": {\"bonus\": \"double_territory_bonus\", \"credits\": 3000}}',0,0,'any',1,'2026-09-23 10:38:07');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gear_items`
--

DROP TABLE IF EXISTS `gear_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gear_items` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slot` enum('helmet','core','dampener','gauntlets','battery') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('armory','black-market') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int NOT NULL,
  `bio_capacity` int NOT NULL DEFAULT '0',
  `recovery_rate` int NOT NULL DEFAULT '0',
  `risk_modifier` int NOT NULL DEFAULT '0',
  `clearance_required` int NOT NULL DEFAULT '1',
  `tier` int NOT NULL DEFAULT '1',
  `is_legendary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gear_slot` (`slot`),
  KEY `idx_gear_source` (`source`),
  KEY `idx_gear_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gear_items`
--

LOCK TABLES `gear_items` WRITE;
/*!40000 ALTER TABLE `gear_items` DISABLE KEYS */;
INSERT INTO `gear_items` VALUES ('b1-01','battery','armory','Standard Battery',300,60,0,0,1,1,0,'2026-09-05 12:35:27','2026-09-05 12:35:27'),('b2-01','battery','armory','High-Capacity Battery',800,150,1,2,1,2,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('b3-01','battery','armory','Quantum Battery',2000,250,2,5,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('b4-01','battery','armory','Infinite Battery',5000,400,3,10,2,4,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('b5-01','battery','black-market','Void Battery',9000,500,4,15,3,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('c1-01','core','armory','Basic Core Unit',800,100,2,-3,1,1,0,'2026-09-05 12:35:27','2026-09-05 12:35:27'),('c2-01','core','armory','Enhanced Core Unit',2000,250,4,-8,1,2,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('c3-01','core','armory','Fusion Core',4000,400,5,-15,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('c4-01','core','armory','Antimatter Core',8000,600,6,-25,2,4,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('c5-01','core','black-market','Singularity Core',15000,800,8,-40,4,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('cor-02','core','black-market','Ferrox Overclock Weave',3400,420,-1,22,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('d1-01','dampener','armory','Energy Dampener V1',600,30,3,-5,1,1,0,'2026-09-05 12:35:27','2026-09-05 12:35:27'),('d2-01','dampener','armory','Energy Dampener V2',1500,80,6,-12,1,2,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('d3-01','dampener','armory','Quantum Dampener',3500,150,8,-20,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('d4-01','dampener','armory','Void Dampener',7000,250,10,-30,2,4,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('d5-01','dampener','black-market','Nexus Dampener',12000,350,12,-50,4,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('g1-01','gauntlets','armory','Starter Gauntlets',400,40,1,-1,1,1,0,'2026-09-05 12:35:27','2026-09-05 12:35:27'),('g2-01','gauntlets','armory','Combat Gauntlets MK2',1000,100,2,-3,1,2,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('g3-01','gauntlets','armory','Energy Blade Gauntlets',2800,180,3,-8,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('g4-01','gauntlets','armory','Titan Gauntlets',6000,300,4,-12,2,4,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('g5-01','gauntlets','black-market','Phantom Gauntlets',11000,400,5,-20,4,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('h1-01','helmet','armory','Standard Issue Helmet',500,50,1,-2,1,1,0,'2026-09-05 12:35:27','2026-09-05 12:35:27'),('h2-01','helmet','armory','Tactical Command Helmet',1200,120,2,-5,1,2,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('h3-01','helmet','armory','Cerebral Interface Helmet',2500,200,3,-10,2,3,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('h4-01','helmet','armory','Psi-Shield Helmet',5000,350,4,-15,2,4,0,'2026-09-05 12:35:27','2026-09-23 10:34:06'),('h5-01','helmet','black-market','Chronos Helmet',10000,500,5,-25,3,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22'),('h5-02','helmet','black-market','Shadow Crown',8500,450,6,-30,3,5,1,'2026-09-05 12:35:27','2026-09-23 10:33:22');
/*!40000 ALTER TABLE `gear_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `gear_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipped` tinyint(1) DEFAULT '0',
  `acquired_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gear_id` (`gear_id`),
  KEY `idx_inventory_combatant` (`combatant_id`),
  KEY `idx_inventory_equipped` (`equipped`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`gear_id`) REFERENCES `gear_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,1,'h3-01',1,'2026-09-05 12:35:27'),(2,1,'c3-01',1,'2026-09-05 12:35:27'),(3,1,'d2-01',1,'2026-09-05 12:35:27'),(4,1,'g3-01',1,'2026-09-05 12:35:27'),(5,1,'b3-01',1,'2026-09-05 12:35:27'),(6,1,'h4-01',0,'2026-09-05 12:35:27'),(7,1,'c5-01',0,'2026-09-05 12:35:27'),(8,2,'h2-01',1,'2026-09-05 12:35:27'),(9,2,'c2-01',1,'2026-09-05 12:35:27'),(10,2,'d1-01',1,'2026-09-05 12:35:27'),(11,2,'g2-01',1,'2026-09-05 12:35:27'),(12,2,'b2-01',1,'2026-09-05 12:35:27'),(13,3,'h4-01',1,'2026-09-05 12:35:27'),(14,3,'c4-01',1,'2026-09-05 12:35:27'),(15,3,'d3-01',1,'2026-09-05 12:35:27'),(16,3,'g4-01',1,'2026-09-05 12:35:27'),(17,3,'b4-01',1,'2026-09-05 12:35:27'),(18,4,'h5-01',1,'2026-09-05 12:35:27'),(19,4,'c5-01',1,'2026-09-05 12:35:27'),(20,4,'d4-01',1,'2026-09-05 12:35:27'),(21,4,'g5-01',1,'2026-09-05 12:35:27'),(22,4,'b5-01',1,'2026-09-05 12:35:27'),(23,7,'h1-01',0,'2026-09-11 07:21:30'),(24,10,'b1-01',0,'2026-09-15 02:39:02'),(25,20,'h1-01',0,'2026-09-15 04:45:48'),(26,21,'g2-01',0,'2026-09-15 11:21:08'),(28,21,'d2-01',0,'2026-09-15 11:21:21'),(29,21,'b1-01',0,'2026-09-15 15:27:55'),(30,22,'cor-02',0,'2026-09-23 10:35:44'),(31,21,'g1-01',0,'2026-09-23 10:56:54');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loadouts`
--

DROP TABLE IF EXISTS `loadouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loadouts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `helmet_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `core_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dampener_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gauntlets_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `battery_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `combatant_id` (`combatant_id`),
  KEY `helmet_id` (`helmet_id`),
  KEY `core_id` (`core_id`),
  KEY `dampener_id` (`dampener_id`),
  KEY `gauntlets_id` (`gauntlets_id`),
  KEY `battery_id` (`battery_id`),
  CONSTRAINT `loadouts_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loadouts_ibfk_2` FOREIGN KEY (`helmet_id`) REFERENCES `gear_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loadouts_ibfk_3` FOREIGN KEY (`core_id`) REFERENCES `gear_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loadouts_ibfk_4` FOREIGN KEY (`dampener_id`) REFERENCES `gear_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loadouts_ibfk_5` FOREIGN KEY (`gauntlets_id`) REFERENCES `gear_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loadouts_ibfk_6` FOREIGN KEY (`battery_id`) REFERENCES `gear_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loadouts`
--

LOCK TABLES `loadouts` WRITE;
/*!40000 ALTER TABLE `loadouts` DISABLE KEYS */;
INSERT INTO `loadouts` VALUES (1,1,'h3-01','c3-01','d2-01','g3-01','b3-01','2026-09-05 12:35:27'),(2,2,'h2-01','c2-01','d1-01','g2-01','b2-01','2026-09-05 12:35:27'),(3,3,'h4-01','c4-01','d3-01','g4-01','b4-01','2026-09-05 12:35:27'),(4,4,'h5-01','c5-01','d4-01','g5-01','b5-01','2026-09-05 12:35:27');
/*!40000 ALTER TABLE `loadouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `combatant_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('civilian','hero','villain','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `combatant_id` (`combatant_id`),
  KEY `idx_login_user` (`user_id`),
  KEY `idx_login_date` (`login_at`),
  KEY `idx_login_role` (`role`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `login_history_ibfk_2` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,2,8,'Steven','admin','2026-09-11 07:34:04','2026-09-11 07:34:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36'),(2,3,9,'john','civilian','2026-09-11 07:34:39','2026-09-11 07:34:45','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36'),(3,2,8,'Steven','admin','2026-09-11 07:34:54','2026-09-11 07:36:24','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36'),(4,2,8,'Steven','admin','2026-09-11 07:36:32',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36'),(5,1,7,'Steve','hero','2026-09-11 07:50:35','2026-09-11 07:50:44','::1',NULL),(6,2,8,'Steven','admin','2026-09-11 07:50:54','2026-09-11 07:52:00','::1',NULL),(7,4,8,'admin','admin','2026-09-15 02:20:14',NULL,'::1',NULL),(8,5,10,'test','civilian','2026-09-15 02:38:50',NULL,'::1',NULL),(9,5,10,'test','civilian','2026-09-15 02:54:41',NULL,'::1',NULL),(10,5,10,'test','civilian','2026-09-15 03:10:23',NULL,'::1',NULL),(11,5,10,'test','civilian','2026-09-15 03:35:45',NULL,'::1',NULL),(12,4,8,'admin','admin','2026-09-15 04:31:00',NULL,'::1',NULL),(13,5,10,'test','civilian','2026-09-15 04:33:49',NULL,'::1',NULL),(14,4,8,'admin','admin','2026-09-15 04:34:38',NULL,'::1',NULL),(15,4,8,'admin','admin','2026-09-15 04:43:04',NULL,'::1',NULL),(16,15,20,'hirosama','hero','2026-09-15 04:45:11',NULL,'::1',NULL),(17,5,10,'test','civilian','2026-09-15 10:47:13',NULL,'::1',NULL),(18,5,10,'test','civilian','2026-09-15 10:55:47',NULL,'::1',NULL),(19,16,21,'hiro','hero','2026-09-15 11:08:20',NULL,'::1',NULL),(20,4,8,'admin','admin','2026-09-15 11:19:03',NULL,'::1',NULL),(21,16,21,'hiro','hero','2026-09-15 11:19:53',NULL,'::1',NULL),(22,16,21,'hiro','hero','2026-09-15 11:33:03',NULL,'::1',NULL),(23,16,21,'hiro','hero','2026-09-15 13:23:33',NULL,'::1',NULL),(24,16,21,'hiro','hero','2026-09-15 13:35:59',NULL,'::1',NULL),(25,16,21,'hiro','hero','2026-09-15 13:37:00',NULL,'::1',NULL),(26,16,21,'hiro','hero','2026-09-15 13:37:06',NULL,'::1',NULL),(27,16,21,'hiro','hero','2026-09-15 13:49:12',NULL,'::1',NULL),(28,16,21,'hiro','hero','2026-09-15 14:03:53',NULL,'::1',NULL),(29,16,21,'hiro','hero','2026-09-15 14:08:27',NULL,'::1',NULL),(30,16,21,'hiro','hero','2026-09-15 14:17:49',NULL,'::1',NULL),(31,16,21,'hiro','hero','2026-09-15 14:24:35',NULL,'::1',NULL),(32,4,8,'admin','admin','2026-09-15 14:47:25',NULL,'::1',NULL),(33,16,21,'hiro','hero','2026-09-15 15:02:36',NULL,'::1',NULL),(34,4,8,'admin','admin','2026-09-15 15:02:54',NULL,'::1',NULL),(35,16,21,'hiro','hero','2026-09-15 15:03:34',NULL,'::1',NULL),(36,5,10,'test','civilian','2026-09-15 15:03:53',NULL,'::1',NULL),(37,16,21,'hiro','hero','2026-09-15 15:05:35',NULL,'::1',NULL),(38,16,21,'hiro','hero','2026-09-15 15:06:20',NULL,'::1',NULL),(39,16,21,'hiro','hero','2026-09-15 15:06:57',NULL,'::1',NULL),(40,16,21,'hiro','hero','2026-09-15 15:10:10',NULL,'::1',NULL),(41,16,21,'hiro','hero','2026-09-15 15:23:02',NULL,'::1',NULL),(42,17,22,'jesnel','villain','2026-09-23 10:19:42',NULL,'::1',NULL),(43,17,22,'jesnel','villain','2026-09-23 10:28:20',NULL,'::1',NULL),(44,17,22,'jesnel','villain','2026-09-23 10:35:35',NULL,'::1',NULL),(45,16,21,'hiro','hero','2026-09-23 10:37:22',NULL,'::1',NULL),(46,16,21,'hiro','hero','2026-09-23 10:38:17',NULL,'::1',NULL),(47,16,21,'hiro','hero','2026-09-23 10:55:54',NULL,'::1',NULL),(48,16,21,'hiro','hero','2026-09-23 11:03:04',NULL,'::1',NULL),(49,16,21,'hiro','hero','2026-09-23 11:18:56',NULL,'::1',NULL),(50,16,21,'hiro','hero','2026-09-25 03:03:41',NULL,'::1',NULL),(51,16,21,'hiro','hero','2026-09-25 04:30:14',NULL,'::1',NULL),(52,16,21,'hiro','hero','2026-09-25 04:30:34',NULL,'::1',NULL),(53,16,21,'hiro','hero','2026-09-25 04:30:57',NULL,'::1',NULL),(54,16,21,'hiro','hero','2026-09-25 04:31:19',NULL,'::1',NULL),(55,16,21,'hiro','hero','2026-09-25 04:58:59',NULL,'::1',NULL),(56,16,21,'hiro','hero','2026-09-25 04:59:09',NULL,'::1',NULL),(57,16,21,'hiro','hero','2026-09-25 05:02:12',NULL,'::1',NULL),(58,16,21,'hiro','hero','2026-09-25 05:03:49',NULL,'::1',NULL),(59,16,21,'hiro','hero','2026-09-25 05:06:18',NULL,'::1',NULL),(60,16,21,'hiro','hero','2026-09-25 05:09:23',NULL,'::1',NULL),(61,16,21,'hiro','hero','2026-09-25 05:09:59',NULL,'::1',NULL),(62,16,21,'hiro','hero','2026-09-25 05:33:57',NULL,'::1',NULL),(63,16,21,'hiro','hero','2026-09-30 09:50:43',NULL,'::1',NULL),(64,18,23,'Stevens','villain','2026-10-02 05:38:43',NULL,'::1',NULL),(65,18,23,'Stevens','villain','2026-10-02 06:09:02',NULL,'::1',NULL),(66,4,8,'admin','admin','2026-10-02 06:09:27',NULL,'::1',NULL),(67,18,23,'Stevens','villain','2026-10-03 12:45:10',NULL,'::1',NULL),(68,2,8,'Steven','admin','2026-10-03 12:55:47',NULL,'192.168.1.183',NULL),(69,16,21,'hiro','hero','2026-10-03 13:20:23',NULL,'192.168.1.183',NULL),(70,16,21,'hiro','hero','2026-10-03 13:25:30',NULL,'192.168.1.165',NULL);
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_completions`
--

DROP TABLE IF EXISTS `mission_completions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission_completions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `mission_id` int NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_completion` (`combatant_id`,`mission_id`),
  KEY `mission_id` (`mission_id`),
  CONSTRAINT `mission_completions_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mission_completions_ibfk_2` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_completions`
--

LOCK TABLES `mission_completions` WRITE;
/*!40000 ALTER TABLE `mission_completions` DISABLE KEYS */;
INSERT INTO `mission_completions` VALUES (1,1,1,'2024-01-20 02:00:00'),(2,1,2,'2024-01-25 06:00:00'),(3,1,3,'2024-02-01 01:00:00'),(4,1,4,'2024-02-10 08:00:00'),(5,1,5,'2024-02-20 03:00:00'),(6,4,1,'2024-01-12 00:00:00'),(7,4,2,'2024-01-18 07:00:00'),(8,4,3,'2024-01-25 04:00:00'),(9,4,4,'2024-02-05 06:00:00');
/*!40000 ALTER TABLE `mission_completions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `missions`
--

DROP TABLE IF EXISTS `missions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `missions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reward` int NOT NULL,
  `difficulty` enum('easy','medium','hard','expert') COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_clearance` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `missions`
--

LOCK TABLES `missions` WRITE;
/*!40000 ALTER TABLE `missions` DISABLE KEYS */;
INSERT INTO `missions` VALUES (1,'Patrol Duty','Complete a routine patrol of the city',500,'easy',1,'2026-09-05 12:35:27'),(2,'Gear Testing','Test prototype gear in the field',800,'easy',1,'2026-09-05 12:35:27'),(3,'Intercept Delivery','Stop a black market shipment',1200,'medium',2,'2026-09-05 12:35:27'),(4,'Rescue Mission','Save civilians from a villain attack',1500,'medium',2,'2026-09-05 12:35:27'),(5,'Counter-Intelligence','Gather intel on enemy movements',2000,'hard',3,'2026-09-05 12:35:27'),(6,'Assault Base','Lead an assault on enemy headquarters',3000,'hard',3,'2026-09-05 12:35:27'),(7,'Stop Superweapon','Prevent activation of a superweapon',5000,'expert',4,'2026-09-05 12:35:27'),(8,'Dark Matter Heist','Steal dark matter from a villain lab',10000,'expert',5,'2026-09-05 12:35:27');
/*!40000 ALTER TABLE `missions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `notification_type` enum('achievement','battle','event','reward','craft','purchase','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 0xF09F9494,
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_combatant` (`combatant_id`),
  KEY `idx_notif_read` (`combatant_id`,`is_read`),
  KEY `idx_notif_created` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'system','Welcome to Vantablack!','Your adventure begins. Explore the marketplace, fight battles, and climb the ranks.','⚡',NULL,0,'high','2026-09-15 15:14:02'),(2,1,'event','New Event Available','The Grand Tournament of Champions is now live! Join for a chance at epic rewards.','🏆',NULL,0,'high','2026-09-15 15:14:02'),(3,4,'system','Welcome to Vantablack!','Your dark journey begins. Conquer territories, defeat heroes, and dominate the city.','⚡',NULL,0,'high','2026-09-15 15:14:02'),(4,4,'event','New Event Available','Faction Supremacy is active! Lead your faction to victory.','🏴',NULL,0,'high','2026-09-15 15:14:02'),(5,2,'system','Welcome to Vantablack!','Your adventure begins. Explore the marketplace, fight battles, and climb the ranks.','⚡',NULL,0,'high','2026-09-15 15:14:02'),(6,8,'system','Welcome to Vantablack!','Discover the world of superpowered combat. Start building your loadout today.','⚡',NULL,0,'high','2026-09-15 15:14:02'),(7,3,'reward','Daily Reward Ready','Your daily reward is available! Log in to claim it.','🎁',NULL,0,'normal','2026-09-15 15:14:02'),(8,5,'battle','New Challenger','A hero has defeated one of your allies. Revenge is at hand!','⚔️',NULL,0,'urgent','2026-09-15 15:14:02'),(9,21,'purchase','Purchase Successful','You bought Standard Battery for ₵300','🛒',NULL,1,'normal','2026-09-15 15:27:55'),(10,22,'purchase','Purchase Successful','You bought Ferrox Overclock Weave for ₵3,400','🛒',NULL,0,'normal','2026-09-23 10:35:44'),(11,21,'purchase','Purchase Successful','You bought Starter Gauntlets for ₵400','🛒',NULL,0,'normal','2026-09-23 10:56:54');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `territories`
--

DROP TABLE IF EXISTS `territories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `controlling_faction` enum('hero','villain') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hero',
  `control_percentage` int NOT NULL DEFAULT '100',
  `defense_power` int NOT NULL DEFAULT '1000',
  `bonus_type` enum('discount','credit_multiplier','crafting_bonus','weather_resist','combat_power','loot_bonus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bonus_value` int NOT NULL DEFAULT '5',
  `region` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Metro Manila',
  `last_attacked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `territories`
--

LOCK TABLES `territories` WRITE;
/*!40000 ALTER TABLE `territories` DISABLE KEYS */;
INSERT INTO `territories` VALUES (1,'Downtown District','The heart of the city with major commercial centers','hero',100,1500,'discount',10,'Metro Manila',NULL,'2026-09-15 13:27:50'),(2,'Industrial Zone','Factories and warehouses producing gear','villain',100,2200,'crafting_bonus',15,'Metro Manila','2026-09-25 05:35:46','2026-09-15 13:27:50'),(3,'Research Facility','Advanced tech and experimental weapons','hero',80,2200,'combat_power',12,'Quezon City',NULL,'2026-09-15 13:27:50'),(4,'Black Market Hub','Underground trading of rare gear','villain',100,1200,'loot_bonus',20,'Manila Bay',NULL,'2026-09-15 13:27:50'),(5,'Government Plaza','Political center granting credit bonuses','hero',60,2500,'credit_multiplier',8,'Makati',NULL,'2026-09-15 13:27:50'),(6,'Energy Grid','Power generation facility','villain',70,1600,'weather_resist',25,'Taguig',NULL,'2026-09-15 13:27:50');
/*!40000 ALTER TABLE `territories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `territory_attacks`
--

DROP TABLE IF EXISTS `territory_attacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territory_attacks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `territory_id` int NOT NULL,
  `attacker_id` int NOT NULL,
  `attacker_faction` enum('hero','villain') COLLATE utf8mb4_unicode_ci NOT NULL,
  `attacker_power` int NOT NULL,
  `defense_power` int NOT NULL,
  `result` enum('victory','defeat') COLLATE utf8mb4_unicode_ci NOT NULL,
  `control_change` int NOT NULL DEFAULT '0',
  `credits_earned` int NOT NULL DEFAULT '0',
  `fought_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `territory_id` (`territory_id`),
  KEY `attacker_id` (`attacker_id`),
  CONSTRAINT `territory_attacks_ibfk_1` FOREIGN KEY (`territory_id`) REFERENCES `territories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `territory_attacks_ibfk_2` FOREIGN KEY (`attacker_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `territory_attacks`
--

LOCK TABLES `territory_attacks` WRITE;
/*!40000 ALTER TABLE `territory_attacks` DISABLE KEYS */;
INSERT INTO `territory_attacks` VALUES (1,2,21,'hero',1196,2017,'defeat',0,-418,'2026-09-15 13:36:38'),(2,2,21,'hero',1060,1942,'defeat',0,-243,'2026-09-15 13:37:21'),(3,2,21,'hero',1093,2112,'defeat',0,-280,'2026-09-15 13:49:30'),(4,2,21,'hero',1458,2345,'defeat',0,-262,'2026-09-25 05:35:46');
/*!40000 ALTER TABLE `territory_attacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combatant_id` int NOT NULL,
  `combatant_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `combatant_role` enum('civilian','hero','villain','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` enum('purchase','sell') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gear_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gear_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL,
  `credits_before` int NOT NULL,
  `credits_after` int NOT NULL,
  `status` enum('completed','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trans_combatant` (`combatant_id`),
  KEY `idx_trans_type` (`transaction_type`),
  KEY `idx_trans_date` (`created_at`),
  KEY `idx_trans_role` (`combatant_role`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,'Vantablack','hero','purchase','h4-01','Psi-Shield Helmet',5000,8200,3200,'completed',NULL,'2026-09-15 14:48:59'),(2,2,'Solar Flare','hero','purchase','h2-01','Tactical Command Helmet',1200,4500,3300,'completed',NULL,'2026-09-15 14:48:59'),(3,4,'Shadow Syndicate','villain','purchase','d4-01','Void Dampener',7000,9800,2800,'completed',NULL,'2026-09-15 14:48:59'),(4,1,'Vantablack','hero','purchase','c3-01','Fusion Core',4000,3200,-800,'failed',NULL,'2026-09-14 14:48:59'),(5,3,'Steel Guardian','hero','purchase','h4-01','Psi-Shield Helmet',5000,3000,-2000,'failed',NULL,'2026-09-14 14:48:59'),(6,5,'Crimson Wraith','villain','purchase','g5-01','Phantom Gauntlets',11000,6700,-4300,'failed',NULL,'2026-09-14 14:48:59'),(7,2,'Solar Flare','hero','sell','h1-01','Standard Issue Helmet',250,3300,3550,'completed',NULL,'2026-09-13 14:48:59'),(8,4,'Shadow Syndicate','villain','purchase','g4-01','Titan Gauntlets',6000,2800,-3200,'failed',NULL,'2026-09-13 14:48:59'),(9,8,'Juan Dela Cruz','civilian','purchase','h1-01','Standard Issue Helmet',500,500,0,'completed',NULL,'2026-09-12 14:48:59'),(10,9,'Maria Santos','civilian','purchase','g1-01','Starter Gauntlets',400,300,-100,'failed',NULL,'2026-09-12 14:48:59'),(11,1,'Vantablack','hero','purchase','d3-01','Quantum Dampener',3500,6700,3200,'completed',NULL,'2026-09-11 14:48:59'),(12,4,'Shadow Syndicate','villain','purchase','b5-01','Void Battery',9000,11800,2800,'completed',NULL,'2026-09-11 14:48:59'),(13,3,'Steel Guardian','hero','purchase','c4-01','Antimatter Core',8000,8000,0,'completed',NULL,'2026-09-10 14:48:59'),(14,5,'Crimson Wraith','villain','purchase','b4-01','Infinite Battery',5000,6700,1700,'completed',NULL,'2026-09-09 14:48:59'),(15,21,'hiro','hero','purchase','b1-01','Standard Battery',300,959,659,'completed',NULL,'2026-09-15 15:27:55'),(16,22,'Motzz','villain','purchase','cor-02','Ferrox Overclock Weave',3400,5100,1700,'completed',NULL,'2026-09-23 10:35:44'),(17,21,'hiro','hero','purchase','g1-01','Starter Gauntlets',400,1521,1121,'completed',NULL,'2026-09-23 10:56:54'),(18,21,'hiro','hero','sell','h2-01','Tactical Command Helmet',600,1121,1721,'completed',NULL,'2026-09-23 10:57:34');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `combatant_id` int NOT NULL,
  `role` enum('civilian','hero','villain','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `login_count` int NOT NULL DEFAULT '0',
  `is_online` tinyint(1) DEFAULT '0',
  `last_activity` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `daily_streak` int NOT NULL DEFAULT '0',
  `last_daily_claim` timestamp NULL DEFAULT NULL,
  `longest_daily_streak` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `combatant_id` (`combatant_id`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_token` (`session_token`),
  KEY `idx_users_reset_token` (`reset_token`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`combatant_id`) REFERENCES `combatants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Steve','$2y$10$Vmt1cNUMWh.ibAzvYmNMDOEc.iPtfWbxKNF.1kE.9AeXCb3r4l8Ee',7,'hero',NULL,'2026-09-11 07:50:35','2026-09-11 07:21:15',2,0,'2026-09-11 07:50:44',NULL,NULL,0,NULL,0),(2,'Steven','$2y$10$slJGU70F.D7LOWPxKWzb0uEv.A5f1ze7Ipd0f6a9FXZw7jd42V73W',8,'admin','edccd2b7cdc0da94b1cfe032607d9e68038624c32cd6cb138fed5e96e925b9c9','2026-10-03 12:55:47','2026-09-11 07:23:05',6,1,'2026-10-03 12:55:47',NULL,NULL,1,'2026-10-02 06:09:33',1),(3,'john','$2y$10$vvkvNBgUfT64RdyrqzTo3uMlpfNyuWWZrjRrqB02/vYxrTaYcTG/a',9,'civilian',NULL,'2026-09-11 07:34:39','2026-09-11 07:34:39',1,0,'2026-09-11 07:34:45',NULL,NULL,0,NULL,0),(4,'admin','$2y$10$HXt2.yvGU13eYPWB0960uePNXfG9F/Y.GvuiS7n/.xJRrnbL6ZVUG',8,'admin','6937a3a698cd5a66f4f88d8f8cb2e2c64a373b1f104f860f2c33ecc76b1f6508','2026-10-02 06:09:27','2026-09-15 02:19:24',8,1,'2026-10-02 06:09:31',NULL,NULL,1,'2026-10-02 06:09:33',1),(5,'test','$2y$10$UM0rScIW0IwoMTlY4H6rqOrCNeuDmcgk6rqAept24APz.qM5NmGRy',10,'civilian','637596ff9fa3ab018f64e852429e0665ff765319796528e10f2d5df8398cf5e6','2026-09-15 15:03:53','2026-09-15 02:38:50',8,1,'2026-09-15 15:03:53',NULL,NULL,1,'2026-09-15 15:03:58',1),(6,'soncedarbezotosniy225','$2y$10$Kz52HmZ7B1W0nXvcwICxYeVIB7gx1hHqa/zoEVbzXvW6zKzq82jXK',11,'hero',NULL,NULL,'2026-09-15 04:42:22',0,0,NULL,NULL,NULL,0,NULL,0),(7,'natalieheggernes864','$2y$10$BwfwioTQ5vif3uIMsxxJAOE5ajH5/T9igseBEaym9EMnooL0p6ynG',12,'hero',NULL,NULL,'2026-09-15 04:42:23',0,0,NULL,NULL,NULL,0,NULL,0),(8,'julianfaure430','$2y$10$HgO5J4Axug.PBSnQ3LGdX.m5mZPMF21rsRWgZtutyPmNpCkkCOOnu',13,'hero',NULL,NULL,'2026-09-15 04:42:24',0,0,NULL,NULL,NULL,0,NULL,0),(9,'ljubicacvejić610','$2y$10$L/FICewza38yYc46uOyy8ek6Kt/.OpwetoyQijL4J.ec64WM5F2Xq',14,'civilian',NULL,NULL,'2026-09-15 04:43:12',0,0,NULL,NULL,NULL,0,NULL,0),(10,'gordanalazić242','$2y$10$zfZoXR0djfrgqZu4iRq7VO65ffgEJF4LClo4Z1UOsfLhVNHg9GxQe',15,'civilian',NULL,NULL,'2026-09-15 04:43:13',0,0,NULL,NULL,NULL,0,NULL,0),(11,'matsnergård296','$2y$10$q3zn0Oz.rOb5gtlf6oFrgee.Ypj6F29/XYd1D49OFubTnba/6XGy.',16,'civilian',NULL,NULL,'2026-09-15 04:43:13',0,0,NULL,NULL,NULL,0,NULL,0),(12,'saidhamdhame890','$2y$10$aZDPXnLTk5y6aaqHJcDlheAl.IwZsp8HfUB0FqbSW.Hgh73UF2yJu',17,'civilian',NULL,NULL,'2026-09-15 04:43:31',0,0,NULL,NULL,NULL,0,NULL,0),(13,'luiseleroy413','$2y$10$H4QV3JXXpfefdok4L/U6Ge40Pim.gvmJrktZkdDJhXqpuA0kGDMWa',18,'civilian',NULL,NULL,'2026-09-15 04:43:31',0,0,NULL,NULL,NULL,0,NULL,0),(14,'maria-luisevogt488','$2y$10$RyL2zRXoFEHW2phHzfEdC.lOctGnSfJ0plO.2aNX3EKahqe0i/VQi',19,'civilian',NULL,NULL,'2026-09-15 04:43:32',0,0,NULL,NULL,NULL,0,NULL,0),(15,'hirosama','$2y$10$YbCGBO9ZCbQVenq8ETK5LO419SrI/InjXVdV/iCAL5YGtmA7xh6N.',20,'hero','20db3aa0106435a275ca5d8a09f5f36212bea009e97b09ccb8be43feb76b37de','2026-09-15 04:45:11','2026-09-15 04:45:11',1,1,NULL,NULL,NULL,0,NULL,0),(16,'hiro','$2y$10$IgN1tgxE/H96LSczRrTr6eMSrFtIQCWDf4LQiU5u2HMbd.Ek4ZuYq',21,'hero','4b4fea1561c0b3bf8f0727a062ade4dac3f730eaeaf3afd6d586bf7da57087aa','2026-10-03 13:25:30','2026-09-15 11:08:20',40,1,'2026-10-03 13:25:34',NULL,NULL,1,'2026-10-03 13:25:35',2),(17,'jesnel','$2y$10$FaK0okbH..MyyQ8mwUrDAu7xazKz08EMVGsJsr.crsgO6slcuMesS',22,'villain','38be350242e0b400ad99c90cc5096d1dd1edae206cb15b0fd9500f3b49d5ed33','2026-09-23 10:35:35','2026-09-23 10:19:42',3,1,'2026-09-23 10:35:35',NULL,NULL,1,'2026-09-23 10:19:56',1),(18,'Stevens','$2y$10$64.8lZ2MdGP3V/HbnLVbP.rTJwP5tdh7wrsdWYhpA.rWNGW8vRPw.',23,'villain','b880acb7ebc74d6f6909bac09a6628619ea12ea19030114e6375fcd055622a0e','2026-10-03 12:45:10','2026-10-02 05:38:43',3,1,'2026-10-03 12:45:16',NULL,NULL,1,'2026-10-02 05:38:50',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_combatant_full`
--

DROP TABLE IF EXISTS `v_combatant_full`;
/*!50001 DROP VIEW IF EXISTS `v_combatant_full`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_combatant_full` AS SELECT 
 1 AS `id`,
 1 AS `name`,
 1 AS `bio_capacity_max`,
 1 AS `base_recovery`,
 1 AS `base_risk`,
 1 AS `credits`,
 1 AS `faction`,
 1 AS `clearance_level`,
 1 AS `last_bonus_claim`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `total_credits_earned`,
 1 AS `total_credits_spent`,
 1 AS `total_battles_won`,
 1 AS `total_battles_lost`,
 1 AS `total_bounties_collected`,
 1 AS `current_win_streak`,
 1 AS `total_gear_owned`,
 1 AS `equipped_count`,
 1 AS `achievements_unlocked`,
 1 AS `achievement_points`*/;
SET character_set_client = @saved_cs_client;

--
-- Current Database: `aegis_db`
--

USE `aegis_db`;

--
-- Final view structure for view `combatant_loadout_view`
--

/*!50001 DROP VIEW IF EXISTS `combatant_loadout_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `combatant_loadout_view` AS select `c`.`id` AS `id`,`c`.`name` AS `name`,`c`.`bio_capacity_max` AS `bio_capacity_max`,`c`.`base_recovery` AS `base_recovery`,`c`.`base_risk` AS `base_risk`,`c`.`credits` AS `credits`,`c`.`faction` AS `faction`,`c`.`clearance_level` AS `clearance_level`,`l`.`helmet_id` AS `helmet_id`,`l`.`core_id` AS `core_id`,`l`.`dampener_id` AS `dampener_id`,`l`.`gauntlets_id` AS `gauntlets_id`,`l`.`battery_id` AS `battery_id`,`g1`.`name` AS `helmet_name`,`g1`.`bio_capacity` AS `helmet_bio_capacity`,`g1`.`recovery_rate` AS `helmet_recovery_rate`,`g1`.`risk_modifier` AS `helmet_risk_modifier`,`g2`.`name` AS `core_name`,`g2`.`bio_capacity` AS `core_bio_capacity`,`g2`.`recovery_rate` AS `core_recovery_rate`,`g2`.`risk_modifier` AS `core_risk_modifier`,`g3`.`name` AS `dampener_name`,`g3`.`bio_capacity` AS `dampener_bio_capacity`,`g3`.`recovery_rate` AS `dampener_recovery_rate`,`g3`.`risk_modifier` AS `dampener_risk_modifier`,`g4`.`name` AS `gauntlets_name`,`g4`.`bio_capacity` AS `gauntlets_bio_capacity`,`g4`.`recovery_rate` AS `gauntlets_recovery_rate`,`g4`.`risk_modifier` AS `gauntlets_risk_modifier`,`g5`.`name` AS `battery_name`,`g5`.`bio_capacity` AS `battery_bio_capacity`,`g5`.`recovery_rate` AS `battery_recovery_rate`,`g5`.`risk_modifier` AS `battery_risk_modifier` from ((((((`combatants` `c` left join `loadouts` `l` on((`c`.`id` = `l`.`combatant_id`))) left join `gear_items` `g1` on((`l`.`helmet_id` = `g1`.`id`))) left join `gear_items` `g2` on((`l`.`core_id` = `g2`.`id`))) left join `gear_items` `g3` on((`l`.`dampener_id` = `g3`.`id`))) left join `gear_items` `g4` on((`l`.`gauntlets_id` = `g4`.`id`))) left join `gear_items` `g5` on((`l`.`battery_id` = `g5`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_combatant_full`
--

/*!50001 DROP VIEW IF EXISTS `v_combatant_full`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_combatant_full` AS select `c`.`id` AS `id`,`c`.`name` AS `name`,`c`.`bio_capacity_max` AS `bio_capacity_max`,`c`.`base_recovery` AS `base_recovery`,`c`.`base_risk` AS `base_risk`,`c`.`credits` AS `credits`,`c`.`faction` AS `faction`,`c`.`clearance_level` AS `clearance_level`,`c`.`last_bonus_claim` AS `last_bonus_claim`,`c`.`created_at` AS `created_at`,`c`.`updated_at` AS `updated_at`,`cs`.`total_credits_earned` AS `total_credits_earned`,`cs`.`total_credits_spent` AS `total_credits_spent`,`cs`.`total_battles_won` AS `total_battles_won`,`cs`.`total_battles_lost` AS `total_battles_lost`,`cs`.`total_bounties_collected` AS `total_bounties_collected`,`cs`.`current_win_streak` AS `current_win_streak`,`cs`.`total_gear_owned` AS `total_gear_owned`,(select count(0) from `inventory` where ((`inventory`.`combatant_id` = `c`.`id`) and (`inventory`.`equipped` = true))) AS `equipped_count`,(select count(0) from `combatant_achievements` where ((`combatant_achievements`.`combatant_id` = `c`.`id`) and (`combatant_achievements`.`is_completed` = true))) AS `achievements_unlocked`,(select sum(`a`.`points`) from (`combatant_achievements` `ca` join `achievements` `a` on((`ca`.`achievement_id` = `a`.`id`))) where ((`ca`.`combatant_id` = `c`.`id`) and (`ca`.`is_completed` = true))) AS `achievement_points` from (`combatants` `c` left join `combatant_stats` `cs` on((`c`.`id` = `cs`.`combatant_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-03 22:10:23

-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.5.34-0ubuntu0.12.04.1 - (Ubuntu)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Version:             8.1.0.4545
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table ocbt.logs
CREATE TABLE IF NOT EXISTS `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `subtype` varchar(50) NOT NULL,
  `relation` varchar(50) NOT NULL,
  `relation_id` varchar(50) NOT NULL,
  `text` text,
  `input` text,
  `output` text,
  `important` tinyint(4) NOT NULL DEFAULT '0',
  `error` tinyint(4) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latest` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `subtype` (`subtype`),
  KEY `relation` (`relation`),
  KEY `relation_id` (`relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table ocbt.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('ACTIVE','CANCELED','EXECUTED','EXPIRED') NOT NULL DEFAULT 'ACTIVE',
  `action` enum('BUY','SELL') DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `amount_currency` enum('USD','BTC') DEFAULT NULL,
  `at_price` float DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_cancel` timestamp NULL DEFAULT NULL,
  `date_edited` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='standing or processed orders';

-- Data exporting was unselected.


-- Dumping structure for table ocbt.users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('USER','ADMIN') NOT NULL DEFAULT 'USER',
  `email` text NOT NULL,
  `email_confirmation` varchar(50) NOT NULL,
  `username` text,
  `password` text NOT NULL,
  `coinbase_access_token` text,
  `coinbase_refresh_token` text,
  `coinbase_expire_time` bigint(20) DEFAULT NULL,
  `date_registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


--
-- Table structure for table `values`
--

DROP TABLE IF EXISTS `values`;
CREATE TABLE IF NOT EXISTS `values` (
  `group` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `value` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `values`
--

INSERT INTO `values` (`group`, `name`, `value`, `updated`) VALUES
('coinbase', 'buyPrice', NULL, '2014-01-01 00:00:00'),
('coinbase', 'sellPrice', NULL, '2014-01-01 00:00:00');


/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

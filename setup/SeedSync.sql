/*
SQLyog Ultimate v12.12 (64 bit)
MySQL - 5.5.43-0ubuntu0.14.04.1 : Database - SeedSync
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`SeedSync` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `SeedSync`;

/*Table structure for table `Config` */

DROP TABLE IF EXISTS `Config`;

CREATE TABLE `Config` (
  `PropertyName` VARCHAR(30) NOT NULL,
  `PropertyValue` VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`PropertyName`)
) ENGINE=INNODB DEFAULT CHARSET=latin1;

INSERT  INTO `Config`(`PropertyName`,`PropertyValue`) VALUES ('CalendarPath','/../../calendar.ics'),('CalendarUrl','http://yoururl.com/to/the/calendar'),('Mode','on');

/*Table structure for table `Downloads` */

DROP TABLE IF EXISTS `Downloads`;

CREATE TABLE `Downloads` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `HostID` INT(11) DEFAULT NULL,
  `File` VARCHAR(400) DEFAULT NULL,
  `FileSize` INT(11) DEFAULT NULL,
  `LastModified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DateAdded` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Priority` TINYINT(4) DEFAULT NULL,
  `Status` ENUM('NEW','DOWNLOADING','PAUSED','RESUMED','FAILED','COMPLETE','RESUME') DEFAULT NULL,
  `DownloaderPID` INT(11) DEFAULT NULL,
  `Reason` VARCHAR(500) DEFAULT NULL,
  `DateStarted` TIMESTAMP NULL DEFAULT NULL,
  `DateComplete` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=INNODB AUTO_INCREMENT=16261 DEFAULT CHARSET=latin1;

/*Table structure for table `Hosts` */

DROP TABLE IF EXISTS `Hosts`;

CREATE TABLE `Hosts` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Host` VARCHAR(200) DEFAULT NULL,
  `User` VARCHAR(100) DEFAULT NULL,
  `Password` VARCHAR(110) DEFAULT NULL,
  `PublicKey` VARCHAR(200) DEFAULT NULL,
  `RemoteFolder` VARCHAR(300) DEFAULT NULL,
  `LocalFolder` VARCHAR(300) DEFAULT NULL,
  `LocalTemp` VARCHAR(300) DEFAULT NULL,
  `SimultaneousDownloads` TINYINT(1) DEFAULT NULL,
  `MaxSpeed` TINYINT(4) DEFAULT NULL,
  `Active` TINYINT(1) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=INNODB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Table structure for table `PidsToKill` */

DROP TABLE IF EXISTS `PidsToKill`;

CREATE TABLE `PidsToKill` (
  `PID` INT(11) NOT NULL,
  PRIMARY KEY (`PID`)
) ENGINE=INNODB DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 09, 2012 at 11:32 PM
-- Server version: 5.5.25a
-- PHP Version: 5.3.15

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `panorame`
--

DELIMITER $$
--
-- Functions
--
DROP FUNCTION IF EXISTS `consolidate`$$
CREATE DEFINER=`panorame`@`localhost` FUNCTION `consolidate`() RETURNS int(11)
BEGIN

  DECLARE start_lat, start_lng, dest_lat, dest_lng DOUBLE;
  DECLARE start_alt, dest_alt INTEGER;
  DECLARE distance, new_distance DOUBLE;
  DECLARE grade, new_grade INTEGER;

  DECLARE count INTEGER;

  SET @count := 0;

  WHILE EXISTS(SELECT e.id FROM edges e WHERE e.is_dirty = 1 AND e.is_deleted = 0) DO

    SELECT e.id, Y(vf.point), X(vf.point), vf.elevation , 
           Y(vt.point), X(vt.point), vt.elevation, 
           e.distance, e.grade
    INTO @id, @start_lat, @start_lng, @start_alt, 
           @dest_lat, @dest_lng, @dest_alt, 
           @distance, @grade
    FROM edges e
      JOIN vertices vf ON (e.from_id = vf.id)
      JOIN vertices vt ON (e.to_id = vt.id)
      WHERE e.is_dirty = 1 AND e.is_deleted = 0 LIMIT 1;

    SET @new_distance := 6371030 * acos( 
        cos(radians( @start_lat ))
      * cos(radians( @dest_lat ))
      * cos(radians( @start_lng ) - radians( @dest_lng ))
      + sin(radians( @start_lat )) 
      * sin(radians( @dest_lat))
    );

    SET @new_grade := CAST(@dest_alt AS SIGNED) - CAST(@start_alt AS SIGNED);

    UPDATE edges SET distance = @new_distance, grade = @new_grade, is_dirty = 0 WHERE id= @id;

    SET @count := @count + 1;

  END WHILE;

  RETURN @count;

END$$

DROP FUNCTION IF EXISTS `exportGIS`$$
CREATE DEFINER=`panorame`@`localhost` FUNCTION `exportGIS`() RETURNS blob
BEGIN

  DECLARE export LONGBLOB;

  SET @export := (SELECT GROUP_CONCAT(CONCAT("(",`id`, ", GEOMFROMTEXT('", ASTEXT(`point`),  "',4326),", `elevation`,  ",", `is_deleted` ,")")) AS inserts 
FROM `vertices`);

  RETURN @export;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `edges`
--
-- Creation: Oct 05, 2012 at 08:53 PM
-- Last update: Oct 09, 2012 at 09:27 PM
-- Last check: Oct 05, 2012 at 08:53 PM
--

DROP TABLE IF EXISTS `edges`;
CREATE TABLE IF NOT EXISTS `edges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` bigint(20) unsigned NOT NULL,
  `to_id` bigint(20) unsigned NOT NULL,
  `distance` double unsigned NOT NULL,
  `grade` int(10) NOT NULL,
  `type` int(10) unsigned NOT NULL,
  `is_deleted` tinyint(1) NOT NULL,
  `is_dirty` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `means`
--
-- Creation: Oct 09, 2012 at 06:48 PM
--

DROP TABLE IF EXISTS `means`;
CREATE TABLE IF NOT EXISTS `means` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `speeds`
--
-- Creation: Oct 09, 2012 at 07:04 PM
-- Last update: Oct 09, 2012 at 08:24 PM
--

DROP TABLE IF EXISTS `speeds`;
CREATE TABLE IF NOT EXISTS `speeds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mean_id` bigint(20) unsigned NOT NULL,
  `type_id` bigint(20) unsigned NOT NULL,
  `flat_speed` float DEFAULT NULL,
  `grade_speed` float DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `types`
--
-- Creation: Oct 09, 2012 at 06:47 PM
-- Last update: Oct 09, 2012 at 06:47 PM
--

DROP TABLE IF EXISTS `types`;
CREATE TABLE IF NOT EXISTS `types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `slug` varchar(25) NOT NULL,
  `secable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `vertices`
--
-- Creation: Oct 05, 2012 at 08:53 PM
-- Last update: Oct 09, 2012 at 09:27 PM
--

DROP TABLE IF EXISTS `vertices`;
CREATE TABLE IF NOT EXISTS `vertices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `point` point NOT NULL,
  `elevation` int(10) unsigned zerofill NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `elevation` (`elevation`),
  SPATIAL KEY `point` (`point`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `areas`
-- ----------------------------
DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL,
  `area_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `backends`
-- ----------------------------
DROP TABLE IF EXISTS `backends`;
CREATE TABLE `backends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `datastreams`
-- ----------------------------
DROP TABLE IF EXISTS `datastreams`;
CREATE TABLE `datastreams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor_id` varchar(255) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `datapoint` bigint(20) NOT NULL,
  `number_votes` int(11) DEFAULT NULL,
  `image_url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=65802 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `devices`
-- ----------------------------
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) DEFAULT NULL,
  `device_id` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `image_width` int(11) DEFAULT NULL,
  `image_height` int(11) DEFAULT NULL,
  `display_density` int(11) DEFAULT NULL,
  `os_version` varchar(255) DEFAULT NULL,
  `online` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `display_width` int(11) DEFAULT NULL,
  `display_height` int(11) DEFAULT NULL,
  `last_pulse` bigint(20) DEFAULT NULL,
  `stealth_mode` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sensors`
-- ----------------------------
DROP TABLE IF EXISTS `sensors`;
CREATE TABLE `sensors` (
  `sensor_id` varchar(255) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `sensor_name` varchar(255) NOT NULL,
  `sensor_question` text NOT NULL,
  `sensor_frequency` varchar(255) NOT NULL,
  `sensor_datatype` varchar(255) NOT NULL,
  `sensor_datatype_values` text,
  `sensor_obfuscation` varchar(255) DEFAULT NULL,
  `active` varchar(255) DEFAULT NULL,
  `sensor_subwindowpoints` text NOT NULL,
  `data` text,
  `source_image_width` int(11) DEFAULT NULL,
  `source_image_height` int(11) DEFAULT NULL,
  `last_updated` bigint(20) DEFAULT NULL,
  `deployment_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`sensor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `votes`
-- ----------------------------
DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datapoint` bigint(11) NOT NULL,
  `sensor_id` varchar(255) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `flag` varchar(255) DEFAULT NULL,
  `flag_data` text,
  `response_time` bigint(20) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `voter_info` text,
  `voter_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=47633 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;

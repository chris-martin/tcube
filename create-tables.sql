USE `CONTRIB_tcube`;

DROP TABLE IF EXISTS `cookie`;
DROP TABLE IF EXISTS `session`;
DROP TABLE IF EXISTS `page`;
DROP TABLE IF EXISTS `site`;

CREATE TABLE `session` (
  `session_id` varchar(32) COLLATE utf8_bin,
  `username` varchar(64) COLLATE utf8_bin,
  `last_access` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timeout` int(11) COMMENT 'number of seconds from last_access',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `cookie` (
  `session_id` varchar(32) COLLATE utf8_bin,
  `domain` varchar(128) COLLATE utf8_bin,
  `name` varchar(128) COLLATE utf8_bin,
  `value` varchar(4000) COLLATE utf8_bin,
  `path` varchar(4000) COLLATE utf8_bin,
  `path_specified` bit(1),
  `expires` int(11),
  PRIMARY KEY (`session_id`,`domain`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `cookie`
  ADD CONSTRAINT `cookie_ibfk_1` FOREIGN KEY (`session_id`)
  REFERENCES `session` (`session_id`) ON DELETE CASCADE;

CREATE TABLE `page` (
  `page_id` varchar(64) COLLATE utf8_bin,
  `username` varchar(64) COLLATE utf8_bin,
  `tool_id` varchar(64) COLLATE utf8_bin,
  `site_id` varchar(64) COLLATE utf8_bin,
  PRIMARY KEY (`username`,`tool_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `site` (
  `site_id` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin,
  `username` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin,
  `title` varchar(400) CHARACTER SET utf8 COLLATE utf8_bin,
  `type` varchar(400) CHARACTER SET utf8 COLLATE utf8_bin,
  PRIMARY KEY (`site_id`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;;

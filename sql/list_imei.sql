CREATE TABLE IF NOT EXISTS `list_imei` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) NOT NULL,
  `imei` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid` (`gid`,`imei`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

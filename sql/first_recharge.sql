CREATE TABLE IF NOT EXISTS `first_recharge` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(64) NOT NULL,
  `pid` int(10) NOT NULL,
  `cash` decimal(8,2) NOT NULL,
  `oid` bigint(20) NOT NULL,
  UNIQUE KEY `gid` (`gid`,`sid`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

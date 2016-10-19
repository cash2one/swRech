CREATE EVENT `list_role` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-26 2:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call newpartition('list_role');
CREATE TABLE IF NOT EXISTS `list_role` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(64) NOT NULL,
  `pid` int(10) NOT NULL,
  `name` varchar(24) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `imei` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (time)
(PARTITION p20160501 VALUES LESS THAN (1462118400) ENGINE = MyISAM);
call newpartition('list_role');

CREATE DEFINER=`root`@`localhost` EVENT `recharge_log` ON SCHEDULE EVERY 1 DAY STARTS '2016-05-01 02:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call neworderpar('recharge_log');
CREATE TABLE IF NOT EXISTS `recharge_log` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(64) NOT NULL,
  `pid` int(10) NOT NULL,
  `oid` bigint(20) NOT NULL,
  `cash` decimal(8,2) NOT NULL,
  `ooid` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (oid)
(PARTITION p20160506 VALUES LESS THAN (146255040000000000) ENGINE = MyISAM);
call neworderpar('recharge_log');


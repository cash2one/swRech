CREATE EVENT `detail_sdk` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-26 2:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call newpartition('detail_sdk');
CREATE TABLE IF NOT EXISTS `detail_sdk` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `mac` varchar(32) NOT NULL,
  `ver` varchar(32) NOT NULL,
  `wifi` varchar(16) NOT NULL,
  `status` tinyint(3) NOT NULL,
  `imei` bigint(20) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (time)
(PARTITION p20160501 VALUES LESS THAN (1462118400) ENGINE = MyISAM);
call newpartition('detail_sdk');

CREATE DEFINER=`root`@`localhost` EVENT `order_apply` ON SCHEDULE EVERY 1 DAY STARTS '2016-05-01 02:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call neworderpar('order_apply');
CREATE TABLE IF NOT EXISTS `order_apply` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(64) NOT NULL,
  `pid` int(10) NOT NULL,
  `cash` int(10) unsigned NOT NULL,
  `ip` varchar(32) NOT NULL,
  `oid` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (oid)
(PARTITION p20160506 VALUES LESS THAN (146255040000000000) ENGINE = MyISAM);
call neworderpar('order_apply');


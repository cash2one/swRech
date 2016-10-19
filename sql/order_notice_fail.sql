CREATE DEFINER=`root`@`localhost` EVENT `order_notice_fail` ON SCHEDULE EVERY 1 DAY STARTS '2016-05-01 02:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call neworderpar('order_notice_fail');
CREATE TABLE IF NOT EXISTS `order_notice_fail` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(64) NOT NULL,
  `pid` int(10) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `cash` decimal(8,2) NOT NULL,
  `oid` bigint(20) NOT NULL,
  `ooid` varchar(64) NOT NULL,
  `dtime` int(10) unsigned NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0, 未重发，>0 重发次数',
  `origin` smallint(6) NOT NULL DEFAULT '0' COMMENT '来源',
  PRIMARY KEY (`oid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (oid)
(PARTITION p20160506 VALUES LESS THAN (146255040000000000) ENGINE = MyISAM);
call neworderpar('order_notice_fail');


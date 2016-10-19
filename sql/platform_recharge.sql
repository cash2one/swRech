CREATE EVENT `platform_recharge` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-26 2:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call neworderpar('platform_recharge');
CREATE TABLE IF NOT EXISTS `platform_recharge` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL COMMENT '渠道ID',
  `sid` mediumint(8) unsigned NOT NULL COMMENT 'serverid',
  `uid` varchar(64) NOT NULL COMMENT '账号ID',
  `pid` int(10) NOT NULL COMMENT '角色ID',
  `ip` varchar(32) NOT NULL COMMENT 'IP',
  `cash` decimal(8,2) NOT NULL COMMENT '金额(元)',
  `oid` bigint(20) unsigned NOT NULL COMMENT '内部订单号',
  `ooid` varchar(64) NOT NULL COMMENT '渠道订单号',
  `dtime` int(10) unsigned NOT NULL COMMENT '发货时间',
  `origin` smallint(6) NOT NULL DEFAULT '0' COMMENT '来源'
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (oid)
(PARTITION p20160501 VALUES LESS THAN (146211840000000000) ENGINE = MyISAM);
call neworderpar('platform_recharge');

DELIMITER //
CREATE TRIGGER `first_recharge` BEFORE INSERT ON `platform_recharge`
FOR EACH ROW BEGIN insert IGNORE into first_recharge(time,gid,sid,uid,pid,cash,oid) values (new.time,new.gid,new.sid,new.uid,new.pid,new.cash,new.oid);END //
CREATE TRIGGER `delete_notify_fail` AFTER INSERT ON `platform_recharge`
FOR EACH ROW BEGIN delete FROM `order_notice_fail` WHERE `oid` = new.oid; END //
DELIMITER ;

CREATE EVENT `platform_login` ON SCHEDULE EVERY 1 DAY STARTS '2016-04-26 2:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call newpartition('platform_login');
CREATE TABLE IF NOT EXISTS `platform_login` (
  `time` int(10) unsigned NOT NULL,
  `gid` smallint(6) unsigned NOT NULL,
  `sid` mediumint(8) unsigned NOT NULL,
  `uid` varchar(32) NOT NULL,
  `imei` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (time)
(PARTITION p20160501 VALUES LESS THAN (1462118400) ENGINE = MyISAM);
call newpartition('platform_login');
DELIMITER //
CREATE TRIGGER `list_imei` AFTER INSERT ON `platform_login`
FOR EACH ROW BEGIN
insert IGNORE into list_imei(time,gid,imei) values (new.time,new.gid,new.imei);
END //
DELIMITER ;

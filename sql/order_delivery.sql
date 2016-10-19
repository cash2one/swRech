CREATE DEFINER=`root`@`localhost` EVENT `order_delivery` ON SCHEDULE EVERY 1 DAY STARTS '2016-05-01 02:13:12' ON COMPLETION NOT PRESERVE ENABLE DO call neworderpar('order_delivery');
CREATE TABLE IF NOT EXISTS `order_delivery` (
  `time` int(10) unsigned NOT NULL,
  `ooid` varchar(64) NOT NULL COMMENT '渠道订单号',
  `cash` decimal(8,2) NOT NULL COMMENT '金额',
  `oid` bigint(20) NOT NULL COMMENT '订单号',
  `origin` smallint(6) NOT NULL DEFAULT '0' COMMENT '来源'
) ENGINE=MyISAM DEFAULT CHARSET=utf8
PARTITION BY RANGE (oid)
(PARTITION p20160506 VALUES LESS THAN (146255040000000000) ENGINE = MyISAM);
call neworderpar('order_delivery');


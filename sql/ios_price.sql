CREATE TABLE IF NOT EXISTS `ios_price` (
 `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
 `itemId` varchar(32) NOT NULL COMMENT '商品编号',
 `price` smallint(5) unsigned NOT NULL COMMENT '商品价格RMB',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

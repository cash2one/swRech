DELIMITER //
CREATE PROCEDURE `newpartition`(IN `newpar` VARCHAR(32) CHARSET utf8)
 NO SQL
BEGIN
declare dvalue int(10);
SET @tday = date_add(NOW(),interval 1 day);
SET @dkey = DATE_FORMAT(@tday,'%Y%m%d');
SET dvalue = UNIX_TIMESTAMP(concat(date_add(@dkey,interval 1 day),' 00:00:00'));
SET @sqlcmd1 = CONCAT('ALTER TABLE `',newpar,'` ADD PARTITION (PARTITION ','p',@dkey,' VALUES LESS THAN (',dvalue,'))');
PREPARE p1 FROM @sqlcmd1;
EXECUTE p1;
DEALLOCATE PREPARE p1;
END //
DELIMITER ;

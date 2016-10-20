#
# 需要安装zlib-devel
# 先执行make.sql pass
#
# 配置conf, rsa, func, start 
#
# ./start start
#
#
# 维护相关
# 关闭80端口访问权限
# 等待1分钟
# ./start restart
# 等待一分钟
# 开启80端口访问权限
#
#
#本服务器配置127.0.0.1
#GMTf服务器 对本服务器IP进行授权
#GRANT ALL PRIVILEGES ON gmt_main.* TO 'acc'@'ip'IDENTIFIED BY 'pass' WITH GRANT OPTION;
#本服务器 对GMT服务器授权
#grant select ON center_platform.* TO 'acc'@'ip'IDENTIFIED BY 'pass' WITH grant OPTION;
#
##对GMT服务器 授权端口 3306 9503 9505 6379 6380 6381 6382
#开放8080端口
#

#!/bin/sh
if [ 'x'$1 == 'x' ]; then
	echo '参数 1 编号 错误！'
	exit 0
fi

SECRET=$1
USER='root'
DATABASE='platform_user'


SOURCE='set names utf8;'

cd sql

mysql -uroot -p${SECRET} -D mysql -e "CREATE DATABASE IF NOT EXISTS ${DATABASE} DEFAULT charset=utf8;"

if [ $? -eq 1 ];then
	echo '数据库 创建失败';
	exit 1
else
	echo '数据库 创建成功';
fi

mysql -u${USER} -p${SECRET} -D ${DATABASE} -e 'set names utf8;source neworderpar.sql;source newpartition.sql;'

if [ $? -eq 1 ];then
	echo "neworderpar.sql,newpartition.sql 导入失败 执行终止！"
	exit 1 
else
	echo '首次sql执行成功！'
fi

FILE_LIST=`ls`

for file in $FILE_LIST
do
	if [ ${file} != 'neworderpar.sql' ]; then
		if [ ${file} != 'newpartition.sql' ]; then
			SOURCE="${SOURCE} source ${file};"
		fi
	fi
done

echo ${SOURCE};
mysql -u${USER} -p${SECRET} -D ${DATABASE} -e "${SOURCE}"

if [ $? -eq 1 ];then
	echo ' 二次sql 执行失败了'
else
	echo ' 二次sql 执行成功了'
fi


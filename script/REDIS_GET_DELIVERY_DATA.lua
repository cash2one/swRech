local orderid
orderid = redis.call('RPOPLPUSH',KEYS[1], KEYS[2])
if orderid == false then
--	redis.log(redis.LOG_WARNING,'noorderidexists' .. KEYS[1]);
	return 1
else
--	redis.log(redis.LOG_WARNING,orderid);
	if redis.call('HGET', orderid, 'status') == '1' then
		redis.call('HSET', orderid,'status',2);
		return redis.pcall('HGETALL',orderid);
	else
		return 2
	end
end

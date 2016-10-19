if redis.call('EXISTS',KEYS[1]) == 1 then
	redis.call('HSET',KEYS[1],'status',1)
	return 1;
end

if redis.call('EXISTS',KEYS[1]) == 1 then
	redis.call('LREM',KEYS[2],1,KEYS[1])
	redis.call('LPUSH',KEYS[3],KEYS[1])
	redis.call('HSET',KEYS[1],'status',1)
	return 1;
end

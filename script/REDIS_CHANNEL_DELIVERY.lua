if redis.call('EXISTS',KEYS[1]) == 1 then
	if(redis.call('HSETNX',KEYS[1],'status',1)) == 0 then
		return 3;
	else
		redis.call('HSET',KEYS[1],'cash',ARGV[1])
		redis.call('HSET',KEYS[1],'dtime',ARGV[2])
		redis.call('HSET',KEYS[1],'ooid',ARGV[3])
		redis.call('HSET',KEYS[1],'origin',ARGV[4])
		redis.call('LPUSH','SIMPLE_PLATFORM_DELIVERY',KEYS[1])
        	return 1
	end
else
        return 2
end

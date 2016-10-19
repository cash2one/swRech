if redis.call('HEXISTS', ARGV[1], 'status') == 1 then
	redis.call('LPUSH', 'SIMPLE_PLATFORM_DELIVERY',ARGV[1]) 
	return 1;
else
	return 2
end

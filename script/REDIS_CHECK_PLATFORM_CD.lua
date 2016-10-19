if redis.call('EXISTS',KEYS[1]) == 1 then
	return 0;
else
	redis.call('SETEX', KEYS[1], ARGV[1], 1);
	return 1;
end

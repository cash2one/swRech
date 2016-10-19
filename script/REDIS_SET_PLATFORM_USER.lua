if redis.call('HSETNX', ARGV[1], KEYS[1], 1) == 1 then
	return 1;
else
	return 2;
end

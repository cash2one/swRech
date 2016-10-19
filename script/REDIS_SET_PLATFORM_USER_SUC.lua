if redis.call('HEXISTS', ARGV[1],KEYS[1]) ~= 1 then
	return 2;
end
redis.call('HSET', ARGV[1],KEYS[1],ARGV[2]);
return 1;

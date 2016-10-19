if redis.call("HEXISTS", ARGV[1], KEYS[1]) == 1 then
	return redis.call("HGET", ARGV[1], KEYS[1]);
else
	return 1;
end

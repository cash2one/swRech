if redis.call('GET','CODE_' .. KEYS[1]) ~= ARGV[1] then
	return 0;
else
	redis.call('DEL','CODE_' .. KEYS[1]);
	redis.call('DEL','CCD_' .. KEYS[1]);
	redis.call('DEL','IPCD_' .. KEYS[2]);
	return 1;
end

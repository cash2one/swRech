if redis.call('SETNX','IPCD_' .. KEYS[2],1) == 0 then
	return 5;
else
	if redis.call('EXPIRE', 'IPCD_' .. KEYS[2], 60) == 0 then
		return 6;
	end
end
if redis.call('SETNX','CCD_' .. KEYS[1],1) == 0 then
	return 3;
else
	if redis.call('EXPIRE', 'CCD_' .. KEYS[1], 60) == 0 then
		return 4;
	end
end
redis.call('SETEX','CODE_' .. KEYS[1],300,ARGV[1]);
return 1;

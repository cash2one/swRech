if redis.call('EXISTS',KEYS[1]) == 1 then
	if redis.call('HGET',KEYS[1],'appid') ~= KEYS[2] then
		return 4
	end
	if redis.call('HEXISTS',KEYS[1],'status') == 1 then
       		return 2
	else
		return 1 
	end
else
        return 3
end

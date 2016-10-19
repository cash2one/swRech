local pid = redis.call("HGET",KEYS[1],KEYS[2])

if pid ~= 0 then
	if redis.call("HDEL", "pid", pid) == 1 and redis.call("HDEL", KEYS[1],KEYS[2] ) then
		return 1
	else 
		return nil
	end
else 
	return nil
end
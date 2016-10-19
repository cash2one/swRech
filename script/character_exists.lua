if redis.call("EXISTS",KEYS[1]) == 1 then
	return 1
else 
	return 2
end

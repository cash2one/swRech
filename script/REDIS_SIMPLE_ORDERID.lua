local orderid
orderid=redis.call('INCR', KEYS[1])
if 99900 < orderid then
	redis.call('SET', KEYS[1], 0)
end
return orderid

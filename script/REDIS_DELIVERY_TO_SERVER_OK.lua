redis.call('DEL',KEYS[1])
redis.call('LREM',KEYS[2],1,KEYS[1])
return 1

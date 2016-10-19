redis.call("HSET",KEYS[1],KEYS[2],KEYS[4])
redis.call("HSET", KEYS[3],KEYS[4],KEYS[2])
return 1

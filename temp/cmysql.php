<?php

class MYSQL
{
protected $db;
protected $sql;
protected $key;
protected $conf;
protected $callback;
protected $calltime;

/**
 * [__construct 构造函数，初始化mysqli]
 * 
 * @param [type] $sqlConf
 *        	[description]
 */
public function __construct( $sqlConf )
{
	
	/*
	 * sqlConf = array(
	 * 'host' => ,
	 * 'port' => ,
	 * 'user' => ,
	 * 'psw' => ,
	 * 'database' => ,
	 * 'charset' => ,
	 * );
	 */
	$this->db = new \mysqli();
	$this->conf = $sqlConf;
}

/**
 * [send 兼容Base类封装的send方法，调度器可以不感知client类型]
 * 
 * @param [type] $callback
 *        	[description]
 * @return [type] [description]
 */
public function send( callable $callback )
{
	if (! isset($this->db))
	{
		
		echo " db not init \n";
		// TODO do callback function to task
		return;
	}
	// TODO conf check
	
	$config = $this->conf;
	$this->callback = $callback;
	$this->calltime = microtime(true);
	$this->key = md5($this->calltime . $config['host'] . $config['port'] . rand(0, 10000));
	
	//$this->db->connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
	$this->db->connect("localhost", "gmt_monster", "gmt_monster");
	if (! empty($config['charset']))
	{
		$this->db->set_charset($config['charset']);
	}
	
	$db_sock = swoole_get_mysqli_sock($this->db);
	swoole_event_add($db_sock, array(
		$this,
		'onSqlReady'
	));
	
	$this->doQuery($this->sql);
}

/**
 * [query 使用者调用该接口，返回当前mysql实例]
 * 
 * @param [type] $sql
 *        	[description]
 * @return [type] [description]
 */
public function query( $sql )
{
	$this->sql = $sql;
	yield $this;
}

/**
 * [doQuery 异步查询，两次重试]
 * 
 * @param [type] $sql
 *        	[description]
 * @return [type] [description]
 */
public function doQuery( $sql )
{
	
	// retry twice
	for ($i = 0; $i < 2; $i ++)
	{
		$result = $this->db->query($this->sql, MYSQLI_ASYNC);
		if ($result === false)
		{
			if ($this->db->errno == 2013 or $this->db->errno == 2006)
			{
				$this->db->close();
				$r = $this->db->connect();
				if ($r === true)
				{
					continue;
				}
			}
		}
		break;
	}
}

/**
 * [onSqlReady eventloog异步回调函数]
 * 
 * @return [type] [description]
 */
public function onSqlReady()
{
	
	// 关链接
	// $this ->db ->close();
	if ($result = $this->db->reap_async_query())
	{
		$this->calltime = $this->calltime - microtime(true);
		
		call_user_func_array($this->callback, array(
			'r' => 0,
			'key' => $this->key,
			'calltime' => $this->calltime,
			'data' => $result->fetch_all()
		));
		// 关链接
		// $this ->db ->close();
		if (is_object($result))
		{
			mysqli_free_result($result);
		}
	}
	else
	{
		echo "MySQLi Error: " . mysqli_error($this->db) . "\n";
		// TODO log callback
	}
}

}

class Task
{
protected $taskId;
protected $coroutine;
protected $sendValue = null;
protected $beforeFirstYield = true;

public function __construct( $taskId, Generator $coroutine )
{
	$this->taskId = $taskId;
	$this->coroutine = $coroutine;
}

public function getTaskId()
{
	return $this->taskId;
}

public function setSendValue( $sendValue )
{
	$this->sendValue = $sendValue;
}

public function run()
{
	if ($this->beforeFirstYield)
	{
		$this->beforeFirstYield = false;
		$retval = $this->coroutine->current();
		return $retval;
	}
	else
	{
		$retval = $this->coroutine->send($this->sendValue);
		// $retval = $this->coroutine->send($this->taskId);
		$this->sendValue = null;
		return $retval;
	}
}

public function isFinished()
{
	return ! $this->coroutine->valid();
}

}

class Scheduler
{
protected $maxTaskId = 0;
protected $taskMap = []; // taskId => task
protected $taskQueue;
protected $flag = 0;
protected $i=0;

// resourceID => [socket, tasks]
protected $waitingMysql = [];

public function waitMysql( $socket, Task $task )
{
	$db_sock = swoole_get_mysqli_sock($socket);
	//echo "111_".$db_sock."\n";
	if (isset($this->waitingMysql[$db_sock]))
	{
		$this->waitingMysql[$db_sock][1][] = $task;
	}
	else
	{
		$this->waitingMysql[$db_sock] = [
			$socket,
			[
				$task,
			]
		];
	}
}

public function ioPoll( $db_sock )
{

	if (! isset($this->waitingMysql[$db_sock]))
		return;
	//echo "333_".$db_sock."\n";
	list (, $tasks) = $this->waitingMysql[$db_sock];
	unset($this->waitingMysql[$db_sock]);
	
	foreach ($tasks as $task)
	{
		$this->schedule($task);
		$task->setSendValue($db_sock);
	}
	$this->run();
}

public function __construct()
{
	$this->taskQueue = new SplQueue();
}

public function newTask( Generator $coroutine )
{
	$tid = ++ $this->maxTaskId;
	$task = new Task($tid, $coroutine);
	$this->taskMap[$tid] = $task;
	$this->schedule($task);
	return $tid;
}

public function schedule( Task $task )
{
	$this->taskQueue->enqueue($task);
}

public function run()
{
	// $this->newTask($this->ioPollTask());
	while (! $this->taskQueue->isEmpty())
	{
		$task = $this->taskQueue->dequeue();
		$retval = $task->run();
		
		if ($retval instanceof SystemCall)
		{
			$retval($task, $this);
			continue;
		}
		
		if ($task->isFinished())
		{
			unset($this->taskMap[$task->getTaskId()]);
		}
		else
		{
			$this->schedule($task);
		}
	}
}

}

class SystemCall
{
protected $callback;

public function __construct( callable $callback )
{
	$this->callback = $callback;
}

public function __invoke( Task $task, Scheduler $scheduler )
{
	$callback = $this->callback;
	return $callback($task, $scheduler);
}

}

function waitMysql( $instance )
{
	return new SystemCall(function ( Task $task, Scheduler $scheduler ) use($instance )
	{
		$scheduler->waitMysql($instance, $task);
	});
}



function getInstance()
{
	global $busy,$instances;
	if(($instance = array_pop($instances)) != false)
	{
		//array_push($busy,$instance);
		$busy[$instance['sock']] = $instance;
		//if(count($instances) < 10)
		//var_dump("size:".count($instances));
		return $instance['mysqli'];
	}
	//echo count($instances);
	//var_dump(1111111111,count($instances));
}

function setInstance($db_sock)
{
	global $busy,$instances;
	$instances[] = $busy[$db_sock];
	unset($busy[$db_sock]);	
}

$vv = 1;
function dmysql_query($instance,$sql,$callback=null)
{
	global $vv;
	var_dump("dquery:".$vv++);
		yield $instance->query($sql, MYSQLI_ASYNC);
		$db_sock = (yield waitMysql($instance));
		$sql_result = $instance->reap_async_query();
		if (is_object($sql_result))
		{
			$sql_result_array = $sql_result->fetch_array(MYSQLI_ASSOC); // 只有一行
			$sql_result->free();
			//
			setInstance($db_sock);
		}
		elseif( $sql_result === true)
		{
			//echo "link err!";
			//var_dump($sql_result);
			
			setInstance($db_sock);
			//call_user_func_array($callback, array($sql_result));
		}
		elseif($sql_result === false)
		{
			var_dump($instance->errno);
			setInstance($db_sock);
			//call_user_func_array($callback, array($sql_result));
		}
		else echo "link err!";
}

$sqls = array();
$instances = array();
$busy = array();
$vvv=1;
function time_check()
{
	global $sqls,$scheduler,$vvv,$sql_time;
	while(!empty($sqls))
	{
		if(false !=($instance = getInstance()))
		{
			$sql = array_pop($sqls);
			var_dump("add:".$vvv++);
			$scheduler->newTask(dmysql_query($instance,$sql/*,$callback*/));
			$scheduler->run();
		}
		else
			break;
	}
	
	if(empty($sqls) && $sql_time)
	{
		swoole_timer_clear($sql_time);
		$sql_time = null;
	}
}

function set_sql($sql, $callback)
{
	global $sqls, $sql_time;
	
	$sqls[] = $sql;
	
	//time_check();
	
	if(is_null($sql_time))
	{
		$sql_time = swoole_timer_tick(5,"time_check");
	}
	
}

//$instances = new \mysqli("localhost", "root", "SDF63Z6GBFG6ZYSSDF4DFA");
$scheduler = new Scheduler();
$serv = new swoole_http_server("0.0.0.0", 8000);
$serv->set(array(
	'worker_num' => 1, // 工作进程数量
	'daemonize' => false
) // 是否作为守护进程
);
$serv->on('WorkerStart', 
		function ()
		{
			global $scheduler, $instances, $sql_time;
			for($i=0; $i<25; $i++)
			{
				$mysqli	= new \mysqli("localhost", "root", "SDF63Z6GBFG6ZYSSDF4DFA","gmt_sess");
				$db_sock = swoole_get_mysqli_sock($mysqli);
				
				swoole_event_add($db_sock, array
					(
						$scheduler,
						'ioPoll'
					));
				$instances[] = array('mysqli'=>$mysqli, 'sock'=>$db_sock);
			}
			var_dump(count($instances));
			$sql_time = swoole_timer_tick(5,"time_check");
			echo "start ok! \n";
		});
$serv->on('connect', function ( $serv, $fd )
{
	
	//echo "Client:Connect.\n";
});


//function receive($serv, $fd, $from_id, $data)
$kk = 1;
$ss = 1;
function receive($request, $response)
{
	//$time = microtime();
	global $kk,$ss;
	global $scheduler,$sqls;;
	
	set_sql("select sleep(1)", function($data){var_dump($data);});

	//$k = get_sql_data($sql);
	//if($kk!=1)
	//	$scheduler->newTask(doSql("insert into sessions values ('1','2');", function($data)use($response){var_dump($data);}));
//	else
	//	$scheduler->newTask(dmysql_query("show status", function($data)use($response){var_dump($data);}));
	$kk++;
// 	$scheduler->newTask(dmysql_query($i));
// 	$i ++;
// 	$scheduler->newTask(dmysql_query($i));
// 	$scheduler->newTask(dmysql_query($i));
// 	$scheduler->newTask(dmysql_query($i));
// 	$i ++;
// 	$scheduler->newTask(dmysql_query($i));
// 	$scheduler->newTask(dmysql_query($i));	
	//$scheduler->run();
	//echo (microtime() - $time)*1000;echo "\n";
	//$serv->send($fd, $data);
	
	$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>" );
}
//$serv->on('receive', function ( $serv, $fd, $from_id, $data )
$serv->on('request', "receive");
$serv->on('close', function ( $serv, $fd )
{
	//echo "Client: Close.\n";
});
$serv->start();

?>

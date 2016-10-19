<?php
class Task
{
protected $taskId;
protected $coroutine;
protected $sendValue = null;
protected $beforeFirstYield = true;

public function __construct( $taskId, Generator $coroutine )
{
	$this->taskId = $taskId;
	$this->coroutine = stackedCoroutine($coroutine);
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
protected $waitingHttp = [];
protected $waitingTask = [];

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

public function waitHttp( $socket, Task $task )
{
	$db_sock = spl_object_hash($socket);
	//echo "111_".$db_sock."\n";
	if (isset($this->waitingHttp[$db_sock]))
	{
		$this->waitingHttp[$db_sock][1][] = $task;
	}
	else
	{
		$this->waitingHttp[$db_sock] = [
			$socket,
			[
				$task,
			]
		];
	}
}

public function waitTask( $socket, Task $task )
{
	$db_sock = $socket->worker_id;
	var_dump('add worker_id:'.$db_sock);
	//echo "111_".$db_sock."\n";
	if (isset($this->waitingTask[$db_sock]))
	{
		$this->waitingTask[$db_sock][1][] = $task;
	}
	else
	{
		$this->waitingTask[$db_sock] = [
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

	list (, $tasks) = $this->waitingMysql[$db_sock];
	unset($this->waitingMysql[$db_sock]);
	
	foreach ($tasks as $task)
	{
		$this->schedule($task);
		$task->setSendValue($db_sock);
	}
	$this->run();
}

public function ioHttp( $socket )
{
	$db_sock = spl_object_hash($socket);
	if (! isset($this->waitingHttp[$db_sock]))
		return;
	
	list (, $tasks) = $this->waitingHttp[$db_sock];
	unset($this->waitingHttp[$db_sock]);
	
	foreach ($tasks as $task)
	{
		$this->schedule($task);
		$task->setSendValue($socket->body);
	}
	$this->run();
}

public function ioTask( $socket,$task_id,$data)
{
	$db_sock = $socket->worker_id;
	if (!isset($this->waitingTask[$db_sock]))
	{
		var_dump('uexists worker'.($socket->worker_id));
		return;
	}
	
	list (, $tasks) = $this->waitingTask[$db_sock];
	unset($this->waitingTask[$db_sock]);
	
	foreach ($tasks as $task)
	{
		$this->schedule($task);
		$task->setSendValue($data);
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

	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	public function __invoke(Task $task, Scheduler $scheduler)
	{
		$callback = $this->callback;
		return $callback($task, $scheduler);
	}
}

class CoroutineReturnValue
{
    protected $value;
 
    public function __construct($value)
	{
        $this->value = $value;
    }
 
    public function getValue() {
        return $this->value;
    }
}
 
function retval($value)
{
    return new CoroutineReturnValue($value);
}

function stackedCoroutine(Generator $gen)
{
	$stack = new SplStack;

	for (;;)
	{
		$value = $gen->current();

		if ($value instanceof Generator)
		{
			$stack->push($gen);
			$gen = $value;
			continue;
		}

		$isReturnValue = $value instanceof CoroutineReturnValue;

		if (!$gen->valid() || $isReturnValue)
		{
			if ($stack->isEmpty())
			{
				return;
			}

			$gen = $stack->pop();
			$gen->send($isReturnValue ? $value->getValue() : NULL);
			continue;
		}

		$gen->send((yield $gen->key() => $value));
	}
}

class CoMysql
{
	protected $mysqli;
	protected $sql;

	public function __construct($mysqli,$tsql)
	{
		$this->mysqli = $mysqli;
		$this->sql	= $tsql;
	}

	public function query()
	{
		yield retval($this->mysqli->query($this->sql, MYSQLI_ASYNC));
	}

	public function read()
	{
		$db_sock = (yield waitMysql($this->mysqli));
		$sql_result = $this->mysqli->reap_async_query();
		if (is_object($sql_result))
		{
			$sql_result_array = $sql_result->fetch_array(MYSQLI_ASSOC); // 只有一行
			$sql_result->free();
			//
			setInstance($db_sock);
			yield retval($sql_result_array);
		}
		elseif( $sql_result === true)
		{
			//echo "link err!";
			//var_dump($sql_result);

			setInstance($db_sock);
			//call_user_func_array($callback, array($sql_result));
			yield retval($sql_result);
		}
		elseif($sql_result === false)
		{
			var_dump($instance->errno);
			setInstance($db_sock);
			//call_user_func_array($callback, array($sql_result));
			yield retval($sql_result);
		}
	}

	public function close()
	{
		@mysqli_close($this->mysqli);
	}
}


class CoHttp
{
	protected $http;
	protected $data;

	public function __construct($http, $data)
	{
		$this->http = $http;
		$this->data	= $data;
	}

	public function send()
	{
		global $scheduler;
		yield retval($this->http->get($this->data[0],array($scheduler,'ioHttp')));
	}

	public function read()
	{
		$data = (yield waitHttp($this->http));
		yield retval($data);
	}

	public function close()
	{
		$this->http->close();
	}
}

class CoTask
{
	protected $task;
	protected $data;

	public function __construct($task, $data)
	{
		$this->task = $task;
		$this->data	= $data;
	}

	public function send()
	{
		global $scheduler;
		yield retval($this->task->task($this->data[0]));
	}

	public function read()
	{
		$data = (yield waitTask($this->task));
		yield retval($data);
	}

	public function close()
	{
		return;
	}
}


function waitMysql( $instance )
{
	return new SystemCall(function ( Task $task, Scheduler $scheduler ) use($instance )
	{
		$scheduler->waitMysql($instance, $task);
	});
}

function waitHttp( $instance )
{
	return new SystemCall(function ( Task $task, Scheduler $scheduler ) use($instance )
	{
		$scheduler->waitHttp($instance, $task);
	});
}

function waitTask( $instance )
{
	return new SystemCall(function ( Task $task, Scheduler $scheduler ) use($instance )
	{
		$scheduler->waitTask($instance, $task);
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
	$cli = new \swoole_http_client('127.0.0.1', 80, false);
	$nhttp = new CoHttp($cli, array('/test.php'));
	$ret = (yield $nhttp->send());
	var_dump($ret);
	$nmysql = new CoMysql($instance,$sql);
	$ret = (yield $nmysql->query());
	var_dump($ret);
	global $serv;
	$s = rand(1000,9999);
	$t = new CoTask($serv, array($s));
	$ret = (yield $t->send());
	var_dump('task_id:'.$ret.':'.$s);
	$data = (yield $t->read());
	 var_dump('task_data:'.$data);

	$data = (yield $nhttp->read());
	var_dump($data);
	$data = (yield $nmysql->read()); 
	//call_user_func_array($callback,array($data));
	do_sql();


}

$sqls = array();
$instances = array();
$busy = array();
$vvv=1;
function do_sql()
{
	global $sqls,$scheduler,$vvv,$sql_time;
	while(!empty($sqls))
	{
		if(false !=($instance = getInstance()))
		{
			$sql = array_pop($sqls);
			var_dump("add:".$vvv++);
			$scheduler->newTask(dmysql_query($instance,$sql[0],$sql[1]));
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

	$sqls[] = array($sql,$callback);

	do_sql();
}

//$instances = new \mysqli("localhost", "root", "SDF63Z6GBFG6ZYSSDF4DFA");
$scheduler = new Scheduler();
$serv = new swoole_http_server("0.0.0.0", 8000);
$serv->set(array(
	'task_worker_num' => 1,
	'worker_num' => 1, // 工作进程数量
	'daemonize' => false
) // 是否作为守护进程
);
$serv->on('WorkerStart', function ($serv,$worker_id)
{
	if ($serv->taskworker)
		return;
	global $scheduler, $instances, $sql_time;
	for($i=0; $i<25; $i++)
	{
		$mysqli	= new \mysqli("localhost", "root", "SDF63Z6GBFG6ZYSSDF4DFA","gmt_sess");
		$db_sock = swoole_get_mysqli_sock($mysqli);
//			var_dump(spl_object_hash($mysqli));

		swoole_event_add($db_sock, array
			(
				$scheduler,
				'ioPoll'
			));
		$instances[] = array('mysqli'=>$mysqli, 'sock'=>$db_sock);
	}
	var_dump(count($instances));
	echo "start ok! \n";
});

$serv->on('connect', function ( $serv, $fd )
{
	//echo "Client:Connect.\n";
});


//function receive($serv, $fd, $from_id, $data)
function receive($request, $response)
{
	set_sql("select sleep(3);", function($data)use($response){var_dump($data,1);$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>" );});
}
//$serv->on('receive', function ( $serv, $fd, $from_id, $data )
$serv->on('request', "receive");
$serv->on('close', function ( $serv, $fd )
{
	//echo "Client: Close.\n";
});
$serv->on('Task', function($serv, $task_id, $from_id, $data){sleep(10);return $data;});
$serv->on('Finish', array($scheduler,'ioTask'));

$serv->start();



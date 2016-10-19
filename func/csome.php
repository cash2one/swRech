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

class CoroutineReturnValue {
    protected $value;
 
    public function __construct($value) {
        $this->value = $value;
    }
 
    public function getValue() {
        return $this->value;
    }
}
 
function retval($value) {
    return new CoroutineReturnValue($value);
}

function stackedCoroutine(Generator $gen) {
	$stack = new SplStack;

	for (;;) {
		$value = $gen->current();

		if ($value instanceof Generator) {
			$stack->push($gen);
			$gen = $value;
			continue;
		}

		$isReturnValue = $value instanceof CoroutineReturnValue;
		if (!$gen->valid() || $isReturnValue) {
			if ($stack->isEmpty()) {
				return;
			}

			$gen = $stack->pop();
			$gen->send($isReturnValue ? $value->getValue() : NULL);
			continue;
		}

		$gen->send((yield $gen->key() => $value));
	}
}

class CoMysql {
	protected $mysqli;
	protected $sql;

	public function __construct($mysqli,$tsql) {
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

	public function close() {
		@mysqli_close($this->mysqli);
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
	$nmysql = new CoMysql($instance,$sql);
	$ret = (yield $nmysql->query());
	var_dump($ret);
	$data = (yield $nmysql->read()); 
	call_user_func_array($callback,array($data));
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
			var_dump(spl_object_hash($mysqli));

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
	function receive($request, $response)
	{
		//$time = microtime();
		global $scheduler,$sqls;;

		//set_sql("insert into sessions values ('1','2');", function($data){var_dump($data);});
		set_sql("select sleep(1);", function($data)use($response){var_dump($data,1);$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>" );});

//		$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>" );
	}
	//$serv->on('receive', function ( $serv, $fd, $from_id, $data )
	$serv->on('request', "receive");
	$serv->on('close', function ( $serv, $fd )
	{
		//echo "Client: Close.\n";
	});
	$serv->start();



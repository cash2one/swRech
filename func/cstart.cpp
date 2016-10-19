#include <iostream>
#include <map>
#include <stdio.h>
#include <sstream>
#include <stdlib.h>
#include <fstream>
#include <signal.h> 
#include <unistd.h>
using namespace std;

pid_t get_target_pid( char *pid_file )
{
	if((access(pid_file, F_OK ) != 0))
	{
		cout <<"pid_file unexists , can`t stop worker, or other question!"<<endl;
		return 0;
	}

	std::ifstream fin(pid_file, std::ios::in);
	char line[6];
	pid_t   tpid;

	if(fin.getline(line, 6))
		sscanf(line,"%d",&tpid);
	else
		return 0;
	
	fin.clear();
	fin.close();
	
	return tpid;
}

int worker_stop( char *pid_file )
{
	pid_t	tpid;
	if(!(tpid = get_target_pid(pid_file)))
	{
		cout<<" pid read fault, check it!"<<endl;
		return 0;
	}

	if(kill(tpid,SIGTERM)==0)
	{
		cout<<"stop worker success !"<<endl;

		if(remove(pid_file)==0)
			cout<<"delete PID_FILE ok!"<<endl;
		else
			cout<<"delete PID_FILE fault!"<<endl;
	}
	else
	{
		cout<<"stop worker fault, check it !"<<endl;
	}

	return 1;

}

int worker_reload( char *pid_file )
{	
	pid_t	tpid;
	if(!(tpid	= get_target_pid(pid_file)))
	{
		cout<<" pid read fault, check it!"<<endl;
		return 0;
	}

	if(kill(tpid,SIGUSR1)==0)
	{
		cout<<"restart worker success !"<<endl;
	}
	else
	{
		cout<<"restart worker fault, check it !"<<endl;
	}

	return 1;

}

int worker_restart( char *pid_file )
{
	pid_t	tpid;
	if(!(tpid = get_target_pid(pid_file)))
	{
		cout<<" pid read fault, check it!"<<endl;
		return 0;
	}

	if(kill(tpid,SIGUSR2)==0)
	{
		cout<<"reload worker success !"<<endl;
	}
	else
	{
		cout<<"reload worker fault, check it !"<<endl;
	}

	return 1;

}


int main(int argc,char *argv[])
{
	char base_dir[100];
	char pid_file[100];
	map<string,int>m;

	getcwd(base_dir, 100);
	sprintf(pid_file,"%s%s",base_dir,"/run/tp_worker.pid");

	if(argc < 2 || argc > 5)
	{
		cout <<"请输入要执行的指令"<<endl;
		return 0;
	}

	m.insert(pair<string,int>("start",1));
	m.insert(pair<string,int>("stop",2));
	m.insert(pair<string,int>("restart",3));
	m.insert(pair<string,int>("reload",4));

	if(m.find(argv[1]) == m.end())
	{
		cout<<" retry one of start,stop,restart,reload ！"<<endl;
		return 0;
	}

	switch(m[argv[1]])
	{
		case 1:
		{
			if((access(pid_file, F_OK ) == 0))
			{
				cout <<"project already start, or other question!"<<endl;
				break;
			}

			if(execl("/usr/bin/php","php","-r",
				"define('PROJECT_ROOT', __DIR__.'/' );\
				define('PROJECT_PID_FILE', PROJECT_ROOT.'/run/tp_worker.pid' );\
				require_once(PROJECT_ROOT.'core/common.php');\
				\
				class master\
				{\
					const	SERVER_STATUS_RUN	= 1;\
					const	SERVER_STATUS_STOP	= 0;\
					public  static  $serv;\
					public  static  $worker_status=self::SERVER_STATUS_RUN;\
					public  static  $script_data;\
					private static  $pid_data;\
					private static  $fd_data;\
					\
					public  static  function get_worker()\
					{\
						return self::$serv;\
					}\
					\
					public  static  function get_worker_status()\
					{\
						return self::$worker_status;\
					}\
					\
					public  static  function set_online_data($fd, $pid, $worker_id)\
					{\
						if(!self::$fd_data->set($fd, array('pd'=>$pid,'wd'=>$worker_id)))\
							return false;\
						return self::$pid_data->set($pid, array('fd'=>$fd,'wd'=>$worker_id));\
					}\
					\
					public  static  function del_online_data($fd)\
					{\
						if(!$odata = self::$fd_data->get($fd) )\
							return false;\
						\
						self::$fd_data->del($fd);\
						return self::$pid_data->del($odata['pd']);\
					}\
					\
					public  static  function get_online_data_by_fd($fd)\
					{\
						return self::$fd_data->get($fd);\
					}\
					\
					public  static  function get_online_data_by_pid($pid)\
					{\
						return self::$pid_data->get($pid);\
					}\
					\
					public  static  function exist_fd($fd)\
					{\
						return  self::$fd_data->exist($fd);\
					}\
					\
					public  static  function exist_pid($fd)\
					{\
						return self::$pid_data->exist($pid);\
					}\
					\
					public  static  function online($pid)\
					{\
						return self::$pid_data->exist($pid);\
					}\
					\
					public  static  function run()\
					{\
						echo swoole_version().\"\\n\";\
						call_user_func_array(array('\\log' , 'setup'),array());\
						$serv = new \\swoole_server('0.0.0.0', WEB_PORT);\
						$serv->listen('0.0.0.0', LOCAL_PORT, SWOOLE_SOCK_TCP);\
						\
						if(SERVER_MAX_ONLINE_LIMIT > 1024)\
						{\
							$pid_data	= new swoole_table(SERVER_MAX_ONLINE_LIMIT);\
							$pid_data->column('fd', swoole_table::TYPE_INT, 8);\
							$pid_data->column('wd', swoole_table::TYPE_INT, 1);\
							$fd_data	= new swoole_table(SERVER_MAX_ONLINE_LIMIT);\
							$fd_data->column('pd',  swoole_table::TYPE_STRING, 64);\
							$fd_data->column('wd', swoole_table::TYPE_INT, 1);\
						}\
						else\
						{\
							$pid_data	= new swoole_table(1024);\
							$pid_data->column('fd', swoole_table::TYPE_INT, 8);\
							$pid_data->column('wd', swoole_table::TYPE_INT, 1);\
							$fd_data	= new swoole_table(1024);\
							$fd_data->column('pd',  swoole_table::TYPE_STRING, 64);\
							$fd_data->column('wd', swoole_table::TYPE_INT, 1);\
						}\
						\
						$pid_data->create();\
						$fd_data->create();\
						self::$pid_data = $pid_data;\
						self::$fd_data  = $fd_data;\
						\
						$serv->set(array\
						(\
								'worker_num'=>WORKER_NUM_COUNT,/*工作进程数量*/\
						       	'task_worker_num'=> TASK_NUM_COUNT,\
						       	'daemonize'		=> DAEMONIZE,/*是否作为守护进程*/\
						       	'log_file'		=> LOG_FILE_DIR,\
						       	'open_length_check'	=> true,\
						       	'package_max_length'	=> 65540,\
						       	'package_length_type'	=> 'n',\
						       	'package_length_offset' => 0,\
						       	'package_body_offset'   => 2,\
						       	'task_tmpdir'		=> '/tmp/task/',\
						       	'dispatch_mode'		=> DISPATCH_MODE,\
								'open_cpu_affinity'	=> true,\
								/*'cpu_affinity_ignore' => array(0),*/\
						       	'heartbeat_idle_time'	=> 600,\
						       	'heartbeat_check_interval'=> 60,\
						       	'open_tcp_nodelay'	=> true,\
						       	'tcp_defer_accept'	=> 30,\
						       	'tcp_defer_accept'	=> SERVER_MAX_ONLINE_LIMIT,\
							/*'chroot'              => '/data/server/'*/\
						));\
						\
						$serv->on('WorkerError', function($serv, $wid, $wpid, $exit_code )\
						{\
							write_log('server_err', array($wid,$wpid,$exit_code));\
						});\
						\
						$serv->on('WorkerStop', function($serv, $worker_id)\
						{\
							echo 'stop:'.$worker_id.\" rsp stop\\n\";\
							if( !$serv->taskworker )\
							{\
								call_user_func_array(array('\\worker', 'stop'), array($serv, $worker_id));\
							}\
							else\
							{\
								call_user_func_array(array('\\task', 'stop'), array($serv, $worker_id));\
							}\
							echo 'stop:'.$worker_id.\" success!\\n\";\
							\\master::$worker_status = \\master::SERVER_STATUS_STOP;\
						});\
						\
						$serv->on('WorkerStart', function($serv, $worker_id)\
						{\
							$GLOBALS['serv'] = $serv;\
							echo 'start:'.$worker_id.\" rsp start\\n\";\
							\
							if(\\dbredis::setup() !== true)\
							{\
								echo \"dbredis load err !!!\\n\";\
								exit();\
							}\
							\
							\\schedule::setup($serv);\
							\
							if(!$serv->taskworker)\
							{\
								swoole_set_process_name('tp_worker_'.$worker_id);\
								load_dir(PROJECT_ROOT.'cmd');\
								$res = call_user_func_array( array('\\worker', 'start'), array($serv, $worker_id) );\
							}\
							else\
							{\
								swoole_set_process_name('tp_task_'.$worker_id);\
								load_dir(PROJECT_ROOT.'task');\
								$res = call_user_func_array(array('\\task', 'start'), array($serv, $worker_id));\
							}\
							if(!$res)\
							{\
								write_warn('start_err', 'redis load err!');$serv->shutdown();\
							}\
							\
							echo 'start:'.$worker_id.\" success!\\n\";\
							/*call_user_func_array(array('\\log' , 'setup'),array());*/\
						});\
						\
						$serv->on('start',function($serv)\
						{\
							swoole_set_process_name('tp_master');\
							if(DAEMONIZE)\
								file_put_contents(PROJECT_PID_FILE, $serv->master_pid);\
						});\
						\
						$serv->on('Managerstart', function($serv)\
						{\
							swoole_set_process_name('tp_manager');\
						});\
						\
						$serv->on('receive'	,function($serv, $fd, $from_id, $data)\
						{\
							if( !$message = call_user_func_array(array(BASE_PROTOCOL,'decode'),array($data)) ){\
								DEBUG && var_dump('decode err !!');\
								\\worker::deal_close($serv,$fd);\
							}\
							DEBUG && write_log('command_data', 'worker_id:'.$serv->worker_id.' fd:'.$fd.\"\\n\".$data);\
							\\worker::deal_command($serv, $fd, $message);\
						});\
						$serv->on('connect'		,array('\\worker', 'deal_connect'));\
						$serv->on('close'		,array('\\worker', 'deal_close'));\
						$serv->on('Shutdown'	,function(){echo 'Shutdown\\n';});\
						\
						if( TASK_NUM_COUNT )\
						{\
							$serv->on('Task',       array('\\task', 'deal_task'));\
							$serv->on('Finish',     array('\\worker', 'task_finish'));\
						}\
						\
						self::$script_data = load_script(PROJECT_ROOT.'script/');\
						$serv->start();\
					}\
				}\
				function get_online_data_by_pid($pid)\
				{\
					return \\master::get_online_data_by_pid($pid);\
				}\
				function get_online_data_by_fd($fd)\
				{\
					return \\master::get_online_data_by_fd($fd);\
				}\
				function set_online_data($fd,$pid,$worker_id)\
				{\
					return \\master::set_online_data($fd,$pid,$worker_id);\
				}\
				function del_online_data($fd)\
				{\
					return \\master::del_online_data($fd);\
				}\
				function get_worker_status()\
				{\
					return \\master::get_worker_status();\
				}\
				function get_worker()\
				{\
					return \\master::get_worker();\
				}\
				function online($pid)\
				{\
					return \\master::online();\
				}\
				\
				\\master::run();",
				 NULL)==-1)
		//	if(execl("/usr/bin/php","php","core/master.php", NULL)==-1)
				cout<<"php path must be /usr/bin, check it and retry"<<endl;
			cout<<"执行结果"<<endl;
			break;
		}
		case 2:
		{
			worker_stop(pid_file);
			break;
		}
		case 3:
		{
			worker_restart(pid_file);
			break;
		}
		case 4:
		{
			worker_reload(pid_file);
			break;
		}
		default:
			cout <<"other"<<endl;
			break;
	}


	return 0;
}

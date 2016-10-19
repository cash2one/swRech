<?php
/**
 *
 * 
 * @author zzd
 */
namespace service 
{
class mysql extends \service_class
{
private	$create_table	= array();

public	static	function setup()
{
	return 1;
}
public	static	function setsup()
{
}

public	static	function crash()
{
}

public	static	function storeProcedure($cmd, $asyncData=array())
{
	if ( !empty($asyncData) )
	{
		return \async_mysql::do_sql($cmd, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $cmd );
}

public	static	function insertDB( $table, $columns, $values, $asyncData=array() )
{
	if (!is_array($columns) || !sizeof($columns))
                	return false;

	if (!is_array($values) || !sizeof($values))
                	return false;

	$sql = 'insert into '.$table. '(`'.(implode( '`,`', $columns )).'`) values ';

	$sql .= "('".implode("','", $values)."');";
	
	if( !empty($asyncData) )
	{
		return \async_mysql::do_sql($sql, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $sql );
}

public	static	function selectLogDB($table,$cols,$asyncData=null,$conds=null,$groups=null,$orders=null,$limit=null)
{
        $sql = 'SELECT ' . $cols . ' FROM ' . $table;

	if ($conds)
	{
		$sql .= ' WHERE ' . $conds;
	}

	if ($groups)
	{
		$sql .= ' GROUP BY ' . $groups;
	}

	if ($orders)
	{
		$sql .= ' ORDER BY ' . $orders;
	}

	if ($limit)
	{
		$sql .= ' LIMIT ' . $limit;
	}

	if( $asyncData )
	{
		return \async_mysql::do_sql($sql, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $sql );
}

//#专用接口用于多条件混合更新
public	static	function updateLogDB($table,$cols,$conds,$asyncData=null)
{// $cols = array('set'=>'case'=>((when=>then),(when=>then)))
        $sql	= 'update ' . $table . ' set ';
//array( set->value  value) array('when'=>value)
	$tset	= array();
	$tsets	= array();
	$temp	= " when '%s' then '%s' ";

	foreach($cols as $set=>$v)
	{
		$t = $set.'=(case ';
		foreach($v as $case=>$vv)
		{
			$t .= $case. '';

			foreach($vv as $when=>$then)
			{
				$t.= sprintf($temp,$when,$then);
			}

			$t.=' else '.$set.' end)';
		}
		$tset[] = $t;
	}

	$sql .= implode(",", $tset);

	$sql .= ' where '.$conds;

	if( $asyncData )
	{
		return \async_mysql::do_sql($sql, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $sql );
}
//#通用接口
public	static	function simpleUpdateLogDB($table,$sets,$conds,$asyncData=null)
{
	$sql	= 'update ' . $table . ' set ';

	$temp = array();
	foreach($sets as $key=>$value)
	{
		$temp[] = sprintf("`%s`='%s'", $key, $value);
	}

	$sql .= implode(',', $temp);

	$sql .= ' where '.$conds;

	if( $asyncData )
	{
		return \async_mysql::do_sql($sql, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $sql );
}

public	static	function insertLogDB( $table, $columns, $values, $asyncData=array() )
{
	if (!is_array($columns) || !sizeof($columns))
                	return false;

	if (!is_array($values) || !sizeof($values))
                	return false;

	$sql = 'insert IGNORE into '.$table. '(`'.(implode( '`,`', $columns )).'`) values ';

	foreach($values as $i=>$value)
	{
		$values[$i] = implode(  "','", $value );
	}

	$sql .= "('".implode("'),('", $values)."');";
	
	if( !empty($asyncData) )
	{
		return \async_mysql::do_sql($sql, $asyncData[0], $asyncData[1]);
	}

	return \sync_mysql::queryi( $sql );
}

}
}
?>

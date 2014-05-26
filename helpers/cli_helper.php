<?php 

function cli_check_bin($list, &$missing = NULL)
{
	$missing = array();
	
	foreach ((array) $list as $item) 
	{
		 if( ! `type -P $item`)
		 {
		 	$missing[] = $item;
		 }
	} unset($item);
	
	return ! $missing;
}

function cli_apply_defaults($args)
{
	// append a ":" to each key from $args;
	$overrides = array();
	foreach ((array) $args as $key => $value) 
	{
		$overrides[] = $key.':';
	} unset($key, $value);
	
	$overrides = getopt('', $overrides);
	
	return array_merge($args, $overrides);
	
}

function cli_args($short_args, $long_args, $aliases = array())
{
	$args_list = getopt($short_args, $long_args);
	
	foreach ($aliases as $short => $long) 
	{
		 if(isset($args_list[$short]))
		 {
		 	$args_list[$long] = $args_list[$short];
		 }
		 elseif (isset($args_list[$long])) 
		 {
			 $args_list[$short] = $args_list[$long];
		 }
	} unset($short, $long);
	
	return $args_list;
}


function is_cli()
{
	return php_sapi_name() == 'cli';
}

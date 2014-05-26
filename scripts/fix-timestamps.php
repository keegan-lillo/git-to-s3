<?php

$file_list =  preg_split("/((\r?\n)|(\r\n?))/", shell_exec('git whatchanged --pretty=%at'));

$file_time = time();
$file_list_clean = array();

foreach ($file_list as $key => &$line) 
{
	 if( ! $line)
	 {
	 	
	 }
	 else if(strpos($line, ':') === 0)
	 {
	 	$file_name = preg_replace('~(?:.+?\s){5}\s*(.*)$~', '$1', $line);
		
	 	if( ! isset($file_list_clean[$file_name]) OR $file_list_clean[$file_name] < $file_time)
		{
			$file_list_clean[$file_name] = $file_time;
		}
		
	 }
	 else 
	 {
		 $file_time = (int) $line;
		
	 }
} unset($key, $line);

foreach ($file_list_clean as $file_name => $time) 
{
	 if(file_exists($file_name)) 
	 {
	 	touch ($file_name, $time);
	 }
} unset($key, $value);
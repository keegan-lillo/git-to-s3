<?php

function config_load($file, $var = 'config')
{
	if( ! file_exists($file)) return FALSE;
	
	include $file;
	
	return $$var;
	
	
}

<?php 
header("Content-Type: text/plain");
header('Cache-Control: max-age=0');
header('Cache-Control: s-maxage=0');

// putenv("HOME=/var/www")  ;

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1'); 

require_once 'helpers/cli_helper.php';
require_once 'helpers/config_helper.php';

$args = cli_apply_defaults(config_load('config.php'));

// check that we have the required binaries
$required_bin = array(
	$args['bin-aws'],
	$args['bin-git'],
	$args['bin-php'],
	$args['bin-gzip'],
);
if( ! cli_check_bin($required_bin, $missing))
{
	exit("could not locate the following binaries: " . implode(', ', $missing). "\n"); 
}

// compile aws creds if we have them in the args
if($args['aws-access-key'] AND $args['aws-secret-key'])	
{
	putenv("AWS_ACCESS_KEY_ID={$args['aws-access-key']}");
	putenv("AWS_SECRET_ACCESS_KEY={$args['aws-secret-key']}");
}
//compile exclusions if we have any
$exclusions = $args['aws-s3-exclusions'] ? '--exclude "'.implode('" --exclude "', (array) $args['aws-s3-exclusions']).'"': '' ;

// lets get the GZipped list
if($args['gzip-enable'])
{
	$gzip_include = '--include "'.implode('" --include "', (array) $args['gzip-filter']).'"';
	$gzip_exclude = '--exclude "'.implode('" --exclude "', (array) $args['gzip-filter']).'"';
}

// get pwd and strip any newlines from it
$dir = shell_exec('pwd | tr -d \'\n\'').'/codebase'; 

// $file_list_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
// foreach($file_list_iterator as $file)
// {
   // foreach ($args['gzip-filter'] as $filter) 
   // {
        // if(preg_match('/'.$filter.'/i', $file))
		// {
// 			
		// }
   // } unset($filter);
// } unset($file);

// put together our shell script
$cmd = "
	echo '--- Removing old codebase dir (if any) ---';
	rm -rf codebase 2>&1
	
	echo '--- Cloning repo into codebase dir ---';
	{$args['bin-git']} clone {$args['git-remote']} --branch {$args['git-branch']}  --single-branch codebase 2>&1
	
	cd '{$dir}';
	echo '--- Running extra user commands ---';
	{$args['extra-cmds']}
	
	cd '{$dir}';
	echo '--- Fixing timestamps ---';
	{$args['bin-php']} ../scripts/fix-timestamps.php 2>&1
";
echo shell_exec($cmd);

if($args['aws-s3-empty-first'])
{
	$cmd = "cd '{$dir}';
			echo '--- deleting all files from Amazon S3 ---';			
			{$args['bin-aws']} s3 rm {$args['aws-s3-path']} --recursive 2>&1;";
	echo shell_exec($cmd);
}

if($args['gzip-enable'])
{
	// send up all the non-gzip files
	$cmd = "
		cd '{$dir}';
		echo '--- Syncing non gzipped files to Amazon S3 ---';
			{$args['bin-aws']} s3 sync {$args['aws-s3-directory']} {$args['aws-s3-path']} \
			{$gzip_exclude} {$exclusions} \
			{$args['aws-s3-sync-extra-args']}  \
		2>&1;";
	echo shell_exec($cmd);
	
	// get list of files that should be gzipped
	$cmd = "
		cd '{$dir}';
		{$args['bin-aws']} s3 sync {$args['aws-s3-directory']} {$args['aws-s3-path']} \
			--exclude '*' {$gzip_include} {$exclusions} --dryrun \
		2>&1;";
		
	$file_list = `$cmd`;
	$file_list = preg_replace('~Completed \d* part\(s\) with \.\.\. file\(s\) remaining~', '', $file_list);
	$file_list = preg_replace('~\(dryrun\) upload: (.*?) to s3:\/\/.*$~m', '$1', $file_list );
	$file_list = preg_split("/\r\n|\n|\r/", $file_list);
	
	echo "--- Gzipping files --- \n";
	// gzip the files
	foreach ($file_list as $file_path) 
	{
		if( ! $file_path) 
		{
			continue; 
		}
		
		$file_path = $dir.'/'.$file_path;
		
		echo "$file_path \n";
		
		shell_exec("{$args['bin-gzip']} -f -S .gzippedtmp -{$args['gzip-level']} {$file_path}") ;
		shell_exec("mv -f {$file_path}.gzippedtmp {$file_path}") ;
		
	} unset($file_path, $fp);
	
	// fix the timestamps again and uplaod the files
	$cmd = "
			cd '{$dir}';
			echo '--- Fixing timestamps ---';
			{$args['bin-php']} ../scripts/fix-timestamps.php 2>&1
			
			echo '--- Syncing gzipped files to Amazon S3 ---';
			{$args['bin-aws']} s3 sync {$args['aws-s3-directory']} {$args['aws-s3-path']} \
			--exclude '*' {$gzip_include} {$exclusions} --content-encoding 'gzip' \
			{$args['aws-s3-sync-extra-args']} \
		2>&1;
	";
	
	echo shell_exec($cmd);
}
else
{
	$cmd = "
		cd '{$dir}';
		echo '--- Syncing files to Amazon S3 ---';
			{$args['bin-aws']} s3 sync {$args['aws-s3-directory']} {$args['aws-s3-path']} \
			{$exclusions} \
			{$args['aws-s3-sync-extra-args']}  \
		2>&1;
	";
	echo shell_exec($cmd);
}

$cmd = "cd '{$dir}' ;
		cd ../ ;
		echo '--- Removing old dir ---';
		rm -rf codebase 2>&1";
		
echo shell_exec($cmd);


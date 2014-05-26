<?php 

$config = array();

// specify the git remote eg git@github.com:user/repo.git
$config['git-remote'] = 'git@github.com:user/repo.git';

// usually set to master but you may change to a tag, commit, branch etc
$config['git-branch'] = 'master';

// The following is used to specify where the script
// should look for the binaries needed for the script
$config['bin-aws'] = 'aws';
$config['bin-git'] = 'git';
$config['bin-php'] = 'php';
$config['bin-gzip'] = 'gzip';

// enter a path to your s3 location with the format s3://<bucket>/<path>
$config['aws-s3-path'] = 's3://<bucket>/<path>';

// directory inside of repo that should be synced
$config['aws-s3-directory'] = '';

// array of exclusions to be passed to the aws s3 --exclude command
$config['aws-s3-exclusions'] = array(
	 '.git/*',
	 '.gitignore'
);


// extra arguments to be passed to the aws "s3 sync" command.
// Defaults to "--delete --acl public-read" 
$config['aws-s3-sync-extra-args'] = "--delete --acl public-read";

// empty target directory on s3 before syncing.
// this is handy for times when you want to update all objects meta data 
// or just want to start fresh
// WARNING: you will experience downtime as the files get re-uploaded
$config['aws-s3-empty-first'] = false;

// optionally specify AWS credentials.
// if either of these are left blank then the script will use your
// existing credentials in your main aws config
$config['aws-access-key'] = '';
$config['aws-secret-key'] = '';

// enable gzipping of files before being synced to S3
$config['gzip-enable'] = true;

// gzip compression level to use
$config['gzip-level'] = 6;

// the files that you would like gzipped.
// uses the same wildcard engine as aws-s3-exclusions
$config['gzip-filter'] = array(
	'*.css',
	'*.js',
	'*.html',
	'*.txt',
	'*.json',
	'*.svg'
);

// include any extra command you wish to run. 
// They will be executed from the repositories root directory 
$config['extra-cmds'] =	'';

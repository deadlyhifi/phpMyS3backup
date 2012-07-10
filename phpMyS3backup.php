<?php
define('DB_HOST', '');											//EX: localhost
define('DB_USER', '');											//EX: root
define('DB_PASS', '');											//EX: AwesomeP4ssW0rd!
define('SRV_NAME', php_uname("n"));								//Either: php_uname("n") OR 'my-personal-server-name'
define('DEBUG_ON', FALSE);										//Either: TRUE or FALSE

define('AWS_KEY', '');											// https://aws-portal.amazon.com/gp/aws/securityCredentials
define('AWS_SECRET', '');										// https://aws-portal.amazon.com/gp/aws/securityCredentials
define('BUCKET', '');											// Bucket to save to.
define('BACKUP_FOLDER', 'mysql_backups');						// Folder to save to.

date_default_timezone_set('Europe/London');						// Timezone, choose yours: http://uk3.php.net/manual/en/timezones.php

// ********************************
// Lets Go
// ********************************
if(DEBUG_ON){error_reporting(-1);}
$now = date("Y-m-d_H:i:s");
syslog(LOG_INFO, "phpMyS3Backup - Starting run ID $now");
require_once 'AWSSDKforPHP/sdk.class.php';

// ********************************
// Get list of databases
// ********************************
$alldb = array();
$GLOBALS['con']= new PDO('mysql:host='.DB_HOST, DB_USER, DB_PASS);
try {
	$res = $GLOBALS['con']->query("SHOW DATABASES");
	while($row = $res->fetch()) {
		if($row['Database'] != "information_schema"){
			$alldb[] = $row['Database'];
			deb("Database found: {$row['Database']}");
		}
	}
}
catch (PDOException $e) {
	print $e->getMessage();
}

// ********************************
// MySQLDump each database
// ********************************
system("mkdir /tmp/$now/");
foreach($alldb as $db){
	$cmd = "mysqldump $db -h ".DB_HOST." -u".DB_USER." -p".DB_PASS." > /tmp/{$now}/$db.sql";
	deb("Doing: $cmd");
	system($cmd);
}

// ********************************
// gzip databases
// ********************************
foreach($alldb as $db){
	system("gzip /tmp/{$now}/$db.sql");
}

// ********************************
// Upload the files to S3
// ********************************
$aws = array( 
		'key' => AWS_KEY,
		'secret' => AWS_SECRET,
		'default_cache_config' => '',
		'certificate_authority' => false
	);

$s3 = new AmazonS3($aws);
foreach ($alldb as $db) {
	$filename = "$db.sql.gz";
	$path = "/tmp/{$now}/";

	$s3->batch()->create_object(BUCKET, BACKUP_FOLDER . "/" . $now . "/" . $filename, array(
		'fileUpload' => $path.$filename,
		'storage' => AmazonS3::STORAGE_REDUCED,
		'acl' => AmazonS3::ACL_PRIVATE,
		'encryption' => 'AES256'
	));
}

deb("files setup done - uploading");

$file_upload_response = $s3->batch()->send();

deb("upload done - waiting for ok");

if(DEBUG_ON){
	if ($file_upload_response->areOK()) {
		foreach ($alldb as $db) {
			print $s3->get_object_url(BUCKET, BACKUP_FOLDER . "/" . $now . "/" . $db . ".sql.gz", '5 minutes') . PHP_EOL . PHP_EOL;
		}
	}
}

// ********************************
// Cleanup the local filesystem
// ********************************

system("rm -rf /tmp/{$now}/");
deb("DONE");
syslog(LOG_INFO, "phpMyS3Backup - completed run ID $now");

// ********************************
// Done!
// ********************************

function deb ($msg) {
	if(DEBUG_ON) { print $msg . "\n"; }
}

<?php
define('DB_HOST', '');											//EX: localhost
define('DB_USER', '');											//EX: root
define('DB_PASS', '');											//EX: AwesomeP4ssW0rd!
define('SRV_NAME', php_uname("n"));								//Either: php_uname("n") OR 'my-personal-server-name'
define('DEBUG_ON', FALSE);										//Either: TRUE or FALSE

define('AWS_KEY', '');											// https://aws-portal.amazon.com/gp/aws/securityCredentials
define('AWS_SECRET', '');										// https://aws-portal.amazon.com/gp/aws/securityCredentials
define('BUCKET', '');											// Bucket to save to.

date_default_timezone_set('Europe/London');						// Timezone, choose yours: http://uk3.php.net/manual/en/timezones.php
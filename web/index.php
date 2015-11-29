<?php

/**
 * Environment variable SERVER_CONTEXT is defined in production server
 * /etc/apache2/sites-available/crawler.conf file's VirtualHost setting: 
 * `SetEnv SERVER_CONTEXT "prod"`
 * So here we can detect environment by this variable
 */
if( getenv('SERVER_CONTEXT') === 'prod' ){
	define('YII_DEBUG', false);
	define('YII_ENV', 'prod');
}else {
	define('YII_DEBUG', true);
	define('YII_ENV', 'dev');
}

// comment out the following two lines when deployed to production
// defined('YII_DEBUG') or define('YII_DEBUG', true);
// defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();

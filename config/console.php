<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$params = require(__DIR__ . '/params.php');
$scraper = require(__DIR__ . '/scraper.php');
$db = require(__DIR__ . '/db.php');

// Include user override settings
$userSettings = file_exists( dirname(__FILE__).'/user.settings.php' ) ?
    require dirname(__FILE__).'/user.settings.php' : array();

// Override db setting
if( !empty( $userSettings ) && array_key_exists( 'db' , $userSettings ) ) {
    $db = array_merge($db, $userSettings['db']);
}

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        //might need to merge array instead of doing it manually
        'goutteClient' => $scraper['goutteClient'],
        'wjscraper' => $scraper['worldjournal'],
        'clscraper' => $scraper['craigslist'],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

<?php

$params = require(__DIR__ . '/params.php');
if(file_exists(__DIR__ . '/params.local.php')) {
    $paramsLocal = require(__DIR__ . '/params.local.php');
    $params = array_merge($params, $paramsLocal);
}

$db = require(__DIR__ . '/db.php');
if(file_exists(__DIR__ . '/db.local.php')) {
    $dbLocal = require(__DIR__ . '/db.local.php');
    $db = array_merge($db, $dbLocal);
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
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

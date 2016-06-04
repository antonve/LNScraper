<?php

require __DIR__ . '/vendor/autoload.php';


$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'dbname' => 'lightnovel',
    'user' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'port' => 2020,
    'driver' => 'pdo_mysql',
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

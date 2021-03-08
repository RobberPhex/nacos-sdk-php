<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

use Aliyun\Nacos\Config\ConfigClient;
use Aliyun\Nacos\Util;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require __DIR__ . '/../vendor/autoload.php';

$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stdout', Logger::INFO)); // <<< uses a stream

$client = new ConfigClient('acm.aliyun.com','namespace',8080);
$client->setLogger($log);
$client->refreshServerList();
$resp = $client->getServerList();

$client->setAccessKey('accessKey');
$client->setSecretKey('secretKey');
$client->setAppName("appname");

echo $client->getConfig('test.test',null)."\n";

$client->publishConfig('test.test',null,"{\"test\":\"asdfasdfasdf\"}")."\n";

$ts = round(microtime(true) * 1000);
$client->publishConfig('test'.$ts,null,"{\"test\":\"asdfasdfasdf\"}")."\n";

$client->removeConfig('test.test',null);

echo strval(Util::isValid('data_id'));

echo strval(Util::isValid('data*id'));

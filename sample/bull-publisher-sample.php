<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BullPublisher\BullPublisher;

$host = 'localhost';
$port = 6379;
$bull = new BullPublisher(
    $host,
    $port,
);

$queue = 'test-queue';
$job = 'process';
$data = [
    'foo' => 'php',
];


print_r('Publish');
echo PHP_EOL;
$result = $bull->add(
    $queue,
    $job,
    $data
);
print_r($result);
echo PHP_EOL;
print_r('===================================================');
echo PHP_EOL;
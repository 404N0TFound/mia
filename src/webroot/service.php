<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once __DIR__ . '/../../vendor/autoload.php';
$runner = new \Ice\Frame\Runner\Service(__DIR__ . '/../conf/app.php');
$runner->run();

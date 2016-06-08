<?php
ini_set('display_errors', 'On');error_reporting(E_ALL);
require_once __DIR__ . '/../../vendor/autoload.php';
$runner = new \Ice\Frame\Runner\Service(__DIR__ . '/../conf/app.php');
$runner->run();

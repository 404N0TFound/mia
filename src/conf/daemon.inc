<?php
/*
 每个日志项, 会被自动注册到自己App的成员变量. 比如comm注册为$app->logger_comm
*/
$daemon_logger = array(
    'comm' => array(
        'log_fmt' => array(
            'fmt_now'             => '', # 默认-Y-m-d H:i:s
            'client_env_ip'       => '',
            'server_env_hostname' => '',
            'request_class'       => '',
            'request_action'      => '',
        ),
        'log_fmt_wf' => array(
            'fmt_now'             => '', # 默认-Y-m-d H:i:s
            'client_env_ip'       => '',
            'server_env_hostname' => '',
            'request_class'       => '',
            'request_action'      => '',
            'level'               => '',
            'errno'               => '',
            'trace'               => '',
        ),
        'log_file' => 'daemon.log',
        'log_path' => $var_path . '/logs',
        'split'    => array(
            'type' => 'file',
            'fmt'  => 'Ymd',
        ),
    ),
);

$daemon_filter = array(
    'compile_path' => $run_path . '/filter',
    'base_filter'  => '\\Ice\\Filter\\Filter',
    'extend_path'  => $root_path . '/filter',
);

$damon_php_bin = '/opt/php/bin/php';

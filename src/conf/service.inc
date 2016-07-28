<?php
/*
 每个日志项, 会被自动注册到自己App的成员变量. 比如comm注册为$app->logger_comm
*/
$service_logger = array(
    'comm' => array(
        'log_fmt' => array(
            'fmt_time'            => '', # 默认-Y-m-d H:i:s
            'client_env.ip'       => '',
            'server_env.hostname' => '',
            'request.uri'         => '',
            'request.originalUri' => '',
            'request.class'       => '',
            'request.action'      => '',
            'request.id'          => '',
        ),
        'log_fmt_wf' => array(
            'fmt_time'            => '', # 默认-Y-m-d H:i:s
            'client_env.ip'       => '',
            'server_env.hostname' => '',
            'request.uri'         => '',
            'request.originalUri' => '',
            'request.class'       => '',
            'request.action'      => '',
            'request.id'          => '',
            'level'               => '',
            'errno'               => '',
            'trace'               => '',
        ),
        'log_file' => 'service.log',
        'log_path' => $var_path . '/logs',
        'split'    => array(
            'type' => 'file',
            'fmt'  => 'Ymd',
        ),
    ),
    'access' => array(
        'log_fmt' => array(
            'fmt_time'            => '', //接口调用时间
            'request.class'       => '', //接口调用类
            'request.action'      => '', //接口调用方法
            'client_env.ip'       => '', //调用方IP
            'server_env.hostname' => '', //调用方机器名
            'request.id'          => '', //请求ID
        ),
        'log_file' => 'access.log',
        'log_path' => $var_path . '/logs',
        'split'    => array(
            'type' => 'file',
            'fmt'  => 'Ymd',
        ),
    ),
);

$service_filter = array(
    'compile_path' => $run_path . '/filter', // 编译产出路径
    'base_filter'  => '\\Ice\\Filter\\Filter', // Filter基类, 可继承后自己扩展op_xxx/type_xxx
    'extend_path'  => $root_path . '/filter', // 标准的filter引用定义路径
);

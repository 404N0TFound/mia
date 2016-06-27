<?php
/**
 * 后台cron配置
 */
$php_cli = '/daemon/cli.php';

//直播——直播状态监控
$cron_list['live_status_check'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=livingcheck",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 3
);

//直播——直播开始推流阶段监控
$cron_list['live_stream_check'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=streamprepare",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 3
);
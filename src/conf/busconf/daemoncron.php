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
    'interval' => 5
);

//直播——直播开始推流阶段监控
$cron_list['live_stream_check'] = array(
    'enable' => false,
    'engine' => 'php',
    'cli_args' => "--class=live --action=streamprepare",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 3
);

//定时发送已售出商品数量消息
$cron_list['sale_sku_count'] = array(
    'enable' => false,
    'engine' => 'php',
    'cli_args' => "--class=live --action=livesaleskunum",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 5
);

//定时发送在线人数消息
$cron_list['online_count'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=chatroomusernum",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 5
);

//打散红包定时任务
$cron_list['split_red_bag'] = array(
    'enable' => false,
    'engine' => 'php',
    'cli_args' => "--class=live --action=splitredbag",
    'start_time' => '2016-07-4 00:00:00',
    'interval' => 3
);

//定时获取聊天室的日志
$cron_list['get_chat_room_log'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=chatroomlog",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 3600
);

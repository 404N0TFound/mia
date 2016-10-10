<?php
/**
 * 后台cron配置
 */
$php_cli = '/daemon/cli.php';

/**************
 * 直播相关开始
 **************/
//直播——直播状态监控
$cron_list['live_status_check'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=livingcheck",
    'start_time' => '2016-06-23 00:00:00',
    'interval' => 60
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
    'interval' => 3
);
//给主播发显示红包定时任务
$cron_list['split_red_bag'] = array(
    'enable' => true,
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
//直播回放移到视频资源并发帖子
$cron_list['live_to_video'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=livetovideo",
    'start_time' => '2016-07-11 00:00:00',
    'interval' => 3
);
//对直播流的帧率检测，发现异常发消息给主播
$cron_list['live_stream_frame_check'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=Livestreamstatuscheck",
    'start_time' => '2016-08-11 00:00:00',
    'interval' => 10
);
//直播计数
$cron_list['live_num_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=Livecountsync",
    'start_time' => '2016-08-11 00:00:00',
    'interval' => 60
);
/*************
 * 直播相关结束
 *************/
 
/*************
 * 帖子相关开始
 *************/
//处理推荐池里的推荐帖子
$cron_list['subject_group_recommend_pool'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=Grouprecommendpool",
    'start_time' => '2016-08-11 00:00:00',
    'interval' => 900
);
//帖子阅读数
$cron_list['view_num_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=Viewnumsync",
    'start_time' => '2016-08-11 00:00:00',
    'interval' => 10
);
/*************
 * 帖子相关结束
 *************/

/*************
 * 口碑相关开始
 *************/
//口碑可变分数修正
$cron_list['koubei_score_update'] = array(
    'enable' => false,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=calrankscore",
    'start_time' => '2016-10-01 01:00:00',
    'interval' => 86400
);
//口碑星级评分修正
$cron_list['koubei_score_correct'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=correctscore",
    'start_time' => '2016-09-30 18:00:00',
    'interval' => 60
);

//视频转码
$cron_list['group_video_transcoding'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=Groupvideo",
    'start_time' => '2016-08-11 00:00:00',
    'interval' => 60
);


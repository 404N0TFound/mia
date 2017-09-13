<?php
/**
 * 后台cron配置
 *
 * 没有限定主机标识的，只能在白名单列表的机器里执行
 * 限定了主机名的，只能在限定的server上面执行
 */
$php_cli = '/daemon/cli.php';

$server_white_list = [
    "nfs_13_14",//脚本机
    "Miya-XM-server",//209测试机
];
$host_check_open = 0;//1打开，0关闭

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
//直播消息推送
$cron_list['live_push_message'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=Livepushmessage",
    'start_time' => '2016-10-12 00:00:00',
    'interval' => 60
);
//直播结束清空，转移配置信息，清空上次直播结束2小时后的
$cron_list['live_clean_setting'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=live --action=Cleanlivesetting",
    'start_time' => '2016-12-6 00:00:00',
    'interval' => 7190
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
//帖子文本语义分析
$cron_list['content_analys'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=contentanalys",
    'start_time' => '2017-03-15 00:00:00',
    'interval' => 1
);
//帖子全量数据导出
$cron_list['subject_data_dump'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectdump full_dump",
    'start_time' => '2017-02-09 00:00:00',
    'interval' => 1
);
//帖子增量数据导出
$cron_list['subject_data_incremental_dump'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectdump incremental_dump",
    'start_time' => '2017-02-09 17:00:00',
    'interval' => 600
);
//帖子前一日数据导出
$cron_list['subject_data_period_dump'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectdump period_dump",
    'start_time' => '2017-09-13 05:00:00',
    'interval' => 86400
);
//帖子全量数据同步
$cron_list['subject_data_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectsync full_dump",
    'start_time' => '2017-02-10 00:10:00',
    'interval' => 86400
);
//帖子增量数据同步
$cron_list['subject_data_incremental_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectsync incremental_dump",
    'start_time' => '2017-02-10 00:15:00',
    'interval' => 600
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

//商品好评率（全量）
$cron_list['item_score_full_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=setitemfeedbackrate full_dump",
    'start_time' => '2017-07-12 02:00:00',
    'interval' => 432000
);

//商品好评率（增量）
$cron_list['item_score_incremental_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=setitemfeedbackrate incremental_dump",
    'start_time' => '2017-07-12 02:00:00',
    'interval' => 86400
);

//发布口碑，首评代金券奖励
$cron_list['koubei_issue_reward'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=issuereward",
    'start_time' => '2017-01-22 18:15:00',
    'interval' => 60
);

//计算商品得分
$cron_list['item_multiple_rank'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=koubei --action=itemmackrelatekoubei",
    'start_time' => '2016-12-23 02:00:00',
    'interval' => 86400
);

//更新活动帖子热度值
$cron_list['active_subject_hotvalue'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=active --action=setactivesubjecthotvalue",
    'start_time' => '2017-03-30 15:00:00',
    'interval' => 3600
);

//图片全量美化
$cron_list['beauty_data_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=imagebeauty full_dump",
    'start_time' => '2017-05-14 14:00:00',
    'interval' => 10
);
//图片增量美化
$cron_list['beauty_data_incremental_sync'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=imagebeauty incremental_dump",
    'start_time' => '2017-05-14 13:55:00',
    'interval' => 5
);

//达人月排行榜更新
$cron_list['user_doozer_month_rank_update'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=user --action=doozerrank pub_month",
    'start_time' => '2017-06-09 01:00:00',
    'interval' => 86400
);
//达人日排行榜更新
$cron_list['user_doozer_day_rank_update'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=user --action=doozerrank pub_day",
    'start_time' => '2017-06-09 01:05:00',
    'interval' => 600
);

/**************
 * 消息相关配置 *
 *************/

//发送消息
$cron_list['send_message'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "nfs_13_14",
);

$cron_list['delay_send_message'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "nfs_13_14",
);
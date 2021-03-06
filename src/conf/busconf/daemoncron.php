<?php
/**
 * 后台cron配置
 */
$php_cli = '/daemon/cli.php';

$server_list = [
    "all" => [
        "Miya-XM-server",//209测试机，所有的脚本都执行
    ],
    "individual" => [
        "nfs_13_14",//脚本机，没有限定主机标识的执行
    ]
];
$host_check_open = 1;//1打开，0关闭

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
//帖子草稿定时发布
$cron_list['subject_clocking_pub'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectdraft subject_clocking_pub",
    'start_time' => '2017-09-25 18:00:00',
    'interval' => 60
);
//帖子异步处理
$cron_list['subject_async'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=subjectsasync",
    'start_time' => '2017-10-25 00:00:00',
    'interval' => 1
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
//帖子图片检查
$cron_list['subject_image_check'] = array(
    'enable' => false,
    'engine' => 'php',
    'cli_args' => "--class=subject --action=imagecheck",
    'interval' => 3
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
/*************
 * 口碑相关结束
 *************/

/*************
 * 用户相关开始
 *************/
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
//马甲年龄信息每日更新
$cron_list['user_majia_baby_birth_incr'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=user --action=majia baby_birth_incr",
    'start_time' => '2017-09-19 00:01:00',
    'interval' => 86400
);
/*************
 * 用户相关结束
 *************/

/**************
 * 消息相关配置  共10+10个*
 *************/

//发送消息
$cron_list['send_message_1'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=1",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_2'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=2",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_3'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=3",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_4'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=4",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_5'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=5",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_6'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=6",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_7'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=7",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_8'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=8",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_9'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=9",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['send_message_10'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=sendmessage --order=10",
    'start_time' => '2017-08-09 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_1'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage --order=1",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_2'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage --order=2",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_3'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage --order=3",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_4'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=4",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_5'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=5",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_6'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=6",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_7'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=7",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_8'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=8",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_9'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=9",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

$cron_list['delay_send_message_10'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=news --action=delaysendmessage  --order=10",
    'start_time' => '2017-09-12 00:00:00',
    'interval' => 1,
    'host' => "miyaquan_54_70",
);

/**************
 * 消息相关配置  end*
 *************/
    
/**
 * 其他脚本配置
 */
//更新活动帖子热度值
$cron_list['active_subject_hotvalue'] = array(
    'enable' => true,
    'engine' => 'php',
    'cli_args' => "--class=active --action=setactivesubjecthotvalue",
    'start_time' => '2017-03-30 15:00:00',
    'interval' => 3600
);

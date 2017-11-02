<?php
//全局key前缀
$basePrefix = 'miagroup_';
//live服务相关key前缀
$liveServicePrefix = $basePrefix . 'live_';
//红包服务相关key前缀
$redBagServicePrefix = $basePrefix . 'redbag_';
//代金券服务相关key前缀
$couponServicePrefix = $basePrefix . 'coupon_';
//头条服务相关key前缀
$headLineServicePrefix = $basePrefix . 'headline_';
//帖子相关key前缀
$subjectServicePrefix = $basePrefix . 'subject_';
//标签相关key前缀
$labelServicePrefix = $basePrefix . 'label_';
//活动相关key前缀
$activeServicePrefix = $basePrefix . 'active_';
//消息相关key前缀
$newsServicePrefix = $basePrefix . 'news:';
//用户相关key前缀
$userServicePrefix = $basePrefix . 'user_';


/**
 * 任务相关rediskey
 */
$taskKey = [
    //到期时间 当天23:59:59
    "member_task" => [
        'key' => "member_task_%d_%d",//member_task_substr($user_id,-1)_$user_id
    ]
];

/**
 * 消息相关rediskey
 */
$newsKey = [
    //特卖消息列表
    "user_news_list_outlets" => [
        'key' => $newsServicePrefix . 'user:outletslist:%d',
        'expire_time' => 86400 * 30,
    ],
    //社交消息列表
    "user_news_list_group" => [
        'key' => $newsServicePrefix . 'user:grouplist:%d',
        'expire_time' => 86400 * 30,
    ],
    //社交消息计数
    "group_count" => [
        'key' => $newsServicePrefix . 'user:groupnum:%d',
        'expire_time' => 86400,
    ],
    //特卖消息计数
    "outlets_count" => [
        'key' => $newsServicePrefix . 'user:outletsnum:%d',
        'expire_time' => 86400,
    ],
    //蜜芽圈互动消息计数
    "group_index_count" => [
        'key' => $newsServicePrefix . 'user:groupindexnum:%d',
        'expire_time' => 86400,
    ],
    /*==========消息5.7相关==========*/
    //待写入消息，redis链表
    "to_write_news" => [
        'key' => $newsServicePrefix . 'towritenews',
        'expire_time' => 86400 * 30
    ],
    "delay_to_write_news" => [
        'key' => $newsServicePrefix . 'delaytowritenews',
        'expire_time' => 86400 * 30
    ],
    /**
     * 消息计数 Hash
     * key如下：
     * "total"
     * getAncestor()获取到的
     * "trade",
     * "plus_active",
     * "plus_interact",
     * "group_active",
     * "group_interact",
     * "activity",
     * "coupon"
     */
    "news_count" => [
        'key' => $newsServicePrefix . 'newscount:%d',
        'expire_time' => 86400
    ],
    //消息首页列表，有序集合
    "news_index" => [
        'key' => $newsServicePrefix . 'newsindex:%d',
        'expire_time' => 86400
    ],
    //子分类列表，%s为类型和uid的组合
    "cate_list" => [
        'key' => $newsServicePrefix . 'catelist:%s',
        'expire_time' => 86400
    ],
    /*==========消息5.7相关end==========*/
    //消息防。重复拉取
    "prevent_pull" => [
        'key' => $newsServicePrefix . 'prevent_pull:%d',
        'expire_time' => 30
    ]
];

/**
 * 直播相关的redisKey
 */
$liveKey = array(
    //在线人数
    'live_audience_online_num' => array( 
        'key' => $liveServicePrefix . 'audience_online_num_%s',
        'expire_time' => 3600,
    ),
    //直播计数变化记录
    'live_count_record' => array(
        'key' => $liveServicePrefix . 'live_count_record',
        'expire_time' => 3600,
    ),
    //已售卖的商品数
    'live_sale_num' => array(
        'key' => $liveServicePrefix . 'sale_num_%s',
        'expire_time' => 3600,
    ),
    //直播推流状态
    'live_stream_status' => array(
        'key' => $liveServicePrefix . 'stream_status_%s',
        'expire_time' => 86400,
    ),
    //直播转换成视频-状态记录
    'live_to_video' => [ //使用String数据结构
        'key' => $liveServicePrefix . 'to_video_%s',
        'expire_time' => 86400 * 30,
    ],
    //待转换成视频的直播列表
    'live_to_video_list' => [ //使用List数据结构
        'key' => $liveServicePrefix . 'live_to_video_list',
        'expire_time' => 86400 * 30,
    ],
    //直播流帧率检测
    'live_stream_frame'=>[
        'key' => $liveServicePrefix . 'live_stream_frame_%s',
        'expire_time' => 60,
    ],
    'live_stream_audio'=>[
        'key' => $liveServicePrefix . 'live_stream_audio_%s',
        'expire_time' => 60,
    ],
    //主播用户
    'live_rong_cloud_user_id' => [ //使用String数据结构
        'key' => $liveServicePrefix . 'rong_cloud_user_id_%s',
        'expire_time' => 86400,
    ],
    //融云用户hash
    'live_rong_cloud_user_hash' => [ //使用Hash数据结构
        'key' => $liveServicePrefix . 'rong_cloud_user_hash_%s',
        'expire_time' => 86400,
    ],
    //金山流信息
    'live_jinshan_stream_info' => [ //使用String数据结构
        'key' => $liveServicePrefix . 'jinshan_stream_info_%s',
        'expire_time' => 86400 * 30,
    ],
    //网宿流信息
    'live_wangsu_stream_info' => [ //使用String数据结构
        'key' => $liveServicePrefix . 'wangsu_stream_info_%s',
        'expire_time' => 86400 * 30,
    ],
    //记录直播流不稳定的次数
    'live_stream_frame_status'=>[
        'key' => $liveServicePrefix . 'stream_frame_status_%s',
        'expire_time' => 300,  
    ],
    'live_stream_audio_status'=>[
        'key' => $liveServicePrefix . 'stream_audio_status_%s',
        'expire_time' => 300,
    ],
);

/**
 * 红包相关的redisKey
 */
$redBagKey = array(
	'splitRedBag' =>array( //使用List数据结构
		'key' => $redBagServicePrefix . 'redbag_%s',
		'expire_time' => 86400 * 30,
	),
    'splitStatus' =>array( //使用String数据结构
        'key' => $redBagServicePrefix . 'split_status_%s',
        'expire_time' => 86400 * 30,
    )
);

/**
 * 蜜豆相关
 */
$miBeanKey = array(
    //记录赠送蜜豆数量的key
    'mibean_give_way' => array(
        'key' => 'mibean_give_%s_%s',
        'expire_time' => 86400
    ),
    
    
);

/**
 * 代金券相关的redisKey
 */
$couponKey = array(
    'sendCoupon' =>array(
        'key' => $couponServicePrefix . 'send_coupon_%s',
        'expire_time' => 86400 * 30,
    ),
    //发送代金券开始时间戳
    'send_coupon_start_time' =>array( //使用String数据结构
        'key' => $couponServicePrefix . 'start_time_%s',
        'expire_time' => 86400,
    )
);

/**
 * 头条相关的rediskey
 */
$headLineKey = array(
    'syncUniqueFlag' =>array( //同步数据唯一校验
        'key' => $headLineServicePrefix . 'sync_unique_flag_%s',
        'expire_time' => 86400,
    ),
);

//帖子相关rediskey
$subjectKey = [
    'subject_read_num' => [//帖子阅读数处理队列，使用List数据结构
        'key' => $subjectServicePrefix . 'read_num',
        'expire_time' => 86400 * 30,
    ],
    'subject_fine_push_num' => [
        'key' => $subjectServicePrefix . 'fine_push_num_%d',
        'expire_time' => 86400 * 30,
    ],
    'subject_update_record' => [//帖子关键数据更新处理队列，使用List数据结构
        'key' => $subjectServicePrefix . 'update_record',
    ],
    'subject_check_resubmit' => [//帖子重复提交标记，使用List数据结构
        'key' => $subjectServicePrefix . 'check_resubmit_%s',
        'expire_time' => 3600,
    ],
    "async_consume" => [//帖子异步操作
        'key' => $subjectServicePrefix ."async_consume",//list
        'expire_time' => 86400 * 30
    ],
];

//用户相关rediskey
$userKey = [
    'user_doozer_rank' => [//达人排行榜，使用SortedSet数据结构
        'key' => $userServicePrefix . 'user_doozer_rank_%s',
        'expire_time' => 86400 * 30,
    ],
    'user_hot_subjects' => [//用户热帖，使用String数据结构
        'key' => $userServicePrefix . 'user_hot_subjects_%s',
        'expire_time' => 86400 * 30,
    ],
    'user_attention_dubious' => [//可疑用户关注限制，使用String数据结构
        'key' => $userServicePrefix . 'attention_dubious_%s',
        'expire_time' => 60,
    ],
];

//标签相关rediskey
$labelKey = [
    'label_subject_read_session' => [//标签页已读标记，使用List数据结构
        'key' => $labelServicePrefix . 'label_subject_read_session_%s_%s_%s',
        'expire_time' => 1800,
    ],
];

//活动相关rediskey
$activeKey = [
    'active_subject_read_session' => [//活动页已读标记，使用List数据结构
        'key' => $activeServicePrefix . 'active_subject_read_session_%s_%s_%s',
        'expire_time' => 1800,
    ],
    'active_subject_user' => [//闯关活动用户id和设备号
        'key' => $activeServicePrefix . 'subject_user_%s_%s',
        'expire_time' => 10800,
    ],
];

//笔记推荐服务相关，string格式，用空格分隔
$recommendSubjectKey = "hot_article_all";//热门文章
$recommendCateKey = "top_cate_hot";//热门分类
$recommendCateSubjectKey = "hot_article_cate_%s";//分类下的热门文章
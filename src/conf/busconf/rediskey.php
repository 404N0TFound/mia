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
    'subject_read_num' => [//使用List数据结构
        'key' => $subjectServicePrefix . 'read_num',
        'expire_time' => 86400 * 30,
    ],
];
<?php
//全局key前缀
$basePrefix = 'miagroup_';
//live服务相关key前缀
$liveServicePrefix = $basePrefix . 'live_';
//红包服务相关key前缀
$redBagServicePrefix = $basePrefix . 'redbag_';
/**
 * 直播相关的redisKey
 */
$liveKey = array(
    //在线人数
    'live_audience_online_num' => array( 
        'key' => $liveServicePrefix . 'audience_online_num_%s',
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
    //记录直播流不稳定的次数
    'live_stream_frame_status'=>[
        'key' => $liveServicePrefix . 'stream_frame_status_%s',
        'expire_time' => 300,  
    ],
    //在线用户系数
    'live_online_users_num'=>[//使用String数据结构
        'key' => $liveServicePrefix . 'online_users_num_%s',
        'expire_time' => 86400,
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


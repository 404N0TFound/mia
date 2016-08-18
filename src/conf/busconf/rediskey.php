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
    //以售卖的商品数
    'live_sale_num' => array(
        'key' => $liveServicePrefix . 'sale_num_%s',
        'expire_time' => 3600,
    ),
    //以售卖的商品数
    'live_stream_status' => array(
        'key' => $liveServicePrefix . 'stream_status_%s',
        'expire_time' => 60,
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
    //收到赞+1蜜豆,以天为周期，每天收到N个赞，最多可得3次蜜豆奖励
    'mibean_receive_praise' => array(
        'key' => 'mibean_receive_praise_%s',
        'expire_time' => 86400
    ),
    //蜜芽圈发帖+3蜜豆（以天为周期，每天晒N单，最多可得3次蜜豆奖励）
    'mibean_publish_pic' => array(
        'key' => 'mibean_publish_pic_%s',
        'expire_time' => 86400
    ),
    //收到别人的评论+1   （以天为周期，每天收到N个别人的评论，最多可得3次蜜豆奖励
    'mibean_receive_comment' => array(
        'key' => 'mibean_receive_comment_%s',
        'expire_time' => 86400
    ),
    // 精品贴+5   被推荐到首页（以周为周期，被推荐到首页N次，最多可得2次蜜豆奖励）
    'mibean_fine_pic' => array(
        'key' => 'mibean_fine_pic_%s',
        'expire_time' => 86400
    ),
    
);


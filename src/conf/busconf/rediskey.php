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
);

/**
 * 红包相关的redisKey
 */
$redBagKey = array(
		'splitRedBag' =>array( //使用List数据结构
			'key' => $redBagServicePrefix . 'redbag_%s',
			'expire_time' => 3600,
		)
);

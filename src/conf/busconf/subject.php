<?php

/**
 * 默认分享信息（subject：帖子；live_by_user：普通用户分享直播；live_by_anchor：主播分享直播）
 */
$defaultShareInfo = array(
		'subject' => array(
				'img_url'   =>'http://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
				'wap_url' => 'http://m.miyabaobei.com/wx/group_detail/%s.html',
				'title'      => '我在蜜芽圈发现一个超有用的帖子，分享给你',
				'desc'      => '超过20万妈妈正在蜜芽圈热聊，快来看看~',
				'extend_text'            => '看白富美妈妈分享的好货',
		),
		'live_by_user'      => array(
				'title'      => '%s正在蜜芽直播',
				'desc'      => '我正在蜜芽观看%s的直播，邀请你一起来看',
				'wap_url'  => 'http://m.miyabaobei.com/wx/room_detail/%s.html',
		),
		'live_by_anchor'      => array(
				'title'      => '我正在蜜芽直播',
				'desc'      => '我正在蜜芽直播，快来一起看',
				'wap_url'  => 'http://m.miyabaobei.com/wx/room_detail/%s.html',
		),
);

/**
 * 帖子站外分享信息格式
 */
$groupShare = array(
    0 => array(
        'share_platform' => 'weixin',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
    1 => array(
        'share_platform' => 'friends',
        'share_title'    => '{|title|}',
        'share_content'  => '{|title|}{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
);
$album = array(
    'h5_url'=>'http://www.mia.com/groupspe/show/%d/%d',
);
$liveRoomTips = "直播房间测试tips";

$liveSetting = array('banners','redbag','share','is_show_gift');

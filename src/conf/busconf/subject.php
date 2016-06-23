<?php

/**
 * 帖子分享默认图
 */
$shareDefaultImage = 'http://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png';

/**
 * 帖子h5 url
 */
$subjectH5Url = 'http://m.miyabaobei.com/wx/group_detail/%s.html';

/**
 * 直播房间房间默认h5 url
 */
$liveH5Url = 'http://m.miyabaobei.com/wx/room_detail/%s.html';

/**
 * 帖子分享默认的desc文案
 */
 $subjectDefaultDesc = '超过20万妈妈正在蜜芽圈热聊，快来看看~';
 
 /**
  * 帖子分享默认的title文案
  */
 $subjectDefaultTitle = '我在蜜芽圈发现一个超有用的帖子，分享给你';
 
 /**
  * 帖子分享默认的扩展文案
  */
 $subjectDefaultExtendText = '看白富美妈妈分享的好货';

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

$liveRoomTips = "直播房间测试tips";

$liveSetting = array('custom','redbag','share','is_show_gift');

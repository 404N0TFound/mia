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
);

/**
 * 帖子站外分享信息格式
 */
$groupShare = array(
    'weixin' => array(
        'share_platform' => 'weixin',
        'share_title'    => '{|title|}',
        'share_content'  => '{|title|}{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
    'friends' => array(
        'share_platform' => 'friends',
        'share_title'    => '{|title|}',
        'share_content'  => '{|title|}{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
);
/**
 * 专栏文章配置
 */
$album = array(
    //h5内嵌页链接
    'h5_url'=>'http://www.mia.com/groupspe/show/%d/%d',
);

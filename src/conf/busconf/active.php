<?php

/**
 * 活动分享配置
 */
$defaultShareInfo = array(
    'active' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.mia.com/wx/group_promotion_share/index/%s',
        'title'      => '发现了一个有意思的活动，分享给你！',
        'desc'      => '%s',
    ),
);

/**
 * 活动站外分享信息格式
 */
$activeShare = array(
    'weixin' => array(
        'share_platform' => 'weixin',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
    ),
    'friends' => array(
        'share_platform' => 'friends',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
    ),
);
//参加活动的文案
$activeTitle = '参加活动';

$xiaoxiaole_init = array(
    'active_prize_init' => '发了%d个口碑,赚了%d蜜豆',
    'active_clock_init' => '已赚%d蜜豆，连续发帖%d天得%d蜜豆，就差%d天啦',
    'active_create_init' => '发%d帖'
);



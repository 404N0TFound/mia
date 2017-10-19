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

/*
 * 消消乐活动配置
 * */
$xiaoxiaole = array(
    // 活动引导配置
    'guide_init' => array(
        'back_color' => '#ffff33',
        'active_regular_link' => '',
        'date_color' => '#ffff33',
    ),
    // 活动用户打卡提示配置
    'user_show_init' => array(
        // 打卡提示
        'mark_notice' => '已赚%d蜜豆，连续发帖%d天得%d蜜豆',
        // 活动日历背景图
        'calendar_image' => array(
            'url' => 'http://img3.imgtn.bdimg.com/it/u=2422152076,2897584653&fm=27&gp=0.jpg',
            'width' => '228',
            'height' => '151',
        ),
        // 打卡奖励配置
        'calendar_prize' => array(
            'three' => 1,
            'seven' => 2,
            'month' => 3,
        ),
    ),
);



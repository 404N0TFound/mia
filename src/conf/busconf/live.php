<?php

/**
 * 默认分享信息（live_by_user：普通用户分享直播；live_by_anchor：主播分享直播）
 */
$liveShareInfo = array(
    'live_by_user'      => array(
        'title'      => '%s正在蜜芽直播',
        'desc'      => '我正在蜜芽观看%s的直播，邀请你一起来看',
        'wap_url'  => 'http://m.mia.com/mialive/live?roomid=%d&liveid=%d',
    ),
    'live_by_anchor'      => array(
        'title'      => '我正在蜜芽直播',
        'desc'      => '我正在蜜芽直播，快来一起看',
        'wap_url'  => 'http://m.mia.com/mialive/live?roomid=%d&liveid=%d',
    ),
);

/**
 * 帖子站外分享信息格式
 */
$liveShare = array(
    'weixin' => array(
        'share_platform' => 'weixin',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
    'friends' => array(
        'share_platform' => 'friends',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
    'qzone' => array(
        'share_platform' => 'qzone',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
    'sinaweibo' => array(
        'share_platform' => 'sinaweibo',
        'share_title'    => '{|title|}',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '{|extend_text|}',
    ),
);

/**
 * 直播房间提示
 */
$liveRoomTips = "主播会不定时发放优惠红包哦，请注意主播的提醒~";

/**
 * 直播设置项
 */
$liveSetting = array('banners','redbag','share','is_show_gift');
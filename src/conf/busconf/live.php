<?php

/**
 * 默认分享信息（live_by_user：普通用户分享直播；live_by_anchor：主播分享直播）
 */
$liveShareInfo = array(
    'live_by_user'      => array(
        'title'      => '%s@你',
        'desc'      => '天啦噜！%s正在蜜芽直播，更有红包等你来领！快上车→',
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
$liveRoomTips = "蜜芽倡导绿色直播，如有吸烟、低俗、引诱、暴露等内容，将被屏蔽账号";

/**
 * 直播设置项
 */
$liveSetting = array('banners','redbag','share','is_show_gift','is_show_playback','title','source');

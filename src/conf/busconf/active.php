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

// 活动下发蜜豆服务类型
$activeBeanType = 'active_add';

/*
 * 消消乐活动配置
 * */
$xiaoxiaole = array(
    // 活动标识
    'active_type' => 'xiaoxiaole',
    // tab 展示数量
    'tab_count' => 5,
    // 活动引导配置
    'guide_init' => array(
        'active_regular_link' => 'https://m.mia.com/special/module/index/23578/app/',
        'date_color' => '#FFE63F',
        'back_color' => '#8ED6FF',
    ),
    // 活动用户打卡提示配置
    'user_show_init' => array(
        // 打卡提示
        'mark_notice' => '连续发帖%d天得%s蜜豆',
        // 活动日历背景图
        'calendar_image' => array(
            'url' => 'https://img05.miyabaobei.com/d1/p5/2017/10/23/64/7a/647a6902d9ce9d8d53c0ca8c8df89a4c435801124.png',
            'width' => 928,
            'height' => 663,
        ),
        // 是否是首贴标识
        'is_first_pub' => 1,
        'no_first_pub' => 0,
        // 首贴提示文案
        'first_pub_notice' => '还没发帖呐，发贴就奖10蜜豆~',
        // 全勤打卡文案
        'fullwork_notice' => '本月全勤打卡，已获得%d蜜豆',
    ),
    // 奖品
    'active_issue_prize' => array(
        //奖品类型
        'prize_type' => array(
            'sign' => 'sign_day',
            'zero' => 'zero_koubei',
            'every' => 'every_pub',
            'other' => 'other'
        ),
        'prize_desc_color' => '#2A69B4',
        'prize_back_color' => '#64C6FF',
    ),
    // 活动tab商品奖励文案
    'active_item_prize' => array(
        'prize_word' => '消灭它，得%d蜜豆'
    ),
    // 排行榜文案
    'active_user_rank' => array(
        'rank_desc' => '哇塞~ %s截至到现在，这些蜜粉发图量最多呦~',
        'rank_desc_color' => '',
        'achievement_desc' => '%s日起已发布了',
        'achievement_desc_subject' => '%s条口碑',
        'rank_back_color' => '#64C6FF',
    ),
    // 活动帖子状态审核
    'active_subject_qualified' => array(
        'audit_failed' => -1,
        'audit_pass' => 1,
    ),
    // 发帖明细
    'active_subject_detail' => array(
            'prize_bean' => '%d蜜豆',
    ),
    // 活动发帖对应0贴奖文案
    'active_no_zero_desc' => '0口碑奖的%d蜜豆已经被抢走了,确认要发布吗？',
);



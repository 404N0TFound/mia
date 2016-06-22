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
 * 帖子站外分享信息格式
 */
$groupShare = array(
    //0 => array(
    //        'share_platform' => 'qzone',
    //        'share_title'    => '{|title|}',
    //        'share_content'  => '{|image_content|}',
    //        'share_img_url'  => '{|image_url|}',
    //        'share_mia_url'  => '{|wap_url|}',
    //        'extend_text'   => '看白富美妈妈分享的好货',
    //    ),
    //1 => array(
    //        'share_platform' => 'sinaweibo',
    //        'share_title'    => '',
    //        'share_content'  => '{|desc|}',
    //        'share_img_url'  => '{|image_url|}',
    //        'share_content'  => '{|image_content|}',
    //        'share_img_url'  => '{|image_url|}',
    //        'share_mia_url'  => '',
    //        'extend_text'   => '看白富美妈妈分享的好货',
    //    ),
    0 => array(
        'share_platform' => 'weixin',
        'share_title'    => '我在蜜芽圈发现一个超有用的帖子，分享给你',
        'share_content'  => '{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '看白富美妈妈分享的好货',
    ),
    1 => array(
        'share_platform' => 'friends',
        'share_title'    => '我在蜜芽圈发现一个超有用的帖子，分享给你',
        'share_content'  => '{|title|}{|desc|}',
        'share_img_url'  => '{|image_url|}',
        'share_mia_url'  => '{|wap_url|}',
        'extend_text'   => '看白富美妈妈分享的好货',
    ),
);

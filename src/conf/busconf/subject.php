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
    'album' => array(
        'img_url'   =>'http://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'http://m.mia.com/headline/detail/%d/%d/1',
        'title'      => '我在蜜芽圈发现一个超有用的专栏，分享给你',
        'desc'      => '超过20万妈妈正在蜜芽圈热聊，快来看看~',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'video' => array(
        'img_url'   =>'http://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'http://m.miyabaobei.com/wx/group_detail/%s.html',
        'title'      => '我在育儿头条发现一个超有用的视频，分享给你',
        'desc'      => '育儿头条，发现有用的育儿知识',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'label' => array(
        'img_url'   =>'http://o6ov54mbs.bkt.clouddn.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'http://m.mia.com/wx/group/grouplable/%s.html',
        'title'      => '蜜芽圈',
        'desc'      => '妈妈们正在热聊#%s~#，你也来看看吧~',
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
        'share_content'  => '{|desc|}',
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
    'h5_url'=>'http://m.mia.com/headline/detail/%d/%d',
);

/**
 * 帖子来源
 */
 $source = array(
     'default'  => 1, //默认
     'koubei'   => 2, //口碑
     'headline' => 3, //头条
 );
 
 /**
  * 帖子加水印的图片域名
  */
 $img_watermark_url = 'http://img05.miyabaobei.com/';
 
 

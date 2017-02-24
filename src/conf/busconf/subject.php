<?php

/**
 * 默认分享信息（subject：帖子；live_by_user：普通用户分享直播；live_by_anchor：主播分享直播）
 */
$defaultShareInfo = array(
    'subject' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/wx/group_detail/%s.html',
        'title'      => '我在蜜芽圈发现一个超有用的帖子，分享给你',
        'desc'      => '超过20万妈妈正在蜜芽圈热聊，快来看看~',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'album' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/headline/detail/%d/%d/1',
        'title'      => '我在蜜芽圈发现一个超有用的专栏，分享给你',
        'desc'      => '超过20万妈妈正在蜜芽圈热聊，快来看看~',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'video' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/wx/group_detail/%s.html',
        'title'      => '我在育儿头条发现一个超有用的视频，分享给你',
        'desc'      => '育儿头条，发现有用的育儿知识',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'label' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/wx/group/grouplable/%s.html',
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
    'h5_url'=>'https://m.miyabaobei.com/headline/detail/%d/%d',
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
  * 帖子ext_info字段
  */
 $extinfo_field = array(
     'koubei',
     'image',
 );
 
 /**
  * 帖子加水印的图片域名
  */
 $img_watermark_url = 'https://img05.miyabaobei.com';
 
 $img_format = array(
     'subject' => array(
         'watermark' => array(
             'width' => 640,
             'height' => 640,
             'suffix' => '.jpg',
             'file_type' => '@style@watermark640new',
             'limit_width' => true,
             'limit_height' => false,
         ),
         'small' => array(
             'width' => 320,
             'height' => 320,
             'suffix' => '.jpg',
             'file_type' => '@style@koubeilist',
             'limit_width' => true,
             'limit_height' => true,
         ),
         'koubeismall' => array(
             'width' => 320,
             'suffix' => '.jpg',
             'file_type' => '@style@koubeismall',
             'limit_width' => true,
             'limit_height' => false,
         ),
     ),
 );

$group_fixed_tab_first = [
    [
        'name' => '发现',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 1,
    ],
    [
        'name' => '关注',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 2,
    ]
];

$group_fixed_tab_last = [
    [
        'name' => '育儿',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 3,
    ]
];

$group_index_operation_tab = [
    [
        'name' => '纸尿裤',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 10,
    ],
    [
        'name' => '宝宝湿巾',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 11,
    ],
    [
        'name' => '连身衣/爬服',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 12,
    ]
];

$recommendSubjectKey = "hot_article_all";
$recommendCateKey = "top_cate_hot";
$recommendCateSubjectKey = "hot_article_cate_%s";
$operate_note_fields = array(
    'subject',
    'doozer',
    'link',
);

$operate_note_ext_fields = array(
    'title',
    'desc',
    'cover_image',
    'url',
);



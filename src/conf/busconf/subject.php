<?php

/**
 * 默认分享信息（subject：帖子；live_by_user：普通用户分享直播；live_by_anchor：主播分享直播来；issue_subject 发布帖子）
 */
$defaultShareInfo = array(
    'subject' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/wx/group_detail/%s.html',
        'title'      => '我在蜜芽圈发现一个超有用的帖子，分享给你',
        'desc'      => '超过20万妈妈正在蜜芽圈热聊，快来看看~',
        'extend_text'            => '看白富美妈妈分享的好货',
    ),
    'issue_subject' => array(
        'img_url'   =>'https://image1.miyabaobei.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png',
        'wap_url' => 'https://m.miyabaobei.com/wx/group_detail/%s.html',
        'title'      => '我刚刚在蜜芽圈发布了帖子，快来看看吧',
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
    'sinaweibo' => array(
        'share_platform' => 'sinaweibo',
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
     'editor'   => 4, //编辑发布
     'material' => 5, //plus用户发布
 );
 
 /**
  * 帖子状态
  */
 $status = array(
     'user_delete'   => 0,   //用户已删除
     'shield'        => -1,  //管理员屏蔽
     'normal'        => 1,   //正常状态
     'transcoding'   => 2,   //视频转码中
     'koubei_hidden' => 3,   //口碑默认好评不展示
     'to_audit'      => 4,   //待审核帖子
     'audit_failed'  => 5,   //审核不通过帖子
 );
 
 /**
  * 帖子ext_info字段
  */
 $extinfo_field = array(
     'koubei',
     'image',
     'cover_image',
     'selection_label',
     'selection',
     'user_period',
     'is_blog',
     'index_cover',
     'is_material',
 );
 
 /**
  * 帖子加水印的图片域名
  */
 $img_watermark_url = 'https://img05.miyabaobei.com';
 
 $img_format = array(
     'subject' => array(
         'normal' => array(
             'file_type' => '@style@normalcompress',
         ),
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
//蜜芽圈首页，起始固定的2个tab
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
    ],
    [
        'name' => '达人',
        'url' => '',
        'type' => 'miagroup_daren',
        'extend_id' => 4,
    ]
];
//蜜芽圈首页，最后一个固定的tab，育儿
$group_fixed_tab_last = [
    [
        'name' => '育儿经',
        'url' => '',
        'type' => 'miagroup',
        'extend_id' => 3,
    ]
];
//蜜芽圈首页，三个配置tab位，暂时不需要了
//$group_index_operation_tab = [
//    [
//        'name' => '屁屁护理',
//        'url' => '',
//        'type' => 'miagroup',
//        'extend_id' => 1003,
//    ],
//    [
//        'name' => '喂养用品',
//        'url' => '',
//        'type' => 'miagroup',
//        'extend_id' => 1005,
//    ],
//    [
//        'name' => '玩具乐器',
//        'url' => '',
//        'type' => 'miagroup',
//        'extend_id' => 1004,
//    ]
//];

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

/**
 * 帖子数据导出屏蔽账号
 * 敏感词验证，不需要验证的ID
 */
$dump_exclude_uids = array(
    "15854327", "3782852","7007613", "13826010", "13608337", "13631424", "13441186", 
    "5212356", "13899814", "13450291", "13384038", "13570947", "1029527", "12926904", 
    "13383275", "6935216", "5725877", "13400758", "13107805", "13760923", "6979953", 
    "7025374", "14528103", "7026097", "13652607", "13569874", "13676888", "7011960", 
    "13383946", "13785068", "13740079", "13584453", "3374586", "6896243", "13574455", 
    "13391461", "3773992", "11995291", "13804451", "13642782", "2898942", "13426326", 
    "5764855", "4509239", "13529491", "7095167", "13584348", "16074529", "1029527", 
    "5212356", "2865827", "13870502", "13784585", "2649269", "6979953", "1026069"
);

//一级分类
$first_level = [
    101 => "选奶粉",
    102 => "买辅食",
    103 => "爱干净",
    104 => "开心玩",
    105 => "爱出行",
    106 => "爱吃饭",
    107 => "爱学习",
    108 => "睡香香",
    109 => "轻松孕",
    110 => "靓孕妈",
    111 => "巧主妇",
    112 => "PP护理",
    113 => "俏lady",
    114 => "母乳mami",
    115 => "潮baby",
    116 => "亲子服务",
    117 => "淘全球"
];
//二级分类
$second_level = [
    "奶粉专区"=>"选奶粉",
    "宝宝食品"=>"买辅食",
    "宝宝营养"=>"买辅食",
    "宝宝洗护"=>"爱干净",
    "宝宝用品"=>"爱干净",
    "游乐"=>"开心玩",
    "玩具乐器"=>"开心玩",
    "出行装备"=>"爱出行",
    "喂养用品"=>"爱吃饭",
    "图书绘本"=>"爱学习",
    "宝宝寝具"=>"睡香香",
    "孕产医疗"=>"轻松孕",
    "孕产用品"=>"轻松孕",
    "孕期护肤"=>"靓孕妈",
    "家居生活"=>"巧主妇",
    "食品"=>"巧主妇",
    "营养健康"=>"巧主妇",
    "清洁消毒"=>"巧主妇",
    "屁屁护理"=>"PP护理",
    "彩妆"=>"俏lady",
    "身体护理"=>"俏lady",
    "面部护肤"=>"俏lady",
    "母乳喂养"=>"母乳mami",
    "童装童鞋"=>"潮baby",
    "摄影"=>"亲子服务",
    "早教游泳"=>"亲子服务",
    "亲子服务"=>"亲子服务",
    "亲子旅游"=>"亲子服务",
    "门票"=>"亲子服务",
    "演出展览"=>"亲子服务",
    "全球代购"=>"淘全球"
];
//一二级对应关系
$level = [
    "选奶粉" => ["奶粉专区"],
    "买辅食" => ["宝宝食品", "宝宝营养"],
    "爱干净" => ["宝宝洗护", "宝宝用品", "清洁消毒"],
    "开心玩" => ["游乐", "玩具乐器"],
    "爱出行" => ["出行装备"],
    "爱吃饭" => ["喂养用品"],
    "爱学习" => ["图书绘本"],
    "睡香香" => ["宝宝寝具"],
    "轻松孕" => ["孕产医疗", "孕产用品"],
    "靓孕妈" => ["孕期护肤"],
    "巧主妇" => ["家居生活", "食品", "营养健康"],
    "PP护理" => ["屁屁护理"],
    "俏lady" => ["彩妆", "身体护理", "面部护肤"],
    "母乳mami" => ["母乳喂养"],
    "潮baby" => ["童装童鞋"],
    "亲子服务" => ["摄影", "早教游泳", "亲子服务", "亲子旅游", "门票", "演出展览"],
    "淘全球" => ["全球代购"]
];


/**
* 育儿相关标签
* 3194  花式晒娃
* 4304  育儿手记
* 4355  夏の辅食
* 3405  我的顺产经
* 3180  家有二宝
* 4272  致敬奶爸
* 732   吃货
* 3345	下厨房
* 3504	我的母乳喂养
* 3322	妈妈互帮
* 4555	宝宝发烧
* 3683  专家来了
*/
$yuer_label_ids = [4304, 3405, 3180, 4272, 3345, 3504, 3322, 4555, 3683];
$yuer_labels = ["育儿手记", "我的顺产经", "家有二宝", "致敬奶爸", "下厨房", "我的母乳喂养", "妈妈互帮", "宝宝发烧", "专家来了"];


/*
 * 发帖初始化信息
 * */
$subject_issue = array(
    'issue' => array(
        'issue_img' => 'http://image1.miyabaobei.com/image/2017/04/13/32aec4563186419a739d4576b167f70da.jpg',
        'issue_img_width' => 750,
        'issue_img_height' => 100,
        'issue_reward' => '怎么晒？正确晒单姿势看这里-->',
        'issue_skip_url' => 'https://m.mia.com/special/module/index/17840/app/'
    ),
);


/*
 * 蜜芽圈口碑跳转映射
 */
$miagroup_cate_mapping = array(
    'default_title' => '蜜芽圈',
    'skip_url' => 'miyabaobei://group_channel?',
    'source' => 'from_url'
);
<?php

/**
 * 蜜芽官号uid
 */
$miaTuUid = '3782852';

/**
 * 蜜芽客服uid
 */
$miaKefuUid = '13638396';

/**
 * 蜜芽小天使
 */
$miaAngelUid = '1026069';

$miaTuIcon = 'https://img03.miyabaobei.com/d1/p5/2016/11/11/b7/8a/b78a0759965feae977bfa1f6da0cf2d5594917861.png';

/**
 * 付费发帖用户role_id
 */
$paidUserGroup = 20;

/**
 * 用户默认头像
 */

$defaultIcon = 'https://image1.miyabaobei.com/image/2016/11/14/c6d0a5bf6b32af8dd5e77b3af0297bb5.png';

/**
 * 商家默认头像
 */
$defaultSupplierIcon = 'https://image1.miyabaobei.com/image/2016/11/18/c6d0a5bf6b32af8dd5e77b3af0297bb5.png';

/**
 * 发布计数屏蔽
 */
$largePublishCountUser = array(13704137);

/**
 * 粉丝计数屏蔽
 */
$largeFansCountUser = array(3782852); 

/**
 * 用户分类
 */
$userCategory = array(
    0 =>array(
        'id' => 1,
        'name' => 'doozer',
        'cn_name' => '达人',
        'sub_cate' => array(
            0 =>array('id' => 1, 'name' => '专家'),
        ),
    ),
//     1 => array(
//         'id' => 1,
//         'name' => 'majia',
//         'cn_name' => '马甲',
//         'sub_cate' => array(
//             0 => array('id' => 1, 'name' => '育儿头条'),
//         ),
//     ),
    1 =>array(
        'id' => 1,
        'name' => 'company',
        'cn_name' => '商家/店铺',
        'sub_cate' => array(
        ),
    ),
    2 =>array(
        'id' => 1,
        'name' => 'official_cert',
        'cn_name' => '官方认证',
        'sub_cate' => array(
        ),
    ),
);

/**
 * 用户状态
 */
$user_status = array(
    '家有宝贝' => 1,
    '怀孕中'   => 2,
    '正在备孕' => 3,
    '我是少女' => 4,
    '我是男生' => 5
);

/**
 * 孕期二级菜单
 */
$pregnancy_period = array(
    '孕早期' => array(
        '孕1周' => array('start' => '+39 week', 'end' => '+40 week'),
        '孕2周' => array('start' => '+38 week', 'end' => '+39 week'),
        '孕3周' => array('start' => '+37 week', 'end' => '+38 week'),
        '孕4周' => array('start' => '+36 week', 'end' => '+37 week'),
        '孕5周' => array('start' => '+35 week', 'end' => '+36 week'),
        '孕6周' => array('start' => '+34 week', 'end' => '+35 week'),
        '孕7周' => array('start' => '+33 week', 'end' => '+34 week'),
        '孕8周' => array('start' => '+32 week', 'end' => '+33 week'),
        '孕9周' => array('start' => '+31 week', 'end' => '+32 week'),
        '孕10周' => array('start' => '+30 week', 'end' => '+31 week'),
        '孕11周' => array('start' => '+29 week', 'end' => '+30 week'),
        '孕12周' => array('start' => '+28 week', 'end' => '+29 week'),
    ),
    '孕中期' => array(
        '孕13周' => array('start' => '+27 week', 'end' => '+28 week'),
        '孕14周' => array('start' => '+26 week', 'end' => '+27 week'),
        '孕15周' => array('start' => '+25 week', 'end' => '+26 week'),
        '孕16周' => array('start' => '+24 week', 'end' => '+25 week'),
        '孕17周' => array('start' => '+23 week', 'end' => '+24 week'),
        '孕18周' => array('start' => '+22 week', 'end' => '+23 week'),
        '孕19周' => array('start' => '+21 week', 'end' => '+22 week'),
        '孕20周' => array('start' => '+20 week', 'end' => '+21 week'),
        '孕21周' => array('start' => '+19 week', 'end' => '+20 week'),
        '孕22周' => array('start' => '+18 week', 'end' => '+19 week'),
        '孕23周' => array('start' => '+17 week', 'end' => '+18 week'),
        '孕24周' => array('start' => '+16 week', 'end' => '+17 week'),
        '孕25周' => array('start' => '+15 week', 'end' => '+16 week'),
        '孕26周' => array('start' => '+14 week', 'end' => '+15 week'),
        '孕27周' => array('start' => '+13 week', 'end' => '+14 week'),
    ),
    '孕晚期' => array(
        '孕28周' => array('start' => '+12 week', 'end' => '+13 week'),
        '孕29周' => array('start' => '+11 week', 'end' => '+12 week'),
        '孕30周' => array('start' => '+10 week', 'end' => '+11 week'),
        '孕31周' => array('start' => '+9 week', 'end' => '+10 week'),
        '孕32周' => array('start' => '+8 week', 'end' => '+9 week'),
        '孕33周' => array('start' => '+7 week', 'end' => '+8 week'),
        '孕34周' => array('start' => '+6 week', 'end' => '+7 week'),
        '孕35周' => array('start' => '+5 week', 'end' => '+6 week'),
        '孕36周' => array('start' => '+4 week', 'end' => '+5 week'),
        '孕37周' => array('start' => '+3 week', 'end' => '+4 week'),
        '孕38周' => array('start' => '+2 week', 'end' => '+3 week'),
        '孕39周' => array('start' => '+1 week', 'end' => '+2 week'),
        '孕39周' => array('start' => 'now', 'end' => '+1 week'),
    ),
);
    
/**
 * 宝宝年龄二级菜单
 */
$child_period = array(
    '新生儿(月子期)' => array(
        '1月龄' => array('start' => '-1 month', 'end' => 'now'),
    ),
    '2-3月龄(哺乳/产后恢复)' => array(
        '2月龄' => array('start' => '-2 month', 'end' => '-1 month'),
        '3月龄' => array('start' => '-3 month', 'end' => '-2 month'),
    ),
    '4-6月龄(哺乳/产后恢复)' => array(
        '4月龄' => array('start' => '-4 month', 'end' => '-3 month'),
        '5月龄' => array('start' => '-5 month', 'end' => '-4 month'),
        '6月龄' => array('start' => '-6 month', 'end' => '-5 month'),
    ),
    '7-12月龄(哺乳/产后恢复)' => array(
        '7月龄' => array('start' => '-7 month', 'end' => '-6 month'),
        '8月龄' => array('start' => '-8 month', 'end' => '-7 month'),
        '9月龄' => array('start' => '-9 month', 'end' => '-8 month'),
        '10月龄' => array('start' => '-10 month', 'end' => '-9 month'),
        '11月龄' => array('start' => '-11 month', 'end' => '-10 month'),
        '12月龄' => array('start' => '-12 month', 'end' => '-11 month'),
    ),
    '1-2岁(断奶/身材重塑)' => array(
        '1岁1月' => array('start' => '-1 year -1 month', 'end' => '-1 year'),
        '1岁2月' => array('start' => '-1 year -2 month', 'end' => '-1 year -1 month'),
        '1岁3月' => array('start' => '-1 year -3 month', 'end' => '-1 year -2 month'),
        '1岁4月' => array('start' => '-1 year -4 month', 'end' => '-1 year -3 month'),
        '1岁5月' => array('start' => '-1 year -5 month', 'end' => '-1 year -4 month'),
        '1岁6月' => array('start' => '-1 year -6 month', 'end' => '-1 year -5 month'),
        '1岁7月' => array('start' => '-1 year -7 month', 'end' => '-1 year -6 month'),
        '1岁8月' => array('start' => '-1 year -8 month', 'end' => '-1 year -7 month'),
        '1岁9月' => array('start' => '-1 year -9 month', 'end' => '-1 year -8 month'),
        '1岁10月' => array('start' => '-1 year -10 month', 'end' => '-1 year -9 month'),
        '1岁11月' => array('start' => '-1 year -11 month', 'end' => '-1 year -10 month'),
        '1岁12月' => array('start' => '-1 year -12 month', 'end' => '-1 year -11 month'),
    ),
    '2-3岁(断奶/身材重塑)' => array(
        '2岁1月' => array('start' => '-2 year -1 month', 'end' => '-2 year'),
        '2岁2月' => array('start' => '-2 year -2 month', 'end' => '-2 year -1 month'),
        '2岁3月' => array('start' => '-2 year -3 month', 'end' => '-2 year -2 month'),
        '2岁4月' => array('start' => '-2 year -4 month', 'end' => '-2 year -3 month'),
        '2岁5月' => array('start' => '-2 year -5 month', 'end' => '-2 year -4 month'),
        '2岁6月' => array('start' => '-2 year -6 month', 'end' => '-2 year -5 month'),
        '2岁7月' => array('start' => '-2 year -7 month', 'end' => '-2 year -6 month'),
        '2岁8月' => array('start' => '-2 year -8 month', 'end' => '-2 year -7 month'),
        '2岁9月' => array('start' => '-2 year -9 month', 'end' => '-2 year -8 month'),
        '2岁10月' => array('start' => '-2 year -10 month', 'end' => '-2 year -9 month'),
        '2岁11月' => array('start' => '-2 year -11 month', 'end' => '-2 year -10 month'),
        '2岁12月' => array('start' => '-2 year -12 month', 'end' => '-2 year -11 month'),
    ),
    '3-4岁' => array(
        '3-4岁' => array('start' => '-3 year', 'end' => '-2 year'),
    ),
    '4-5岁' => array(
        '4-5岁' => array('start' => '-4 year', 'end' => '-3 year'),
    ),
    /*
    '5-6岁' => array(
        '5-6岁' => array('start' => '-5 year', 'end' => '-4 year'),
    ),
    */
);

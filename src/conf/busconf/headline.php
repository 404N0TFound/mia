<?php

/**
 * 锁定栏目
 */
$lockedChannel = array(
    'recommend' => array(
        'id' => 1,
        'title' => '推荐',
    ),
    'attention' => array(
        'id' => 2,
        'title' => '订阅',
    ),
    'homepage' => array(
        'id' => 3,
        'title' => '首页轮播',
        'shield' => 1
    ),
);

/**
 * 特殊栏目样式
 */
$channelStyle = array(
    'default' => 'common', //通用样式
    '2' => 'follow', //订阅页样式
    '4' => 'video' //视频页样式
);

/**
 * 客户端头条展示类型与服务端类型的映射 
 */
$clientServerMapping = array(
    'album' => 'album',
    'video' => 'video',
    'live'  => 'live',
    'headline_topic' => 'topic',
    'banner' => 'banner',
    'promotion' => 'promotion',
    'subject' => 'subject',
    'urecom' => 'urecom'
);

//推荐头条专家ID
$expert = [
    16041851,
    15857254,
    13864590,
    13864591,
    13864592,
    13864593,
    13864594,
    13864595,
    13864596,
    13864597,
    13864598,
    13864599,
];

/**
 * 头条banner降级开关
 */
$headLineSwitch = array(
    'default_switch' => 1, //默认开启
);
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
 * 客户端头条展示类型与服务端类型的映射 
 */
$clientServerMapping = array(
    'album' => 'album',
    'video' => 'video',
    'live'  => 'live',
    'headline_topic' => 'topic',
);

//推荐头条专家ID
$expert = [
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

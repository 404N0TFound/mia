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
    'album' => 'subject',
    'video' => 'subject',
    'live'  => 'live',
    'headline_topic' => 'topic',
);

//推荐头条专家ID
$expert = [
    1509160,
    1508546,
    1508925,
    1072032,
    1509174,
    1509179,
    2109015,
    1145319,
    1145064,
    1145078,
];

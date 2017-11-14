<?php

/**
 * 头像素材状态，0未使用 1已编辑 2已生成用户
 */
$avatar_material_status = [
    'unused' => 0,
    'used'   => 1,
    'create_user' => 2
];

/**
 * 文本素材状态，0未使用 1已编辑
 */
$text_material_status = [
    'unused' => 0,
    'used' => 1
];

/**
 * 帖子素材状态，0待编辑 1列表锁定 2编辑中 3已编辑
 */
$subject_material_status = [
    'unused' => 0,
    'locked' => 1,
    'editing'=> 2,
    'used'   => 3
];

/**
 * 知识素材状态，0待编辑 1列表锁定 2编辑中 3已生成帖子
 */
$knowledge_material_status = [
    'unused' => 0,
    'locked' => 1,
    'editing'=> 2,
    'used'   => 3
];

/**
 * 运营帖子状态，1编辑完成 2编辑中 3已生成帖子
 */
$editor_subject_status = [
    'edited' => 1,
    'editing'   => 2,
    'create_subject' => 3
];
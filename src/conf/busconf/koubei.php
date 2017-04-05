<?php

/**
 * 默认好评文案
 */
$autoEvaluateText = '好评！';

/**
 * 普通标签ID
 */
$normalTagArr = array(
    1 => "全部",
    2 => "有图",
    3 => "好评",
);

/**
 * 印象标签打开/关闭   1/0
 */
$tagOpen = 1;

/**
 * 商品口碑转换映射
 */
$itemKoubeiTransfer = array(
    '1688408' => '1021382',
    '1688407' => '1132948',
    '1688406' => '1021380',
);

/**
 * 甄选商品口碑印象标签
 */
$selection_quality_labels_A = array(
    'dimension' => '品质',
    'tag_info' => array(
        array(
            'tag_name' => '很好吃',
            'positive' => 1,
        ),
        array(
            'tag_name' => '超难吃',
            'positive' => 2,
        ),
    ),
);

$selection_quality_labels_B = array(
    'dimension' => '品质',
    'tag_info' => array(
        array(
            'tag_name' => '面料好',
            'positive' => 1,
        ),
        array(
            'tag_name' => '超级差',
            'positive' => 2,
        ),
    ),
);

/**
 * 甄选商品口碑印象标签
 */
$selection_price_labels = array(
    'dimension' => '价格',
    'tag_info' => array(
        array(
            'tag_name' => '物超所值',
            'positive' => 1,
        ),
        array(
            'tag_name' => '性价比低',
            'positive' => 2,
        ),
    ),
);

/**
 * 甄选商品口碑印象标签
 */
$selection_exper_labels = array(
    'dimension' => '体验',
    'tag_info' => array(
        array(
            'tag_name' => '服务好',
            'positive' => 1,
        ),
        array(
            'tag_name' => '售后差',
            'positive' => 2,
        ),
    ),
);

/**
 * 甄选商品分类标签对应关系
 */
$selection_cate = array(
    352 => 'A',
);

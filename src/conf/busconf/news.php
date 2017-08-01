<?php
/**
 * 消息类型，目前用到的类型 2017/5/23
 * ------社交------
 *
 * 'img_comment'  图片评论
 * 'img_like'  图片赞
 * 'follow'  关注
 * 'add_fine' 帖子加精 v5.4
 * 'group_coupon'  优惠券
 * 'group_custom'  自定义 （跳转为自定义链接）
 *
 * ------特卖------
 *
 * 'order'  订单
 * 'coupon'
 * 'custom'  自定义 （跳转为自定义链接）
 */

//特卖
$outlets = ['order', 'coupon', 'custom'];

//社交
$group = ['img_comment', 'img_like', 'follow', 'add_fine', 'group_coupon', 'group_custom'];

//蜜芽圈首页社交
$group_index = ['img_comment', 'img_like', 'follow'];

//所有类型
$all_type = ['order', 'coupon', 'custom', 'img_comment', 'img_like', 'follow', 'add_fine', 'group_coupon', 'group_custom'];


/*==============================5.7消息==============================*/

/**
 * 消息目前分三级，分类列表可以展示一二级的分类
 */

/**
 * 5大类
 * trade-交易物流；
 * plus-会员plus；
 * group-蜜芽圈（活动，动态）；
 * activity-蜜芽活动；
 * property-我的资产；
 */
$newsType = ['trade', 'plus', 'group', 'activity', 'coupon'];

/**
 * 消息类
 * 最低层级的上一级，用于展示分类
 * group_custom
 * coupon
 * img_comment
 * add_fine
 * img_like
 * custom
 * follow
 * order
 */
$layer = [
    "trade" => [
        "order",//旧类型，只有文字content
        "order_unpay",
        "order_cancel",
        "order_send_out",
        "order_delivery",
        "return_audit_pass",
        "return_audit_refuse",
        "return_overdue",
        "refund_success",
        "refund_fail"
    ],
    "plus" => [
        "plus_active" => [
            "plus_active"
        ],
        "plus_interact" => [
            "plus_new_members",
            "plus_new_fans",
            "plus_get_commission"
        ]
    ],
    "group" => [
        "group_active" => [
            "group_custom"
        ],
        "group_interact" => [
            "img_comment",
            "add_fine",
            "img_like",
            "follow",
            "new_subject"
        ]
    ],
    "activity" => [
        "custom"
    ],
    "coupon" => [
        "coupon",//旧类型
        "coupon_receive",
        "coupon_overdue",
        "redbag_receive",
        "redbag_overdue"
    ]
];

//会员Plus站内信：news_type类型
$plus_news_type = [
    "plus_new_members",
    "plus_new_fans",
    "plus_get_commission"
];

//交易相关站内信：order_status订单状态
$trade_order_status = [
    "order_unpay",
    "order_cancel",
    "order_send_out",
    "order_delivery",
    "return_audit_pass",
    "return_audit_refuse",
    "return_overdue",
    "refund_success",
    "refund_fail"
];

//用户资产站内信：news_type类型
$coupon_news_type = [
    "coupon_receive",
    "coupon_overdue",
    "redbag_receive",
    "redbag_overdue"
];
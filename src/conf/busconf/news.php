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
 * 注意：名称不要重复！！！！！
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
        "order_received",
        "order_auto_confirm",
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
    "property" => [
        "coupon",//旧类型
        "coupon_receive",
        "coupon_overdue",
        "redbag_receive",
        "redbag_overdue"
    ]
];

//退款图标
$refund_image = "https://img.miyabaobei.com/d1/p5/2017/08/09/57/64/57646d7a5c9be7770ddfc04fbaf86314498159953.png";

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
    "order_received",
    "order_auto_confirm",
    "return_audit_pass",
    "return_audit_refuse",
    "return_overdue",
    "refund_success",
    "refund_fail"
];

//用户资产站内信：news_type类型
$property_news_type = [
    "coupon_receive",
    "coupon_overdue",
    "redbag_receive",
    "redbag_overdue"
];

//消息类型和模板的对应关系
$template_news_type = [
    //站内信子分类模板，展示分类用的
    "news_sub_category_template" => [
        "trade",
        "plus_active",
        "plus_interact",
        "group_active",
        "group_interact",
        "activity",
        "property"
    ],
    //以下是消息列表用的，最低级分类模板
    //站内信图文模板
    "news_text_pic_template" => [
        "order",
        "order_unpay",
        "order_cancel",
        "order_send_out",
        "order_delivery",
        "order_received",
        "order_auto_confirm",
        "return_audit_pass",
        "return_audit_refuse",
        "return_overdue",
        "refund_success",
        "refund_fail",
        "plus_new_members",
        "plus_new_fans",
        "plus_get_commission",
        "coupon",//旧类型
        "coupon_receive",
        "coupon_overdue",
        "redbag_receive",
        "redbag_overdue",
        "add_fine",
    ],
    //站内信图片模板
    "news_pic_template" => [

    ],
    "news_banner_template" => [
        "custom",
        "group_custom",
        "plus_active"

    ],
    "news_miagroup_template" => [
        "img_comment",
        "img_like",
        "follow",
        "new_subject"
    ],
];


/*======消息首页标题，图标，跳转。======*/
$new_index_title = [
    "trade" => "交易物流",
    "plus_active" => "会员Plus",
    "plus_interact" => "会员Plus",
    "group_active" => "蜜芽圈",
    "group_interact" => "蜜芽圈",
    "activity" => "蜜芽活动",
    "property" => "我的资产"
];

$new_index_img = [
    "trade" => "https://img.miyabaobei.com/d1/p5/2017/08/07/5b/f2/5bf27f66fd50b1bbf9f81eddcec50871730412128.png",
    "plus_active" => "https://img.miyabaobei.com/d1/p5/2017/08/07/9a/c7/9ac7ec76104517e9ec9a1a28e7bd4a65730722896.png",
    "plus_interact" => "https://img.miyabaobei.com/d1/p5/2017/08/07/9a/c7/9ac7ec76104517e9ec9a1a28e7bd4a65730722896.png",
    "group_active" => "https://img.miyabaobei.com/d1/p5/2017/08/07/1b/4e/1b4e708bebf24cdea1d6da1720aa6330731079987.png",
    "group_interact" => "https://img.miyabaobei.com/d1/p5/2017/08/07/1b/4e/1b4e708bebf24cdea1d6da1720aa6330731079987.png",
    "activity" => "https://img.miyabaobei.com/d1/p5/2017/08/07/ab/6b/ab6b109038bd981dfb47e1fea90e7c3c730129454.png",
    "property" => "https://img.miyabaobei.com/d1/p5/2017/08/07/97/bf/97bfe0ed2dadd261ab97390ecab08e5a727857932.png"
];

$new_index_url = [
    "trade" => "miyabaobei://message_category?category=trade",
    "plus_active" => "miyabaobei://message_category?category=plus_active",
    "plus_interact" => "miyabaobei://message_category?category=plus_interact",
    "group_active" => "miyabaobei://message_category?category=group_active",
    "group_interact" => "miyabaobei://message_category?category=group_interact",
    "activity" => "miyabaobei://message_category?category=activity",
    "property" => "miyabaobei://message_category?category=property"
];
/*======消息首页标题，图标，跳转。END======*/

//消息列表数量限制
$user_list_limit = 1000;
$page_limit = 20;

//sub_tab
$sub_type = [
    "all" => ["group_active", "group_interact"],
//    "plus_active" => [
//        "name" => "活动",
//        "equal_level" => [
//            [
//                "type" => "plus_interact",
//                "name" => "动态"
//            ],
//        ],
//
//    ],
//    "plus_interact" => [
//        "name" => "动态",
//        "equal_level" => [
//            [
//                "type" => "plus_active",
//                "name" => "活动"
//            ],
//        ],
//
//    ],
    "group_active" => [
        "name" => "活动",
        "equal_level" => [
            [
                "type" => "group_interact",
                "name" => "动态"
            ],
        ],

    ],
    "group_interact" => [
        "name" => "动态",
        "equal_level" => [
            [
                "type" => "group_active",
                "name" => "活动"
            ],
        ],
    ],
];

//push设置，顺序固定
$push_setting_list = [
    "trade" => "交易物流",
    "plus" => "会员Plus",
    "activity" => "蜜芽活动",
    "group" => "蜜芽圈",
    "property" => "我的资产",
];

$push_type = [
    "order_cancel",//订单取消
    "order_send_out",//订单发货
    "order_auto_confirm",
    "return_audit_pass",
    "return_audit_refuse",
    "return_overdue",
    "refund_success",
    "refund_fail",
    "plus_new_members",
    "plus_active",
    "img_comment",
    "add_fine",
    "new_subject",
    "group_custom",
    "coupon_receive",
    "coupon_overdue",
    "redbag_receive",
    "redbag_overdue",
    "custom"
];

//推送允许时间
$push_time = [
    "start" => "8:00",
    "end" => "23:00",
];
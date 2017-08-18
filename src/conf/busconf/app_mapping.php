<?php
/**
 * app url映射配置
 */

/**
 * 商品搜索页
 */
$search_result = 'miyabaobei://search_result?keyword=%s&brand_id=%s&category_id=%s';
 
/**
 * 分类品牌页
 */
$category_detail = 'miyabaobei://category_detail?id=%s&type=%s&name=%s'; 

/**
 * 标签详情页
 */
$label_detail = 'miyabaobei://topic?id=%s';


//粉丝列表页
$user_fans_follow = "miyabaobei://user_fans_follow?user_id=%d&type=fans";

//Plus管理中心-蜜粉列表
$plus_manage_fans = "miyabaobei://plus_manage_fans";

//Plus管理中心-会员列表
$plus_manage_member = "miyabaobei://plus_manage_member";

//分享赚钱页
$plus_manage_income_share = "miyabaobei://plus_manage_income_share";
//优惠券页
$userCoupon = "miyabaobei://userCoupon";
//退货进度：退货单号
$return_detail = "miyabaobei://return_detail?id=%d";
//退货/退款列表
$order_refund = "miyabaobei://order_refund?tab=refund";
//红包
$redbag = "miyabaobei://redbag";
//帖子页
$subject = "miyabaobei://subject?id=%d";
//订单页
$order_detail = "miyabaobei://order_detail?sub_order_id=%d";

/*
 * 订单列表。
 * tab{ 0, 商品订单 1, 服务订单 }
 * 当tab = 0
 * focus { 0, 全部 1,未支付 2, 未发货 3, 已发货 4, 完成 }
 * 当tab = 1
 * focus { 0, 全部 1,已消费 2,退款 }
*/
$order_list = "miyabaobei://order_list?tab=%d&focus=%d";


$news_cate_list = "miyabaobei://message_category?category=%s&category_title=%s";
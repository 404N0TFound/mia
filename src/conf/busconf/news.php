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
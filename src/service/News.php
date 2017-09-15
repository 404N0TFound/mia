<?php

namespace mia\miagroup\Service;

use mia\miagroup\Model\News as NewsModel;
use mia\miagroup\Service\User as userService;
use mia\miagroup\Service\Comment as commentService;
use mia\miagroup\Service\Praise as praiseService;
use mia\miagroup\Service\Subject as subjectService;
use mia\miagroup\Service\UserRelation as userRelationService;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\NormalUtil;

class News extends \mia\miagroup\Lib\Service
{
    public $newsModel;

    public function __construct()
    {
        parent::__construct();
        $this->newsModel = new NewsModel();
        $this->config = \F_Ice::$ins->workApp->config->get('busconf.news');
    }

    /*=============5.7新版本消息=============*/
    /**
     * 发送会员Plus站内信
     * @param $to_user_id
     * @param $news_type string
     * plus_new_members：新plus会员提醒；
     * plus_new_fans：Plus会员新粉丝提醒；
     * plus_get_commission：获取佣金提醒；
     * @param $ext_info string JSON
     * user_id：相关用户id；
     * money：金额；
     */
    public function addPlusNews($to_user_id, $news_type, $ext_info = [])
    {
        if (empty($to_user_id) || !is_numeric(intval($to_user_id))) {
            return $this->error(500, 'to_user_id参数错误！');
        }
        if (empty($news_type) || !in_array($news_type, $this->config['plus_news_type'])) {
            return $this->error(500, 'news_type参数错误！');
        }
        //获取信息
        $type = "plus";
        $newsType = $news_type;

        if (!empty($ext_info)) {
            if (!is_array($ext_info)) {
                return $this->error(500, 'ext_info格式错误！');
            }
            foreach ($ext_info as $k => $v) {
                if (!in_array($k, ["user_id", "money"])) {
                    return $this->error(500, 'ext_info参数错误！');
                }
            }
        }

        $time = date("Y-m-d H:i:s");

        $newsInfo = json_encode(
            [
                "type" => $type,
                "newsType" => $newsType,
                "toUserId" => $to_user_id,
                "ext_info" => $ext_info,
                "time" => $time
            ]
        );

        //存储redis
        list($redis, $redis_key, $expire_time) = $this->getRedis("to_write_news");
        $redis->lpush($redis_key, $newsInfo);
        $redis->expire($redis_key, $expire_time);

        return $this->succ("发送成功");
    }

    /**
     * 发送交易相关站内信
     * @param $to_user_id
     * @param $order_code string 订单号
     * @param $order_status string 订单状态
     * order_unpay：订单未付款；
     * order_cancel：订单取消；
     * order_send_out：订单已发货；
     * order_delivery：订单正在派送；
     * return_audit_pass：退货申请审核通过；
     * return_audit_refuse：退货申请审核不通过；
     * return_overdue：退货申请过期；
     * refund_success：退款成功；
     * refund_fail：退款失败；
     */
    public function addTradingNews($to_user_id, $order_code, $order_status)
    {
        if (empty($to_user_id) || !is_numeric(intval($to_user_id))) {
            return $this->error(500, 'to_user_id参数错误！');
        }
        if (empty($order_code)) {
            return $this->error(500, 'order_code不为空！');
        }
        if (empty($order_status) || !in_array($order_status, $this->config['trade_order_status'])) {
            return $this->error(500, 'news_type参数错误！');
        }
        //获取信息
        $type = "trade";
        $newsType = $order_status;
        $orderCode = $order_code;

        $time = date("Y-m-d H:i:s");

        $newsInfo = json_encode(
            [
                "type" => $type,
                "newsType" => $newsType,
                "toUserId" => $to_user_id,
                "ext_info" => ["orderCode" => $orderCode],
                "time" => $time
            ]
        );

        //存储redis
        list($redis, $redis_key, $expire_time) = $this->getRedis("to_write_news");
        $redis->lpush($redis_key, $newsInfo);
        $redis->expire($redis_key, $expire_time);

        return $this->succ("发送成功");
    }

    /**
     * 发送用户资产站内信
     * @param $to_user_id
     * @param $news_type string 站内信类型
     * coupon_receive：优惠券获取通知；
     * coupon_overdue：优惠券过期通知；
     * redbag_receive：红包获取通知；
     * redbag_overdue：红包过期通知；
     * @param $ext_info string JSON
     * money：金额；
     */
    public function addCouponNews($to_user_id, $news_type, $ext_info)
    {
        if (empty($to_user_id) || !is_numeric(intval($to_user_id))) {
            return $this->error(500, 'to_user_id参数错误！');
        }
        if (empty($news_type) || !in_array($news_type, $this->config['property_news_type'])) {
            return $this->error(500, 'news_type参数错误！');
        }
        //获取信息
        $type = "property";
        $newsType = $news_type;

        if (!empty($ext_info)) {
            if (!is_array($ext_info)) {
                return $this->error(500, 'ext_info格式错误！');
            }
            foreach ($ext_info as $k => $v) {
                if (!in_array($k, ["money"])) {
                    return $this->error(500, 'ext_info参数错误！');
                }
            }
        }

        $time = date("Y-m-d H:i:s");

        $newsInfo = json_encode(
            [
                "type" => $type,
                "newsType" => $newsType,
                "toUserId" => $to_user_id,
                "ext_info" => $ext_info,
                "time" => $time
            ]
        );

        //存储redis
        list($redis, $redis_key, $expire_time) = $this->getRedis("delay_to_write_news");
        $redis->lpush($redis_key, $newsInfo);
        $redis->expire($redis_key, $expire_time);

        return $this->succ("发送成功");
    }



    /**
     * 发布消息，提供外部接口
     * 1.交易物流：订单状态变更，物流动态。
     * 2.我的资产：红包优惠券。
     * 3.会员plus：佣金，提现，成员加入，新增粉丝，分享购买，分享页面产生新Plus会员，即将成为城市经理。
     * 4.蜜芽圈：活动：旧蜜芽圈活动 ；动态：被关注、被回复、被点赞、帖子被加精、关注的达人发了新帖。
     * 5.蜜芽活动：旧特卖消息。
     *
     * @param $type string 消息类型
     * @param $toUserId int 接收人ID
     * @param $sendFromUserId int 发送人ID
     * @param $source_id int 来源ID
     * @param $ext_info array
     *
     */
    public function postMessage($type, $toUserId = 0, $sendFromUserId = 0, $source_id = 0, $ext_info = [])
    {
        //参数检测
        if (empty($type)) {
            return $this->error(500, 'type参数不为空！');
        }

        //检查是否是最低级分类
        if (!in_array($type, $this->getFinalCate()['data'])) {
            return $this->error(500, 'type不合法！');
        }

        $insert_data = [];
        $insert_data['news_type'] = $type;
        $insert_data['user_id'] = $toUserId;
        $insert_data['send_user'] = $sendFromUserId;
        $insert_data['source_id'] = $source_id;
        //发送时间
        if (isset($ext_info['time'])) {
            $insert_data['create_time'] = $ext_info['time'];
            unset($ext_info['time']);
        } else {
            $insert_data['create_time'] = date("Y-m-d H:i:s");
        }
        //检测是否需要增加需要消息未读计数
        $addCountCheck = 0;
        switch ($type) {
            /*========蜜芽圈：动态========*/
            case "blog_quote"://长文引用帖子，给帖子作者发信息
                //source_id，记引用的长文帖子id；ext_info传入时加上：被引用的帖子id；
                break;
            case "img_comment"://被回复
                //评论消息里面，source_id记得是评论ID，ext_info补上：帖子ID
                $commentService = new commentService();
                $subject_id = $commentService->getBatchComments([$source_id], [])["data"][$source_id]["subject_id"];
                if (!empty($subject_id)) {
                    $ext_info["subject_id"] = $subject_id;
                }
                break;
            case "img_like"://被点赞
                //点赞消息里面，source_id记得是点赞ID，ext_info补上：帖子ID
                $praiseService = new praiseService();
                $subject_id = $praiseService->getPraisesByIds([$source_id], [])["data"][$source_id]["subject_id"];
                if (!empty($subject_id)) {
                    $ext_info["subject_id"] = $subject_id;
                }
                break;
            case "follow"://被关注
                break;
            case "add_fine"://帖子被加精
                //加精消息里面，source_id记得就是帖子ID
                break;
            case "new_subject"://关注的达人发了新帖
                break;

            /*========交易物流：订单状态变更，物流动态========*/
            case "order"://订单，旧的消息类型
                break;
            case "order_unpay":
            case "order_cancel":
                $orderService = new Order();
                $orderInfo = $orderService->getOrderSuperiorInfo([$source_id])['data'];
                $ext_info["item_ids"] = $orderInfo[$source_id];
            case "order_send_out":
            case "order_delivery":
            case "order_auto_confirm":
            case "order_received":
                //item_id
                if (empty($orderInfo)) {
                    $orderService = new Order();
                    $orderInfo = $orderService->getOrderItemInfo([$source_id])['data'];
                    $ext_info["item_ids"] = $orderInfo[$source_id];
                }
            case "return_audit_pass":
            case "return_audit_refuse":
            case "return_overdue":
                if(empty($orderInfo)) {
                    $orderService = new Order();
                    $returnInfo = $orderService->getReturnInfo([$source_id])["data"];
                    $ext_info["item_ids"] = $returnInfo[$source_id];
                }
                //item_id
            case "refund_success":
            case "refund_fail":
                //money
                if(empty($orderInfo) && empty($returnInfo)) {
                    $orderService = new Order();
                    $refundInfo = $orderService->getRefundInfo([$source_id])["data"];
                    $ext_info["money"] = $refundInfo[$source_id];
                }
                //每个订单只有最新的一条信息，不需要按天合并
                $lastNewsInfo = $this->newsModel->getLastNews($this->config["layer"]["trade"], $toUserId, $source_id, false)[0];

                if (!empty($lastNewsInfo)) {
                    //更新
                    $insert_data['id'] = $lastNewsInfo['id'];
                    //删除和已读状态还原
                    $insert_data['is_read'] = 0;
                    $insert_data['status'] = 1;
                    if ($lastNewsInfo["is_read"] == 0 && $lastNewsInfo["status"] == 1) {
                        $addCountCheck = 1;
                    }
                }
                break;
            /*========蜜芽活动：旧特卖消息========*/
            /*========蜜芽圈：活动========*/
            case "group_custom"://蜜芽圈：活动，批量后台发送，单条和全站这里发送
            case "custom"://蜜芽活动：旧特卖消息，后台发送，单条和全站这里发送
            case "pull_custom":
            case "pull_group_custom":
            if (isset($ext_info['news_id']) && !empty($ext_info['news_id'])) {
                $insert_data['news_id'] = $ext_info['news_id'];
                unset($ext_info);
            }
                break;
            /*========我的资产：红包优惠券========*/
            case "coupon"://旧优惠券
                break;
            //以下4种类型，按天跑脚本，需要把同类型多条合并
            case "coupon_receive":
            case "coupon_overdue":
            case "redbag_receive":
            case "redbag_overdue":
                $lastNewsInfo = $this->newsModel->getLastNews($type, $toUserId, $source_id, true)[0];
                if (!empty($lastNewsInfo)) {
                    //更新
                    $insert_data['id'] = $lastNewsInfo['id'];
                    //删除和已读状态还原
                    $insert_data['is_read'] = 0;
                    $insert_data['status'] = 1;

                    $ext_info["money"] += $lastNewsInfo["ext_info"]["money"];
                    $ext_info["num"] = $lastNewsInfo["ext_info"]["num"] + 1;
                    if ($lastNewsInfo["is_read"] == 0 && $lastNewsInfo["status"] == 1) {
                        $addCountCheck = 1;
                    }
                } else {
                    $ext_info["num"] += 1;
                }
                break;
            /*========会员plus：动态========*/
            //以下2种类型，plus_new_members，plus_new_fans按天把同类型合并
            case "plus_new_members":
            case "plus_new_fans":
                $lastNewsInfo = $this->newsModel->getLastNews($type, $toUserId, $source_id, true)[0];
                if (!empty($lastNewsInfo)) {
                    //更新
                    $insert_data['id'] = $lastNewsInfo['id'];
                    //删除和已读状态：还原
                    $insert_data['is_read'] = 0;
                    $insert_data['status'] = 1;

                    $ext_info["user_id"] = array_merge([$ext_info["user_id"]], $lastNewsInfo["ext_info"]["user_id"]);
                    $ext_info["num"] = $lastNewsInfo["ext_info"]["num"] + 1;
                    if ($lastNewsInfo["is_read"] == 0 && $lastNewsInfo["status"] == 1) {
                        $addCountCheck = 1;
                    }
                } else {
                    $ext_info["user_id"] = [$ext_info["user_id"]];
                    $ext_info["num"] += 1;
                }
                break;
            case "plus_get_commission":
                break;
            /*========会员plus：活动========*/
        }

        if (!empty($ext_info)) {
            $insert_data['ext_info'] = json_encode($ext_info);
        }

        //添加消息
        $insertRes = $this->newsModel->postNews($insert_data);

        if (!$insertRes) {
            return $this->error(500, '发送用户消息失败！');
        }

        //消息计数增加
        //计数存在，才操作
        list($redis, $redis_key, $expire_time) = $this->getRedis("news_count", intval($toUserId));
        //合并类型的消息中，更新旧的未读消息不需要计数加1，$addCountCheck为1时，不需要增加计数
        if ($redis->exists($redis_key) && $addCountCheck == 0) {
            $listType = $this->getAncestor($type)['data'];
            if ($redis->hExists($redis_key, $listType)) {
                $redis->hIncrBy($redis_key, $listType, 1);
                $redis->expire($redis_key, $expire_time);
            }
            if ($redis->hExists($redis_key, "total")) {
                //总数+1
                $redis->hIncrBy($redis_key, "total", 1);
            }
        }
        //发送push提醒
        $this->newsPush($insert_data, $insertRes);
        //分类redis有序集合更新,首页redis有序集合更新
        $showType = $this->getAncestor($insert_data["news_type"])["data"];
        list($redis, $redis_key, $expire_time) = $this->getRedis("cate_list", $showType . ":" . intval($insert_data["user_id"]));
        list($redis, $redis_key_index, $expire_time_index) = $this->getRedis("news_index", intval($insert_data["user_id"]));
        if (isset($insert_data["id"])) {
            //分类redis，cate列表存在才插入
            if ($redis->exists($redis_key)) {
                $score = strtotime($insert_data["create_time"]) . str_pad($insert_data['id'] % 100, 3, 0, STR_PAD_LEFT);
                $redis->zAdd($redis_key, $score, $insert_data['id']);
                $redis->expire($redis_key, $expire_time);
            }
            //首页redis，可以单插
            $redis->zAdd($redis_key_index, strtotime($insert_data["create_time"]), $showType . ":" . $insert_data['id']);
            $redis->expire($redis_key_index, $expire_time_index);
        } else {
            //分类redis，cate列表存在才插入
            if ($redis->exists($redis_key)) {
                $score = strtotime($insert_data["create_time"]) . str_pad($insertRes % 100, 3, 0, STR_PAD_LEFT);
                $redis->zAdd($redis_key, $score, $insertRes);
                $redis->expire($redis_key, $expire_time);
            }
            //首页redis，可以单插
            $redis->zAdd($redis_key_index, strtotime($insert_data["create_time"]), $showType . ":" . $insertRes);
            $redis->expire($redis_key_index, $expire_time_index);
        }
        return $this->succ("发送成功");
    }

    /**
     * 发送push
     * @param $newsInfo
     */
    public function newsPush($newsInfo, $newsId)
    {
        $newsInfo["ext_info"] = json_decode($newsInfo["ext_info"], true);
        if (!isset($newsInfo["id"])) {
            $newsInfo["id"] = $newsId;
        }
        $type = $newsInfo['news_type'];
        if (!in_array($type, $this->config["push_type"])) {
            return;
        }
        //判断是否是发送时间
        $now = time();
        if (strtotime($this->config['push_time']['start']) > $now || $now > strtotime($this->config['push_time']['end'])) {
            return;
        }
        //判断用户是否开启消息发送
        $root_cate = $this->getRootCate($newsInfo['news_type']);
        $typeSetting = $this->newsModel->getTypeSet($newsInfo['user_id'], $root_cate);

        if(!empty($typeSetting) && array_pop($typeSetting)["value"] == 0) {
            return;
        }

        $newsInfoRes = $this->formatNews($newsInfo["id"], $newsInfo["user_id"], 2, [$newsInfo]);
        $newsInfoRes = array_pop($newsInfoRes);
        $showType = $this->getAncestor($newsInfo['news_type'])["data"];
        switch ($showType) {
            case "trade":
                //标题+正文，逗号间隔
                $content = $newsInfoRes[$newsInfoRes["template_type"]]["news_title"] . "，" . $newsInfoRes[$newsInfoRes["template_type"]]["news_text"];
                $url = $newsInfoRes[$newsInfoRes["template_type"]]["redirect_url"];
                break;
            case "group_active":
            case "group_interact":
                //标题+正文，空格间隔
                if (!empty($newsInfoRes[$newsInfoRes["template_type"]]["user_info"])) {
                    $group_title = $newsInfoRes[$newsInfoRes["template_type"]]["user_info"]["nickname"] ? $newsInfoRes[$newsInfoRes["template_type"]]["user_info"]["nickname"] : $newsInfoRes[$newsInfoRes["template_type"]]["user_info"]["username"];
                } else {
                    $group_title = $newsInfoRes[$newsInfoRes["template_type"]]["news_title"];
                }
                $content = $group_title . " " . $newsInfoRes[$newsInfoRes["template_type"]]["news_text"];
                $url = $newsInfoRes[$newsInfoRes["template_type"]]["redirect_url"];
                //标题+正文
                break;
            case "plus_interact":
                //只取消息标题
                $content = $newsInfoRes[$newsInfoRes["template_type"]]["news_title"];
                $url = $newsInfoRes[$newsInfoRes["template_type"]]["redirect_url"];
                break;
            case "activity":
            case "property":
            case "plus_active":
                //只取消息正文
                $content = $newsInfoRes[$newsInfoRes["template_type"]]["news_text"];
                $url = $newsInfoRes[$newsInfoRes["template_type"]]["redirect_url"];
                break;
        }
        $pushService = new Push();
        if(empty($content) || empty($url) || empty($newsInfo["user_id"])) {
            return;
        }
        $pushService->pushMsg($newsInfo["user_id"], $content, $url);
    }


    /**
     * 添加系统消息
     * @param $data array ums后台设置参数
     * resource_type  'group','outlets'
     * content 消息正文
     * resource_sub_type 'custom'
     *
     * custom_title 自定义标题
     * custom_photo 自定义图片
     * custom_url 自定义链接
     * valid_time 发送时间
     * created 发送时间
     * type  'all','single'
     * send_from_id  1026069目前传的
     * @param $userArr array  定制发送的用户数组
     *
     */
    public function addSystemMessage($data, $userArr = [])
    {
        //ums调用
        if (empty($data['content']) || mb_strlen($data['content'], 'utf-8') > 70 || empty($data['valid_time']) || ($data['type'] == "single" && empty($userArr))) {
            return $this->error(500, '参数错误！');
        }
        if ($data['resource_sub_type'] !== "custom") {
            return $this->error(500, '类型错误！');
        }
        //批量发送

        if ($data['resource_type'] == "group") {
            $type = "pull_group_custom";
        } else if ($data['resource_type'] == "outlets") {
            $type = "pull_custom";
        }
        $content_info = [
            "title" => $data['custom_title'],
            "content" => $data['content'],
            "photo" => $data['custom_photo'],
            "photo_info" => $data['custom_photo_info'],
            "url" => $data['custom_url'],
            "users" => $userArr
        ];
        $send_time = $data['valid_time'];
        $abandon_time = date("Y-m-d H:i:s", strtotime($send_time) + 30 * 24 * 3600);//有效期30天
        if ($data['type'] == "single") {
            $sendType = 3;
        } else if ($data['type'] == "all") {
            $sendType = 2;
        }

        $res = $this->addSystemNews($type, $content_info, $send_time, $abandon_time, $sendType);
        if ($res['code'] != 0) {
            return $this->error(500, $res['msg']);
        } else {
            $newsId = $res['data'];
        }


        if ($data['type'] == "single") {
            //单人发送
            if ($data['resource_type'] == "group") {
                $type = "group_custom";
            } else if ($data['resource_type'] == "outlets") {
                $type = "custom";
            }
            $ext_info = [
                "time" => $data['valid_time'],
                "news_id" => $newsId,
            ];
            foreach ($userArr as $toUserId) {
                $this->postMessage($type, $toUserId, 0, 0, $ext_info);
            }
        }
        return $this->succ([], "发送成功");
    }

    /**
     * 拉取消息，全站消息
     */
    public function pullMessage($userId)
    {
        if (empty(intval($userId))) {
            return $this->error(500, '用户ID不为空！');
        }
        //获取某个用户消息里最大的系统消息，时间
        $maxSystemTime = $this->newsModel->getMaxSysTime($userId);
        $create_date = '';
        if (empty($maxSystemId)) {
            //新用户，获取用户的注册时间
            $userService = new userService();
            $create_date = $userService->getUserInfoByUids([$userId])["data"][$userId]["create_date"];
        }
        //查询用户需要拉取的系统消息列表
        $systemNewsList = $this->newsModel->getPullList($userId, $maxSystemTime, $create_date);
        if (empty($systemNewsList)) {
            return $this->succ([]);
        }

        //把系统消息写入用户消息表
        foreach ($systemNewsList as $val) {
            $ext_info = json_decode($val['ext_info'], true);
            $ext_info['time'] = $val['send_time'];
            $ext_info['news_id'] = $val['id'];
            $this->postMessage($val["news_type"], $userId, 0, 0, $ext_info);
        }
        return $this->succ([]);
    }

    /**
     * 获取系统消息列表
     * @param $params 查询参数
     * resource_type  group,outlets
     * is_send  0全部, 1已发送, 2未发送
     * content
     * offset
     * pagesize
     * start_time
     * end_time
     * id
     */
    public function systemNewsList($params)
    {
        if (!isset($params['resource_type']) || !in_array($params['resource_type'], ["group", "outlets"])) {
            return $this->error(500, 'resource_type参数错误！');
        }
        if (isset($params['is_send']) && !in_array($params['is_send'], [0, 1, 2])) {
            return $this->error(500, 'is_send参数错误！');
        }
        if (empty(intval($params['offset']))) {
            $params['offset'] = 0;
        }
        if (empty(intval($params['pagesize']))) {
            $params['pagesize'] = 20;
        }
        if ($params['content'] == "-1") {//ums默认-1
            unset($params['content']);
        } else {
            $params['content'] = str_replace("\\", "_", NormalUtil::utf8_unicode($params['content']));
        }
        //消息状态
        $params['status'] = isset($params['status']) ? $params['status'] : 1;
        
        $res = $this->newsModel->getUmsSystemNews($params, 1);
        return $this->succ($res);
    }

    /**
     * 获取单个系统消息详情
     * @param $systemId
     * @return mixed
     */
    public function getSingleSystemNews($systemId)
    {
        if(empty(intval($systemId))) {
            return $this->succ([]);
        }
        $res = $this->newsModel->getUmsSystemNews(["id"=>intval($systemId)])[0];
        return $this->succ($res);
    }

    /**
     * 删除系统消息
     * @param $systemId
     * @return mixed
     */
    public function delSystemNews($systemId)
    {
        if (empty(intval($systemId))) {
            return $this->error(500, 'id不为空！');
        }
        $system_info = $this->getSingleSystemNews($systemId)["data"];

        if($system_info["status"] == 0) {
            return $this->error(500, '消息已被删除或不存在！');
        }

        $res = $this->newsModel->delSystemNews($systemId);
        if ($res) {
            return $this->succ([], "修改成功");
        } else {
            return $this->error(500, '修改失败！');
        }
    }

    /**
     * 发送当前系统消息
     */
    public function sendSystemNow($systemId)
    {
        if (empty(intval($systemId))) {
            return $this->error(500, 'id不为空！');
        }
        $system_info = $this->getSingleSystemNews($systemId)["data"];
        if(strtotime($system_info["send_time"]) < time()) {
            return $this->error(500, '消息已发送！');
        }

        $res = $this->newsModel->sendSystemNews($systemId);
        if ($res) {
            return $this->succ([], "发送成功");
        } else {
            return $this->error(500, '发送失败！');
        }
    }

    /**
     * 获取站内信未读数 total_count，group_count
     */
    public function noReadCounts($userId)
    {
        $this->pullMessage($userId);
        $cateNum = [];
        $showCate = $this->getShowCate()['data'];
        foreach ($showCate as $cate) {
            $cateNum[$cate] = $this->getCateNewsNum($cate, $userId);
        }

        //计算总数
        $return = [
            "total_count" => array_sum($cateNum),
            "group_count" => $cateNum['group_active'] + $cateNum['group_interact'],
        ];
        return $this->succ($return);
    }

    /**
     * 获取分类计数
     * @param $cate
     * @param $userId
     * @return int
     */
    public function getCateNewsNum($cate, $userId)
    {
        //type类型验证
        list($redis, $redis_key, $expire_time) = $this->getRedis("news_count", intval($userId));

        if ($redis->exists($redis_key)) {
            if ($redis->hExists($redis_key, $cate)) {
                //取redis
                if(in_array($cate,["group_active","plus_active","activity"])) {
                    $limitTime = $redis->hGet($redis_key, $cate . "_time");
                    if(time() < $limitTime) {
                        //未到过期时间，可取
                        $num = $redis->hGet($redis_key, $cate);
                        return $num;
                    }
                } else {
                    $num = $redis->hGet($redis_key, $cate);
                    return $num;
                }
            }
        }

        $num = $this->newsModel->getUserNewsCount($userId, $this->getAllChlidren($cate)['data']);
        if (in_array($cate, ["group_active", "plus_active", "activity"])) {
            $redis->hSet($redis_key, $cate, $num);
            //当前时间之后的整10分钟时间
            $limitTime = strtotime((date("Y-m-d H:") . (ceil(date("i") / 10) * 10) . ":00"));
            $redis->hSet($redis_key, $cate . "_time", $limitTime);
        } else {
            $redis->hSet($redis_key, $cate, $num);
        }
        $redis->expire($redis_key, $expire_time);
        return $num;
    }

    /**
     * 蜜芽圈首页，消息框
     */
    public function groupNews($userId)
    {
        $groupNews = [
            "count" => 0,
            "text" => "",
            "img" => "",
            "url" => "",
        ];
        if (empty($userId)) {
            return $this->succ($groupNews);
        }
        //先查询蜜芽圈消息计数，计数为空则不显示
        $group_active_num = $this->getCateNewsNum("group_active", $userId);
        $group_interact_num = $this->getCateNewsNum("group_interact", $userId);
        $count = $group_active_num + $group_interact_num;

        if ($count == 0) {
            return $this->succ($groupNews);
        }

        //第一条
        $res = $this->newsModel->getLastNews(array_merge($this->getAllChlidren("group_active")['data'], $this->getAllChlidren("group_interact")['data']), $userId, 0, false, [1]);
        if(empty($res)){
            return $this->succ($groupNews);
        }
        $newsId = $res[0]["id"];
        $newsInfo = array_pop($this->formatNews([$newsId], $userId, 2));//计数在格式化模板是查询的

        $app_mapping_config = \F_Ice::$ins->workApp->config->get('busconf.app_mapping');

        if(isset($newsInfo['news_miagroup_template'])) {
            //"img_comment","img_like","follow","new_subject" 目前类型
            $showCate = $this->getAncestor($newsInfo['type'])['data'];
            //蜜芽圈动态
            return $this->succ([
                "count" => $count,
                "text" => $newsInfo['news_miagroup_template']['index_title'],
                "img" => $newsInfo['news_miagroup_template']['user_info']['icon'],
                "url" => sprintf($app_mapping_config['news_cate_list'], $showCate, $this->config['new_index_title'][$showCate]),
            ]);
        } else if (isset($newsInfo['news_text_pic_template'])) {
            //add_fine
            $showCate = $this->getAncestor($newsInfo['type'])['data'];
            //蜜芽圈动态
            return $this->succ([
                "count" => $count,
                "text" => $newsInfo['news_text_pic_template']['index_title'],
                "img" => "",
                "url" => sprintf($app_mapping_config['news_cate_list'], $showCate, $this->config['new_index_title'][$showCate]),
            ]);
        } else {
            //蜜芽兔
            $showCate = $this->getAncestor($newsInfo['type'])['data'];
            if(empty($showCate)) {
                $redirect_url = sprintf($app_mapping_config['news_cate_list'], "group_active", $this->config['new_index_title']["group_active"]);
            } else {
                $redirect_url = sprintf($app_mapping_config['news_cate_list'], $showCate, $this->config['new_index_title'][$showCate]);
            }
            return $this->succ([
                "count" => $count,
                "text" => "蜜芽兔@了你",
                "img" => \F_Ice::$ins->workApp->config->get('busconf.user.miaTuIcon'),
                "url" => $redirect_url,
            ]);
        }
    }

    /**
     * 站内信首页列表
     */
    public function indexList($userId)
    {
        $news_list["news_list"] = [];
        if (empty(intval($userId))) {
            return $this->succ($news_list);
        }
        //redis取列表
        list($redis, $redis_key, $expire_time) = $this->getRedis("news_index", intval($userId));
        $indexList = $redis->zRevRange($redis_key, 0, -1, true);

        $existType = [];
        $newIndexList = [];
        if(!empty($indexList)) {
            foreach ($indexList as $k=>$v) {
                list($nType, $newsId) = explode(":", $k);
                $existType[] = $nType;
                $newIndexList[$v] = $k;
            }
        }

        //数据库查询，查询分类下最新一条记录
        $cateList = $this->getShowCate()['data'];
        $tmp = [];
        foreach ($cateList as $showCate) {
            if (!in_array($showCate, $existType)) {
                $res = $this->newsModel->getLastNews($this->getAllChlidren($showCate)['data'], $userId, 0, false, [1]);
                if (!empty($res)) {
                    $tmp[strtotime($res[0]["create_time"])] = $showCate . ":" . $res[0]["id"];
                    $redis->zAdd($redis_key, strtotime($res[0]["create_time"]), $showCate . ":" . $res[0]["id"]);
                } else {
                    $redis->zAdd($redis_key, time(), $showCate . ":" . 0);//防止不存在的下次再查
                }
            }
        }
        $redis->expire($redis_key, $expire_time);
        //需要对查出的数据排序
        $newIndexList = $tmp + $newIndexList;
        krsort($newIndexList);
        $newIndexList = array_values($newIndexList);


        //查询列表信息
        $newsIds = [];
        foreach ($newIndexList as $value) {
            list($type, $id) = explode(":", $value);
            if (!isset($newsIds[$type])) {
                $newsIds[$type] = $id;
            } else {
                $redis->zRem($redis_key, $value);//因为发消息插入首页时，不检查是否攒在同类消息，类型会有重复type
            }
        }

        $indexNewsList = $this->formatNews(array_values($newsIds), $userId, 1);//计数在格式化模板是查询的

        //没有的补充空
        $news_index = $this->config["news_index"];
        foreach ($indexNewsList as $k=>$v) {
            if (in_array($v['type'], $news_index)) {
                unset($news_index[array_search($v['type'], $news_index)]);
            }
        }

        foreach ($news_index as $need) {
            $indexNewsList[] = [
                "id" => "",
                "template_type" => "news_sub_category_template",
                "type" => $need,
                "create_time" => "",
                "news_sub_category_template" => [
                    "news_title" => $this->config["new_index_title"][$need],
                    "news_text" => "暂无消息",
                    "news_image_url" => $this->config["new_index_img"][$need],
                    "news_count" => 0,
                    "redirect_url" => $this->config["new_index_url"][$need],
                ]
            ];
        }


        //合并同类型
        //plus_interact plus_active 合并，计数相加
        //group_interact group_active 合并，计数相加
        $userService = new userService();
        $userInfo = $userService->getUserInfoByUids([$userId], 0, [])["data"];
        $userType = $userInfo[$userId]["mia_user_type"];//2是plus

        $return = [];
        //已经排好序了
        foreach ($indexNewsList as $val) {
            //非plus会员不显示
            if($userType != 2 && in_array($val['type'], ['plus_interact', 'plus_active'])) {
                continue;
            }
            if (in_array($val['type'], ['plus_interact', 'plus_active', 'group_interact', 'group_active'])) {
                list($firstType, $secondType) = explode("_", $val['type']);
                if (isset($return[$firstType])) {
                    $oldCount = $return[$firstType]['news_sub_category_template']['news_count'];
                    $return[$firstType]['news_sub_category_template']['news_count'] = $oldCount + $val['news_sub_category_template']['news_count'];
                } else {
                    $return[$firstType] = $val;
                }
            } else {
                $return[$val['type']] = $val;
            }
        }
        return $this->succ(["news_list" => array_values($return)]);
    }

    /**
     * 站内信子分类列表
     * @param $category
     * @param $offset
     * @return mixed
     */
    public function categoryList($category, $userId, $offset = 0)
    {
        if (empty($category) || empty($userId)) {
            return $this->succ(["news_list" => [], "offset" => "", "sub_tab" => $this->getSubTab($category, $userId)]);
        }

        $pageLimit = $this->config['page_limit'];

        list($redis, $redis_key, $expire_time) = $this->getRedis("cate_list", $category.":".intval($userId));

        if (empty($offset)) {
            $newsScoreArr = $redis->zRevRange($redis_key, 0, $pageLimit - 1, true);
        } else {
            //key max min [WITHSCORES] [LIMIT offset count]
            $newsScoreArr = $redis->zRevRangeByScore($redis_key, $offset, 0, array('withscores' => TRUE, 'limit' => array(1, $pageLimit)));
        }

        if (!empty($newsScoreArr)) {
            $newsIds = array_keys($newsScoreArr);
            //获取newOffset
            $newOffset = $newsScoreArr[end($newsIds)];
        } else {
            $newsIds = [];
            $newOffset = 0;
        }

        if (empty($newsIds) && !$redis->exists($redis_key)) {
            //查询数据库
            $news = $this->newsModel->getBatchList($this->getAllChlidren($category)['data'], $userId, $this->config['user_list_limit']);

            if(empty($news)) {
                //TODO 优化
                return $this->succ(["news_list" => [], "offset" => "", "sub_tab" => $this->getSubTab($category, $userId)]);
            }
            //存入redis
            foreach ($news as $val) {
                $score = strtotime($val["create_time"]) . str_pad($val['id'] % 100, 3, 0, STR_PAD_LEFT);
                $redis->zAdd($redis_key, $score, $val['id']);
                $newsScoreArr[$val['id']] = $score;
            }
            //下一个整10分钟过期
            if (in_array($category, ["activity", "group_active", "plus_active"])) {
                $expireTime = strtotime((date("Y-m-d H:") . (ceil(date("i") / 10) * 10) . ":00"));
                $redis->expireAt($redis_key, $expireTime);
            } else {
                $redis->expire($redis_key, $expire_time);
            }

            $idArr = array_keys($newsScoreArr);
            $timeArr = array_values($newsScoreArr);
            if(empty($offset)) {
                $newsIds = array_slice($idArr, 0, $pageLimit);
                //获取newOffset
                $newOffset = $newsScoreArr[end($newsIds)];
            } else {
                $newsIds = array_slice($idArr, array_search($offset, $timeArr) + 1, $pageLimit);
                //获取newOffset
                $newOffset = $newsScoreArr[end($newsIds)];
            }
        }
        $newsList = $this->formatNews($newsIds, $userId, 2);

        //获取sub_tab
        $return = [
            "news_list" => $newsList,
            "offset" => $newOffset,
            "sub_tab" => $this->getSubTab($category, $userId)
        ];

        //清空消息计数
        $this->clearNewsNum($userId, $category);
        //设置已读，同级分类下都清空
        $this->setReadStatus($userId, $category);

        return $this->succ($return);
    }


    /**
     * 获取sub_tab
     * @param $type
     * @param $userId
     * @return array
     */
    public function getSubTab($type, $userId)
    {
        $subArr = [];
        if (!in_array($type, $this->config["sub_type"]["all"])) {
            $subArr[] = [
                "tab_title" => "",
                "category" => $type,
                "is_current" => 1,
                "news_count" => 0,
            ];
        } else {
            $subArr[] = [
                "tab_title" => $this->config["sub_type"][$type]["name"],
                "category" => $type,
                "is_current" => 1,
                "news_count" => $this->getCateNewsNum($type, $userId),
            ];
            foreach ($this->config["sub_type"][$type]["equal_level"] as $val) {
                $sub = [
                    "tab_title" => $val["name"],
                    "category" => $val["type"],
                    "is_current" => 0,
                    "news_count" => $this->getCateNewsNum($val["type"], $userId),
                ];
                if($val["position"]) {
                    //后
                    array_push($subArr, $sub);
                } else {
                    $subArr = array_merge([$sub],$subArr);
                }
            }
        }
        return $subArr;
    }
    /**
     * 格式化消息模板
     * @param $newsIds
     * @param $userId
     * @param $type int 控制使用的模板的。格式化方式：
     * 1.子分类列表；
     * 2.消息详情页；
     * @return mixed
     */
    public function formatNews($newsIds, $userId, $type, $newsList = [])
    {
        if(empty($newsIds) || empty($userId) || empty($type)) {
            return [];
        }

        //查询消息基础信息
        if (empty($newsList)) {
            $newsList = $this->getNewListByIds($newsIds, $userId)['data'];
        }
        //查询消息相关信息
        //订单号
        //退货单号
        //帖子ID
        //评论ID
        //用户ID
        $subjectIds = [];
        $commentIds = [];
        $userIds = [];
        $itemIds = [];
        $newsIds = [];
        foreach ($newsList as $val) {
            switch ($val['news_type']) {
                case "order":
                    break;
                case "order_unpay":
                case "order_cancel":
                case "order_send_out":
                case "order_delivery":
                case "order_auto_confirm":
                case "order_received":
                    //收集item_id
                    if(!empty($val["ext_info"]["item_ids"])) {
                        $itemIds = array_merge($itemIds,$val["ext_info"]["item_ids"]);
                    }
                    break;
                case "return_audit_pass":
                case "return_audit_refuse":
                case "return_overdue":
                    //收集item_id
                    if(!empty($val["ext_info"]["item_ids"])) {
                        $itemIds = array_merge($itemIds, $val["ext_info"]["item_ids"]);
                    }
                    break;
                case "refund_success":
                case "refund_fail":
                    break;
                case "plus_active":
                    break;
                case "plus_new_members":
                case "plus_new_fans":
                    break;
                case "plus_get_commission":
                    break;
                case "group_custom":
                case "pull_group_custom":
                    if(!empty($val["news_id"])) {
                        $newsIds[] = $val["news_id"];
                    }
                    break;
                case "img_comment"://source_id记得是评论ID，ext_info补上：帖子ID
                    //收集subject_id和comment_id和user_id
                    $commentIds[] = $val["source_id"];
                    $subjectIds[] = $val["ext_info"]["subject_id"];
                    $userIds[] = $val["send_user"];
                    break;
                case "blog_quote":
                    $subjectIds[] = $val["source_id"];
                    $userIds[] = $val["send_user"];
                    break;
                case "add_fine"://加精消息里面，source_id记得就是帖子ID
                    //收集subject_id
                    $subjectIds[] = $val["source_id"];
                    break;
                case "img_like"://点赞消息里面，source_id记得是点赞ID，ext_info补上：帖子ID
                    //收集subject_id
                    $subjectIds[] = $val["ext_info"]["subject_id"];
                    $userIds[] = $val["send_user"];
                    break;
                case "follow":
                    //从发送人，收集user_id
                    $userIds[] = $val["send_user"];
                    break;
                case "new_subject":
                    //收集subject_id
                    break;
                case "custom":
                case "pull_custom":
                    if(!empty($val["news_id"])) {
                        $newsIds[] = $val["news_id"];
                    }
                    break;
                case "coupon":
                case "coupon_receive":
                case "coupon_overdue":
                case "redbag_receive":
                case "redbag_overdue":
                    break;
            }
        }
        //查询额外信息
        if(!empty($itemIds)) {
            $itemService = new Item();
            $this->itemInfo = $itemService->getBatchItemBrandByIds(array_unique($itemIds), true, [-1, 0, 1, 3])["data"];
        }
        if (!empty($subjectIds)) {
            $subjectService = new Subject();
            $this->subjectInfo = $subjectService->getBatchSubjectInfos($subjectIds, 0, [])['data'];
        }
        if (!empty($commentIds)) {
            $commentService = new Comment();
            $this->commentInfo = $commentService->getBatchComments($commentIds,[])['data'];
        }
        if (!empty($userIds)) {
            $userService = new userService();
            $this->userInfo = $userService->getUserInfoByUids($userIds)['data'];
        }
        if (!empty($newsIds)) {
            $this->newsInfo = $this->newsModel->getSystemNewsList($newsIds);
        }

        $formatList = [];
        foreach ($newsList as $newsInfo) {
            $tmp = [];
            $tmp['id'] = $newsInfo['id'];
            //模板选择
            if ($type == 1) {
                $newShowType = $this->getAncestor($newsInfo['news_type'])['data'];
            } elseif ($type == 2) {
                $newShowType = $newsInfo['news_type'];
            }
            $tmp['template_type'] = $this->getTemplate($newShowType)['data'];//模板读配置
            $tmp['type'] = $newShowType;//展示分类
            $tmp['create_time'] = NormalUtil::formatNewsDate($newsInfo['create_time'], $tmp['template_type']);//展示分类
            $singleContent = $this->singleTemplate($tmp['template_type'], $newsInfo, $newShowType, $userId);
            if (empty($singleContent)) {
                continue;
            }
            $tmp[$tmp['template_type']] = $singleContent;
            $formatList[] = $tmp;
        }
        return $formatList;
    }

    //单个模板解析
    public function singleTemplate($templateType, $newsInfo, $showType, $userId)
    {
        switch ($templateType) {
            case "news_sub_category_template"://站内信子分类模板
                $newsInfoRes = $this->getNewsContent($newsInfo);
                switch ($showType) {
                    case "trade":
                        //标题+正文，逗号间隔
                        $text = $newsInfoRes["title"] . "，" . $newsInfoRes["text"];
                        break;
                    case "group_active":
                    case "group_interact":
                        //标题+正文，空格间隔
                        if(!empty($newsInfoRes["user_info"])) {
                            $group_title = $newsInfoRes["user_info"]["nickname"]?$newsInfoRes["user_info"]["nickname"]:$newsInfoRes["user_info"]["username"];
                        } else {
                            $group_title = $newsInfoRes["title"];
                        }
                        $text = $group_title . " " . $newsInfoRes["text"];
                        break;
                    case "plus_interact":
                        //只取消息标题
                        $text = $newsInfoRes["title"];
                        break;
                    case "activity":
                    case "property":
                    case "plus_active":
                        //只取消息正文
                        $text = $newsInfoRes["text"];
                        break;
                }
                $resArr = [
                    "news_title" => $this->config['new_index_title'][$showType],
                    "news_text" => $text,
                    "news_image_url" => $this->config['new_index_img'][$showType],
                    "news_count" => $this->getCateNewsNum($showType, $userId),
                    "redirect_url" => $this->config['new_index_url'][$showType],
                ];
                break;
            case "news_text_pic_template"://站内信图文模板
                $newsInfoRes = $this->getNewsContent($newsInfo);
                if (is_array($newsInfoRes["image"])) {
                    $image_res = $newsInfoRes["image"];
                } else {
                    $image_res["url"] = $newsInfoRes["image"];
                }
                $resArr = [
                    "news_title" => $newsInfoRes["title"],
                    "news_text" => $newsInfoRes["text"],
                    "news_image" => $image_res,
                    "mark_icon_type" => $newsInfoRes["icon"],
                    "redirect_url" => $newsInfoRes["url"],
                    "index_title" => $newsInfoRes["index_title"]
                ];
                break;
            case "news_pic_template"://站内信图片模板（暂时没用上）
                $resArr = [
                    "news_title" => "",
                    "news_image_list" => "",
                    "mark_icon_type" => "",
                    "redirect_url" => "",
                ];
                break;
            case "news_banner_template"://站内信banner模板
                $newsInfoRes = $this->getNewsContent($newsInfo);
                if(empty($newsInfoRes)) {
                    return null;
                }
                if (is_array($newsInfoRes["image"])) {
                    $image_res = $newsInfoRes["image"];
                } else {
                    $image_res["url"] = $newsInfoRes["image"];
                }
                $resArr = [
                    "news_text" => $newsInfoRes["text"],
                    "news_image" => $image_res,
                    "news_title" => $newsInfoRes["title"],
                    "redirect_url" => $newsInfoRes["url"],
                ];
                break;
            case "news_miagroup_template":
                //站内信蜜芽圈消息模板
                $newsInfoRes = $this->getNewsContent($newsInfo);
                $resArr = [
                    "user_info" => $newsInfoRes["user_info"],
                    "news_text" => $newsInfoRes["text"],
                    "news_refer_text" => $newsInfoRes["refer_text"],
                    "news_refer_image" => $newsInfoRes["refer_img"],
                    "redirect_url" => $newsInfoRes["url"],
                    "index_title" => $newsInfoRes["index_title"]
                ];
                break;
        }
        return $resArr;
    }

    /**
     * 根据不同类型获取消息内容
     * @param $newsInfo
     * @return mixed
     */
    public function getNewsContent($newsInfo)
    {
        $text = "";
        $title = "";
        $image = "";
        $icon = "";
        $url = "";
        $userInfo = [];
        $refer_text = "";
        $index_title = "";
        $refer_img = [
            "url" => "",
            "width" => 0,
            "height" => 0
        ];
        $app_mapping_config = \F_Ice::$ins->workApp->config->get('busconf.app_mapping');
        switch ($newsInfo['news_type']) {
            case "order":
                $title = "订单消息";
                $text = $newsInfo["ext_info"]["content"];
                $url = sprintf($app_mapping_config['order_list'], 0, 3);
                break;
            case "order_unpay":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."共".count($newsInfo["ext_info"]["item_ids"])."件商品付款后会尽快为您发货";
                $title = "订单".$newsInfo["source_id"]."未付款";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_list'], 0, 1);
                break;
            case "order_cancel":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."共".count($newsInfo["ext_info"]["item_ids"])."件商品未能及时付款被取消啦";
                $title = "订单".$newsInfo["source_id"]."已取消";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_list'], 0, 0);
                break;
            case "order_send_out":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."共".count($newsInfo["ext_info"]["item_ids"])."件商品发货啦";
                $title = "订单".$newsInfo["source_id"]."已发货";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_detail'], $newsInfo["source_id"]);
                break;
            case "order_delivery":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."共".count($newsInfo["ext_info"]["item_ids"])."件商品开始派送";
                $title = "订单".$newsInfo["source_id"]."开始派送";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_detail'], $newsInfo["source_id"]);
                break;
            case "order_received":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."共".count($newsInfo["ext_info"]["item_ids"])."件商品已到，评价晒单得蜜豆哟~";
                $title = "订单".$newsInfo["source_id"]."已签收";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_detail'], $newsInfo["source_id"]);
                break;
            case "order_auto_confirm":
                $text = "您的订单将在3日后自动确认收货，如果还没有收到包裹，请到“我的订单”延长收货";
                $title = "订单".$newsInfo["source_id"]."将在3日后自动确认收货";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['order_list'], 0, 3);
                break;
            case "return_audit_pass":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."申请退货，已审核";
                $title = "退货申请".$newsInfo["source_id"]."审核通过";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['return_detail'], $newsInfo["source_id"]);
                break;
            case "return_audit_refuse":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."申请退货，未能通过审核";
                $title = "退货申请".$newsInfo["source_id"]."未能通过审核";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['return_detail'], $newsInfo["source_id"]);
                break;
            case "return_overdue":
                $text = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_name"]."申请退货，请尽快填写物流信息";
                $title = "退货申请".$newsInfo["source_id"]."即将过期";
                $image = $this->itemInfo[$newsInfo["ext_info"]["item_ids"][0]]["item_img"];
                $url = sprintf($app_mapping_config['return_detail'], $newsInfo["source_id"]);
                break;
            case "refund_success":
                $text = "钱款将原路退回，预计入账时间1-5个工作日，如果超时没有收到，请联系客服";
                $title = "您有一笔".$newsInfo["ext_info"]["money"]."元的退款已成功";
                $url = $app_mapping_config['order_refund'];
                $image = $this->config["refund_image"];
                break;
            case "refund_fail":
                $text = "退款申请未能通过，请查看详情";
                $title = "您有一笔".$newsInfo["ext_info"]["money"]."元的退款已失败";
                $url = $app_mapping_config['order_refund'];
                $image = $this->config["refund_image"];
                break;
            case "plus_active":
                break;
            case "plus_new_members":
                $title = "有" . $newsInfo['ext_info']['num'] . "个用户在您的影响下成为Plus会员";
                $text = "太棒啦，在您的影响下有" . $newsInfo['ext_info']['num'] . "个用户成为了Plus会员，多喝水多休息，不要太劳累哟~";
                $url = $app_mapping_config['plus_manage_member'];
                break;
            case "plus_new_fans":
                $title = "有" . $newsInfo['ext_info']['num'] . "个用户成为了您的粉丝";
                $text = "有" . $newsInfo['ext_info']['num'] . "个用户成为了您的粉丝，哇~粉丝有限，魅力无限！棒棒哒！！";
                $url = $app_mapping_config['plus_manage_fans'];
                break;
            case "plus_get_commission":
                $title = "恭喜，您获得" . $newsInfo['ext_info']['money'] . "元佣金";
                $text = "您收获了" . $newsInfo['ext_info']['money'] . "元佣金，可喜可贺，继续带领您的小伙伴们为大家搜索优质商品吧~";
                $icon = "money";
                $url = $app_mapping_config['plus_manage_income_share'];
                break;
            case "group_custom":
            case "pull_group_custom":
                if (!empty($newsInfo['news_id'])) {
                    if($this->newsInfo[$newsInfo["news_id"]]['status'] == 0 || empty($this->newsInfo[$newsInfo["news_id"]])) {
                        return null;
                    }
                    $ext_info = json_decode($this->newsInfo[$newsInfo["news_id"]]["ext_info"], true);
                }
                $text = $ext_info["content"];
                $pattern = '/(http.*?\..*?\.com\/?)?(http.*?\..*?\.com\/\/?.*)/';
                preg_match($pattern, $ext_info["photo"], $match);
                $image = $match[2];
                if(isset($ext_info["photo_info"])) {
                    $tmp["url"] = $image;
                    $tmp["width"] = $ext_info["photo_info"]["width"];
                    $tmp["height"] = $ext_info["photo_info"]["height"];
                    $image = $tmp;
                }
                $url = $ext_info["url"];
                $title = $ext_info["title"];
                break;
            case "img_comment"://source_id记得是评论ID，ext_info补上：帖子ID
                $text = "回复：“".$this->commentInfo[$newsInfo["source_id"]]["comment"]."”";
                $url = sprintf($app_mapping_config['subject'], $newsInfo["ext_info"]["subject_id"]);
                $refer_text = $this->subjectInfo[$newsInfo["ext_info"]["subject_id"]]["text"];
                $image = $this->subjectInfo[$newsInfo["ext_info"]["subject_id"]]["cover_image"]["url"];
                //cover替换koubeilist样式
                if(!empty($image)) {
                    $pattern = '/([^@]*)(@.{1,20}?@.{1,20}?(?=@|$))/';
                    preg_match($pattern, $image, $match);
                    $refer_img = [
                        "url" => $match[1] . "@style@koubeilist",
                        "width" => 320,
                        "height" => 320
                    ];
                }
                $userInfo = $this->userInfo[$newsInfo['send_user']];
                $nick_name = $userInfo["nickname"] ? $userInfo["nickname"] : $userInfo["username"];
                $index_title = $nick_name . "评论了你";
                break;
            case "blog_quote":
                $title = "【".$this->subjectInfo[$newsInfo["source_id"]]["title"]."】中引用了你的帖子，快去看看吧~";
                $url = sprintf($app_mapping_config['subject'], $newsInfo["source_id"]);
                $text = $this->subjectInfo[$newsInfo["source_id"]]["text"];
                $image = $this->subjectInfo[$newsInfo["source_id"]]["cover_image"]["url"];
                //cover替换koubeilist样式
                if(!empty($image)) {
                    $pattern = '/([^@]*)(@.{1,20}?@.{1,20}?(?=@|$))/';
                    preg_match($pattern, $image, $match);
                    $image = [
                        "url" => $match[1] . "@style@koubeilist",
                        "width" => 320,
                        "height" => 320
                    ];
                }
                $user_name = $this->userInfo[$newsInfo['send_user']]["nickname"]?$this->userInfo[$newsInfo['send_user']]["nickname"]:$this->userInfo[$newsInfo['send_user']]["username"];
                $index_title = "您的帖子被".$user_name."在文章中引用啦";
                break;
            case "add_fine"://加精消息里面，source_id记得就是帖子ID
                $title = "您的帖子被加精，奉上50蜜豆";
                $url = sprintf($app_mapping_config['subject'], $newsInfo["source_id"]);
                $text = $this->subjectInfo[$newsInfo["source_id"]]["text"];
                $image = $this->subjectInfo[$newsInfo["source_id"]]["cover_image"]["url"];
                //cover替换koubeilist样式
                if(!empty($image)) {
                    $pattern = '/([^@]*)(@.{1,20}?@.{1,20}?(?=@|$))/';
                    preg_match($pattern, $image, $match);
                    $image = [
                        "url" => $match[1] . "@style@koubeilist",
                        "width" => 320,
                        "height" => 320
                    ];
                }
                $icon = "essence";
                $index_title = "您帖子加精啦";
                break;
            case "img_like"://点赞消息里面，source_id记得是点赞ID，ext_info补上：帖子ID
                $userInfo = $this->userInfo[$newsInfo['send_user']];
                $text = "赞了你";
                $url = sprintf($app_mapping_config['subject'], $newsInfo["ext_info"]['subject_id']);
                $refer_text = $this->subjectInfo[$newsInfo["ext_info"]['subject_id']]["text"];
                $image = $this->subjectInfo[$newsInfo["ext_info"]['subject_id']]["cover_image"]["url"];
                if(!empty($image)) {
                    //cover替换koubeilist样式
                    $pattern = '/([^@]*)(@.{1,20}?@.{1,20}?(?=@|$))/';
                    preg_match($pattern, $image, $match);
                    $refer_img = [
                        "url" => $match[1] . "@style@koubeilist",
                        "width" => 320,
                        "height" => 320
                    ];
                }
                $nick_name = $userInfo["nickname"] ? $userInfo["nickname"] : $userInfo["username"];
                $index_title = $nick_name . "赞了你";
                break;
            case "follow":
                $userInfo = $this->userInfo[$newsInfo['send_user']];
                $text = "关注了你";
                $url = sprintf($app_mapping_config['personal_space'], $newsInfo["send_user"]);
                $nick_name = $userInfo["nickname"] ? $userInfo["nickname"] : $userInfo["username"];
                $index_title = $nick_name . "关注了你";
                break;
            case "new_subject":
                break;
            case "custom":
            case "pull_custom":
                if (!empty($newsInfo['news_id'])) {
                    if ($this->newsInfo[$newsInfo["news_id"]]['status'] == 0 || empty($this->newsInfo[$newsInfo["news_id"]])) {
                        return null;
                    }
                    $ext_info = json_decode($this->newsInfo[$newsInfo["news_id"]]["ext_info"], true);
                }
                $text = $ext_info["content"];
                $pattern = '/(http.*?\..*?\.com\/?)?(http.*?\..*?\.com\/\/?.*)/';
                preg_match($pattern, $ext_info["photo"], $match);
                $image = $match[2];
                if(isset($ext_info["photo_info"])) {
                    $tmp["url"] = $image;
                    $tmp["width"] = $ext_info["photo_info"]["width"];
                    $tmp["height"] = $ext_info["photo_info"]["height"];
                    $image = $tmp;
                }
                $url = $ext_info["url"];
                $title = $ext_info["title"];
                break;
            case "coupon":
                $title = "优惠券消息";
                $text = $newsInfo['ext_info']['content'];
                $url = $app_mapping_config['userCoupon'];
                break;
            case "coupon_receive":
                $title = "优惠券到账";
                $text = "您收到" . $newsInfo['ext_info']['num'] . "张总价值" . $newsInfo['ext_info']['money'] . "元优惠券，愉快的买买买吧~";
                $url = $app_mapping_config['userCoupon'];
                break;
            case "coupon_overdue":
                $title = "优惠券即将过期";
                $text = "您有" . $newsInfo['ext_info']['num'] . "张总价值" . $newsInfo['ext_info']['money'] . "元优惠券即将到期，快去用吧，浪费太可惜啦~";
                $url = $app_mapping_config['userCoupon'];
                break;
            case "redbag_receive":
                $title = "红包到账";
                $text = "您收到" . $newsInfo['ext_info']['num'] . "个总价值" . $newsInfo['ext_info']['money'] . "元的红包，愉快的买买买吧~";
                $url = $app_mapping_config['redbag'];
                break;
            case "redbag_overdue":
                $title = "红包即将过期";
                $text = "您有" . $newsInfo['ext_info']['num'] . "个总价值" . $newsInfo['ext_info']['money'] . "元的红包即将到期，快去用吧，浪费太可惜啦~";
                $url = $app_mapping_config['redbag'];
                break;
        }
        return ["index_title" => $index_title, "text" => $text, "title" => $title, "image" => $image, "icon" => $icon, "url" => $url, 'user_info' => $userInfo, 'refer_text' => $refer_text, 'refer_img' => $refer_img];
    }


    /**
     * 根据id，查询用户news列表
     * @param $newsIds
     * @param $userId
     * @return mixed
     */
    public function getNewListByIds($newsIds, $userId)
    {
        if (empty($newsIds) || empty($useId)) {
            $this->succ([]);
        }
        $newsList = $this->newsModel->getNewsInfoList($newsIds, $userId);
        $return = [];
        foreach ($newsIds as $val) {
            if(!empty($newsList[$val])) {
                $return[$val] = $newsList[$val];
            }
        }
        return $this->succ($return);
    }

    /**
     * 清空站内信
     * @param $userId
     * @param $type string 列表用的分类
     * @return mixed
     */
    public function clearList($userId, $type)
    {
        //type类型验证
        if (!in_array($type, $this->getShowCate()['data'])) {
            return $this->error(500, '类型不合法！');
        }
        //status状态置0
        $lastType = $this->getAllChlidren($type)['data'];
        $res = $this->newsModel->delUserNews($userId, $lastType);

        if ($res) {
            //计数修改
            list($redis, $redis_key, $expire_time) = $this->getRedis("news_count", intval($userId));
            if ($redis->exists($redis_key)) {
                if ($redis->hExists($redis_key, "total")) {
                    $redis->hIncrBy($redis_key, "total", -$res);
                    $redis->hGet($redis_key, "total");
                    $redis->expire($redis_key, $expire_time);
                }
                if ($redis->hExists($redis_key, $type)) {
                    $redis->hSet($redis_key, $type, 0);
                    $redis->expire($redis_key, $expire_time);
                }
            }
            //首页列表修改
            list($redis, $redis_key_index, $expire_time_index) = $this->getRedis("news_index", intval($userId));
            $indexList = $redis->zRevRange($redis_key_index, 0, -1);

            foreach ($indexList as $val) {
                list($category, $id) = explode(":", $val);
                if ($category == $type) {
                    $redis->zRem($redis_key_index, $val);
                }
            }
            $redis->expire($redis_key_index, $expire_time_index);
            //子分类有序集合清空
            list($redis, $redis_key_cate, $expire_time_cate) = $this->getRedis("cate_list", $type . ":" . intval($userId));
            $redis->zRemRangeByRank($redis_key_cate, 0, -1);
            $redis->expire($redis_key_index, $expire_time_cate);
            return $this->succ("清空列表成功");
        } else {
            return $this->succ("清空列表失败");
        }
    }

    /**
     * 消息push设置列表
     * @param $userId
     * @return mixed
     */
    public function pushSetting($userId)
    {
        if (empty($userId)) {
            return $this->succ(["push_setting_list" => []]);
        }
        $settingList = $this->newsModel->getUserPushSetting($userId);
        $return = [];

        $userService = new userService();
        $userInfo = $userService->getUserInfoByUids([$userId], 0, [])["data"];
        $userType = $userInfo[$userId]["mia_user_type"];//2是plus

        foreach ($this->config["push_setting_list"] as $key => $val) {
            //非plus会员不显示
            if ($userType != 2 && in_array($key, ['plus'])) {
                continue;
            }
            foreach ($settingList as $v) {
                if ($key == $v["type"]) {
                    $return[$key] = [
                        "title" => $val,
                        "type" => $key,
                        "value" => intval($v["value"])
                    ];
                }
            }
            if (!array_key_exists($key, $return)) {
                $return[$key] = [
                    "title" => $val,
                    "type" => $key,
                    "value" => 1
                ];
            }
        }
        return $this->succ(["push_setting_list" => array_values($return)]);
    }


    /**
     * 消息push设置列表
     * @param $userId
     * @param $type
     * @param $value
     * @return mixed
     */
    public function pushSet($userId, $type, $value)
    {
        if (!in_array(intval($value), [0, 1]) || !array_key_exists($type, $this->config["push_setting_list"]) || empty($userId)) {
            return $this->error("500", "参数不合法");
        }
        //查找是否存在
        $typeSetting = $this->newsModel->getTypeSet($userId, $type);
        if(empty($typeSetting)) {
            //插入
            $insertData = [
                "user_id" => $userId,
                "type" => $type,
                "value" => $value,
                "create_time" => date("Y-m-d H:i:s"),
            ];
            $res = $this->newsModel->addTypeSet($insertData);

        } else {
            $oldSetting = array_pop($typeSetting);
            if($value != $oldSetting["value"]) {
                //更新
                $res = $this->newsModel->pushSet($userId, $type, $value);
            } else {
                return $this->error("500", "无需修改");
            }
        }
        return $this->succ($res);
    }


    /**
     * 设置已读
     */
    public function setReadStatus($userId, $category)
    {
        //$category获取所有自己所有子分类，和同级所有子分类
        $children = $this->getAllChlidren($category)["data"];
        $allType = $this->getAllChlidren($this->getRootCate(array_pop($children))["data"])["data"];
        $res = $this->newsModel->setReadStatus($userId, $allType);
        return $this->succ($res);
    }

    /**
     * 清空计数
     */
    public function clearNewsNum($userId, $type)
    {
        list($redis, $redis_key, $expire_time) = $this->getRedis("news_count", intval($userId));
        $redis->hSet($redis_key, $type, 0);
        $redis->expire($redis_key, $expire_time);
    }

    /**
     * 查询分类所用模板
     */
    public function getTemplate($type)
    {
        $templateInfo = $this->config['template_news_type'];

        $template = $this->searchTemplate($templateInfo,$type);
        return $this->succ($template);
    }

    public function searchTemplate($templateInfo, $type, $before = "")
    {
        foreach ($templateInfo as $key => $val) {
            if (!is_array($val)) {
                if ($type == $val) {
                    return $before;
                } else {
                    continue;
                }
            }
            if (is_array($val)) {
                $res = $this->searchTemplate($val, $type, $key);
                if (!$res) {
                    continue;
                } else {
                    return $res;
                }
            }
        }
        return false;
    }

    /**
     * 获取redis实例，key，expire_time
     * @param $key string 变量名
     * @param $format_str string 格式化的变量
     * @return array
     */
    public function getRedis($key, $format_str = null)
    {
        if ($this->redis) {
            $redis = $this->redis;
        } else {
            $redis = new Redis('news/default');
            $this->redis = $redis;
        }
        $redis_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.' . $key);
        if (!empty($format_str)) {
            $key = sprintf($redis_info['key'], $format_str);
        } else {
            $key = $redis_info['key'];
        }
        $expire_time = $redis_info['expire_time'];
        return [$redis, $key, $expire_time];
    }

    /*=====分类关系操作。=====*/
    /**
     * 查询最低级分类的上个父级分类（展示分类，列表用）
     */
    public function getAncestor($lastType)
    {
        //检查是否是最低级分类
        if (!in_array($lastType, $this->getFinalCate()['data'])) {
            return $this->succ("");
        }
        $layer = $this->config['layer'];
        $belongTo = $this->isLastChild($lastType, $layer);
        return $this->succ($belongTo);
    }

    public function isLastChild($lastType, $layer, $before = '')
    {
        foreach ($layer as $key => $val) {
            if (!is_array($val)) {
                if ($lastType == $val) {
                    return $before;
                } else {
                    continue;
                }
            }
            if (is_array($val)) {
                $res = $this->isLastChild($lastType, $val, $key);
                if (!$res) {
                    continue;
                } else {
                    return $res;
                }
            }
        }
        return false;
    }

    /**
     * 获取非最低级分类 (用于展示列表的分类)
     */
    public function getShowCate()
    {
        $layer = $this->config['layer'];
        $showArr = array_unique($this->getNextLevel($layer));
        return $this->succ(array_values($showArr));
    }

    public function getNextLevel($layer, $before = [])
    {
        $cateArr = [];
        foreach ($layer as $key => $val) {
            if (is_array($val)) {
                $cateArr = array_merge($cateArr, $this->getNextLevel($val, [$key]));
            } else {
                $cateArr = array_merge($cateArr, $before);
            }
        }
        return $cateArr;
    }

    /**
     * 获取最低级分类
     */
    public function getFinalCate()
    {
        $layer = $this->config['layer'];
        $lastCate = $this->getNextCate($layer);
        return $this->succ($lastCate);
    }

    public function getNextCate($layer)
    {
        $cateArr = [];
        foreach ($layer as $val) {
            if (is_array($val)) {
                $cateArr = array_merge($cateArr, $this->getNextCate($val));
            } elseif (!empty($val)) {
                $cateArr[] = $val;
            }
        }
        return $cateArr;
    }

    /**
     * 获取指定分类下，所有最低级分类
     * @param $type string 非最低级分类
     */
    public function getAllChlidren($type)
    {
        $layer = $this->config['layer'];
        $newLayer = $this->getChlidren($layer,$type);
        $lastCate = $this->getNextCate($newLayer);
        return $this->succ($lastCate);
    }

    public function getChlidren($layer, $type)
    {
        foreach ($layer as $key => $val) {
            if ($key == $type && is_array($val)) {
                return $val;
            }
            if (!is_array($val)) {
                continue;
            }
            if ($key != $type && is_array($val)) {
                $res = $this->getChlidren($val, $type);
                if (empty($res)) {
                    continue;
                } else {
                    return $res;
                }
            }
        }
        return [];
    }

    /**
     * 获取最低级分类的根分类
     * @return mixed
     */
    public function getRootCate($type)
    {
        $layer = $this->config['layer'];
        foreach ($layer as $key => $val) {
            if (in_array($type, $this->getAllChlidren($key)["data"])) {
                return $this->succ($key);
            }
        }
    }
    /*=====分类关系操作。end=====*/

    /*=============5.7新版本消息end=============*/


    /**
     * 发布一条消息  |  旧版本
     *
     * @param $type              enum 消息类型 enum('single','all')
     * @param $resourceType      enum 消息相关资源类型 enum('group','outlets')
     * @param $resourceSubType   enum 消息相关资源子类型 enum('group','img_comment','img_like','follow','mibean','order','score','coupon','productDetail','freebuy','special','outletsList')
     * @param $sendFromUserId    int  消息所属用户id
     * @param $toUserId          int  消息所属用户id
     * @param $resourceId        int  消息相关资源id
     * @param $content           string 消息内容
     *
     **/
    public function addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = "")
    {
        $data = $this->newsModel->addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId, $resourceId, $content);
        return $this->succ($data);
    }


    /*================新消息系统================*/

    /**
     * 获取用户消息列表
     * @param $userId int 用户ID
     * @param $type string 消息类型 group：社交；outlets：特卖；
     * @param $page int 页数
     * @param $count int 每页个数
     * @return mixed
     */
    public function getUserNewsList($userId, $type = "outlets", $page = 1, $count = 20)
    {
        if (empty($userId) || !in_array($type, ["group", "outlets"])) {
            return $this->succ([]);
        }
        $page = $page ? $page : 1;
        $newsIdList = $this->newsModel->getUserNewsList($userId, $type);
        $offset = ($page - 1) * $count;
        $newsIds = array_slice($newsIdList, $offset, $count);
        //批量获取用户消息
        $newsList = $this->getBatchNewsInfo($newsIds, $userId)["data"];//分表的用户ID必传
        //格式化结果集
        $newsList = $this->formatNewList($newsList, $userId);
        //消息置已读 is_read=1，计数清零
        $this->newsModel->changeReadStatus($userId);
        $this->newsModel->clearNewsNum($userId);

        return $newsList;
    }


    /**
     * 批量获取用户消息详情
     * @param $newsIds
     * @param $userId
     * @return mixed
     */
    public function getBatchNewsInfo($newsIds, $userId)
    {
        if (!is_array($newsIds) || empty($newsIds) || empty($userId)) {
            return $this->succ([]);
        }
        //分表的用户ID必传
        $newsList = $this->newsModel->getBatchNewsInfo($newsIds, $userId);
        return $this->succ($newsList);
    }


    /**
     * 格式化用户消息类别，消息格式完全符合旧版本 before 5.5  2017/05/26
     * @param $newsList
     * @param $userId
     * @return array
     */
    public function formatNewList($newsList, $userId)
    {
        if (empty($newsList) || !is_array($newsList)) {
            return [];
        }
        $systemNewsIds = [];
        $commentIds = [];
        $userIds = [];
        $subjectIds = [];
        foreach ($newsList as $val) {
            if (!empty($val["send_user"])) {
                $userIds[] = $val["send_user"];
            }
            //系统消息
            if ($val["news_type"] == "group_custom" || $val["news_type"] == "custom") {
                $systemNewsIds[] = $val["news_id"];
            }
            if ($val["news_type"] == "img_comment") {
                $commentIds[] = $val["source_id"];
            }
            if ($val["news_type"] == "img_like" || $val["news_type"] == "add_fine") {
                //收集帖子信息
                $subjectId = json_decode($val["ext_info"], true)["subject_id"];
                if (empty($subjectId)) {
                    continue;
                }
                $subjectIds[] = $subjectId;
            }
            if ($val["news_type"] == "follow") {
                //获取互相关注状态
                $followIds[] = $val["send_user"];
            }

        }
        //取发送人信息
        if (!empty($userIds)) {
            $userService = new userService();
            $sendUserInfos = $userService->getUserInfoByUids($userIds)["data"];
        }
        //取系统消息
        if (!empty($systemNewsIds)) {
            $systemNewsList = $this->newsModel->getSystemNewsList($systemNewsIds);
        }
        //取评论消息
        if (!empty($commentIds)) {
            $commentService = new commentService();
            $commentList = $commentService->getBatchComments($commentIds, ['subject', 'parent_comment'])["data"];
        }
        //取帖子信息，目前是为了图片
        if (!empty($subjectIds)) {
            $subjectService = new subjectService();
            $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, $userId, []);
        }
        //查询是否互相关注
        if (!empty($followIds)) {
            //获取互相关注状态
            $userRelationService = new userRelationService();
            $followInfos = $userRelationService->getMeRelationWithUser($userId, $followIds)["data"];
        }

        //新旧消息类型对应上
        $listFormat = [];//var_dump($newsList);
        $miaAngelInfo = [];
        $miaAngelInfo['icon'] = 'https://img03.miyabaobei.com/d1/p5/2016/11/11/b7/8a/b78a0759965feae977bfa1f6da0cf2d5594917861.png';
        $miaAngelInfo['user_id'] = '1026069';


        foreach ($newsList as $news) {
            $tmp = [];
            $tmp["id"] = $news["id"];
            $tmp["is_read"] = intval($news["is_read"]);
            $tmp["created"] = $news["create_time"];

            $tmp["custom_title"] = '';
            $tmp["custom_photo"] = '';
            $tmp["custom_url"] = '';

            $tmp["resource_id"] = $news['source_id'];
            switch ($news["news_type"]) {
                //自定义消息类型
                case "group_custom":
                case "custom":
                    $systemNews = $systemNewsList[$news["news_id"]];
                    if (!empty($systemNews)) {
                        $ext_info = json_decode($systemNews["ext_info"], true);
                    } else {
                        $ext_info = json_decode($news["ext_info"], true);
                    }

                    $tmp["content"] = $ext_info["content"];
                    $tmp["custom_title"] = $ext_info["title"];
                    $tmp["custom_photo"] = $ext_info["photo"];
                    $tmp["custom_url"] = $ext_info["url"];

                    $tmp["resource_sub_type"] = "custom";
                    $tmp["resource_id"] = "0";

                    //蜜芽小天使
                    if (empty($news["send_user"])) {
                        $tmp["user_info"] = $miaAngelInfo;
                    } else {
                        $sendUserInfos[$news["send_user"]];
                    }
                    break;
                //评论
                case "img_comment":
                    $commentInfo = $commentList[$news['source_id']];
                    if (empty($commentInfo)) {
                        continue 2;
                    }

                    $tmp['img'] = strval($commentInfo['subject']['image_url'][0]);

                    $tmp['resource_id'] = $commentInfo['subject_id'];
                    $tmp['resource_sub_type'] = "image";

                    if ($commentInfo['fid'] == 0 && $commentInfo['user_id'] != $userId) {
                        //父评论id为0，且不是自己评论自己
                        $tmp['content'] = "评论了你的帖子";
                    } else {
                        //父评论为1，或自己评论自己
                        $subjectInfo = $commentInfo['subject'];
                        $commentsInfo = $commentInfo['parent_comment'];
                        if ($subjectInfo['user_id'] == $userId && $commentsInfo['user_id'] != $userId) {
                            //帖子是是自己的，且不是自己评论自己
                            $tmp['content'] = "评论了你的帖子";
                        } else {
                            $tmp['content'] = "回复了你的评论";
                        }
                    }
                    if ($commentInfo['status'] == 0) {
                        $tmp['resource_content'] = "评论已删除";
                    } elseif ($commentInfo['status'] == -1) {
                        $tmp['resource_content'] = "评论已屏蔽";
                    } else {
                        $tmp['resource_content'] = $commentInfo['comment'];
                    }
                    $tmp["user_info"] = $sendUserInfos[$news["send_user"]];
                    break;
                //赞
                case "img_like":
                    $curSubjectId = json_decode($news["ext_info"], true)["subject_id"];
                    if (!empty($curSubjectId)) {
                        $tmp['img'] = strval($subjectInfos[$curSubjectId]['image_url'][0]);
                        $tmp['resource_id'] = $curSubjectId;
                    } else {
                        continue 2;
                    }
                    $tmp['resource_sub_type'] = "image";
                    $tmp['content'] = "赞了你的帖子";
                    $tmp["user_info"] = $sendUserInfos[$news["send_user"]];
                    break;
                //关注
                case "follow":
                    $isFollowToo = $followInfos[$news['send_user']]["relation_with_him"];
                    $tmp['content'] = "关注了你";
                    $tmp['resource_sub_type'] = "follow";
                    $tmp['is_follow'] = $isFollowToo ? 1 : 0;

                    $tmp["user_info"] = $sendUserInfos[$news["send_user"]];
                    break;
                //加精
                case "add_fine":
                    $curSubjectId = json_decode($news["ext_info"], true)["subject_id"];
                    if (!empty($curSubjectId)) {
                        $tmp['img'] = strval($subjectInfos[$curSubjectId]['image_url'][0]);
                    } else {
                        continue 2;
                    }
                    $tmp['resource_sub_type'] = "image";
                    $tmp['resource_id'] = $news['source_id'];
                    $tmp['resource_content'] = "您分享的帖子被加精华啦，帖子会有更多展示机会，再奉上5蜜豆奖励";

                    $tmp["user_info"] = $sendUserInfos[$news["send_user"]];
                    break;
                case "group_coupon":
                case "coupon":
                    $curContent = json_decode($news["ext_info"], true)["content"];
                    if (empty($curContent)) {
                        continue 2;
                    }
                    $tmp['content'] = $curContent;
                    $tmp['resource_sub_type'] = "coupon";
                    //优惠券消息发送人是蜜芽小天使
                    $tmp["user_info"] = $miaAngelInfo;
                    break;
                case "order":
                    $curContent = json_decode($news["ext_info"], true)["content"];
                    if (empty($curContent)) {
                        continue 2;
                    }
                    $tmp['content'] = $curContent;
                    $tmp['resource_sub_type'] = "order";
                    //订单消息发送人是蜜芽小天使
                    $tmp["user_info"] = $miaAngelInfo;
                default:
                    continue 2;
            }
            $listFormat[] = $tmp;
        }
        return $this->succ($listFormat);
    }


    /**
     * 获取用户未读消息计数
     * @param int $userId
     */
    public function getUserNewsNum($userId)
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return $this->succ([
                "group_count" => 0,
                "outlets_count" => 0,
                "index_group_count" => 0
            ]);
        }
        $index_group_count = $this->newsModel->getUserGroupNewsNum($userId);
        list($outlets_count, $group_count) = $this->newsModel->getUserAllNewsNum($userId);
        return $this->succ([
            "group_count" => $group_count,
            "outlets_count" => $outlets_count,
            "index_group_count" => $index_group_count
        ]);
    }


    /**
     * 新增单条用户消息
     *
     * @param $type string 消息类型
     * ======================消息类型======================
     *
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
     *
     * @param $sendFromUserId    int  发送人ID
     * @param $toUserId          int  接收用户ID
     * @param $resourceId        int  消息相关资源id
     * @param $content_info           array 消息内容
     *
     */
    public function addUserNews($type, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content_info = [])
    {
        $insert_data = [];
        //判断type类型是否合法
        if (!in_array($type, \F_Ice::$ins->workApp->config->get('busconf.news.all_type'))) {
            return $this->error(500, '类型不合法！');
        }
        $insert_data['news_type'] = $type;

        $insert_data['user_id'] = $toUserId;
        $insert_data['send_user'] = $sendFromUserId;
        $insert_data['source_id'] = $resourceId;
        $ext_info = [];
        //额外信息
        $userService = new userService();
        switch ($type) {
            //自定义的标题，图片，内容，url
            //目前特卖outlets的，表里发送人没有指定ID，输出时统一指定的蜜芽小天使（1026069）；社交自定义消息指定的了发送人，也为蜜芽小天使（1026069）；都可以替换的；2017-5-25；
            case "custom":
            case "group_custom":
                if (!empty($content_info['title'])) {
                    $ext_info['title'] = $content_info['title'];
                }
                if (!empty($content_info['content'])) {
                    $ext_info['content'] = $content_info['content'];
                }
                if (!empty($content_info['photo'])) {
                    $ext_info['photo'] = $content_info['photo'];
                }
                if (!empty($content_info['url'])) {
                    $ext_info['url'] = $content_info['url'];
                }
                //$miaAngelUid = \F_Ice::$ins->workApp->config->get('busconf.user.miaAngelUid');
                //$ext_info["user_info"] = $userService->getUserInfoByUids([$miaAngelUid])["data"][$miaAngelUid];//蜜芽小天使
                break;
            case "img_comment"://图片，帖子评论
                //评论消息里面，source_id记得是评论ID，ext_info补上：帖子ID
                $commentService = new commentService();
                $subject_id = $commentService->getBatchComments([$resourceId], [])["data"][$resourceId]["subject_id"];
                if (!empty($subject_id)) {
                    $ext_info["subject_id"] = $subject_id;
                }
                break;
            case "img_like":
                //点赞消息里面，source_id记得是点赞ID，ext_info补上：帖子ID
                $praiseService = new praiseService();
                $subject_id = $praiseService->getPraisesByIds([$resourceId], [])["data"][$resourceId]["subject_id"];
                if (!empty($subject_id)) {
                    $ext_info["subject_id"] = $subject_id;
                }
                break;
            case "follow":
                break;
            case "add_fine":
                //加精消息里面，source_id记得就是帖子ID
                $ext_info["subject_id"] = $resourceId;
                break;
            case "group_coupon":
                if (empty($content_info['content'])) {
                    return $this->error(500, '发送内容不能为空！');
                }
                $ext_info['content'] = $content_info['content'];
                break;
            case "coupon":
                if (empty($content_info['content'])) {
                    return $this->error(500, '发送内容不能为空！');
                }
                $ext_info['content'] = $content_info['content'];
                break;
            case "order":
                if (empty($content_info['content'])) {
                    return $this->error(500, '发送内容不能为空！');
                }
                $ext_info['content'] = $content_info['content'];
                break;
        }

        if (isset($content_info['create_time'])) {
            $insert_data['create_time'] = $content_info['create_time'];
        } else {
            $insert_data['create_time'] = date("Y-m-d H:i:s");
        }
        if (isset($content_info['id'])) {
            $insert_data['id'] = $content_info['id'];
        }
        if (isset($content_info['news_id'])) {
            $insert_data['news_id'] = $content_info['news_id'];
        }
        if (isset($content_info['is_read'])) {
            $insert_data['is_read'] = $content_info['is_read'];
        }
        if (isset($content_info['status'])) {
            $insert_data['status'] = $content_info['status'];
        }
        if (!empty($ext_info)) {
            $insert_data['ext_info'] = json_encode($ext_info);
        }
        //蜜芽圈防止重复导入 || 系统消息防止重复
        if (in_array($type, ['custom','group_custom','img_comment', 'img_like', 'follow', 'add_fine'])) {
            switch ($type) {
                case "img_comment"://img_comment  source_id 评论ID
                case "img_like": //img_like source_id 点赞ID
                case "add_fine"://add_fine  source_id 帖子ID
                    $uniqueCondition = [
                        'user_id' => $toUserId,
                        'source_id' => $resourceId,
                        'news_type' => $type
                    ];
                    break;
                case "follow"://follow 无source_id  通过发送人去重
                    $uniqueCondition = [
                        'user_id' => $toUserId,
                        'send_user' => $sendFromUserId,
                        'news_type' => $type
                    ];
                    break;
                case "custom":
                case "group_custom":
                    $uniqueCondition = [
                        'user_id' => $toUserId,
                        'news_id' => $insert_data['news_id'],
                        'news_type' => $type
                    ];
                    break;
            }
            //查询user_news_%d表
            $uniqueRes = $this->newsModel->checkUnique($uniqueCondition);
            if(!empty($uniqueRes)) {
                return $this->error(500, '已经添加数据，无需重复添加！');
            }
        }
        //添加消息
        $insertRes = $this->newsModel->addUserNews($insert_data, $type);
        if (!$insertRes) {
            return $this->error(500, '发送用户消息失败！');
        } else {
            return $this->succ("发送成功");
        }
    }


    /**
     * 新增系统消息，系统消息只给批量用户发，不会给单人发
     *
     * @param $type string 消息类型
     * ======================消息类型======================
     *
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
     * 'coupon'  优惠券
     * 'custom'  自定义 （跳转为自定义链接）
     *
     * @param $content_info array 消息内容（标题，图片，内容，url）
     * @param $send_time string 发送时间
     * @param $abandon_time string 失效时间
     * @param $user_group string 用户组
     * @param $source_id int 相关资源ID
     *
     */
    public function addSystemNews($type, $content_info, $send_time, $abandon_time, $send_type = 1, $user_group = "", $send_user = 0, $source_id = 0)
    {
        $insert_data = [];
        //判断type类型是否合法
        if (!in_array($type, \F_Ice::$ins->workApp->config->get('busconf.news.all_type'))) {
            return $this->error(500, '类型不合法！');
        }
        $insert_data['news_type'] = $type;//一般为 custom
        $insert_data['send_user'] = 0; //蜜芽兔/蜜芽小天使，读的时候指定

        //标题，图片，内容，url
        if (isset($content_info['title'])) {
            $ext_arr["title"] = $content_info['title'] ? $content_info['title'] : "";
        }
        if (isset($content_info['content'])) {
            $ext_arr["content"] = $content_info['content'] ? $content_info['content'] : "";
        }
        if (isset($content_info['photo'])) {
            $ext_arr["photo"] = $content_info['photo'] ? $content_info['photo'] : "";
        }
        
        if (isset($content_info['photo_info'])) {
            $ext_arr["photo_info"] = $content_info['photo_info'] ? $content_info['photo_info'] : "";
        }
        if (isset($content_info['url'])) {
            $ext_arr["url"] = $content_info['url'] ? $content_info['url'] : "";
        }
        if (isset($content_info['users']) && !empty($content_info['users'])) {
            $ext_arr["users"] = $content_info['users'];
        }
        if (!empty($source_id)) {
            $ext_arr["source_id"] = $source_id;
        }
        $insert_data['ext_info'] = json_encode($ext_arr);

        if ($type == 'single' && strtotime($send_time) < (time() + 600)) {
            return $this->error(500, '发送时间应在未来的十分钟之外！');
        }
        $insert_data['send_time'] = $send_time;
        $insert_data['send_user'] = $send_user;
        $insert_data['abandon_time'] = $abandon_time;//废弃时间，过了此时间用户不拉取该消息
        $insert_data['user_group'] = $user_group;
        $insert_data['create_time'] = date("Y-m-d H:i:s");
        $insert_data['send_type'] = $send_type;
        //添加消息
        $insertNewsRes = $this->newsModel->addSystemNews($insert_data);
        return $this->succ($insertNewsRes);
    }


    /**
     * 用户拉取系统消息
     */
    public function pullUserSystemNews($userId)
    {
        if (empty(intval($userId))) {
            return $this->error(500, '用户ID不为空！');
        }
        //获取某个用户消息里最大的系统消息ID
        $maxSystemId = $this->newsModel->getMaxSystemId($userId);
        $create_date = '';
        if (empty($maxSystemId)) {
            //新用户，获取用户的注册时间
            $userService = new userService();
            $create_date = $userService->getUserInfoByUids([$userId])["data"][$userId]["create_date"];
        }
        //查询用户需要拉取的系统消息列表
        $systemNewsList = $this->newsModel->getPullList($userId, $maxSystemId, $create_date);

        //把系统消息写入用户消息表
        $insertRes = $this->newsModel->batchAddUserSystemNews($systemNewsList, $userId);
        return $this->succ($insertRes);
    }
}
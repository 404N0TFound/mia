<?php

namespace mia\miagroup\Service;

use mia\miagroup\Model\News as NewsModel;
use mia\miagroup\Service\User as userService;
use mia\miagroup\Service\Comment as commentService;
use mia\miagroup\Service\Praise as praiseService;
use mia\miagroup\Service\Subject as subjectService;
use mia\miagroup\Service\UserRelation as userRelationService;
use mia\miagroup\Lib\Redis;

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
    public function test()
    {
        list($redis, $redis_key, $expire_time) = $this->getRedis("to_write_news");
        return $this->succ($redis->lrange($redis_key, 0, -1));
    }

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
        if (empty($news_type) || !in_array($news_type, $this->config['coupon_news_type'])) {
            return $this->error(500, 'news_type参数错误！');
        }
        //获取信息
        $type = "coupon";
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
        list($redis, $redis_key, $expire_time) = $this->getRedis("to_write_news");
        $redis->lpush($redis_key, $newsInfo);
        $redis->expire($redis_key, $expire_time);

        return $this->succ("发送成功");
    }

    /**
     * 获取redis实例，key，expire_time
     * @param $key string 变量名
     * @param $format_str string 格式化的变量
     * @return array
     */
    public function getRedis($key, $format_str = "")
    {
        if ($this->redis) {
            $redis = $this->redis;
        } else {
            $redis = new Redis('news/default');
            $this->redis = $redis;
        }
        $redis_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.' . $key);
        if (!empty($format_str)) {

        } else {
            $key = $redis_info['key'];
        }
        $expire_time = $redis_info['expire_time'];
        return [$redis, $key, $expire_time];
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
     * @param $sendFromUserId int 发送人ID
     * @param $toUserId int 接收人ID
     * @param $source_id int 来源ID
     * @param $status int 消息状态
     *
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

        switch ($type) {
            /*========蜜芽圈：动态========*/
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
            /*========蜜芽圈：活动========*/
            case "group_custom"://蜜芽圈：活动，后台发送
                break;
            /*========交易物流：订单状态变更，物流动态========*/
            case "order"://订单，旧的消息类型
                break;
            case "order_unpay":
            case "order_cancel":
            case "order_send_out":
            case "order_delivery":
            case "return_audit_pass":
            case "return_audit_refuse":
            case "return_overdue":
            case "refund_success":
            case "refund_fail":
                //每个订单只有最新的一条信息，不需要按天合并
                $lastNewsInfo = $this->newsModel->getLastNews($this->config["layer"]["trade"], $toUserId, $source_id, false)[0];
                if (!empty($lastNewsInfo)) {
                    //更新
                    $insert_data['id'] = $lastNewsInfo['id'];
                    //删除和已读状态还原
                    $insert_data['is_read'] = 0;
                    $insert_data['status'] = 1;
                }
                break;
            /*========蜜芽活动：旧特卖消息========*/
            case "custom"://蜜芽活动：旧特卖消息，后台发送
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
        } else {
            return $this->succ("发送成功");
        }
    }


    /**
     * 写入消息
     */
    public function addMessage()
    {

    }


    /**
     * 获取站内信未读数
     */
    public function noReadCounts()
    {

    }

    /**
     * 站内信首页列表
     */
    public function indexList()
    {


    }

    /**
     * 删除站内信
     */
    public function deleteNews()
    {

    }


    /**
     * 设置已读
     */
    public function setReadStatus()
    {

    }

    /**
     * 清空消息
     * @param $type ：all-所有；trade-交易物流；plus-会员plus；group-蜜芽圈；activity-蜜芽活动；property-我的资产；
     */
    public function delNews($type)
    {

    }

    /**
     * 站内信子分类列表
     * @param $type ：trade-交易物流；plus-会员plus；group-蜜芽圈；activity-蜜芽活动；property-我的资产；
     */
    public function categoryList($type, $offset)
    {
        if (empty($type)) {
            return $this->succ([]);
        }
    }


    /**
     * 用户设置允许的push消息类型
     */
    public function setAcceptCate()
    {

    }

    /**
     * 查询最低级分类的上个父级分类（展示分类）
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
    public function addSystemNews($type, $content_info, $send_time, $abandon_time, $user_group = "", $send_user = 0, $source_id = 0)
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
        if (isset($content_info['url'])) {
            $ext_arr["url"] = $content_info['url'] ? $content_info['url'] : "";
        }
        if (!empty($source_id)) {
            $ext_arr["source_id"] = $source_id;
        }
        $insert_data['ext_info'] = json_encode($ext_arr);

        if (strtotime($send_time) < (time() + 600)) {
            return $this->error(500, '发送时间应在未来的十分钟之外！');
        }
        $insert_data['send_time'] = $send_time;
        $insert_data['send_user'] = $send_user;
        $insert_data['abandon_time'] = $abandon_time;//废弃时间，过了此时间用户不拉取该消息
        $insert_data['user_group'] = $user_group;
        $insert_data['create_time'] = date("Y-m-d H:i:s");
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
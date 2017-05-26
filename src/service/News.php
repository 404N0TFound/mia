<?php

namespace mia\miagroup\Service;

use mia\miagroup\Model\News as NewsModel;
use mia\miagroup\Service\User as userService;
use mia\miagroup\Service\Comment as commentService;
use mia\miagroup\Service\Praise as praiseService;
use mia\miagroup\Service\Subject as subjectService;
use mia\miagroup\Service\UserRelation as userRelationService;

class News extends \mia\miagroup\Lib\Service
{

    public $newsModel;

    public function __construct()
    {
        parent::__construct();
        $this->newsModel = new NewsModel();
    }

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
        //TODO 消息置已读，计数清零
        $this->newsModel->changeReadStatus($userId);
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
                    if (empty($systemNews)) {
                        continue;
                    }
                    $ext_info = json_decode($systemNews["ext_info"], true);
                    $tmp["content"] = $ext_info["content"];
                    $tmp["custom_title"] = $ext_info["title"];
                    $tmp["custom_photo"] = $ext_info["photo"];
                    $tmp["custom_url"] = $ext_info["url"];

                    $tmp["resource_sub_type"] = "custom";
                    $tmp["resource_id"] = "0";

                    //自定义消息发送人都是蜜芽小天使
                    $tmp["user_info"] = $miaAngelInfo;
                    break;
                //评论
                case "img_comment":
                    $commentInfo = $commentList[$news['source_id']];
                    if (empty($commentInfo)) {
                        continue;
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
                        continue;
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
                        continue;
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
                        continue;
                    }
                    $tmp['content'] = $curContent;
                    $tmp['resource_sub_type'] = "coupon";
                    //优惠券消息发送人是蜜芽小天使
                    $tmp["user_info"] = $miaAngelInfo;
                    break;
                case "order":
                    $curContent = json_decode($news["ext_info"], true)["content"];
                    if (empty($curContent)) {
                        continue;
                    }
                    $tmp['content'] = $curContent;
                    $tmp['resource_sub_type'] = "order";
                    //订单消息发送人是蜜芽小天使
                    $tmp["user_info"] = $miaAngelInfo;
                default:
                    continue;
            }
            $listFormat[] = $tmp;
        }
        return $this->succ($listFormat);
    }

    /**
     * 获取用户未读消息计数
     */
    public function getUserNewsNum()
    {

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
                $ext_info['title'] = $content_info['title'] ? $content_info['title'] : "";
                $ext_info['content'] = $content_info['content'] ? $content_info['content'] : "";
                $ext_info['photo'] = $content_info['photo'] ? $content_info['photo'] : "";
                $ext_info['url'] = $content_info['url'] ? $content_info['url'] : "";

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
        $insert_data['create_time'] = date("Y-m-d H:i:s");
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
     * @param $source_id int 相关资源ID
     * @param $user_group string 用户组
     *
     */
    public function addSystemNews($type, $content_info, $send_time, $abandon_time, $user_group = "")
    {
        $insert_data = [];
        //判断type类型是否合法
        if (!in_array($type, \F_Ice::$ins->workApp->config->get('busconf.news.all_type'))) {
            return $this->error(500, '类型不合法！');
        }
        $insert_data['news_type'] = $type;//一般为 custom
        $insert_data['send_user'] = 0; //蜜芽兔/蜜芽小天使，读的时候指定

        //标题，图片，内容，url
        $title = $content_info['title'] ? $content_info['title'] : "";
        $content = $content_info['content'] ? $content_info['content'] : "";
        $photo = $content_info['photo'] ? $content_info['photo'] : "";
        $url = $content_info['url'] ? $content_info['url'] : "";
        $insert_data['ext_info'] = json_encode(["content" => $content, "title" => $title, "photo" => $photo, "url" => $url]);

        if (strtotime($send_time) < (time() + 600)) {
            return $this->error(500, '发送时间应在未来的十分钟之外！');
        }
        $insert_data['send_time'] = $send_time;

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
        $insertRes = $this->newsModel->batchAddUserSystemNews($systemNewsList);
        return $this->succ($insertRes);
    }
}
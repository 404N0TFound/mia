<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\News as NewsModel;

class News extends \mia\miagroup\Lib\Service {
    
    public $newsModel;
    
    public function __construct(){
        parent::__construct();
        $this->newsModel = new NewsModel();
    }
    
    /**
     *@todo 发布一条消息
     *
     *@param $type              enum 消息类型 enum('single','all')
     *@param $resourceType      enum 消息相关资源类型 enum('group','outlets')
     *@param $resourceSubType   enum 消息相关资源子类型 enum('group','img_comment','img_like','follow','mibean','order','score','coupon','productDetail','freebuy','special','outletsList')
     *@param $sendFromUserId    int  消息所属用户id
     *@param $toUserId          int  消息所属用户id
     *@param $resourceId        int  消息相关资源id
     *@param $content           string 消息内容
     *
     **/
    public function addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = "") {
        $data = $this->newsModel->addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId, $resourceId, $content);
        return $this->succ($data);
    }

    /*================新消息系统================*/

    /**
     * 获取用户消息列表
     */
    public function getUserNewsList($userId, $page = 1, $count = 20)
    {
        if(empty($userId)) {
            return $this->succ([]);
        }
        $page = $page ? $page : 1;
        $newsIdList = $this->newsModel->getUserNewsList($userId);
        $offset = ($page - 1) * $count;
        $newsIds = array_slice($newsIdList, $offset, $count);
        //批量获取用户消息
        $newsList = $this->getBatchNewsInfo($newsIds, $userId);//分表的用户ID必传
        //格式化结果集
        $newsList = $this->formatNewList($newsList);

        return $newsList;

    }

    /**
     * 批量获取用户消息
     */
    public function getBatchNewsInfo($newsIds, $userId)
    {
        if(!is_array($newsIds) || empty($newsIds) || empty($userId)){
            return $this->succ([]);
        }
        //分表的用户ID必传
        $newsList = $this->newsModel->getBatchNewsInfo($newsIds, $userId);
        return $newsList;
    }

    /**
     * 格式化用户消息类别
     * @param $newsList
     */
    public function formatNewList($newsList){

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
        $insert_data['news_type'] = $type;//'img_comment', 'img_like', 'follow', 'add_fine', 'group_coupon', 'group_custom'

        $insert_data['user_id'] = $toUserId;
        $insert_data['send_user'] = $sendFromUserId;
        $insert_data['source_id'] = $resourceId;
        $ext_info = [];
        //标题，图片，内容，url
        if ($type == "group_custom") {
            $ext_info['title'] = $content_info['title'] ? $content_info['title'] : "";
            $ext_info['content'] = $content_info['content'] ? $content_info['content'] : "";
            $ext_info['photo'] = $content_info['photo'] ? $content_info['photo'] : "";
            $ext_info['url'] = $content_info['url'] ? $content_info['url'] : "";
        }

        $insert_data['create_time'] = date("Y-m-d H:i:s");
        if (!empty($ext_info)) {
            $insert_data['ext_info'] = json_encode($ext_info);
        }
        //添加消息
        $insertRes = $this->newsModel->addUserNews($insert_data);
        if (!$insertRes) {
            return $this->error(500, '发送用户消息失败！');
        }
        return $this->succ($insertRes);
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

        //查询用户需要拉取的系统消息列表
        $systemNewsList = $this->newsModel->getPullList($userId, $maxSystemId);

        //把系统消息写入用户消息表
        $insertRes = $this->newsModel->batchAddUserSystemNews($systemNewsList);
        return $this->succ($insertRes);
    }
}
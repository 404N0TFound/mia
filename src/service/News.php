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
    public function getUserNewsList($userId)
    {

    }

    /**
     * 获取用户未读消息计数
     */
    public function getUserNewsNum()
    {

    }

    /**
     * 新增用户消息
     *
     * @param $type 消息类型
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
     * @param $content           array 消息内容
     *
     */
    public function addUserNews($type, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = [])
    {

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
        $insert_data['type'] = $type;//一般为 custom
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
    public function pullUserSystemNews()
    {

    }
}
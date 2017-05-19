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
     * 'group'  圈活动
     * 'img_comment'  图片评论
     * 'img_like'  图片赞
     * 'mibean'  蜜豆消息
     * 'follow'  关注
     * 'order'  订单
     * 'score'  积分
     * 'coupon'  优惠券
     * 'productDetail'  商品详情
     * 'freebuy'  0元抢
     * 'special'  专题
     * 'outletsList'  特卖
     * 'wish_list_detail'  清单
     * 'brand_info'  品牌页
     * 'wish_like'  清单赞v3.4
     * 'wish_comment'  清单评论v3.4
     * 'wish_list_detail_app'  清单详情app用v3.4
     * 'super_wish_list'  超级清单v3.4
     * 'wish_list_category'  超级清单分类v3.4
     * 'wish_list_rebate'  清单返利V3.5
     * 'wish_list_reward'  清单打赏v3.5
     * 'act_cute_like'  卖萌赞v3.5
     * 'act_cute_comment'  卖萌评论v3.5
     * 'act_cute_detail'  卖萌详情v3.5
     * 'act_cute_detail_app'  卖萌详情appv3.5
     * 'act_cute_record'  卖萌成果v3.5
     * 'add_fine' 帖子加精 v5.4
     *
     * @param $user_id  int  消息所属用户id
     * @param $send_user  int  消息所属用户id
     * @param $source_id  int  消息相关资源id
     * @param $content  string  消息内容
     *
     */
    public function addUserNews($type, $user_id, $send_user, $source_id, $content)
    {

    }

    /**
     * 新增用户消息
     *
     * @param $type 消息类型
     * ======================消息类型======================
     * 'group'  蜜芽圈活动
     *
     * 'outletsList'  特卖
     * 'productDetail'  商品详情/单品
     * 'custom'  自定义
     *
     */
    public function addSystemNews($type, $user_id, $send_user, $source_id, $content)
    {

    }

    /**
     * 用户拉取系统消息
     */
    public function pullUserSystemNews()
    {

    }
}
<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Item as ItemModel;
use mia\miagroup\Service\Koubei as KoubeiService;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Model\Koubei as KoubeiModel;

class Item extends \mia\miagroup\Lib\Service {
    public $itemModel;
    public function __construct() {
        $this->itemModel = new ItemModel();
        $this->emojiUtil = new EmojiUtil();
        $this->koubeiModel = new KoubeiModel();
    }
    
    /**
     * 获取商品相关的套装id
     * @param $itemId 商品id
     */
    public function getSpuRelateItem($itemId){
        $spuArr = $this->itemModel->getSpuByItemId($itemId);
        return $this->succ($spuArr);
    }
    
    /**
     * 获取套装的商品
     * @param $spuId 套装id
     */
    public function getItemRelateSpu($spuId){
        $itmeArr = $this->itemModel->getItemBySpuId($spuId);
        return $this->succ($itmeArr);
    }
    
    /**
     * 根据商品id批量获取商品
     * @param int $itemIds
     */
    public function getItemList($itemIds){
        $itemInList = $this->itemModel->getBatchItemByIds($itemIds);
        return $this->succ($itemInList);
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getRelateItemList($relateFlags){
        $itemInList = $this->itemModel->getBatchItemByFlags($relateFlags);
        return $this->succ($itemInList);
    }
    
    //批量获取商品信息
    public function getBatchItemBrandByIds($itemsIds)
    {
        $data = $this->itemModel->getBatchItemBrandByIds($itemsIds);
        return $this->succ($data);
    }

    /**
     * 发布口碑
     * @param  $order_code    订单编号
     * @param  $title         标题
     * @param  $text          帖子内容
     * @param  $score         用户评分
     * @param  $image_infos   图片地址（包含宽高）
     * @param  $item_id       商品SKU
     * @param  $item_size     商品规格
     * @param  $labels        蜜芽圈标签结构体
     * @param  $issue_reward  口碑发布奖励
     */
    public function createKoubei($koubeiData = array()){
        if(empty($koubeiData)){
            $this->error(500);
        }
        $koubei = new KoubeiService();
        $res = $koubei->issueinit($koubeiData['user_id'], $koubeiData['order_code'], $koubeiData['item_id']);
        if(isset($res['code'])  == true && $res['code'] == 0){
            $issue_reward = $res['data']['issue_reward'];
            if($koubeiData['issue_reward'] != $issue_reward){
                $this->error(500);
            }
        }
        $koubeiData['content'] = $this->emojiUtil->emoji_unified_to_html($koubeiData['text']);
        $koubeiData = $this->itemModel->insertKoubei($koubeiData);
        $koubeiInsertId = $this->koubeiModel->saveKoubei($koubeiData);
        if(empty($koubeiInsertId)) {
            return $this->error(6101);
        }
        return $this->succ($koubeiInsertId);
    }
}

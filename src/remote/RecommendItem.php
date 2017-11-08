<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\RemoteCurl;

class RecommendItem
{
    private $session_info;
    
    public function __construct($session_info) {
        $this->session_info = $session_info;
    }
    
    /**
     * 获取推荐商品
     * @param $type 推荐场景：cart购物车 home个人中心 payment支付完成 item商品详情 sellout特卖 itemgroupon团购
     * @param $item_ids 关联商品
     */
    public function getRecommedItemList($type, $count = 10, $item_ids = array(), $viewed_items = array()) {
        switch ($type) {
            case 'cart':
                $post_params['recs_id'] = '1000100';
                break;
            case 'home':
                $post_params['recs_id'] = '1000101';
                break;
            case 'payment':
                $post_params['recs_id'] = '1000102';
                break;
            case 'item':
                $post_params['recs_id'] = '1000103';
                break;
            case 'sellout':
                $post_params['recs_id'] = '1000103';
                break;
            case 'itemgroupon':
                $post_params['recs_id'] = '3000103';
                break;
        }
        if (!empty($this->session_info['version'])) {
            list($os, $version) = explode('_', $this->session_info['version'], 2);
        } else {
            $os = 'ios';
        }
        $post_params['device'] = $os;
        $post_params['nums'] = $count;
        $post_params['source'] = 'miagroup';
        $post_params['click'] = !empty($viewed_items) ? implode(',', $viewed_items) : '';
        $post_params['sku'] = !empty($item_ids) ? implode(',', $item_ids) : '';;
        if ($this->session_info['current_uid'] > 0) {
            $post_params['uid'] = $this->session_info['current_uid'];
        }
        if (!empty($this->session_info['dvc_id'])) {
            $post_params['dvc_id'] = $this->session_info['dvc_id'];
        }
        $remote_curl = new RemoteCurl('item_recommend');
        $result = $remote_curl->curl_remote('/recom/', $post_params);
        $recommend_items = array();
        if (!empty($result['recs_list'])) {
            foreach ($result['recs_list'] as $recomm) {
                $recommend_items[] = $recomm['csku'];
            }
        }
        unset($result['recs_list']);
        return ['rec_info' => $result, 'sku_ids' => $recommend_items];
    }

    /**
     * 获取个性化笔记列表
     * @param $userId
     * @param $tabId
     * @return array [sujectId_subject]  口碑帖子
     */
    public function getRecommendNoteList($userId, $tabId)
    {
        return ['266898_subject','267343_subject','267342_subject','267341_subject','267339_subject','267338_subject','267337_subject'];
    }
}
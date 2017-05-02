<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class SetCoupon extends \DB_Query {

    protected $tableName = '';
    protected $dbResource = 'miagroupums';
    protected $defaultTable = 'group_coupon_rule';

    /*
     * 获取代金券发放关系
     * */
    public function getCouponInfo($limit = 0, $offset = 0, $condition = array())
    {
        $this->tableName = $this->defaultTable;
        $order_by = 'created_time desc';
        $where = [];
        $field = '*';
        if(!empty($condition)) {
            foreach ($condition as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    case 'category_id':
                        $where[] = [':eq','category_id', $v];
                        break;
                    case 'brand_id':
                        $where[] = [':eq','brand_id', $v];
                        break;
                    case 'item_id':
                        $where[] = [':eq','item_id', $v];
                        break;
                    default:
                        $where[] = [':eq','status', $v];
                }
            }
        }
        $data = $this->getRows($where, $field, $limit, $offset, $order_by);
        return $data;
    }

    /*
     * 代金券规则添加
     * */
    public function addCoupon($data)
    {
        if(empty($data)) {
            return 0;
        }
        $ext_info = '';
        $insert = array();
        $ext_param = array();
        $date = date("Y-m-d H:i:s", time());
        $this->tableName = $this->defaultTable;
        $item_ids = explode(',', $data['item_ids']);
        $start_time = !empty($data['start_time']) ? $data['start_time'] : '';
        $end_time = !empty($data['end_time']) ? $data['end_time'] : '';
        $created_time = !empty($data['created_time']) ? $data['created_time'] : $date;
        $update_time = !empty($data['update_time']) ? $data['update_time'] : $date;
        $category_id = !empty($data['category_id']) ? $data['category_id'] : 0;
        $brand_id = !empty($data['brand_id']) ? $data['brand_id'] : 0;
        if(!empty($data['chat_count'])) {
            $ext_param['chat_count'] = $data['chat_count'];
        }
        if(!empty($data['image_count'])) {
            $ext_param['image_count'] = $data['image_count'];
        }
        if(!empty($data['prompt'])) {
            $ext_param['prompt'] = $data['prompt'];
        }
        if(!empty($data['intro'])) {
            $ext_param['intro'] = $data['intro'];
        }
        if(!empty($data['remarks'])) {
            $ext_param['remarks'] = $data['remarks'];
        }
        if(!empty($data['image'])) {
            $ext_param['image']['url'] = $data['image'];
            // 获取宽高
            $image_info = getimagesize($data['image']);
            $ext_param['image']['width'] = $image_info[0];
            $ext_param['image']['height'] = $image_info[1];
        }
        if(!empty($ext_param)) {
            $ext_info = json_encode($ext_param);
        }

        foreach($item_ids as $key => $item_id) {
            $insert[$key]['item_id'] = $item_id;
            $insert[$key]['ext_info'] = $ext_info;
            $insert[$key]['update_time'] = $update_time;
            $insert[$key]['created_time'] = $created_time;
            $insert[$key]['start_time'] = $start_time;
            $insert[$key]['end_time'] = $end_time;
            $insert[$key]['category_id'] = $category_id;
            $insert[$key]['brand_id'] = $brand_id;
        }
        $res = $this->multiInsert($insert);
        return $res;
    }

    /*
     * 代金券规则删除
     * */
    public function deleteCoupon($id)
    {
        if(empty($id)) {
            return 0;
        }
        $this->tableName = $this->defaultTable;
        $where[] = [':eq', 'id', $id];
        $setData[] = ['status', 0];
        $affect = $this->update($setData, $where);
        return $affect;
    }
}
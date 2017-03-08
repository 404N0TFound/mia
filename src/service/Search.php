<?php
namespace mia\miagroup\Service;

use \mia\miagroup\Lib\Service;
use mia\miagroup\Model\Album as AlbumModel;
use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Remote\Search as SearchRemote;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\Koubei as KoubeiService;
use mia\miagroup\Util\NormalUtil;

/**
 * 蜜芽圈搜索服务类
 * Class Search
 * @package mia\miagroup\Service
 */
class Search extends Service
{
    public function __construct()
    {
        $this->abumModel = new AlbumModel();
        $this->userService = new UserService();
        $this->subjectService = new Subject();
        $this->koubeiModel = new KoubeiModel();
        $this->itemService = new ItemService();
        $this->koubeiService = new KoubeiService();
        $this->searchRemote = new SearchRemote($this->ext_params);
        parent::__construct();
    }

    /**
     * 笔记搜索
     * @param $param
     * @return mixed
     */
    public function noteSearch($param)
    {
        if(empty($param['key'])){
            return $this->succ([]);
        }
        $searchArr["query"] = $param['key'];
        $searchArr["pn"] = $param['page'] - 1;
        $searchArr["rn"] = $param['count'] ? $param['count'] : 20;
        if (!in_array($param['order'], array('normal', 'hot', 'new'))) {
            return $this->succ([]);
        }

        switch ($param['order']) {
            case 'normal':
                $sort_by_field = 0;
                break;
            case 'hot':
                $sort_by_field = 1;
                break;
            case 'new':
                $sort_by_field = 2;
                break;
            default:
                $sort_by_field = 0;
                break;
        }

        $searchArr["sort_by_field"] = $sort_by_field;//按域排序，默认是0，1表示按照热度排序，2表示按时间排序

        $searchArr["sort_field_style"] = $param['sort'] ? $param['sort'] : 1;//按域排序的方式，默认是1表示降序，0表示升序
        $searchArr["search_type"] = $param['search_type'] ? $param['search_type'] : 0x10 | 0x20;//0x10 ：获取结果 0x20：获取筛选器。两个条件可做位或操作
        if ($param['brand_id']) {
            $searchArr["filter_brand_ids"] = $param['brand_id'];//筛选指定品牌id列表，以逗号分隔：2,3,5
        }

        $searchArr["query_source"] = 2;
        //$searchArr["cluster_type"] = 0;//0不做任何聚合，1进行默认方法聚合
        $searchArr["version"] = 1;

        $searchResult = $this->searchRemote->noteSearch($searchArr);
        $noteIds = [];
        if(!empty($searchResult['data']) && is_array($searchResult['data'])) {
            $noteIds = array_map(function ($v) {
                return $v['id'];
            }, $searchResult['data']);
        }

        //$noteIds = ['267344', '267343', '267342', '267341', '267339', '267338', '267337'];
        $noteInfos = $this->subjectService->getBatchSubjectInfos(array_values($noteIds))['data'];

        $res['total'] = $searchResult['disp_num'];

        //替换品牌名称，精简
        $brand_condition = [];
        $brandIds = [];
        if(!empty($searchResult['search_filter']['b_array'])) {
            foreach ($searchResult['search_filter']['b_array'] as $brand) {
                $brandIds[] = $brand['b_id'];
            }
        }
        if(!empty($brandIds)) {
            $itemService = new ItemService();
            $brandInfo = $itemService->getRelationBrandName($brandIds)['data'];
            foreach ($searchResult['search_filter']['b_array'] as $val) {
                $brand_condition[] = [
                    'id' => $val['b_id'],
                    'name' => $brandInfo[$val['b_id']]['name']
                ];
            }
        }
        $res['brand_condition'] = $brand_condition;
        $res['search_notes'] = array_values($noteInfos);
        return $this->succ($res);
    }

    /**
     * 用户搜索
     * @param $keyWords
     * @param $page
     * @param $count
     * @return mixed
     */
    public function userSearch($keyWords, $page = 1, $count = 20)
    {
        if (empty($keyWords)) {
            return $this->succ([]);
        }
        $searchArr["key"] = $keyWords;
        $searchArr["page"] = $page;
        $searchArr["count"] = $count;

        $userIds = $this->searchRemote->userSearch($searchArr);

        if (empty($userIds)) {
            return $this->succ(['desc' => "搜索结果为空"]);
        } else {
            $userIds = array_map(function ($v) {
                return $v['user_id'];
            }, $userIds);
            //$userIds = [220103494, 1508587, 7509605, 7509576, 7509596, 7509608, 7509603, 7509614, 7509571, 7509569];
            $userList = $this->userService->getUserInfoByUids($userIds)['data'];
            foreach ($userIds as $v) {
                if (empty($userList[$v])) {
                    continue;
                }
                $res[] = $userList[$v];
            }

            $result['search_users'] = !empty($res) ? $res : [];
//            $res['search_info'] = 1;
//            $res['rs'] = 1;
            return $this->succ($result);
        }
    }

    public function itemSearch($param)
    {
        //复用api的逻辑，参照4.9 api接口说明
        //接收参数
        $keyword = $param['key'];
        $page = isset($param['page']) ? $param['page'] : 0;
        $count = isset($param['count']) ? $param['count'] : 10;
        $order = isset($param['order']) ? $param['order'] : 'normal';
        $status = isset($param['so']) ? $param['so'] : 0;
        $brandId = isset($param['brand_id']) ? $param['brand_id'] : 0;
        $categoryId = isset($param['category_id']) ? $param['category_id'] : 0;
        $minPrice = isset($param['min_price']) ? $param['min_price'] : 0;
        $maxPrice = isset($param['max_price']) ? $param['max_price'] : 0;
        $propertyIds = isset($param['propertyIds']) ? $param['propertyIds'] : '';//4.0有结构体

        $sort = isset($param['sort']) ? $param['sort'] : 'desc';
        //筛选器格式化
        if ($param['so'] == 8) {
            $status = 4;//过滤规则
        } elseif ($param['so'] == 16) {
            $status = 8;
        } elseif ($param['so'] == 24) {
            $status = 4 | 8;
        }

        $items = [];
        if (!$keyword) {
            return $this->succ($items);
        }
        if (!in_array($sort, array('desc', 'asc'))) {
            return $this->succ($items);
        }
        if (!in_array($order, array('normal', 'sales', 'price', 'newGoods'))) {
            return $this->succ($items);
        }

        switch ($order) {
            case 'normal':
                $sort_by_field = 0;
                break;
            case 'price':
                $sort_by_field = 2;
                break;
            case 'sales':
                $sort_by_field = 3;
                break;
            case 'newGoods':
                $sort_by_field = 4;
                break;
            default:
                $sort_by_field = 0;
                break;
        }

        $searchArr["query"] = $keyword;//搜索query，需要做urlencode
        $searchArr["rn"] = $count;//搜索结果每页的结果数
        $searchArr["pn"] = $page - 1;//搜索结果的翻页数
        $searchArr["sort_by_field"] = $sort_by_field;//排序，默认是0；2价格排序；3销售量排序；4按新品排序
        $searchArr["sort_field_style"] = strtolower($sort) == "asc" ? 0 : 1;//排序方式，1降序，0升序
        $searchArr["filter_by_field"] = $status;//过滤规则
        $searchArr["search_type"] = 0x10 | 0x20;//0x10 ：获取结果 0x20：获取筛选器。两个条件可做位或操作
        $searchArr["session"] = $param['session'];
        $searchArr["device_token"] = $param['device_token'];
        $searchArr["dvc_id"] = $param['dvc_id'];
        $searchArr["user_id"] = $param['user_id'];
        $searchArr["bi_session_id"] = $param['bi_session_id'];
        $searchArr["cluster_type"] = 0;//0不做任何聚合，1进行默认方法聚合
        $searchArr["version"] = 1;//api版本,不传为老接口，传1为新接口

        if ($brandId) {
            $searchArr["filter_brand_ids"] = $brandId;//筛选指定品牌id列表，以逗号分隔：2,3,5
        }
        if ($categoryId) {
            $searchArr["filter_category_ids"] = $categoryId;//筛选指定的分类id列表，以逗号分隔：2,3,5
        }
        if (($minPrice >= 0) && $maxPrice) {
            $searchArr["min_price"] = $minPrice;
            $searchArr["max_price"] = $maxPrice;
        }

        if ($propertyIds) {
            $propertyIdsStr = "";
            $attrId = 0;
            $attr_v_array = [];
            foreach ($propertyIds as $value) {
                foreach ($value as $key => $propertyValue) {
                    if ($key == 'attr_id') {//属性Id
                        $attrId = $propertyValue;
                    }

                    if ($key == 'attr_v_array') {
                        $attr_v_array = [];
                        foreach ($propertyValue as $k => $v) {
                            $attr_v_array[] = $v['attr_v_id'];//属性中具体属性id
                        }
                    }
                }

                if ($attrId > 0 && !empty($attr_v_array)) {
                    $propertyIdsStr .= $attrId . ":" . implode(",", $attr_v_array) . "||";
                    continue;
                }
            }

            if ($propertyIdsStr) {
                $propertyIdsStr = substr($propertyIdsStr, 0, -2);
                $searchArr["attr_ids"] = $propertyIdsStr;//attr_id:attr_value_id1,attr_value_id2||.....(每个筛选属性组合以||分割)
            }
        }

        $result = ['matches' => [], 'total' => 0];
        $searchArr["query_source"] = 2;//查询来源2.APP

        $datas = $this->searchRemote->itemSearch($searchArr);

        if (!$datas) {
            unset($result['matches']);
        } else {
            if (isset($datas['disp_num']) && $datas['disp_num'] > 0) {
                $result['total'] = $datas['disp_num'];//搜索返回的预估总结果数
                foreach ($datas['data'] as $key => $value) {
                    $result['matches'][] = $value;
                }
            } else {
                unset($result['matches']);
            }

            //获取筛选器
            $items['price_condition'] = [];
            $items['category_condition'] = [];
            $items['brand_condition'] = [];
            $items['property_condition'] = [];
            $items['search_info'] = [];
            $items['rs'] = [];
            //筛选格式化搜索筛选器
            $type = 'getSearchItems';
            $format_filter_rank = $this->format_filter_rank($datas['search_filter']['filter_rank'], $type);

            $items['filter_rank'] = $format_filter_rank['filter_rank'];
            //是否配置筛选器图标
            if (isset($format_filter_rank['filter_rank_extend'])) {
                $items['filter_rank_extend'] = $format_filter_rank['filter_rank_extend'];
            }

            if (isset($datas['search_filter']['price_array']) && !empty($datas['search_filter']['price_array'])) {
                $items['price_condition'] = $datas['search_filter']['price_array'];
            }

            if (isset($datas['search_filter']['c_array']) && !empty($datas['search_filter']['c_array'])) {
                $items['category_condition'] = $datas['search_filter']['c_array'];
            }

            if (isset($datas['search_filter']['b_array']) && !empty($datas['search_filter']['b_array'])) {
                $items['brand_condition'] = $datas['search_filter']['b_array'];
            }

            if (isset($datas['search_filter']['attr_array']) && !empty($datas['search_filter']['attr_array'])) {
                $items['property_condition'] = $datas['search_filter']['attr_array'];
            }

            if (isset($datas['search_info']) && !empty($datas['search_info'])) {
                $items['search_info'] = $datas['search_info'];
            }

            if (isset($datas['rs']) && !empty($datas['rs'])) {
                $items['rs'] = $datas['rs'];
            }
        }

        $items['total'] = is_null($result['total']) ? 0 : $result['total'];
        $ids = [];
        $rank = [];
        $item_cluster = [];
        if ($items['total'] > 0) {
            foreach ($result['matches'] as $key => $row) {
                if ($row['type'] == 0) {
                    $ids[] = $row['id'];
                    if (!empty($row['cluster_ids'])) {
                        $item_cluster[$row['id']] = $row['cluster_ids'];
                    }
                } else {
                    $rank[$row['id']]['type'] = $row['type'];
                    $rank[$row['id']]['index'] = $key;
                }
            }
            $items['item_ids'] = $ids;
            $items['rank'] = $rank;//type 1 为榜单
            $items['item_cluster'] = $item_cluster;//类聚
        }
        if(!empty($items['item_ids']) && is_array($items['item_ids'])){
            foreach ($items['item_ids'] as $v) {
                //口碑印象列表不需要了
                $ext_info = $this->koubeiService->getKoubeiNums($v)['data'];
                if ($ext_info['user_unm'] == 0 || $ext_info['item_rec_nums'] == 0) {
                    $recommend_desc = [
                        [
                            'text' => "",
                            'color' => ''
                        ]
                    ];
                } else {
                    $userNum = NormalUtil::formatNum($ext_info['user_unm']);
                    $koubeiNum = NormalUtil::formatNum($ext_info['item_rec_nums']);
                    $recommend_desc = [
                        [
                            'text' => "{$userNum}",
                            'color' => '#fa4b9b'
                        ],
                        [
                            'text' => "位妈妈发表了",
                            'color' => '#333333'
                        ],
                        [
                            'text' => "{$koubeiNum}",
                            'color' => '#fa4b9b'
                        ],
                        [
                            'text' => "篇口碑",
                            'color' => '#333333'
                        ],
                    ];
                }
                $items['recommend_desc'][$v] = $recommend_desc;
                unset($recommend_desc);
            }
        }
        return $this->succ($items);
    }

    /**
     * 笔记搜索，推荐热词列表
     */
    public function noteHotWordsList()
    {
        $searchKeys['hot_words'] = $this->koubeiModel->getNoteSearchKey();
        array_walk($searchKeys['hot_words'],function(&$n){
            $n['key_word'] = trim($n['key_word']);
        });
        return $this->succ($searchKeys);
    }

    /**
     * 获取推荐用户列表，最新的10个
     * @param int $count
     * @return mixed
     */
    public function userHotList($count = 10)
    {
        //推荐池数据
        $userIdRes = $this->abumModel->getGroupDoozerList();
        $userIds = array_slice($userIdRes, 0, 20);
        $currentUid = $this->ext_params['current_uid'];
        $userList = $this->userService->getUserInfoByUids($userIds, $currentUid)['data'];
        $return = [];
        foreach ($userList as $val) {
            if($val['relation_with_me'] == 0){
                $return[] = $val;
            }
        }
        $return = array_slice($return, 0, $count);
        return $this->succ(['user_list' => $return]);
    }

    /**
     * 商品搜索，推荐热词列表
     */
    public function itemHotWordsList()
    {
        //复用商品的，完全一样的接口
    }


    /**
     * 筛选器格式化
     * @param array $filter_rank 筛选器
     * @param array $type 筛选页面标识
     * @return  array 筛选格式化后的筛选器
     */
    public function format_filter_rank($filter_rank, $type)
    {
        if (!is_array($filter_rank) || empty($filter_rank) || empty($type)) {
            return false;
        }
        //getSearchItems    搜索（搜索结果）
        //getSearchStoreItems   店铺商品搜索（店铺分类，店铺）
        //filterOrder   分类列表
        //items 品牌页
        //getoutletitems    特卖页
        //activityItemList  凑单活动页
        //大促筛选器显示列表
        $dcFilter = array(
            'getSearchItems',//搜索结果页
            'filterOrder',//分类页
            'items',//品牌页
            'getSearchStoreItems',//店铺页
        );
        //自营显示列表
        $zyFilter = array(
            'getSearchItems',//搜索结果页
            'filterOrder',//分类页
            'items',//品牌页
//            'getoutletitems',//特卖
            'activityItemList',//凑单活动
        );
        $format = [];
        $itemConfig = \F_Ice::$ins->workApp->config->get('busconf.item');

        $filter_rank_extend_list = $itemConfig['filter_rank_extend_list'];
        $format_rank = array();
        foreach ($filter_rank as $k => $v) {
            //过滤大促筛选器
            $time = time();
            if ($v == 5) {
                if (!in_array($type, $dcFilter)) {
                    unset($filter_rank[$k]);
                }
                //不在大促期间不显示
                if ($time < strtotime($itemConfig['promote_start_time']) || $time > $itemConfig['promote_end_time']) {
                    unset($filter_rank[$k]);
                }
            }
            //过滤自营筛选器
            if (!in_array($type, $zyFilter) && $v == 4) {
                unset($filter_rank[$k]);
            }
            if (isset($filter_rank[$k])) {
                $format_rank[] = $filter_rank[$k];
                //筛选器图标配置,筛选器存在切被配置
                if (isset($filter_rank_extend_list[$v])) {
                    $format['filter_rank_extend'][] = $filter_rank_extend_list[$v];
                }
            }
        }
        $format['filter_rank'] = $format_rank;
        return $format;
    }
}
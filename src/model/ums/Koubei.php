<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Koubei extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //口碑
    protected $tableKoubei = 'koubei';
    // 代金券
    protected $tableCoupon = 'group_coupon_rule';
    protected $indexKoubei = array('id', 'item_id', 'user_id', 'rank_score', 'order_id', 'subject_id', 'created_time','supplier_id','self_sale');
    //口碑相关蜜芽贴
    protected $tableKoubeiSubjects = 'koubei_subjects';
    protected $tableKoubeiItem = 'group_subject_point_tags';
    protected $tableSupplierMapping = 'user_supplier_mapping';
    protected $tableKoubeiTagsRelation = 'koubei_tags_relation';
    protected $indexKoubeiSubjects = array('subject_id', 'item_id', 'user_id', 'is_audited', 'create_time');
    //口碑申诉
    protected $tableKoubeiAppeal = 'koubei_appeal';
    protected $indexKoubeiAppeal = array('koubei_id', 'supplier_id', 'appeal_time');
    
    /**
     * 查询口碑表数据
     */
    public function getKoubeiData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableKoubei;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubei))) {
                $where[] = [':ge',$this->tableName. '.created_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            $orderBy = $this->tableName. '.id desc';
            $fileds = $this->tableName. '.*';
            $join = null;
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'status':
                        $where[] = [$this->tableName. '.'.$k, $v];
                        if ($v == 2) {
                            $where[] = [':ne',$this->tableName. '.subject_id', 0];
                        }
                        break;
                    case 'comment_style':
                        if($v == 1){
                            $where[] = [':ge',$this->tableName. '.comment_id', $v];
                            $where[] = [':ge',$this->tableName. '.comment_supplier_id', $v];
                            break;
                        }else{
                            $where[] = [':gt',$this->tableName. '.comment_id', $v];
                            $where[] = [$this->tableName. '.comment_supplier_id', $v];
                            break;
                        }
                    case 'start_time':
                        $where[] = [':ge',$this->tableName. '.created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le',$this->tableName. '.created_time', $v];
                        break;
                    case 'comment_start_time':
                        $where[] = [':ge',$this->tableName. '.comment_time', $v];
                        break;
                    case 'comment_end_time':
                        $where[] = [':le',$this->tableName. '.comment_time', $v];
                        break;
                    case 'self_sale':
                        if (in_array($v,array(0,1))) {
                            //非自主包括（自营和未开通回复权限的商家）、自主为开通回复权限的商家
                            if($v == 0){
                                $where [] = [
                                    ':and',[
                                        [$this->tableName.'.supplier_id', 0],
                                        [
                                            ':or',[
                                                [':gt', $this->tableName.'.supplier_id',0],
                                                ':and',[':isnull','sm.supplier_id']
                                            ]
                                        ]
                                    ]
                                ];
                                $join = 'left join '.$this->tableSupplierMapping. ' as sm on ' .$this->tableName . '.supplier_id=sm.supplier_id';
                            }else{
                                $where[] = [':gt', $this->tableName.'.supplier_id',0];
                                $join = 'inner join '.$this->tableSupplierMapping. ' as sm on ' .$this->tableName . '.supplier_id=sm.supplier_id';
                            }
                        }
                        break;
                    case 'op':
                        //生成口碑、差评口碑、差评机选口碑(默认生成口碑)
                        $where[] = [':ge',$this->tableName.'.status',1];
                        $where[] = [':le',$this->tableName.'.status',2];
                        if($v == 'lscore_nums'){
                            $where[] = [':ge',$this->tableName.'.score',1];
                            $where[] = [':le',$this->tableName.'.score',3];
                        }elseif($v == 'mscore_nums'){
                            $where[] = [':ge',$this->tableName.'.score',4];
                            $where[] = [$this->tableName.'.machine_score',1];
                        }else{
                            $where[] = [':gt', $this->tableName.'.score',0];
                        }
                        break;
                    default:
                        $where[] = [$this->tableName . '.' . $k, $v];
                }
            }
        }
            
        $result['count'] = $this->count($where, $join, 1);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, $fileds, $limit, $offset, $orderBy, $join);
        return $result;
    }
    
    /**
     * 查询口碑申诉表数据
     */
    public function getKoubeiAppealData($cond, $offset = 0, $limit = 50, $orderBy = 'id desc') {
        $this->tableName = $this->tableKoubeiAppeal;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubeiAppeal))) {
                $where[] = [':ge','appeal_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','appeal_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','appeal_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $result;
    }
    
    /**
     * 查询口碑相关帖子表数据
     */
    public function getSubjectData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableKoubeiSubjects;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubeiSubjects))) {
                $where[] = [':ge','create_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            
            //因为连表查询，两个表中都存在帖子id，所以要选择一个作为查询条件
            if(isset($cond['subject_id'])){
                $cond['koubei_subjects.subject_id'] = $cond['subject_id'];
                unset($cond['subject_id']);
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','create_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        
        $join = 'left join '.$this->tableKoubeiItem. ' as i on ' .$this->tableName . '.subject_id=i.subject_id or i.subject_id is null ';
        $fileds = 'distinct '. $this->tableName. '.subject_id,is_audited ';
        $result['count'] = $this->count($where, $join, $fileds);
        
        if (intval($result['count']) <= 0) {
            return $result;
        }

        $result['list'] = $this->getRows($where, $fileds, $limit, $offset, $orderBy, $join);
        return $result;
    }
    
    /**
     * 查询商家口碑表数据
     */
    public function getSupplierKoubeiData($cond, $orderBy = '') {
        $this->tableName = $this->tableKoubei;
        $result = array();
        $where = array();
        $where[] = ['status',array(1,2)];
    
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
    
        $fileds = "id,supplier_id";
        $result= $this->getRows($where, $fileds, false, 0, $orderBy);
        return $result;
    }
    
    /**
     * 组装商家口碑的各计数
     */
    public function supplierKoubeiStatistics($condition){
        $koubeiStatistics = array();
        if(empty($condition) || !is_array($condition)){
            return $koubeiStatistics;
        }
        $condition['status'] = array(1,2);
        //生成口碑数
        $koubeiStatistics['koubei'] = $this->getKoubeiStatistics($condition,'koubei');
        $koubeiStatistics['lowscore'] = $this->getKoubeiStatistics($condition,'lowscore');
        $koubeiStatistics['mscore'] = $this->getKoubeiStatistics($condition,'mscore');
        $koubeiStatistics['deal_lowscore'] = $this->getKoubeiStatistics($condition,'deal_lowscore');
        $koubeiStatistics['deal_mscore'] = $this->getKoubeiStatistics($condition,'deal_mscore');
        
        $koubeiStatistics['appeal'] = $this->getKoubeiAppealStatistics($condition,0);
        $koubeiStatistics['pass'] = $this->getKoubeiAppealStatistics($condition,1);
        $koubeiStatistics['reject'] = $this->getKoubeiAppealStatistics($condition,2);

        return $koubeiStatistics;
    }
    
    /**
     * 获取商家口碑相关计数
     */
    public function getKoubeiStatistics($cond,$sType=''){
        $this->tableName = $this->tableKoubei;
        $resArr = array();
        $where = array();
    
        if(empty($cond)){
            return $resArr;
        }
    
        $where[] = [':gt','supplier_id', 0];
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
    
        switch ($sType) {
            case 'deal_lowscore'://商家回复差评
                $where[] = ['comment_status',1];
                $where[] = [':ge','score',1];
                $where[] = [':le','score',3];
                break;
            case 'deal_mscore'://商家回复机选差评
                $where[] = ['comment_status',1];
                $where[] = [':ge','score',4];
                $where[] = ['machine_score',1];
                break;
            case 'lowscore'://差评
                $where[] = [':ge','score',1];
                $where[] = [':le','score',3];
                break;
            case 'mscore'://机选差评
                $where[] = [':ge','score',4];
                $where[] = ['machine_score',1];
                break;
            default://生成口碑
                $where[] = [':gt', 'score',0];
        }
    
        $filed = "supplier_id, count(1) as nums";
        $groupBy = "supplier_id";
        $result = $this->getRows($where,$filed,false,0,false,false,$groupBy);
        if(!empty($result)){
            foreach($result as $value){
                $resArr[$value['supplier_id']] = $value['nums'];
            }
        }
    
        return $resArr;
    }
    
    /**
     * 获取商家口碑相关申诉计数
     */
    public function getKoubeiAppealStatistics($cond,$status){
        $this->tableName = $this->tableKoubei;
        $resArr = array();
        $where = array();
    
        if(empty($cond)){
            return $resArr;
        }
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    case 'supplier_id':
                        $where[] = [$this->tableName.'.supplier_id', $v];
                        break;
                    default:
                        $where[] = [$this->tableName.'.status', $v];
                }
            }
        }
        
        $where[] = [':gt',$this->tableName.'.supplier_id', 0];
    
        if(isset($status)){
            $where[] = ['a.status', $status];
        }

        $filed = $this->tableName.".supplier_id, count(1) as nums";
        $groupBy = $this->tableName.".supplier_id";
        
        $join = 'left join '.$this->tableKoubeiAppeal. ' as a on ' .$this->tableName . '.id=a.koubei_id ';

        $result = $this->getRows($where,$filed,false,0,false,$join,$groupBy);
        if(!empty($result)){
            foreach($result as $value){
                $resArr[$value['supplier_id']] = $value['nums'];
            }
        }
    
        return $resArr;
    }

    /*
     * 统计商品的口碑得分情况
     * */
    public function itemKoubeiStatistics($item_ids, $conditions = [])
    {
        $this->tableName = $this->tableKoubei;
        $where = [];
        $where[] = ['item_id', $item_ids];
        if(isset($conditions['status'])){
            $where[] = ['status', $conditions['status']];
        }
        if(isset($conditions['score'])){
            $where[] = ['score', $conditions['score']];
        }
        if(isset($conditions['auto_evaluate'])){
            $where[] = ['auto_evaluate',$conditions['auto_evaluate']];
        }
        if(isset($conditions['subject_id'])) {
            $where[] = [':gt','subject_id', 0];
        }
        $groupBy = 'score,item_id';
        $field = 'count(id) as count, score,item_id';
        $res = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        return $res;
    }

    /*
    * 口碑迁移更新口碑表
    * */
    public function updateKoubeiTransfer($updateItemData, $oriItemId, $conditions = [])
    {
        if(empty($updateItemData) || empty($oriItemId)) {
            return false;
        }
        switch ($conditions['table']) {
            case 'koubei':
                $this->tableName = $this->tableKoubei;
                break;
            case 'relation':
                $this->tableName = $this->tableKoubeiTagsRelation;
                break;
            case 'tag':
                $this->tableName = $this->tableKoubeiItem;
                break;
        }
        $where = [];
        $where[] = ['item_id', $oriItemId];
        if(!empty($conditions['type'])) {
            $where[] = ['type', $conditions['type']];
        }
        $setData = array();
        foreach($updateItemData as $key=>$val){
            $setData[] = [$key, $val];
        }
        $res = $this->update($setData, $where);
        return $res;
    }
}
<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\Redis;
use mia\miagroup\Lib\RemoteCurl;

class Solr
{
    private  $core          = '';
    public   $config        = '';
    public   $solrserver    = '';
    public   $export_count  = 2000;

    public function __construct($core, $host = 'solr'){
        $this->setCore($core);
        $this->switchServer($host);
    }

    /*
     * 主从配置，主服务器不可用时，切换从服务器，从服务器自动拉取主服务器数据
     * 主服务器全量更新的同时，从服务器也全量更新
     * */
    public function switchServer($host){

        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.'.$host.'.online');
        $this->handleSolrUrlParams();
        if($this->ping() == false){
            $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.'.$host.'.online_slave');
            $this->handleSolrUrlParams();
        }

        /*$solrConfigList = \F_Ice::$ins->workApp->config->get('thrift.address.solr_switch');
        $ipCount = count($solrConfigList);
        $master_num = rand(0,$ipCount-1);
        $this->config = $solrConfigList['online'.$master_num];
        $this->handleSolrUrlParams();
        if($this->ping() == false){
            unset($solrConfigList[$master_num]);
            $ipCount = count($solrConfigList);
            $slave_num = rand(0,$ipCount-1);
            $this->config = $solrConfigList['online'.$slave_num];
            $this->handleSolrUrlParams();
        }*/
    }

    /**
     * 封装数据
     */
    public function handleSolrUrlParams(){
        $this->solrserver = 'http://'.$this->config['host'].':'.$this->config['port'].'/'.$this->config['path'].'/';
    }

    /**
     * solr set core
     */
    public function setCore($core){
        $this->core = $core;
    }

    /**
     * solr get core
     */
    public function getCore(){
        return $this->core;
    }


    /**
     * @param unknown_type $data
     * $data['q'] 查询关键词
     * $data['page'] 当前页
     * $data['pageSize'] 每页数据量
     * $data['fl'] 查询结果返回的字段,
     * $data['sort'] 排序字段,
     * $data['wt'] 返回结果格式,可选值json或xml,默认返回json格式
     * $data['hl.fl'] 指定高亮字段,
     * $data['facet.field'] 分组统计
     * $data['fq'] where 条件
     * hl=true&hl.fl=name,features
     */
    public function select($data){
        $result = array('success'=>0,'info'=>'操作失败');
        if(empty($data['q']) == true) {
            $result = array('success'=>0,'info'=>'关键词不能为空');
        } else {
            $params = array();
            $params['q'] = urlencode($data['q']);
            $rows = isset($data['pageSize']) == true ? intval($data['pageSize']) : 10;
            $page = isset($data['page']) == true ? intval($data['page']) : 1;
            $start = $page > 0 ? ($page - 1) * $rows : 0;
            $params['start'] = $start;
            $params['rows'] = $rows;
            $params['indent'] = 'true';
            if(empty($data['fl']) == false) {
                $params['fl'] = urlencode($data['fl']);
            }
            if(empty($data['sort']) == false) {
                $params['sort'] = urlencode($data['sort']);
            }
            if(empty($data['hl.fl']) == false) {
                $params['hl.fl'] = urlencode($data['hl.fl']);
            }
            if(empty($data['facet.limit']) == false){
                $params['facet.limit'] = $data['facet.limit'];
            }
            if(empty($data['json.facet']) == false){
                $params['json.facet'] = $data['json.facet'];
            }
            if(empty($data['fq']) == false) {
                $fieldStr = '';
                foreach ($data['fq'] as $key => $field){
                    if($key == 0){
                        $fieldStr .= urlencode($field);
                    }else{
                        $fieldStr .= '&fq='.urlencode($field);
                    }
                }
                $params['fq'] = $fieldStr;
            }
            if(empty($data['facet.field']) == false) {
                $fields = $data['facet.field'];
                $fieldStr = '';
                foreach($fields as $field) {
                    if($fieldStr == ''){
                        $fieldStr = $field;
                    } else {
                        $fieldStr .= '&facet.field='.$field;
                    }
                }
                $params['facet'] = 'on';
                $params['facet.field'] = $fieldStr;
            }
            if(empty($data['facet.pivot']) == false){
                $params['facet.pivot'] = $data['facet.pivot'];
            }
            // Field Collapsing
            if(empty($data['group']) == false){
                $params['group'] = $data['group'];
            }
            if(empty($data['group.main']) == false){
                $params['group.main'] = $data['group.main'];
            }
            if(empty($data['group.field']) == false){

                // 扩展多字段分组业务
                $fields = $data['group.field'];
                if(is_array($fields)) {
                    $fieldStr = '';
                    foreach($fields as $field) {
                        if($fieldStr == ''){
                            $fieldStr = $field;
                        } else {
                            $fieldStr .= '&group.field='.$field;
                        }
                    }
                    $params['group.field'] = $fieldStr;
                }else{
                    $params['group.field'] = $data['group.field'];
                }
            }
            if(isset($data['group.limit'])){
                // 每组返回的文档数(默认为1)
                $params['group.limit'] = $data['group.limit'];
            }

            // solr 操作目前只处理get形式
            $remote_curl = new RemoteCurl($this->core);
            $solrData = $remote_curl->curl_remote('', $params);

            if(empty($solrData) == false) {
                if(isset($solrData['responseHeader']['status']) == true && $solrData['responseHeader']['status'] == 0) {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$solrData);
                } else {
                    $result = array('success'=>0,'info'=>'查询失败', 'error'=>json_encode($solrData));
                }
            } else {
                $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
            }
        }
        return $result;
    }

    private function httpPost($method, $data){
        $data_string = json_encode($data);
// 		$url = $this->solrserver . $this->core . '/update?commit=true';
        $url = $this->solrserver . $this->core . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  // 更新需要post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $solrData = curl_exec($ch);
        return $solrData;
    }

    /**
     * 删除索引
     * @param unknown_type $data $data=array('id'=>xx),id为索引主键字段
     * @return multitype:number string
     */
    public function delete($data)
    {
        $data = array('delete'=>$data);

        $method = '/update?commit=true';
        $solrData = $this->httpPost($method, $data);
        if(empty($solrData) == false) {
            $data = json_decode($solrData, true);
            if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0) {
                $result = array('success'=>1,'info'=>'操作成功', 'data'=>json_decode($solrData, true));
        } else {
                $result = array('success'=>0,'info'=>'更新失败', 'error'=>$solrData);
        }
        } else {
            $result = array('success'=>0,'info'=>'网络错误,服务器繁忙');
    }
        return $result;
    }
    
    /**
     * 通过品牌id获取优质口碑
     */
    public function getHighQualityKoubeiByBrandId($category_id, $brand_id = 0, $page = 1, $category_name)
    {

        $koubei_list = array();
        if(is_array($category_id)){
            $category_cache = implode(",",$category_id);
        }else{
            $category_cache = $category_id;
        }
        if(is_array($brand_id)){
            $brand_cache = implode(",",$brand_id);
        }else{
            $brand_cache = $brand_id;
        }
        $koubeiListKey = md5($category_cache.$brand_cache.$category_name.$page);
        $redis = new Redis();
        $result = $redis->get($koubeiListKey);
        $koubei_list = $result;

        if(empty($koubei_list)) {

            $field = 'id,item_id';
            $sort = 'score desc,id desc,rank_score desc';

            $solrInfo = [
                'q'         => '*:*',
                'fl'        => $field,
                'sort'      => $sort,
                'pageSize'    => $this->export_count,
            ];

            $solrInfo['fq'][] = 'local_url:*';
            $solrInfo['fq'][] = 'status:2';
            $solrInfo['fq'][] = 'score:(4 OR 5)';

            // 5.1 需求变更（传入的为三级类目，兼容老版本，老版本默认为四级类目）
            if(!empty($category_id)){
                if(is_array($category_id)){
                    $solrInfo['fq'][] = $category_name.":(". implode(' OR ', $category_id) . ")";
                }else{
                    $solrInfo['fq'][]    = $category_name.':'.$category_id;
                }
            }

            // 5.1 需求变更（传入的为三级品牌，兼容老版本，老版本默认为四级品牌）
            if(!empty($brand_id)){
                if(is_array($brand_id)){
                    $solrInfo['fq'][] = "brand_id:(". implode(' OR ', $brand_id) . ")";
                }else{
                    $solrInfo['fq'][]    = 'brand_id:'.$brand_id;
                }
            }

            $res = $this->select($solrInfo);

            if($res['success'] == 1){
                $res = $res['data']['response'];
                //$sort_ids = $this->sortKoubeiId($res['list'], 'item_id', $res['count'], 20, $page);
                if(!empty($res['docs'])){
                    $sort_ids = $this->anotherSortKoubeiId($res['docs'], 'item_id', $res['numFound'], 20, $page);
                    $koubei_list = array('list' => $sort_ids, 'count' => $res['numFound']);
                }
            }
            // 缓存
            $redis->setex($koubeiListKey, $koubei_list, 20*60);
        }
        return $koubei_list;
    }
    
    /**
     * 通过类目id获取优质口碑
     */
    public function getHighQualityKoubeiByCategoryId($category_id = 0, $page = 1, $category_name = "category_id")
    {
        $brand_ids = $this->brandList($category_id, $category_name);

        if(!empty($brand_ids) && is_array($brand_ids)){

            // 通过品牌获取口碑列表
            $result = $this->getHighQualityKoubeiByBrandId($category_id, $brand_ids, $page, $category_name);
            return $result;

        }
        return array();
    }
    
    /**
     * 获取口碑列表
     * condition key : category_id brand_id warehouse_type self_sell koubei_with_pic
     */
    public function getKoubeiList($conditon, $field = 'id', $page = 1, $count = 20, $order_by = 'rank_score desc')
    {
        $solr_info = [
            'q'         => '*:*',
            'fq'        => array(),
            'page'      => $page,
            'pageSize'  => $count,
            'fl'        => $field,
            'sort'      => $order_by,
        ];
        if(intval($conditon['category_id']) > 0) { 
            //类目ID
            if (is_array($conditon['category_id'])) {
                $solr_info['fq'][]   = "category_id:(". implode(' OR ', $conditon['category_id']) . ")";
            } else {
                $solr_info['fq'][]   = 'category_id:'. $conditon['category_id'];
            }
            
        }
        if(intval($conditon['category_id_ng']) > 0) {
            //新类目ID（5.1版本使用此字段）
            if (is_array($conditon['category_id_ng'])) {
                $solr_info['fq'][]   = "category_id_ng:(". implode(' OR ', $conditon['category_id_ng']) . ")";
            } else {
                $solr_info['fq'][]   = 'category_id_ng:'. $conditon['category_id_ng'];
            }

        }
        if(intval($conditon['brand_id']) > 0) { 
            //品牌ID
            if (is_array($conditon['brand_id'])) {
                $solr_info['fq'][]   = "brand_id:(". implode(' OR ', $conditon['brand_id']) . ")";
            } else {
                $solr_info['fq'][]   = 'brand_id:'. $conditon['brand_id'];
            }
        }
        if($conditon['koubei_with_pic'] === true || $conditon['self_sell'] === false) { 
            //是否带图
            $solr_info['fq'][]   = $conditon['koubei_with_pic'] === true ? 'local_url:*' : '-(local_url:*)';
        }
        /*if (isset($conditon['self_sale']) && in_array($conditon['self_sale'],array(0,1))) {
            //自营非自营
            $solr_info['fq'][]   = $conditon['self_sale'] == 0 ? 'supplier_id:0' : 'supplier_id:[1 TO *]';
        }*/
        if (isset($conditon['self_sale']) && in_array($conditon['self_sale'],array(0,1))) {
            //自营非自营
            if($conditon['self_sale'] == 0) {
                // 非自主
                $solr_info['fq'][]   = '(supplier_id:0 OR (supplier_id:[1 TO *] AND NOT supplier_status:1))';
            }else {
                // 自主
                $solr_info['fq'][]   = '(supplier_id:[1 TO *] AND supplier_status:1)';
            }
        }
        //所属仓库
        if (!empty($conditon['warehouse_type'])) {
            //如果只选择一级仓库分类，就包括一级下的所有二级分类
            if(is_array($conditon['warehouse_type'])){
                $solr_info['fq'][]   = "warehouse_type:(". implode(' OR ', $conditon['warehouse_type']) . ")";
            }else{
                //如果选择了二级分类就直接等于改二级分类的仓库
                $solr_info['fq'][]   = 'warehouse_type:'. $conditon['warehouse_type'];
            }
        }
        if (!empty($conditon['pop_type'])) {
            //所属仓库
            $solr_info['fq'][]   = 'pop_type:'. $conditon['pop_type'];
        }
        if (!empty($conditon['stock_id'])) {
            //仓库信息
            $solr_info['fq'][]   = 'stock_id:'. $conditon['stock_id'];
        }
        if(isset($conditon['status']) && in_array($conditon['status'],array(0,1,2))){
            $solr_info['fq'][]   = 'status:'. $conditon['status'];
            if($conditon['status'] == 2){
                $solr_info['fq'][]   = 'subject_id:[1 TO *]';
            }
        }
        if(isset($conditon['auto_evaluate']) && in_array($conditon['auto_evaluate'],array(0,1))){
            $solr_info['fq'][]   = 'auto_evaluate:'. $conditon['auto_evaluate'];
        }
        if(isset($conditon['score']) && $conditon['score'] > 0){
            if(is_array($conditon['score'])){
                if(count($conditon['score']) > 1){
                    $maxScore = current($conditon['score']);
                    $minScore = end($conditon['score']);
                    $solr_info['fq'][]   = 'score:[' . $minScore . ' TO ' . $maxScore . ']';
                }else{
                    $solr_info['fq'][]   = 'score:'. $conditon['score'][0];
                }
            }else{
                $solr_info['fq'][]   = 'score:'. $conditon['score'];
            }
        }
        if(isset($conditon['op']) && in_array($conditon['op'],array('koubei_nums','lscore_nums','mscore_nums'))){
            $solr_info['fq'][]   = 'status:[1 TO 2]';
            if($conditon['op'] == 'lscore_nums'){
                $solr_info['fq'][]   = 'score:[1 TO 3]';
            }elseif($conditon['op'] == 'mscore_nums'){
                $solr_info['fq'][]   = 'machine_score:1';
                $solr_info['fq'][]   = 'score:[4 TO 5]';
            }else{
                $solr_info['fq'][]   = 'score:[1 TO 5]';
            }
        }
        if(!empty($conditon['item_id'])){
            $solr_info['fq'][]   = 'item_id:'.$conditon['item_id'];
        }
        if(!empty($conditon['subject_id'])){
            $solr_info['fq'][]   = 'subject_id:'.$conditon['subject_id'];
        }
        //口碑类型（精品/非精品）
        if(isset($conditon['rank']) && in_array($conditon['rank'],array(0,1))){
            $solr_info['fq'][]   = 'rank:'. $conditon['rank'];
        }
        //机器评分
        if(!empty(intval($conditon['machine_score']))){
            $solr_info['fq'][]   = 'machine_score:'. $conditon['machine_score'];
        }
        //用户id
        if(!empty(intval($conditon['user_id']))){
            $solr_info['fq'][]   = 'user_id:'. $conditon['user_id'];
        }
        //口碑id
        if(!empty(intval($conditon['id']))){
            $solr_info['fq'][]   = 'id:'. $conditon['id'];
        }
        if (strtotime($conditon['start_time']) > 0 && empty(strtotime($conditon['end_time']))) {
            //起始时间
            $solr_info['fq'][]   = "created_time:[".strtotime($conditon['start_time']) ." TO *]";
        }
        if (strtotime($conditon['end_time']) > 0 && empty(strtotime($conditon['start_time']))) {
            //结束时间
            $solr_info['fq'][]   =  "created_time:[* TO ". strtotime($conditon['end_time']) ."]";
        }
        if (strtotime($conditon['start_time']) > 0 && strtotime($conditon['end_time']) > 0) {
            //结束时间
            $solr_info['fq'][]   =  "created_time:[".strtotime($conditon['start_time'])." TO ". strtotime($conditon['end_time']) ."]";
        }
        //商家id
        if(!empty(intval($conditon['supplier_id']))){
            $solr_info['fq'][]   = 'supplier_id:'. $conditon['supplier_id'];
        }
        //回复状态
        if(isset($conditon['comment_status']) && in_array($conditon['comment_status'],array(0,1))){
            $solr_info['fq'][]   = 'comment_status:'. $conditon['comment_status'];
        }
        if (strtotime($conditon['comment_start_time']) > 0) {
            //回复起始时间
            $solr_info['fq'][]   = "comment_time:[".strtotime($conditon['comment_start_time']) ." TO *]";
        }
        if (strtotime($conditon['comment_end_time']) > 0) {
            //回复结束时间
            $solr_info['fq'][]   =  "comment_time:[* TO ". strtotime($conditon['comment_end_time']) ."]";
        }
        //回复方
        if (isset($conditon['comment_style']) && in_array($conditon['comment_style'],array(0,1))) {
            //商家
            if($conditon['comment_style'] == 1){
                $solr_info['fq'][]   = 'comment_supplier_id:[1 TO *]';
                $solr_info['fq'][]   = 'comment_id:[1 TO *]';
            }else{
                //客服
                $solr_info['fq'][]   = 'comment_supplier_id:0';
                $solr_info['fq'][]   = 'comment_id:[1 TO *]';
            }
            
        }
        //回复人(回复商家id)
        if (!empty($conditon['comment_supplier_id']) ) {
            $solr_info['fq'][]   = 'comment_supplier_id:'. $conditon['comment_supplier_id'];
        }
        
        //是否是甄选
        if (isset($conditon['selections']) && in_array($conditon['selections'],array(0,1))) {
            //是甄选
            if($conditon['selections'] == 1){
                $solr_info['fq'][]   = 'brand_id:6769';
            }else{
                //不是甄选
                $solr_info['fq'][]   = '-brand_id:6769';
            }
        
        }
        // 思源商家
        if (!empty($conditon['siyuan_group'])) {
            $solr_info['fq'][] = 'siyuan_group:'. $conditon['siyuan_group'];
        }
        if (!empty($conditon['siyuan_son_supplier_id'])) {
            $solr_info['fq'][] = 'siyuan_son_supplier_id:'. $conditon['siyuan_son_supplier_id'];
        }
        
        //如果获取大于某个id的话，就取该id后的信息
        if(isset($conditon['after_id']) && !empty($conditon['after_id'])){
            $solr_info['fq'][] = 'id:['.$conditon['after_id'].' TO *]';
        }

        // solr select
        $res = $this->select($solr_info);
        if($res['success'] == 1){
            $res = $res['data']['response'];
            $data = array('list' => $res['docs'], 'count' => $res['numFound']);
            return $data;
        }
        return array();
    }

    public function curl_get($url, $time_out=1) {
        if(0 === stripos($url, 'http:')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
            $data = curl_exec($ch);
            $res[0] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res[1] = $data;
            curl_close($ch);
            return $res;
        } else {
            echo 'ERROR URL';
            exit();
        }
    }


    /**
     * 搜索引擎状态
     */
    public function ping() {
        $res = $this->curl_get($this->solrserver . $this->core.'/admin/ping?wt=php');
        if($res[0] == 200 && !empty($res[1])){
            return true;
        }
        return false;
    }


    /**
     * 对口碑id重新排序，使unique_key不重复
     */
    private function sortKoubeiId($sort_data, $unique_key, $total_count, $default_count, $page)
    {
        if (!in_array($unique_key, array('item_id'))) {
            return false;
        }
        if($total_count <= $default_count){
            $final_data = array_column($sort_data,'id');
            return $final_data;
        }
        $sorted_data = array();
        // 规则排序
        foreach ($sort_data as $data) {
            $sorted_data[$data[$unique_key]][] = $data['id'];
        }
        // 循环取出的条数（默认值）
        $final = array();
        $sort_ids = array();
        for ($i=1; $i <= $page; $i++) {
            if($i <= round($total_count/$default_count)){
                //echo '第'.$i.'页数据';
                $sort_ids = $this->order_list($sorted_data, $default_count, $final, $total_count, $i);
            }
        }
        // 取出当前页的条数
        $current_sort_ids = array_slice($sort_ids,($page -1)*$default_count);
        return $current_sort_ids;
    }


    /**
     * @param $sorted_data
     * @param int $default
     * @param $final
     * @param int $total_count
     * @param int $page
     * @return array
     */
    public function order_list(&$sorted_data, $default = 20, &$final, $total_count = 0, $page = 1)
    {
        $i = 0;
        static $count=0;
        if(count($final) < $total_count){
            foreach($sorted_data as $key => $data){
                if($i < $default){
                    //echo '<pre>-';print_r($data);
                    if(!empty($data) && !in_array(reset($data),$final)){
                        $final[] = reset($data);
                        array_shift($sorted_data[$key]);
                        $i++;
                        $count++;
                    }
                }
            }
            // 11为每页显示的数量，66为总个数
            $default_rows = 20;
            // 记录当前页
            $begin_position = ($page-1)*$default_rows;
            if($begin_position < $total_count){
                $current_count = count(array_slice($final, $begin_position));
                //echo '第'.$page.'已取到'.$current_count.'条';
                if($current_count < $default_rows && $count < $total_count){
                    $diff_count = $default_rows - $current_count;
                    //echo '差'.$diff_count;
                    $this->order_list($sorted_data, $diff_count, $final, $total_count, $page);
                }
            }
        }
        return $final;
    }

    /*
    * another koubei sort arithmetic
    * */
    private function anotherSortKoubeiId($sort_data, $unique_key, $total_count, $default_count, $page){

        if (!in_array($unique_key, array('item_id'))) {
            return false;
        }

        /*if($total_count <= $default_count){
            $final_data = array_column($sort_data,'id');
            return $final_data;
        }*/
        $sorted_data = array();
        array_values($sort_data);
        // 规则排序
        foreach ($sort_data as $key => $data) {
            $sorted_data[$key][$data['item_id']] = $data['id'];
        }
        // 循环取出的条数（默认值）
        $sort_ids = array();
        if($page <= ceil($total_count/$default_count)){
            for ($i=1; $i <= $page; $i++) {
                //echo '第'.$i.'页数据';
                $sort_ids = $this->another_order_list($sorted_data, $default_count);
            }
        }else{
            return array();
        }
        // 取出当前页的条数
        $current_sort_ids = array();
        foreach($sort_ids as $value){
            foreach($value as $item){
                $current_sort_ids[] = $item;
            }
        }
        return $current_sort_ids;
    }

    /*
     * another sort arithmetic
     * */
    private function another_order_list(&$arr, $default = 20){

        //echo '<pre>';print_r($arr);exit;

        $return_arr  = [];
        $removal_arr = [];

        foreach ($arr as $key => $value) {
            if(in_array(array_keys($value)[0], $removal_arr)){
                continue;
            }
            $return_arr[] = $value;
            //echo '<pre>';print_r($return_arr);
            $removal_arr[]= array_keys($value)[0];
            unset($arr[$key]);
            if(count($return_arr) == $default){
                return $return_arr;
            }
        }
        if(empty($arr)){
            return $return_arr;
        }
        if(count($return_arr)< $default){
            return array_merge($return_arr, $this->another_order_list($arr,$default-count($return_arr)));
        }
    }


    /**
     * 口碑列表
     * @return array
     */
    public function koubeiList($brand_id, $category_id, $count, $page)
    {
        $solrInfo = [
            'q'         => '*:*',
            'fq'        => array(),
            'page'      => $page,
            'pageSize'  => $count,
            'fl'        => 'id',
            'sort'      => 'score desc,id desc,rank_score desc',
        ];
        if(!empty($category_id)){
            $solrInfo['fq'][]   = 'category_id:'.$category_id;
        }
        if(!empty($brand_id)){
            $solrInfo['fq'][]   = 'brand_id:'.$brand_id;
        }
        $solrInfo['fq'][] = 'local_url:*';
        $solrInfo['fq'][] = 'status:2';
        $solrInfo['fq'][] = 'score:(4 OR 5)';
        // solr select
        $res = $this->select($solrInfo);
        if($res['success'] == 1){
            $res = $res['data']['response'];
            return $res;
        }
        return array();
    }

    /**
     * 品牌列表
     * @return array 默认返回所有
     */
    public function brandList($category_id, $category_name)
    {
        $brand_ids = array();
        if(is_array($category_id)){
            $category_cache = implode(",",$category_id);
        }else{
            $category_cache = $category_id;
        }
        $brandListKey = md5($category_cache.$category_name);
        $redis = new Redis();
        $cache_brand_list = $redis->get($brandListKey);
        $brand_ids = $cache_brand_list;

        if(empty($brand_ids)){
            $solrInfo = [
                'q'           => '*:*',
                'pageSize'    => '20',
                'facet'       => 'true',
                'facet.pivot' => 'brand_id',
                'facet.field' => array('brand_id'),
            ];
            $solrInfo['fq'][] = 'local_url:*';
            $solrInfo['fq'][] = 'status:2';
            $solrInfo['fq'][] = 'score:(4 OR 5)';

            // 5.1 需求变更（传入的为三级类目，兼容老版本，老版本默认为四级类目）
            if(!empty($category_id)){
                if(is_array($category_id)){
                    $solrInfo['fq'][] = $category_name.":(". implode(' OR ', $category_id) . ")";
                }else{
                    $solrInfo['fq'][]    = $category_name.':'.$category_id;
                }
            }
            $res = $this->select($solrInfo);

            if($res['success'] == 1){
                $facet_count = $res['data']['facet_counts']['facet_pivot']['brand_id'];
                if(!empty($facet_count) && is_array($facet_count)){
                    $brand_ids = $facet_count;
                    if(count($facet_count) > 20){
                        // 展示前20条品牌
                        $brand_ids = array_slice($facet_count, 0, 20);
                    }
                    $brand_ids = array_column($brand_ids, 'value');
                }
            }
            // 缓存
            $redis->setex($brandListKey, $brand_ids, 20*60);
        }
        return $brand_ids;
    }


    /*
     * 获取各项分数统计
     * */
    public function getSupplierGoodsScore($screen = 'supplier_id', $screen_param = 0, $search_time = ''){

        // solr facet维度：默认只支持100（查询总量不现实）
        $begin_time = strtotime("-3 months", $search_time);

        $solrInfo = [
            'q'           => '*:*',
            'facet'       => 'true',
            'facet.pivot' => 'score',
            'facet.field' => array('score'),
        ];

        $solrInfo['fq'][] = $screen .":".$screen_param;
        $solrInfo['fq'][] = 'status:2';

        if(!empty($begin_time)){
            $solrInfo['fq'][] = 'created_time:['.$begin_time.' TO ' . $search_time.']';
        }
        if($screen == 'item_id'){
            $solrInfo['fq'][] = '-(auto_evaluate:1)';
        }

        $res = $this->select($solrInfo);

        $statis['count'] = [
            'num_five'  => 0,
            'num_four'  => 0,
            'num_three' => 0,
            'num_two'   => 0,
            'num_one'   => 0,
        ];

        if($res['success'] == 1){

            $facet_score = $res['data']['facet_counts']['facet_pivot']['score'];
            if(!empty($facet_score) && is_array($facet_score)) {

                foreach($facet_score as $value){
                    if($value['value'] == 5){
                        $statis['count']['num_five'] = $value['count'];
                    }
                    if($value['value'] == 4){
                        $statis['count']['num_four'] = $value['count'];
                    }
                    if($value['value'] == 3){
                        $statis['count']['num_three'] = $value['count'];
                    }
                    if($value['value'] == 2){
                        $statis['count']['num_two'] = $value['count'];
                    }
                    if($value['value'] == 1){
                        $statis['count']['num_one'] = $value['count'];
                    }
                }
            }
        }
        return $statis;
    }


    /*
     * 获取默认5分好评
     * */
    public function getDefaultScoreFive($screen = 'supplier_id', $screen_param = 0, $search_time = ''){

        $begin_time = strtotime("-3 months", $search_time);
        $solrInfo = [
            'q'           => '*:*',
            'fl'          => 'order_id',
        ];
        $solrInfo['fq'][] = $screen .":".$screen_param;
        $solrInfo['fq'][] = 'status:5';

        if(!empty($begin_time)){
            $solrInfo['fq'][] = 'created_time:['.$begin_time.' TO ' . $search_time.']';
        }

        $res = $this->select($solrInfo);

        $docs = $res['data']['response']['docs'];

        if(!empty($docs) && is_array($docs)){
            return array( 'count' => $res['data']['response']['numFound']);
        }
        return array();
    }


    /*
     * 通用发代金券
     * */
    public function getcouponsSolrIds($item_ids){
        // 查询符合条件总条数
        $result = array();
        $solrInfo = $this->getcouponParams($item_ids, 0);
        $res = $this->select($solrInfo);
        $totalCount = $res['data']['response']['numFound'];
        if(empty($totalCount)){
            return $result;
        }
        $solrInfo = $this->getcouponParams($item_ids, $totalCount);
        $res = $this->select($solrInfo);
        $result = array_column($res['data']['response']['docs'],'id');
        return $result;
    }


    public function getcouponParams($item_ids, $totalCount = 0){

        // 执行前一天的时间戳
        date_default_timezone_set('PRC');
        $end_time = strtotime(date("Y-m-d"),time());
        $begin_time = $end_time - 24*60*60;

        $solrInfo = [
            'q'         => '*:*',
            'fl'        => 'id',
        ];

        if (!empty($item_ids)) {
            $solrInfo['fq'][]   = "item_id:(". implode(' OR ', $item_ids) . ")";
        } else {
            $solrInfo['fq'][]   = 'item_id:'. $item_ids;
        }
        if(!empty($totalCount)){
            $solrInfo['pageSize'] = $totalCount;
        }
        $solrInfo['fq'][] = 'status:2';
        $solrInfo['fq'][] = 'score:(4 OR 5)';
        $solrInfo['fq'][] = 'created_time:['.$begin_time.' TO '.$end_time.']';
        return $solrInfo;
    }


    /*
     * 蜜芽圈综合搜索
     * $cond :筛选字段
     * $field : 检索字段
     * $order : 排序
     * $stats : 分组统计
     * */
    public function getSeniorSolrSearch($cond, $field = 'id', $page = 1, $limit = 50,  $order = [], $stats = [])
    {
        // 排序处理
        $orderBy = 'created desc,';
        if(!empty($order)) {
            // 组装排序字段
            foreach($order as $k => $v) {
                foreach ($v as $q) {
                    $orderBy.= $q.' '.$k.',';
                }
            }
        }
        $orderBy = rtrim($orderBy, ',');

        // 基础参数
        $where = [
            'q'         => '*:*',
            'fq'        => [],
            'page'      => $page,
            'pageSize'  => $limit,
            'fl'        => $field,
            'sort'      => $orderBy,
        ];

        // 统计操作
        if(!empty($stats)) {
            if(array_key_exists('count', $stats)) {
                // 去重统计
                $params = ['count' => 'unique('.$stats['count'].')'];
                $where['json.facet'] = json_encode($params);
                $where['pageSize'] = 0;
            }
            if(array_key_exists('sum', $stats)) {
                // 求和统计
                $params = ['sum' => 'sum('.$stats['sum'].')'];
                $where['json.facet'] = json_encode($params);
                $where['pageSize'] = 0;
            }
            if(array_key_exists('group', $stats)) {
                // 分组统计
                $where['group']       = 'true';
                // 不显示文档信息
                //$where['group.limit'] = 0;
                $where['group.field'] = $stats['group']['field'];
            }
            if(array_key_exists('facet', $stats)) {
                // 分片统计
                $where['facet']       = 'true';
                //$where['facet.pivot'] = '';
                $where['facet.field'] = $stats['facet']['field'];
            }
        }

        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'after_id':
                        $where['fq'][]   = 'id:['.$v.' TO *]';
                        break;
                    case 'start_time':
                        $v = date('YmdHis',strtotime($v));
                        $where['fq'][]   = 'created:['.$v.' TO *]';
                        break;
                    case 'end_time':
                        $v = date('YmdHis',strtotime($v));
                        $where['fq'][]   = 'created:[* TO '.$v.']';
                        break;
                    case 'before_image':
                        $where['fq'][]   = 'image_count:[* TO '.$v.']';
                        break;
                    case 'after_image':
                        $where['fq'][]   = 'image_count:['.$v.' TO *]';
                        break;
                    case 'have_title':
                        $where['fq'][]   = 'title:["" TO *]';
                        break;
                    case 'no_title':
                        $where['fq'][]   = 'title:[* TO ""]';
                        break;
                    case 'before_text':
                        $where['fq'][]   = 'text_count:[* TO '.$v.']';
                        break;
                    case 'after_text':
                        $where['fq'][]   = 'text_count:['.$v.' TO *]';
                        break;
                    case 'title_like' :
                        $where['fq'][]   = 'title:*'.$v.'*';
                        break;
                    case 'text_like' :
                        $where['fq'][]   = 'text:*'.$v.'*';
                        break;
                    case 'after_score':
                        $where['fq'][]   = 'score:['.$v.' TO *]';
                        break;
                    case 'before_score':
                        $where['fq'][]   = 'score:[* TO '.$v.']';
                        break;
                    case 'after_text_count':
                        $where['fq'][]   = 'text_count:['.$v.' TO *]';
                        break;
                    case 'after_pic_count':
                        $where['fq'][]   = 'image_count:['.$v.' TO *]';
                        break;
                    case 'status':
                        // 存在负值，单独处理
                        $where['fq'][]   = 'status:"'.$v.'"';
                        break;
                    case 'item_id':
                        $where['fq'][]   = "goods:(". implode(' OR ', $v) . ")";
                        break;
                    case 'brand_id':
                        $where['fq'][] = 'brand:'.$v;
                        break;
                    case 'subject_type':
                        if($v == 'material') {
                            $where['fq'][] = 'is_material:1';
                        }else{
                            $where['fq'][] = 'subject_type:"'.$v.'"';
                        }
                        break;
                    case 'no_active':
                        if(!empty($cond['active_id'])) {
                            $where['fq'][] = '-(active_id:'.$cond['active_id'].')';
                        }
                        break;
                    default:
                        if(!empty($cond['no_active']) && $k == "active_id") {
                            continue;
                        }
                        if(is_array($v)) {
                            $where['fq'][]   = $k.":(". implode(' OR ', $v) . ")";
                        }else {
                            $where['fq'][]   = $k.':'.$v;
                        }
                }
            }
        }
        $return = $this->select($where);
        return $return;
    }
}
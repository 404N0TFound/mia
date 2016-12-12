<?php
namespace mia\miagroup\Remote;
class Solr
{
    private  $core          = '';
    public   $config        = '';
    public   $solrserver    = '';
    public   $export_count  = 2000;

    public function __construct(){
        $this->switchServer();
    }

    /*
     * 主从配置，主服务器不可用时，切换从服务器，从服务器自动拉取主服务器数据
     * 主服务器全量更新的同时，从服务器也全量更新
     * */
    public function switchServer(){

        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.solr.online');
        $this->handleSolrUrlParams();
        if($this->ping() == false){
            $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.solr.online_slave');
            $this->handleSolrUrlParams();
        }
    }

    /**
     * 封装数据
     */
    public function handleSolrUrlParams(){
        $this->setCore($this->config['core']);
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
            // Field Collapsing
            if(empty($data['group']) == false){
                $params['group'] = $data['group'];
            }
            if(empty($data['group.main']) == false){
                $params['group.main'] = $data['group.main'];
            }
            if(empty($data['group.field']) == false){
                $params['group.field'] = $data['group.field'];
            }
            $method = 'select';
            $solrData = $this->httpGet($method, $params);

            if(empty($solrData) == false) {
                $data = json_decode($solrData, true);
                if(isset($data['responseHeader']['status']) == true && $data['responseHeader']['status'] == 0) {
                    $result = array('success'=>1,'info'=>'操作成功', 'data'=>$data);
                } else {
                    $result = array('success'=>0,'info'=>'查询失败', 'error'=>$solrData);
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

    public function httpGet($method, $parame){
        $url = $this->solrserver . $this->core."/".$method;
        $data = "";
        $wt = 'json';
        if(empty($parame['wt']) == false) {
            $wt = $parame['wt'];
            unset($parame['wt']);
        }
        $url .= "?wt=".$wt;
        foreach($parame as $key=>$value) {
            $data .= "&". $key."=".$value;
        }
        $url .= $data;
        //echo $url."\n";
        $result = file_get_contents($url);
        return $result;
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
    public function getHighQualityKoubeiByBrandId($brand_id = 0, $page = 1)
    {
        $field = 'id,item_id';
        $sort = 'score desc,id desc,rank_score desc';
        // 处理brand_id
        $conditon = array(
            'brand_id' => $brand_id,
            'koubei_with_pic' => true,
            'status' => 2,
            'score' => '(4 OR 5)',
            'fl' => $field,
            'sort' => $sort,
            'subject_id' => '-(subject_id:0)',
            'item_id' => '-(item_id:0)'
        );

        $res = $this->getKoubeiList($conditon, $field, $page, $this->export_count, $sort);
        if(!empty($res['list'])){
            //$sort_ids = $this->sortKoubeiId($res['list'], 'item_id', $res['count'], 20, $page);
            $sort_ids = $this->anotherSortKoubeiId($res['list'], 'item_id', $res['count'], 20, $page);
            $result = array('list' => $sort_ids, 'count' => $res['count']);
            return $result;
        }
        return array();
    }
    
    /**
     * 通过类目id获取优质口碑
     */
    public function getHighQualityKoubeiByCategoryId($category_id = 0, $page = 1)
    {
        $brand_ids = $this->brandList($category_id);
        if(!empty($brand_ids)){
            $brand_ids = array_column($brand_ids, 'id');
            // 通过品牌获取口碑列表
            $result = $this->getHighQualityKoubeiByBrandId($brand_ids, $page);
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
        if(intval($conditon['brand_id']) > 0) { 
            //品牌ID
            if (is_array($conditon['brand_id'])) {
                $solr_info['fq'][]   = "brand_id:". implode(' OR ', $conditon['brand_id']);
            } else {
                $solr_info['fq'][]   = 'brand_id:'. $conditon['brand_id'];
            }
        }
        if($conditon['koubei_with_pic'] === true || $conditon['self_sell'] === false) { 
            //是否带图
            $solr_info['fq'][]   = $conditon['koubei_with_pic'] === true ? 'local_url:*' : '-(local_url:*)';
        }
        if (in_array($conditon['self_sale'],array(0,1))) {
            //自营非自营
            $solr_info['fq'][]   = $conditon['self_sale'] == 1 ? 'supplier_id:0' : 'supplier_id:[1 TO *]';
        }
        if (!empty($conditon['warehouse_type'])) {
            //所属仓库
            $solr_info['fq'][]   = 'warehouse_type:'. $conditon['warehouse_type'];
        }
        if(!empty(intval($conditon['status']))){
            $solr_info['fq'][]   = 'status:'. $conditon['status'];
        }
        if(!empty($conditon['score'])){
            $solr_info['fq'][]   = 'score:'. $conditon['score'];
        }
        if(!empty($conditon['item_id'])){
            $solr_info['fq'][]   = $conditon['item_id'];
        }
        if(!empty($conditon['subject_id'])){
            $solr_info['fq'][]   = $conditon['subject_id'];
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

        if($total_count <= $default_count){
            $final_data = array_column($sort_data,'id');
            return $final_data;
        }
        $sorted_data = array();
        array_values($sort_data);
        // 规则排序
        foreach ($sort_data as $key => $data) {
            $sorted_data[$key][$data['item_id']] = $data['id'];
        }
        // 循环取出的条数（默认值）
        $sort_ids = array();
        if($page <= round($total_count/$default_count)){
            for ($i=1; $i <= $page; $i++) {
                //echo '第'.$i.'页数据';
                $sort_ids = $this->another_order_list($sorted_data, $default_count);
            }
        }else{
            return array();
        }
        // 取出当前页的条数
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
            $removal_arr[]=array_keys($value);
            unset($arr[$key]);
            if(count($return_arr) == $default){
                return $return_arr;
            }
        }
        if(empty($arr)){
            return $return_arr;
        }
        if(count($return_arr)< $default){
            return array_merge($this->another_order_list($arr,$default-count($return_arr)),$return_arr);
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
        $solrInfo['fq'][] = '-(subject_id:0)';
        $solrInfo['fq'][] = '-(item_id:0)';
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
    public function brandList($category_id)
    {
        //$category_id = 2;
        $solrInfo = [
            'q'           => '*:*',
            'sort'        => 'brand_id desc',
            'group'       => 'true',
            'group.main'  => 'true',
            'group.field' => 'brand_id',
            'fl'          => 'brand_id,name',
            'pageSize'    => '20',
            'group.cache.percent' => '20'
        ];
        if(!empty($category_id)){
            $solrInfo['fq'][]    = 'category_id:'.$category_id;
        }
        $solrInfo['fq'][] = 'local_url:*';
        $solrInfo['fq'][] = 'status:2';
        $solrInfo['fq'][] = 'score:(4 OR 5)';
        $solrInfo['fq'][] = '-(subject_id:0)';
        $solrInfo['fq'][] = '-(item_id:0)';
        // solr select
        $res = $this->select($solrInfo);
        $new_brand_list = array();
        if($res['success'] == 1){
            $tmp = $res['data']['response']['docs'];
            foreach ($tmp as $k => $v){
                $new_brand_list[$k]['id'] = $v['brand_id'];
                $new_brand_list[$k]['name'] = $v['name'];
            }
            return $new_brand_list;
        }
        return array();
    }

}
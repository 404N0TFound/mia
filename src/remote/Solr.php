<?php
namespace mia\miagroup\Remote;
class Solr
{
    private  $core          = '';
    public   $config        = '';
    public   $solrserver    = '';

    public function __construct(){
        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.solr.default');
        $this->handleSolrUrlParams();
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
            'sort'      => 'brand_id desc',
        ];
        if(!empty($category_id)){
            $solrInfo['group.field'] = "brand_id";
            $solrInfo['group.main'] = "true";
            $solrInfo['group'] = "true";
            $solrInfo['fq'][]   = 'category_id:'.$category_id;
        }
        if(!empty($brand_id)){
            $solrInfo['fq'][]   = 'brand_id:'.$brand_id;
        }
        $solrInfo['fq'][] = 'local_url:*';
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